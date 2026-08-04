<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\RazorpayService;
use App\Services\WhatsAppService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RazorpayPaymentController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $order = Order::query()
            ->with('address')
            ->whereKey($request->integer('id'))
            ->where('user_id', Auth::id())
            ->first();

        if (! $order) {
            return redirect()
                ->route('checkout')
                ->with('error', 'Booking not found.');
        }

        if ((float) $order->grand_total <= 0) {
            return redirect()
                ->route('checkout')
                ->with('error', 'Invalid booking amount.');
        }

        if ($this->isPaid($order)) {
            return redirect()->route('success', ['id' => $order->id]);
        }

        return view('razorpay', [
            'order' => $order,
            'customerName' => $order->address?->full_name ?? Auth::user()?->name ?? '',
            'customerEmail' => $order->address?->email ?? Auth::user()?->email ?? '',
            'customerPhone' => $order->address?->phone ?? Auth::user()?->mobile ?? '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'booking_id' => ['required', 'integer'],
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_order_id' => ['nullable', 'string', 'max:100'],
            'razorpay_signature' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::query()
            ->whereKey($validated['booking_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (! $order) {
            return redirect()
                ->route('checkout')
                ->with('error', 'Booking not found.');
        }

        try {
            $paymentWasProcessed = false;

            $order = DB::transaction(function () use (
                $order,
                $validated,
                &$paymentWasProcessed
            ): Order {
                /** @var Order $lockedOrder */
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($this->isPaid($lockedOrder)) {
                    return $lockedOrder;
                }

                $paymentId = trim($validated['razorpay_payment_id']);
                $razorpayOrderId = trim((string) ($validated['razorpay_order_id'] ?? ''));
                $signature = trim((string) ($validated['razorpay_signature'] ?? ''));

                $this->ensurePaymentIdIsNotReused($lockedOrder, $paymentId);

                if ($razorpayOrderId !== '' || $signature !== '') {
                    if ($razorpayOrderId === '' || $signature === '') {
                        throw new RuntimeException('Incomplete Razorpay signature data.');
                    }

                    $verification = RazorpayService::verifyPayment(
                        $razorpayOrderId,
                        $paymentId,
                        $signature
                    );

                    if (! ($verification['status'] ?? false)) {
                        throw new RuntimeException(
                            (string) ($verification['message'] ?? 'Razorpay signature verification failed.')
                        );
                    }
                }

                $paymentResult = RazorpayService::fetchPayment($paymentId);

                if (! ($paymentResult['status'] ?? false)) {
                    throw new RuntimeException(
                        (string) ($paymentResult['message'] ?? 'Unable to verify Razorpay payment.')
                    );
                }

                $payment = $paymentResult['data'] ?? null;

                if (! is_array($payment)) {
                    throw new RuntimeException('Invalid payment details received from Razorpay.');
                }

                $this->validatePaymentAgainstOrder(
                    $lockedOrder,
                    $payment,
                    $razorpayOrderId
                );

                if (($payment['status'] ?? null) === 'authorized') {
                    $captureResult = RazorpayService::capturePayment(
                        $paymentId,
                        (float) $lockedOrder->grand_total,
                        'INR'
                    );

                    if (! ($captureResult['status'] ?? false)) {
                        throw new RuntimeException(
                            (string) ($captureResult['message'] ?? 'Unable to capture Razorpay payment.')
                        );
                    }

                    $capturedPayment = $captureResult['data'] ?? null;

                    if (is_array($capturedPayment)) {
                        $payment = $capturedPayment;
                    }
                }

                if (($payment['status'] ?? null) !== 'captured') {
                    throw new RuntimeException('Razorpay payment has not been captured.');
                }

                $updates = [
                    'payment_method' => 'razorpay',
                    'payment_status' => 'paid',
                ];

                $this->putIfColumnExists($updates, 'razorpay_payment_id', $paymentId);
                $this->putIfColumnExists(
                    $updates,
                    'razorpay_order_id',
                    $razorpayOrderId !== ''
                        ? $razorpayOrderId
                        : ($payment['order_id'] ?? null)
                );
                $this->putIfColumnExists($updates, 'razorpay_signature', $signature ?: null);
                $this->putIfColumnExists($updates, 'paid_at', now());

                $lockedOrder->forceFill($updates)->save();
                $paymentWasProcessed = true;

                return $lockedOrder->fresh(['address', 'items']) ?? $lockedOrder;
            }, 3);

            if ($paymentWasProcessed) {
                $this->sendPaymentSuccessWhatsApp(
                    $order,
                    (string) $validated['razorpay_payment_id']
                );
            }

            $request->session()->forget('booking_draft');

            return redirect()
                ->route('success', ['id' => $order->id])
                ->with('success', 'Payment completed successfully.');
        } catch (Throwable $e) {
            Log::error('Website Razorpay verification failed', [
                'order_id' => $order->id,
                'payment_id' => $validated['razorpay_payment_id'],
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            $this->markPaymentFailed($order);

            return redirect()
                ->route('razorpay', ['id' => $order->id])
                ->with('error', $this->publicErrorMessage($e));
        }
    }

    private function sendPaymentSuccessWhatsApp(
        Order $order,
        string $paymentId
    ): void {
        $customerNumber = $this->customerMobile($order);

        if ($customerNumber === '') {
            Log::warning(
                'Payment WhatsApp skipped because customer mobile is missing.',
                ['order_id' => $order->id]
            );

            return;
        }

        $bookingId = $this->bookingReference($order);
        $amount = $this->formattedAmount($order->grand_total);

        try {
            $receiptSent = WhatsAppService::paymentReceipt(
                $customerNumber,
                [
                    'booking_id' => $bookingId,
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                ]
            );

            if (! $receiptSent) {
                Log::warning('Payment receipt WhatsApp was not accepted.', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId,
                    'number' => $this->maskedMobile($customerNumber),
                ]);
            }
        } catch (Throwable $exception) {
            Log::error('Payment receipt WhatsApp failed.', [
                'order_id' => $order->id,
                'payment_id' => $paymentId,
                'number' => $this->maskedMobile($customerNumber),
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $confirmationSent = WhatsAppService::bookingConfirmation(
                $customerNumber,
                [
                    'customer_name' => $this->customerName($order),
                    'booking_id' => $bookingId,
                    'pickup' => $this->pickupLabel($order),
                    'drop' => $this->dropLabel($order),
                    'date' => $this->travelDateLabel($order),
                    'time' => $this->travelTimeLabel($order),
                    'amount' => $amount,
                ]
            );

            if (! $confirmationSent) {
                Log::warning('Booking confirmation WhatsApp was not accepted.', [
                    'order_id' => $order->id,
                    'number' => $this->maskedMobile($customerNumber),
                ]);
            }
        } catch (Throwable $exception) {
            Log::error('Booking confirmation WhatsApp failed.', [
                'order_id' => $order->id,
                'number' => $this->maskedMobile($customerNumber),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function customerMobile(Order $order): string
    {
        return trim((string) (
            $order->address?->phone
            ?: $order->user?->mobile
            ?: Auth::user()?->mobile
            ?: ''
        ));
    }

    private function customerName(Order $order): string
    {
        return trim((string) (
            $order->address?->full_name
            ?: $order->user?->name
            ?: Auth::user()?->name
            ?: 'Customer'
        ));
    }

    private function bookingReference(Order $order): string
    {
        $reference = trim((string) (
            $order->booking_no
            ?? $order->order_no
            ?? ''
        ));

        return $reference !== ''
            ? $reference
            : 'DC' . str_pad(
                (string) $order->id,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    private function pickupLabel(Order $order): string
    {
        return trim((string) (
            $order->cityFrom
            ?: $order->booking_from
            ?: 'N/A'
        ));
    }

    private function dropLabel(Order $order): string
    {
        return trim((string) (
            $order->cityTo
            ?: $order->booking_to
            ?: $order->cityFrom
            ?: 'N/A'
        ));
    }

    private function travelDateLabel(Order $order): string
    {
        if (blank($order->date)) {
            return 'N/A';
        }

        try {
            return \Carbon\Carbon::parse($order->date)->format('d M Y');
        } catch (Throwable) {
            return trim((string) $order->date);
        }
    }

    private function travelTimeLabel(Order $order): string
    {
        if (blank($order->time)) {
            return 'N/A';
        }

        try {
            return \Carbon\Carbon::parse($order->time)->format('h:i A');
        } catch (Throwable) {
            return trim((string) $order->time);
        }
    }

    private function formattedAmount(mixed $amount): string
    {
        return number_format(
            max(0, (float) $amount),
            2,
            '.',
            ''
        );
    }

    private function maskedMobile(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (strlen($digits) <= 4) {
            return $digits;
        }

        return str_repeat('*', strlen($digits) - 4)
            . substr($digits, -4);
    }

    private function validatePaymentAgainstOrder(
        Order $order,
        array $payment,
        string $submittedOrderId
    ): void {
        $expectedAmount = (int) round(((float) $order->grand_total) * 100);
        $actualAmount = (int) ($payment['amount'] ?? 0);

        if ($actualAmount !== $expectedAmount) {
            throw new RuntimeException('Razorpay payment amount does not match the booking amount.');
        }

        $currency = strtoupper(trim((string) ($payment['currency'] ?? '')));

        if ($currency !== 'INR') {
            throw new RuntimeException('Invalid Razorpay payment currency.');
        }

        if (isset($payment['captured']) && ! (bool) $payment['captured']
            && ($payment['status'] ?? null) === 'captured') {
            throw new RuntimeException('Razorpay payment capture status is invalid.');
        }

        $paymentOrderId = trim((string) ($payment['order_id'] ?? ''));

        if ($submittedOrderId !== '' && $paymentOrderId !== ''
            && ! hash_equals($submittedOrderId, $paymentOrderId)) {
            throw new RuntimeException('Razorpay order ID does not match the payment.');
        }

        $noteBookingId = data_get($payment, 'notes.booking_id');

        if ($noteBookingId !== null && (string) $noteBookingId !== (string) $order->id) {
            throw new RuntimeException('Razorpay payment does not belong to this booking.');
        }
    }

    private function ensurePaymentIdIsNotReused(Order $order, string $paymentId): void
    {
        if (! Schema::hasColumn($order->getTable(), 'razorpay_payment_id')) {
            return;
        }

        $alreadyUsed = Order::query()
            ->where('razorpay_payment_id', $paymentId)
            ->whereKeyNot($order->id)
            ->exists();

        if ($alreadyUsed) {
            throw new RuntimeException('This Razorpay payment has already been used.');
        }
    }

    private function markPaymentFailed(Order $order): void
    {
        try {
            $freshOrder = Order::query()->find($order->id);

            if (! $freshOrder || $this->isPaid($freshOrder)) {
                return;
            }

            $freshOrder->forceFill([
                'payment_method' => 'razorpay',
                'payment_status' => 'failed',
            ])->save();
        } catch (QueryException|Throwable $e) {
            Log::warning('Unable to mark Razorpay payment as failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function isPaid(Order $order): bool
    {
        return strtolower(trim((string) $order->payment_status)) === 'paid';
    }

    private function putIfColumnExists(array &$updates, string $column, mixed $value): void
    {
        if (Schema::hasColumn((new Order())->getTable(), $column)) {
            $updates[$column] = $value;
        }
    }

    private function publicErrorMessage(Throwable $e): string
    {
        if (config('app.debug')) {
            return $e->getMessage();
        }

        if ($e instanceof RuntimeException) {
            return $e->getMessage();
        }

        return 'Payment verification failed. Please try again or contact support.';
    }
}