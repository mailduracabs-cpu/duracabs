<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Secure Payment - Dura Cabs')]
class RazorePay extends Component
{
    #[Url(history: true)]
    public ?int $id = null;

    public function render()
    {
        abort_unless(Auth::guard('customer')->check() || Auth::check(), 401);

        if ((int) $this->id < 0) {
            $service = app(\App\Services\BookingPaymentCheckoutService::class);
            $checkout = $service->owned((int) $this->id, (int) (Auth::guard('customer')->id() ?: Auth::id()));
            abort_unless($checkout->source === 'order', 422);
            if ($checkout->booking_id) {
                return redirect()->route('success', ['id' => $checkout->booking_id]);
            }
            $payload = json_decode($checkout->payload, true, 512, JSON_THROW_ON_ERROR);
            $order = new Order();
            $order->setRawAttributes($payload['attributes']);
            $order->id = (int) $this->id;
            $address = new \App\Models\Address();
            $address->forceFill($payload['address']);
            $order->setRelation('address', $address);
            $gateway = [];
            $checkoutError = null;
            if ($checkout->state === 'paid_review_required') {
                $checkoutError = 'Payment received. Booking needs review. Do not pay again. Contact support with payment ID ' . $checkout->gateway_payment_id;
            } else {
                try {
                    $gateway = $service->gatewayOrder($checkout);
                } catch (\Throwable $e) {
                    $checkoutError = $e->getMessage();
                }
            }
            return view('livewire.razore-pay', [
                'order' => $order, 'checkoutPending' => true, 'gatewayOrderId' => $gateway['id'] ?? null,
                'paymentUnavailable' => $checkoutError !== null, 'checkoutError' => $checkoutError,
                'isSelfDrive' => $order->ride_type === 'self_drive',
                'paymentOption' => $checkout->payment_type === 'advance' ? 'token' : 'full',
                'paymentAmount' => (float) $checkout->amount, 'bookingTotal' => (float) $checkout->total,
                'balanceAmount' => round((float) $checkout->total - (float) $checkout->amount, 2),
                'customerName' => $address->full_name, 'customerEmail' => $address->email,
                'customerPhone' => $address->phone,
            ]);
        }

        $order = Order::query()
            ->with('address')
            ->whereKey($this->id)
            ->where('user_id', Auth::guard('customer')->id() ?: Auth::id())
            ->firstOrFail();

        $extraOptions = $this->normaliseExtraOptions($order->extraOptions);

        $reservation = Arr::get($extraOptions, 'reservation', []);
        $isSelfDrive = (string) $order->ride_type === 'self_drive';

        $paymentOption = $isSelfDrive
            ? (string) ($reservation['payment_option'] ?? 'full')
            : 'full';

        $bookingTotal = $this->money($order->grand_total);

        $paymentAmount = $isSelfDrive && $paymentOption === 'token'
            ? $this->money(
                $reservation['amount_payable_now']
                    ?? $reservation['reservation_token_amount']
                    ?? 0
            )
            : $bookingTotal;

        /*
         * Safe fallback for older self-drive orders where reservation metadata
         * may not yet contain amount_payable_now.
         */
        if ($isSelfDrive && $paymentOption === 'token' && $paymentAmount <= 0) {
            $paymentAmount = min(500.00, $bookingTotal);
        }

        $balanceAmount = $this->money(
            $reservation['balance_amount']
                ?? max(0, $bookingTotal - $paymentAmount)
        );

        abort_if($bookingTotal <= 0, 403, 'Invalid booking amount.');
        abort_if($paymentAmount <= 0, 403, 'Invalid payment amount.');
        abort_if($paymentAmount > $bookingTotal, 403, 'Payment amount exceeds the booking total.');

        return view('livewire.razore-pay', [
            'order' => $order,
            'isSelfDrive' => $isSelfDrive,
            'paymentOption' => $paymentOption,
            'paymentAmount' => $paymentAmount,
            'bookingTotal' => $bookingTotal,
            'balanceAmount' => $balanceAmount,
            'customerName' => $order->address?->full_name
                ?? Auth::guard('customer')->user()?->name
                ?? Auth::user()?->name
                ?? '',
            'customerEmail' => $order->address?->email
                ?? Auth::guard('customer')->user()?->email
                ?? Auth::user()?->email
                ?? '',
            'customerPhone' => $order->address?->phone
                ?? Auth::guard('customer')->user()?->mobile
                ?? Auth::user()?->mobile
                ?? '',
        ]);
    }

    private function normaliseExtraOptions(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }
}