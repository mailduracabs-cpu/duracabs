<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\RazorpayService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends BaseApiController
{
    public function __construct(
        private readonly WalletService $walletService
    ) {
    }

    /**
     * Create a Razorpay order for General Booking or Self Drive/Bike Rental.
     */
    public function razorpayOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['nullable', 'numeric', 'min:1'],
            'booking_id' => ['nullable'],
            'booking_no' => ['nullable', 'string', 'max:100'],
            'booking_source' => ['nullable', 'in:order,self_drive,bike_rental'],
            'payment_type' => ['nullable', 'in:advance,full,remaining'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        if (! $request->filled('booking_id') && ! $request->filled('booking_no')) {
            return $this->error('booking_id or booking_no is required', 422);
        }

        $booking = $this->findBooking(
            $request->booking_id,
            $request->booking_no,
            $request->booking_source
        );

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        if (! $this->canAccessBooking($request, $booking)) {
            return $this->error('You are not allowed to access this booking', 403);
        }

        if ($this->isClosedBooking($booking)) {
            return $this->error('Payment cannot be created for this booking', 422);
        }

        $payable = $this->bookingPayableAmount($booking);
        $paid = $this->bookingPaidAmount($booking);
        $remaining = max(0, round($payable - $paid, 2));

        if ($remaining <= 0) {
            return $this->error('This booking is already fully paid', 422);
        }

        $paymentType = $request->input('payment_type', 'remaining');
        $requestedAmount = $request->filled('amount')
            ? round((float) $request->amount, 2)
            : $remaining;

        if ($paymentType === 'advance') {
            $advanceAmount = $this->bookingAdvanceAmount($booking);
            $requestedAmount = $request->filled('amount')
                ? $requestedAmount
                : ($advanceAmount > 0 ? $advanceAmount : min(500, $remaining));
        }

        if ($paymentType === 'full' || $paymentType === 'remaining') {
            $requestedAmount = min($requestedAmount, $remaining);
        }

        if ($requestedAmount <= 0 || $requestedAmount > $remaining) {
            return $this->error(
                'Payment amount must be greater than zero and cannot exceed remaining amount',
                422,
                [
                    'payable_amount' => $payable,
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                ]
            );
        }

        $bookingNo = $this->bookingNumber($booking);
        $receipt = $this->makeReceipt($bookingNo);

        $result = RazorpayService::createOrder(
            $requestedAmount,
            $receipt,
            [
                'booking_id' => (string) $booking->id,
                'booking_no' => $bookingNo,
                'booking_source' => $booking->_source,
                'payment_type' => $paymentType,
                'customer_id' => (string) ($this->bookingUserId($booking) ?? ''),
                'source' => 'dura_cabs_app',
            ]
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to create Razorpay order',
                422,
                $result
            );
        }

        $data = $result['data'] ?? [];
        $razorpayOrderId = $data['id'] ?? $data['order_id'] ?? null;

        if (! $razorpayOrderId) {
            return $this->error('Invalid Razorpay order response', 502);
        }

        $this->updateBookingPayment($booking, [
            'payment_method' => 'razorpay',
            'payment_status' => $paid > 0 ? 'partial' : 'pending',
            'razorpay_order_id' => $razorpayOrderId,
            'payment_type' => $paymentType,
        ], [
            'razorpay_order_amount' => $requestedAmount,
            'razorpay_order_receipt' => $receipt,
            'razorpay_order_created_at' => now()->toISOString(),
        ]);

        $data['key'] = config('services.razorpay.key', env('RAZORPAY_KEY_ID'));
        $data['booking_id'] = $booking->id;
        $data['booking_no'] = $bookingNo;
        $data['booking_source'] = $booking->_source;
        $data['payment_type'] = $paymentType;
        $data['payment_amount'] = $requestedAmount;
        $data['payable_amount'] = $payable;
        $data['paid_amount'] = $paid;
        $data['remaining_amount'] = $remaining;
        $data['receipt'] = $receipt;

        return $this->success($data, 'Razorpay order created successfully');
    }

    /**
     * Verify Razorpay checkout signature and safely apply payment once.
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
            'booking_id' => ['nullable'],
            'booking_no' => ['nullable', 'string', 'max:100'],
            'booking_source' => ['nullable', 'in:order,self_drive,bike_rental'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $result = RazorpayService::verifyPayment(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Payment verification failed',
                400,
                $result
            );
        }

        $booking = $this->findBooking(
            $request->booking_id,
            $request->booking_no,
            $request->booking_source,
            $request->razorpay_order_id
        );

        if (! $booking) {
            return $this->error('Booking not found for this payment', 404);
        }

        if (! $this->canAccessBooking($request, $booking)) {
            return $this->error('You are not allowed to access this booking', 403);
        }

        $storedOrderId = $this->storedPaymentValue($booking, 'razorpay_order_id');

        if ($storedOrderId && ! hash_equals((string) $storedOrderId, (string) $request->razorpay_order_id)) {
            return $this->error('Razorpay order does not belong to this booking', 422);
        }

        try {
            $paymentData = DB::transaction(function () use ($booking, $request): array {
                $locked = $this->lockBooking($booking);

                if (! $locked) {
                    throw new \RuntimeException('Booking not found while processing payment');
                }

                $existingPaymentId = $this->storedPaymentValue($locked, 'razorpay_payment_id');

                if ($existingPaymentId === $request->razorpay_payment_id) {
                    return $this->paymentResponseData($locked, true);
                }

                if (
                    $existingPaymentId
                    && ($locked->payment_status ?? null) === 'paid'
                ) {
                    throw new \RuntimeException('Booking already has a successful payment');
                }

                $meta = $this->paymentMeta($locked);
                $orderAmount = round((float) ($meta['razorpay_order_amount'] ?? 0), 2);

                if ($orderAmount <= 0) {
                    $orderAmount = $this->bookingRemainingAmount($locked);
                }

                $payable = $this->bookingPayableAmount($locked);
                $oldPaid = $this->bookingPaidAmount($locked);
                $newPaid = min($payable, round($oldPaid + $orderAmount, 2));
                $remaining = max(0, round($payable - $newPaid, 2));
                $paymentStatus = $remaining <= 0 ? 'paid' : 'partial';
                $bookingStatus = ($locked->_source ?? null) === 'bike_rental'
                    && ($locked->booking_status ?? null) === 'pending_payment'
                    ? 'pending_vendor_confirmation'
                    : ($locked->booking_status ?? null);

                $this->updateBookingPayment($locked, [
                    'booking_status' => $bookingStatus,
                    'payment_method' => 'razorpay',
                    'payment_status' => $paymentStatus,
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'payment_reference' => $request->razorpay_payment_id,
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $remaining,
                    'balance_due' => $remaining,
                    'payment_completed_at' => $paymentStatus === 'paid' ? now() : null,
                ], [
                    'last_payment_amount' => $orderAmount,
                    'last_payment_verified_at' => now()->toISOString(),
                    'last_payment_source' => 'checkout_verify',
                ]);

                $fresh = $this->reloadBooking($locked);

                return $this->paymentResponseData($fresh ?: $locked, false);
            }, 3);
        } catch (Throwable $e) {
            Log::error('Razorpay payment apply failed', [
                'booking_id' => $booking->id,
                'booking_source' => $booking->_source,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 422);
        }

        return $this->success($paymentData, 'Payment verified successfully');
    }

    /**
     * Pay all or part of the remaining amount using wallet.
     */
    public function walletPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => ['nullable'],
            'booking_no' => ['nullable', 'string', 'max:100'],
            'booking_source' => ['nullable', 'in:order,self_drive,bike_rental'],
            'amount' => ['nullable', 'numeric', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $user = $request->user();

        if (! $user) {
            return $this->error('Unauthenticated', 401);
        }

        $booking = $this->findBooking(
            $request->booking_id,
            $request->booking_no,
            $request->booking_source
        );

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        if (! $this->canAccessBooking($request, $booking)) {
            return $this->error('You are not allowed to access this booking', 403);
        }

        $remaining = $this->bookingRemainingAmount($booking);

        if ($remaining <= 0) {
            return $this->error('This booking is already fully paid', 422);
        }

        $amount = $request->filled('amount')
            ? round((float) $request->amount, 2)
            : $remaining;

        if ($amount <= 0 || $amount > $remaining) {
            return $this->error('Invalid wallet payment amount', 422, [
                'remaining_amount' => $remaining,
            ]);
        }

        $walletResult = $this->walletService->pay(
            $user,
            $amount,
            (int) $booking->id
        );

        if (! ($walletResult['status'] ?? false)) {
            return $this->error(
                $walletResult['message'] ?? 'Wallet payment failed',
                (int) ($walletResult['code'] ?? 422),
                $walletResult['errors'] ?? null
            );
        }

        try {
            $paymentData = DB::transaction(function () use ($booking, $amount, $walletResult): array {
                $locked = $this->lockBooking($booking);

                if (! $locked) {
                    throw new \RuntimeException('Booking not found while applying wallet payment');
                }

                $payable = $this->bookingPayableAmount($locked);
                $oldPaid = $this->bookingPaidAmount($locked);
                $newPaid = min($payable, round($oldPaid + $amount, 2));
                $remaining = max(0, round($payable - $newPaid, 2));
                $paymentStatus = $remaining <= 0 ? 'paid' : 'partial';
                $bookingStatus = ($locked->_source ?? null) === 'bike_rental'
                    && ($locked->booking_status ?? null) === 'pending_payment'
                    ? 'pending_vendor_confirmation'
                    : ($locked->booking_status ?? null);

                $transaction = $walletResult['data'] ?? null;
                $transactionId = is_object($transaction)
                    ? ($transaction->transaction_id ?? $transaction->id ?? null)
                    : ($transaction['transaction_id'] ?? $transaction['id'] ?? null);

                $this->updateBookingPayment($locked, [
                    'booking_status' => $bookingStatus,
                    'payment_method' => $paymentStatus === 'paid' ? 'wallet' : 'wallet_partial',
                    'payment_status' => $paymentStatus,
                    'payment_reference' => $transactionId,
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $remaining,
                    'balance_due' => $remaining,
                    'payment_completed_at' => $paymentStatus === 'paid' ? now() : null,
                ], [
                    'wallet_transaction_id' => $transactionId,
                    'last_wallet_payment_amount' => $amount,
                    'last_wallet_payment_at' => now()->toISOString(),
                ]);

                $fresh = $this->reloadBooking($locked);

                return $this->paymentResponseData($fresh ?: $locked, false);
            }, 3);
        } catch (Throwable $e) {
            Log::critical('Wallet debited but booking update failed', [
                'booking_id' => $booking->id,
                'booking_source' => $booking->_source,
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Wallet was debited but booking update failed. Please contact support.',
                500
            );
        }

        return $this->success($paymentData, 'Wallet payment successful');
    }

    /**
     * Return payment status for either booking table.
     */
    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => ['nullable'],
            'booking_no' => ['nullable', 'string', 'max:100'],
            'booking_source' => ['nullable', 'in:order,self_drive,bike_rental'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $booking = $this->findBooking(
            $request->booking_id,
            $request->booking_no,
            $request->booking_source
        );

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        if (! $this->canAccessBooking($request, $booking)) {
            return $this->error('You are not allowed to access this booking', 403);
        }

        return $this->success(
            $this->paymentResponseData($booking, false),
            'Payment status fetched successfully'
        );
    }

    /**
     * Process a Razorpay refund with over-refund and duplicate protection.
     */
    public function refund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'booking_id' => ['nullable'],
            'booking_no' => ['nullable', 'string', 'max:100'],
            'booking_source' => ['nullable', 'in:order,self_drive,bike_rental'],
            'refund_to' => ['nullable', 'in:razorpay,wallet'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $booking = $this->findBooking(
            $request->booking_id,
            $request->booking_no,
            $request->booking_source
        );

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        if (! $this->canManageRefund($request, $booking)) {
            return $this->error('You are not allowed to refund this booking', 403);
        }

        $paidAmount = $this->bookingPaidAmount($booking);
        $alreadyRefunded = round((float) ($booking->refund_amount ?? 0), 2);
        $availableRefund = max(0, round($paidAmount - $alreadyRefunded, 2));
        $refundAmount = round((float) $request->amount, 2);

        if ($refundAmount > $availableRefund) {
            return $this->error('Refund amount exceeds refundable amount', 422, [
                'paid_amount' => $paidAmount,
                'already_refunded' => $alreadyRefunded,
                'refundable_amount' => $availableRefund,
            ]);
        }

        $refundTo = $request->input('refund_to');
        $paymentMethod = strtolower((string) ($booking->payment_method ?? ''));

        if (! $refundTo) {
            $refundTo = str_contains($paymentMethod, 'wallet') ? 'wallet' : 'razorpay';
        }

        if ($refundTo === 'wallet') {
            $user = $this->bookingUser($booking);

            if (! $user) {
                return $this->error('Customer not found for wallet refund', 404);
            }

            $result = $this->walletService->refund(
                $user,
                $refundAmount,
                (int) $booking->id
            );

            if (! ($result['status'] ?? false)) {
                return $this->error(
                    $result['message'] ?? 'Wallet refund failed',
                    (int) ($result['code'] ?? 422),
                    $result['errors'] ?? null
                );
            }

            $transaction = $result['data'] ?? null;
            $refundReference = is_object($transaction)
                ? ($transaction->transaction_id ?? $transaction->id ?? null)
                : ($transaction['transaction_id'] ?? $transaction['id'] ?? null);
        } else {
            $paymentId = $request->payment_id
                ?: $this->storedPaymentValue($booking, 'razorpay_payment_id')
                ?: ($booking->payment_reference ?? null);

            if (! $paymentId) {
                return $this->error('Razorpay payment ID is required', 422);
            }

            $result = RazorpayService::refund(
                (string) $paymentId,
                $refundAmount,
                $request->reason ?? ''
            );

            if (! ($result['status'] ?? false)) {
                return $this->error(
                    $result['message'] ?? 'Refund failed',
                    422,
                    $result
                );
            }

            $refundReference = $result['data']['id']
                ?? $result['data']['refund_id']
                ?? null;
        }

        $totalRefunded = round($alreadyRefunded + $refundAmount, 2);
        $refundStatus = $totalRefunded >= $paidAmount ? 'refunded' : 'partial_refund';

        $this->updateBookingPayment($booking, [
            'refund_amount' => $totalRefunded,
            'refund_status' => $refundStatus,
            'refund_reference' => $refundReference,
            'refund_reason' => $request->reason,
            'refund_initiated_at' => now(),
            'refunded_at' => now(),
            'payment_status' => $refundStatus === 'refunded'
                ? 'refunded'
                : ($booking->payment_status ?? 'paid'),
        ], [
            'last_refund_amount' => $refundAmount,
            'last_refund_to' => $refundTo,
            'last_refund_reference' => $refundReference,
            'last_refund_at' => now()->toISOString(),
        ]);

        return $this->success([
            'booking_id' => $booking->id,
            'booking_no' => $this->bookingNumber($booking),
            'booking_source' => $booking->_source,
            'refund_to' => $refundTo,
            'refund_amount' => $refundAmount,
            'total_refunded' => $totalRefunded,
            'refund_status' => $refundStatus,
            'refund_reference' => $refundReference,
            'refund' => $result['data'] ?? null,
        ], 'Refund processed successfully');
    }

    /**
     * Razorpay webhook endpoint.
     * Invalid signatures intentionally return HTTP 400.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');

        if ($signature === '' || ! RazorpayService::verifyWebhook($payload, $signature)) {
            Log::warning('Invalid Razorpay webhook signature', [
                'ip' => $request->ip(),
                'event' => $request->input('event'),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook signature',
            ], 400);
        }

        $event = (string) $request->input('event', '');
        $paymentEntity = (array) $request->input('payload.payment.entity', []);
        $orderEntity = (array) $request->input('payload.order.entity', []);
        $refundEntity = (array) $request->input('payload.refund.entity', []);

        $razorpayOrderId = $paymentEntity['order_id']
            ?? $orderEntity['id']
            ?? null;
        $razorpayPaymentId = $paymentEntity['id']
            ?? $refundEntity['payment_id']
            ?? null;
        $notes = $paymentEntity['notes']
            ?? $orderEntity['notes']
            ?? [];

        $booking = $this->findBooking(
            $notes['booking_id'] ?? null,
            $notes['booking_no'] ?? null,
            $notes['booking_source'] ?? null,
            $razorpayOrderId
        );

        Log::info('Razorpay webhook received', [
            'event' => $event,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'booking_found' => (bool) $booking,
        ]);

        if (! $booking) {
            return response()->json([
                'status' => true,
                'message' => 'Webhook accepted; booking not found',
            ]);
        }

        try {
            DB::transaction(function () use (
                $booking,
                $event,
                $paymentEntity,
                $refundEntity,
                $razorpayOrderId,
                $razorpayPaymentId
            ): void {
                $locked = $this->lockBooking($booking);

                if (! $locked) {
                    return;
                }

                if (in_array($event, ['payment.captured', 'order.paid'], true)) {
                    $existingPaymentId = $this->storedPaymentValue($locked, 'razorpay_payment_id');

                    if ($existingPaymentId === $razorpayPaymentId && ($locked->payment_status ?? null) === 'paid') {
                        return;
                    }

                    $capturedAmount = isset($paymentEntity['amount'])
                        ? round(((float) $paymentEntity['amount']) / 100, 2)
                        : 0.0;

                    if ($capturedAmount <= 0) {
                        $meta = $this->paymentMeta($locked);
                        $capturedAmount = round((float) ($meta['razorpay_order_amount'] ?? 0), 2);
                    }

                    if ($capturedAmount <= 0) {
                        $capturedAmount = $this->bookingRemainingAmount($locked);
                    }

                    $payable = $this->bookingPayableAmount($locked);
                    $oldPaid = $this->bookingPaidAmount($locked);
                    $newPaid = min($payable, round($oldPaid + $capturedAmount, 2));
                    $remaining = max(0, round($payable - $newPaid, 2));
                    $status = $remaining <= 0 ? 'paid' : 'partial';

                    $this->updateBookingPayment($locked, [
                        'payment_method' => 'razorpay',
                        'payment_status' => $status,
                        'razorpay_order_id' => $razorpayOrderId,
                        'razorpay_payment_id' => $razorpayPaymentId,
                        'payment_reference' => $razorpayPaymentId,
                        'paid_amount' => $newPaid,
                        'remaining_amount' => $remaining,
                        'balance_due' => $remaining,
                        'payment_completed_at' => $status === 'paid' ? now() : null,
                    ], [
                        'last_payment_amount' => $capturedAmount,
                        'last_payment_source' => 'webhook',
                        'last_payment_webhook_event' => $event,
                        'last_payment_webhook_at' => now()->toISOString(),
                    ]);

                    return;
                }

                if ($event === 'payment.failed') {
                    if (($locked->payment_status ?? null) === 'paid') {
                        return;
                    }

                    $this->updateBookingPayment($locked, [
                        'payment_method' => 'razorpay',
                        'payment_status' => 'failed',
                        'razorpay_order_id' => $razorpayOrderId,
                        'razorpay_payment_id' => $razorpayPaymentId,
                    ], [
                        'payment_error_code' => $paymentEntity['error_code'] ?? null,
                        'payment_error_description' => $paymentEntity['error_description'] ?? null,
                        'payment_failed_at' => now()->toISOString(),
                    ]);

                    return;
                }

                if (in_array($event, ['refund.processed', 'refund.created'], true)) {
                    $refundAmount = isset($refundEntity['amount'])
                        ? round(((float) $refundEntity['amount']) / 100, 2)
                        : 0.0;
                    $refundId = $refundEntity['id'] ?? null;
                    $meta = $this->paymentMeta($locked);

                    if ($refundId && ($meta['last_refund_reference'] ?? null) === $refundId) {
                        return;
                    }

                    $paid = $this->bookingPaidAmount($locked);
                    $oldRefund = round((float) ($locked->refund_amount ?? 0), 2);
                    $totalRefund = min($paid, round($oldRefund + $refundAmount, 2));
                    $refundStatus = $totalRefund >= $paid ? 'refunded' : 'partial_refund';

                    $this->updateBookingPayment($locked, [
                        'refund_amount' => $totalRefund,
                        'refund_status' => $refundStatus,
                        'refund_reference' => $refundId,
                        'refund_initiated_at' => now(),
                        'refunded_at' => $event === 'refund.processed' ? now() : null,
                        'payment_status' => $refundStatus === 'refunded'
                            ? 'refunded'
                            : ($locked->payment_status ?? 'paid'),
                    ], [
                        'last_refund_amount' => $refundAmount,
                        'last_refund_reference' => $refundId,
                        'last_refund_webhook_event' => $event,
                        'last_refund_at' => now()->toISOString(),
                    ]);
                }
            }, 3);
        } catch (Throwable $e) {
            Log::error('Razorpay webhook processing failed', [
                'event' => $event,
                'booking_id' => $booking->id,
                'booking_source' => $booking->_source,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Webhook processed successfully',
        ]);
    }

    /**
     * Authenticated customer's combined payment history.
     */
    public function history(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_status' => ['nullable', 'string', 'max:30'],
            'booking_source' => ['nullable', 'in:order,self_drive,bike_rental'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'user_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $user = $request->user();
        $requestedUserId = $request->integer('user_id');

        if (! $user && ! $requestedUserId) {
            return $this->error('Unauthenticated', 401);
        }

        $isAdmin = $user && method_exists($user, 'hasAnyRole')
            ? $user->hasAnyRole(['super_admin', 'admin'])
            : false;

        $userId = $isAdmin && $requestedUserId
            ? $requestedUserId
            : (int) $user->id;

        $limit = min(max((int) $request->input('limit', 20), 1), 100);
        $source = $request->input('booking_source');
        $rows = collect();

        if ((! $source || $source === 'order') && Schema::hasTable('orders')) {
            $query = DB::table('orders')->orderByDesc('id');

            if (Schema::hasColumn('orders', 'user_id')) {
                $query->where('user_id', $userId);
            } elseif (Schema::hasColumn('orders', 'customer_id')) {
                $query->where('customer_id', $userId);
            }

            if ($request->filled('payment_status') && Schema::hasColumn('orders', 'payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $rows = $rows->concat(
                $query->limit($limit)->get()->map(
                    fn (object $row): array => $this->historyRow($row, 'order')
                )
            );
        }

        if ((! $source || $source === 'self_drive') && Schema::hasTable('self_drive_bookings')) {
            $query = DB::table('self_drive_bookings')
                ->where('customer_id', $userId)
                ->orderByDesc('id');

            if ($request->filled('payment_status') && Schema::hasColumn('self_drive_bookings', 'payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $rows = $rows->concat(
                $query->limit($limit)->get()->map(
                    fn (object $row): array => $this->historyRow($row, 'self_drive')
                )
            );
        }

        if ((! $source || $source === 'bike_rental') && Schema::hasTable('bike_rental_bookings')) {
            $query = DB::table('bike_rental_bookings')
                ->where('customer_id', $userId)
                ->orderByDesc('id');

            if ($request->filled('payment_status') && Schema::hasColumn('bike_rental_bookings', 'payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $rows = $rows->concat(
                $query->limit($limit)->get()->map(
                    fn (object $row): array => $this->historyRow($row, 'bike_rental')
                )
            );
        }

        $payments = $rows
            ->sortByDesc(fn (array $row) => $row['created_at'] ?? '')
            ->take($limit)
            ->values();

        return $this->success([
            'payments' => $payments,
        ], 'Payment history fetched successfully');
    }

    private function findBooking(
        mixed $bookingId = null,
        ?string $bookingNo = null,
        ?string $preferredSource = null,
        ?string $razorpayOrderId = null
    ): ?object {
        $bookingPrefix = strtoupper((string) $bookingNo);

        $sources = match ($preferredSource) {
            'order' => ['order'],
            'self_drive' => ['self_drive'],
            'bike_rental' => ['bike_rental'],
            default => match (true) {
                Str::startsWith($bookingPrefix, 'SD') => ['self_drive', 'bike_rental', 'order'],
                Str::startsWith($bookingPrefix, 'BR') => ['bike_rental', 'self_drive', 'order'],
                default => ['order', 'self_drive', 'bike_rental'],
            },
        };

        foreach ($sources as $source) {
            $table = match ($source) {
                'self_drive' => 'self_drive_bookings',
                'bike_rental' => 'bike_rental_bookings',
                default => 'orders',
            };

            if (! Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);

            if ($bookingId !== null && $bookingId !== '') {
                $query->where('id', $bookingId);
            } elseif ($bookingNo && Schema::hasColumn($table, 'booking_no')) {
                $query->where('booking_no', $bookingNo);
            } elseif ($razorpayOrderId) {
                if (Schema::hasColumn($table, 'razorpay_order_id')) {
                    $query->where('razorpay_order_id', $razorpayOrderId);
                } elseif ($table === 'orders' && Schema::hasColumn($table, 'extraOptions')) {
                    $query->where('extraOptions', 'like', '%"razorpay_order_id":"' . $razorpayOrderId . '"%');
                } elseif (in_array($table, ['self_drive_bookings', 'bike_rental_bookings'], true)
                    && Schema::hasColumn($table, 'payment_reference')) {
                    $query->where('payment_reference', 'like', '%' . $razorpayOrderId . '%');
                } else {
                    continue;
                }
            } else {
                continue;
            }

            $booking = $query->first();

            if ($booking) {
                $booking->_source = $source;
                $booking->_table = $table;

                return $booking;
            }
        }

        return null;
    }

    private function lockBooking(object $booking): ?object
    {
        $row = DB::table($booking->_table)
            ->where('id', $booking->id)
            ->lockForUpdate()
            ->first();

        if ($row) {
            $row->_source = $booking->_source;
            $row->_table = $booking->_table;
        }

        return $row;
    }

    private function reloadBooking(object $booking): ?object
    {
        $row = DB::table($booking->_table)
            ->where('id', $booking->id)
            ->first();

        if ($row) {
            $row->_source = $booking->_source;
            $row->_table = $booking->_table;
        }

        return $row;
    }

    private function updateBookingPayment(
        object $booking,
        array $values,
        array $meta = []
    ): void {
        $table = $booking->_table;
        $updates = [];
        $unmapped = [];

        foreach ($values as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $updates[$column] = $value;
            } elseif ($value !== null) {
                $unmapped[$column] = $value;
            }
        }

        $meta = array_merge($meta, $unmapped);

        if ($meta) {
            $existingMeta = $this->paymentMeta($booking);
            $mergedMeta = array_merge($existingMeta, $meta);

            if ($table === 'orders' && Schema::hasColumn($table, 'extraOptions')) {
                $updates['extraOptions'] = json_encode(
                    $mergedMeta,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            } elseif (Schema::hasColumn($table, 'payment_meta')) {
                $updates['payment_meta'] = json_encode(
                    $mergedMeta,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            } elseif (Schema::hasColumn($table, 'payment_reference')) {
                $directReference = $values['payment_reference'] ?? null;

                if (! $directReference || count($mergedMeta) > 1) {
                    $updates['payment_reference'] = json_encode(
                        $mergedMeta,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                }
            }
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if ($updates) {
            DB::table($table)
                ->where('id', $booking->id)
                ->update($updates);
        }
    }

    private function paymentMeta(object $booking): array
    {
        $candidates = [];

        if (property_exists($booking, 'extraOptions')) {
            $candidates[] = $booking->extraOptions;
        }

        if (property_exists($booking, 'payment_meta')) {
            $candidates[] = $booking->payment_meta;
        }

        if (property_exists($booking, 'payment_reference')) {
            $candidates[] = $booking->payment_reference;
        }

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                return $candidate;
            }

            if (is_string($candidate) && Str::startsWith(trim($candidate), ['{', '['])) {
                $decoded = json_decode($candidate, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }

    private function storedPaymentValue(object $booking, string $key): mixed
    {
        if (property_exists($booking, $key) && filled($booking->{$key})) {
            return $booking->{$key};
        }

        return $this->paymentMeta($booking)[$key] ?? null;
    }

    private function bookingNumber(object $booking): string
    {
        if (property_exists($booking, 'booking_no') && filled($booking->booking_no)) {
            return (string) $booking->booking_no;
        }

        $prefix = match ($booking->_source) {
            'self_drive' => 'SD',
            'bike_rental' => 'BR',
            default => 'DURA',
        };

        return $prefix . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
    }

    private function makeReceipt(string $bookingNo): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_-]/', '', $bookingNo) ?: 'booking';

        return Str::limit(
            'dura_' . $clean . '_' . now()->format('YmdHis'),
            40,
            ''
        );
    }

    private function bookingPayableAmount(object $booking): float
    {
        foreach (['final_amount', 'grand_total', 'total_amount'] as $column) {
            if (property_exists($booking, $column) && is_numeric($booking->{$column})) {
                $amount = round((float) $booking->{$column}, 2);

                if ($amount > 0) {
                    return $amount;
                }
            }
        }

        return 0.0;
    }

    private function bookingPaidAmount(object $booking): float
    {
        if (property_exists($booking, 'paid_amount') && is_numeric($booking->paid_amount)) {
            return max(0, round((float) $booking->paid_amount, 2));
        }

        return in_array(($booking->payment_status ?? null), ['paid', 'refunded'], true)
            ? $this->bookingPayableAmount($booking)
            : 0.0;
    }

    private function bookingRemainingAmount(object $booking): float
    {
        if (property_exists($booking, 'remaining_amount') && is_numeric($booking->remaining_amount)) {
            return max(0, round((float) $booking->remaining_amount, 2));
        }

        return max(
            0,
            round($this->bookingPayableAmount($booking) - $this->bookingPaidAmount($booking), 2)
        );
    }

    private function bookingAdvanceAmount(object $booking): float
    {
        return property_exists($booking, 'advance_amount') && is_numeric($booking->advance_amount)
            ? max(0, round((float) $booking->advance_amount, 2))
            : 0.0;
    }

    private function bookingUserId(object $booking): ?int
    {
        foreach (['customer_id', 'user_id'] as $column) {
            if (property_exists($booking, $column) && filled($booking->{$column})) {
                return (int) $booking->{$column};
            }
        }

        return null;
    }

    private function bookingUser(object $booking): ?object
    {
        $userId = $this->bookingUserId($booking);

        if (! $userId || ! Schema::hasTable('users')) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->first();
    }

    private function canAccessBooking(Request $request, object $booking): bool
    {
        $user = $request->user();

        if (! $user) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $bookingUserId = $this->bookingUserId($booking);

        return ! $bookingUserId || (int) $bookingUserId === (int) $user->id;
    }

    private function canManageRefund(Request $request, object $booking): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return false;
    }

    private function isClosedBooking(object $booking): bool
    {
        $status = strtolower((string) ($booking->status ?? $booking->booking_status ?? ''));

        return in_array($status, ['cancelled', 'rejected', 'failed'], true);
    }

    private function paymentResponseData(object $booking, bool $duplicate): array
    {
        $meta = $this->paymentMeta($booking);

        return [
            'verified' => true,
            'duplicate' => $duplicate,
            'booking_id' => $booking->id,
            'booking_no' => $this->bookingNumber($booking),
            'booking_source' => $booking->_source,
            'payment_status' => $booking->payment_status ?? 'pending',
            'payment_method' => $booking->payment_method ?? null,
            'payable_amount' => $this->bookingPayableAmount($booking),
            'paid_amount' => $this->bookingPaidAmount($booking),
            'remaining_amount' => $this->bookingRemainingAmount($booking),
            'refund_amount' => round((float) ($booking->refund_amount ?? 0), 2),
            'refund_status' => $booking->refund_status ?? null,
            'razorpay_order_id' => $this->storedPaymentValue($booking, 'razorpay_order_id'),
            'razorpay_payment_id' => $this->storedPaymentValue($booking, 'razorpay_payment_id'),
            'last_payment_amount' => isset($meta['last_payment_amount'])
                ? (float) $meta['last_payment_amount']
                : null,
            'payment_completed_at' => $booking->payment_completed_at ?? null,
        ];
    }

    private function historyRow(object $row, string $source): array
    {
        $row->_source = $source;
        $row->_table = match ($source) {
            'self_drive' => 'self_drive_bookings',
            'bike_rental' => 'bike_rental_bookings',
            default => 'orders',
        };

        return [
            'booking_id' => $row->id,
            'booking_no' => $this->bookingNumber($row),
            'booking_source' => $source,
            'payment_method' => $row->payment_method ?? null,
            'payment_status' => $row->payment_status ?? 'pending',
            'payable_amount' => $this->bookingPayableAmount($row),
            'paid_amount' => $this->bookingPaidAmount($row),
            'remaining_amount' => $this->bookingRemainingAmount($row),
            'refund_amount' => round((float) ($row->refund_amount ?? 0), 2),
            'created_at' => $row->created_at ?? null,
        ];
    }
}