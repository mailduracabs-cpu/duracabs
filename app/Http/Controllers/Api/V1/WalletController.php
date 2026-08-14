<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\OtpService;
use App\Services\RazorpayService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;
use App\Services\NotificationService;

class WalletController extends BaseApiController
{
    private const ADMIN_RECHARGE_PURPOSE = 'admin_wallet_recharge';

    /*
    |--------------------------------------------------------------------------
    | Customer Wallet APIs
    |--------------------------------------------------------------------------
    */

    public function balance(
        Request $request,
        WalletService $walletService
    ): JsonResponse {
        $result = $walletService->balance($request->user());

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to fetch wallet balance',
                (int) ($result['code'] ?? 422),
                $result['errors'] ?? null
            );
        }

        return $this->success(
            $result['data'] ?? null,
            $result['message'] ?? 'Wallet balance fetched successfully'
        );
    }

    public function history(
        Request $request,
        WalletService $walletService
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $result = $walletService->history(
            $request->user(),
            (int) $request->query('limit', 20)
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to fetch wallet history',
                (int) ($result['code'] ?? 422),
                $result['errors'] ?? null
            );
        }

        return $this->success(
            $result['data'] ?? [],
            $result['message'] ?? 'Wallet history fetched successfully'
        );
    }

    public function addMoney(
        Request $request,
        WalletService $walletService
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1', 'max:500000'],
            'payment_method' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        /*
         * Customer-side manual credit must not be exposed publicly.
         * Razorpay verification will call wallet credit only after payment
         * signature verification.
         */
        $paymentMethod = strtolower(
            trim((string) $request->input('payment_method', 'manual'))
        );

        if (
            $paymentMethod === 'manual' &&
            ! $this->isAuthorizedAdmin($request->user())
        ) {
            return $this->error(
                'Direct manual wallet credit is not allowed.',
                403
            );
        }

        $result = $walletService->addMoney(
            $request->user(),
            (float) $request->input('amount'),
            $paymentMethod
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to add money',
                (int) ($result['code'] ?? 422),
                $result['errors'] ?? null
            );
        }

        return $this->success(
            $result['data'] ?? null,
            $result['message'] ?? 'Money added successfully'
        );
    }

    public function payment(
        Request $request,
        WalletService $walletService
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1', 'max:500000'],
            'booking_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $result = $walletService->pay(
            $request->user(),
            (float) $request->input('amount'),
            $request->filled('booking_id')
                ? (int) $request->input('booking_id')
                : null
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Wallet payment failed',
                (int) ($result['code'] ?? 422),
                $result['errors'] ?? null
            );
        }

        return $this->success(
            $result['data'] ?? null,
            $result['message'] ?? 'Wallet payment successful'
        );
    }

    public function refund(
        Request $request,
        WalletService $walletService
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1', 'max:500000'],
            'booking_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $result = $walletService->refund(
            $request->user(),
            (float) $request->input('amount'),
            $request->filled('booking_id')
                ? (int) $request->input('booking_id')
                : null
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to process wallet refund',
                (int) ($result['code'] ?? 422),
                $result['errors'] ?? null
            );
        }

        return $this->success(
            $result['data'] ?? null,
            $result['message'] ?? 'Refund credited successfully'
        );
    }

    public function cashback(
        Request $request,
        WalletService $walletService
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1', 'max:500000'],
            'booking_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $result = $walletService->cashback(
            $request->user(),
            (float) $request->input('amount'),
            $request->filled('booking_id')
                ? (int) $request->input('booking_id')
                : null
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to credit cashback',
                (int) ($result['code'] ?? 422),
                $result['errors'] ?? null
            );
        }

        return $this->success(
            $result['data'] ?? null,
            $result['message'] ?? 'Cashback credited successfully'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Customer Razorpay Wallet Recharge
    |--------------------------------------------------------------------------
    */

    public function createRechargeOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1', 'max:500000'],
            'currency' => ['nullable', 'string', 'size:3'],
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
            return $this->error('Unauthenticated.', 401);
        }

        $amount = round((float) $request->input('amount'), 2);
        $currency = strtoupper(
            trim((string) $request->input('currency', 'INR'))
        );

        if ($currency !== 'INR') {
            return $this->error(
                'Only INR wallet recharge is supported.',
                422
            );
        }

        $receipt = 'WLT-' . $user->id . '-' . now()->format('YmdHis')
            . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));

        $result = RazorpayService::createOrder(
            $amount,
            $receipt,
            [
                'purpose' => 'wallet_recharge',
                'user_id' => (string) $user->id,
                'customer_mobile' => (string) ($user->mobile ?? ''),
                'source' => 'dura_cabs_app',
            ],
            $currency
        );

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to create wallet recharge order.',
                422,
                $result
            );
        }

        $order = $result['data'] ?? [];

        if (! is_array($order) || empty($order['id'])) {
            return $this->error(
                'Invalid Razorpay order response.',
                502
            );
        }

        $key = RazorpayService::publicKey();

        if (! $key) {
            return $this->error(
                'Razorpay public key is not configured.',
                500
            );
        }

        Log::info('Wallet recharge Razorpay order created', [
            'user_id' => $user->id,
            'amount' => $amount,
            'order_id' => $order['id'],
            'receipt' => $receipt,
        ]);

        return $this->success([
            'order_id' => (string) $order['id'],
            'razorpay_order_id' => (string) $order['id'],
            'key' => $key,
            'amount' => $amount,
            'amount_paise' => (int) round($amount * 100),
            'currency' => $currency,
            'receipt' => $receipt,
        ], 'Wallet recharge order created successfully.');
    }

    public function verifyRechargePayment(
        Request $request,
        WalletService $walletService
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'max:255'],
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
            return $this->error('Unauthenticated.', 401);
        }

        $orderId = trim((string) $request->input('razorpay_order_id'));
        $paymentId = trim((string) $request->input('razorpay_payment_id'));
        $signature = trim((string) $request->input('razorpay_signature'));

        $signatureResult = RazorpayService::verifyPayment(
            $orderId,
            $paymentId,
            $signature
        );

        if (! ($signatureResult['status'] ?? false)) {
            return $this->error(
                $signatureResult['message'] ?? 'Invalid Razorpay signature.',
                422
            );
        }

        $orderResult = RazorpayService::fetchOrder($orderId);

        if (! ($orderResult['status'] ?? false)) {
            return $this->error(
                $orderResult['message'] ?? 'Unable to fetch Razorpay order.',
                422
            );
        }

        $order = $orderResult['data'] ?? null;

        if (! is_array($order)) {
            return $this->error('Invalid Razorpay order details.', 502);
        }

        $notes = is_array($order['notes'] ?? null)
            ? $order['notes']
            : [];

        if (
            (string) ($notes['purpose'] ?? '') !== 'wallet_recharge'
            || (string) ($notes['user_id'] ?? '') !== (string) $user->id
        ) {
            Log::warning('Wallet recharge order ownership mismatch', [
                'authenticated_user_id' => $user->id,
                'order_id' => $orderId,
                'notes' => $notes,
            ]);

            return $this->error(
                'This wallet recharge order does not belong to your account.',
                403
            );
        }

        $paymentResult = RazorpayService::fetchPayment($paymentId);

        if (! ($paymentResult['status'] ?? false)) {
            return $this->error(
                $paymentResult['message'] ?? 'Unable to fetch Razorpay payment.',
                422
            );
        }

        $payment = $paymentResult['data'] ?? null;

        if (! is_array($payment)) {
            return $this->error('Invalid Razorpay payment details.', 502);
        }

        if (
            ! empty($payment['order_id'])
            && ! hash_equals(
                (string) $orderId,
                (string) $payment['order_id']
            )
        ) {
            return $this->error(
                'Razorpay payment does not belong to this recharge order.',
                422
            );
        }

        $orderAmountPaise = (int) ($order['amount'] ?? 0);
        $paymentAmountPaise = (int) ($payment['amount'] ?? 0);

        if (
            $orderAmountPaise <= 0
            || $paymentAmountPaise <= 0
            || $orderAmountPaise !== $paymentAmountPaise
        ) {
            return $this->error(
                'Razorpay payment amount does not match the wallet recharge amount.',
                422
            );
        }

        $orderCurrency = strtoupper(
            trim((string) ($order['currency'] ?? ''))
        );
        $paymentCurrency = strtoupper(
            trim((string) ($payment['currency'] ?? ''))
        );

        if ($orderCurrency !== 'INR' || $paymentCurrency !== 'INR') {
            return $this->error(
                'Invalid wallet recharge currency.',
                422
            );
        }

        $paymentStatus = strtolower(
            trim((string) ($payment['status'] ?? ''))
        );

        if ($paymentStatus === 'authorized') {
            $captureResult = RazorpayService::capturePayment(
                $paymentId,
                $paymentAmountPaise / 100,
                'INR'
            );

            if (! ($captureResult['status'] ?? false)) {
                return $this->error(
                    $captureResult['message']
                        ?? 'Unable to capture Razorpay payment.',
                    422
                );
            }

            $payment = is_array($captureResult['data'] ?? null)
                ? $captureResult['data']
                : $payment;

            $paymentStatus = strtolower(
                trim((string) ($payment['status'] ?? ''))
            );
        }

        if ($paymentStatus !== 'captured') {
            return $this->error(
                'Razorpay payment has not been captured.',
                422
            );
        }

        $amount = round($paymentAmountPaise / 100, 2);
        $reference = 'razorpay_wallet:' . $paymentId;

        $walletResult = $walletService->addMoney(
            $user,
            $amount,
            'razorpay',
            $reference,
            [
                'purpose' => 'wallet_recharge',
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
                'receipt' => (string) ($order['receipt'] ?? ''),
                'currency' => 'INR',
            ]
        );

        if (! ($walletResult['status'] ?? false)) {
            return $this->error(
                $walletResult['message'] ?? 'Unable to credit wallet.',
                (int) ($walletResult['code'] ?? 422),
                $walletResult['errors'] ?? null
            );
        }

        $balanceResult = $walletService->balance($user);

        Log::info('Customer wallet recharge completed', [
            'user_id' => $user->id,
            'amount' => $amount,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'reference' => $reference,
        ]);

        return $this->success([
            'transaction' => $walletResult['data'] ?? null,
            'wallet' => $balanceResult['data'] ?? null,
            'amount' => $amount,
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
        ], 'Wallet recharged successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Wallet Recharge OTP
    |--------------------------------------------------------------------------
    */

    public function sendAdminRechargeOtp(
        Request $request,
        OtpService $otpService
    ): JsonResponse {
        $admin = $request->user();

        if (! $this->isAuthorizedAdmin($admin)) {
            return $this->error(
                'You are not authorized to recharge customer wallets.',
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => [
                'required',
                'integer',
                'min:1',
                'exists:users,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:500000',
            ],
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $customer = User::query()->find(
            (int) $request->input('customer_id')
        );

        if (! $customer) {
            return $this->error('Customer not found.', 404);
        }

        if (
            isset($customer->is_active) &&
            ! (bool) $customer->is_active
        ) {
            return $this->error(
                'Selected customer account is inactive.',
                422
            );
        }

        $amount = round(
            (float) $request->input('amount'),
            2
        );

        $reason = trim(
            (string) $request->input('reason')
        );

        $superAdminMobile = $this->superAdminMobile();

        if (strlen($superAdminMobile) !== 10) {
            Log::critical(
                'Wallet recharge super admin mobile is invalid or missing.'
            );

            return $this->error(
                'Super Admin OTP mobile is not configured.',
                500
            );
        }

        $purpose = $this->adminRechargePurpose(
            (int) $admin->id
        );

        $payload = [
            'customer_id' => (int) $customer->id,
            'customer_name' => (string) (
                $customer->name ?? 'Customer'
            ),
            'customer_mobile' => (string) (
                $customer->mobile ?? ''
            ),
            'amount' => $amount,
            'reason' => $reason,
            'admin_id' => (int) $admin->id,
            'admin_name' => (string) (
                $admin->name ?? 'Admin'
            ),
            'requested_ip' => (string) $request->ip(),
            'requested_user_agent' => substr(
                (string) $request->userAgent(),
                0,
                500
            ),
        ];

        try {
            $result = $otpService->sendPurposeOtp(
                $superAdminMobile,
                $purpose,
                $payload
            );
        } catch (Throwable $e) {
            Log::error('Admin wallet recharge OTP send failed', [
                'admin_id' => $admin->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Unable to send wallet recharge OTP.',
                500
            );
        }

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to send OTP.',
                (int) ($result['code'] ?? 422),
                $result
            );
        }

        Log::info('Admin wallet recharge OTP requested', [
            'admin_id' => $admin->id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'reason' => $reason,
            'verification_id' =>
                $result['verification_id'] ?? null,
            'ip' => $request->ip(),
        ]);

        return $this->success([
            'verification_id' =>
                $result['verification_id'] ?? null,
            'customer' => [
                'id' => (int) $customer->id,
                'name' => (string) (
                    $customer->name ?? 'Customer'
                ),
                'mobile' => $this->maskMobile(
                    (string) ($customer->mobile ?? '')
                ),
            ],
            'amount' => $amount,
            'reason' => $reason,
            'otp_sent_to' => $this->maskMobile(
                $superAdminMobile
            ),
            'expires_in' => (int) (
                $result['expires_in'] ?? 300
            ),
            'resend_after' => (int) (
                $result['resend_after'] ?? 30
            ),
            'delivered_on' =>
                $result['delivered_on'] ?? [],
        ], 'OTP sent to Super Admin successfully.');
    }

   public function verifyAdminRechargeOtp(
    Request $request,
    OtpService $otpService,
    WalletService $walletService,
    NotificationService $notificationService

    ): JsonResponse {
        $admin = $request->user();

        if (! $this->isAuthorizedAdmin($admin)) {
            return $this->error(
                'You are not authorized to recharge customer wallets.',
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'verification_id' => [
                'required',
                'uuid',
            ],
            'otp' => [
                'required',
                'digits:4',
            ],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );
        }

        $superAdminMobile = $this->superAdminMobile();

        if (strlen($superAdminMobile) !== 10) {
            return $this->error(
                'Super Admin OTP mobile is not configured.',
                500
            );
        }

        $purpose = $this->adminRechargePurpose(
            (int) $admin->id
        );

        try {
            $otpResult = $otpService->verifyPurposeOtp(
                $superAdminMobile,
                $purpose,
                (string) $request->input('otp'),
                (string) $request->input('verification_id')
            );
        } catch (Throwable $e) {
            Log::error(
                'Admin wallet recharge OTP verification failed',
                [
                    'admin_id' => $admin->id,
                    'verification_id' =>
                        $request->input('verification_id'),
                    'error' => $e->getMessage(),
                ]
            );

            return $this->error(
                'Unable to verify wallet recharge OTP.',
                500
            );
        }

        if (! ($otpResult['status'] ?? false)) {
            return $this->error(
                $otpResult['message'] ?? 'Invalid OTP.',
                (int) ($otpResult['code'] ?? 422),
                $otpResult
            );
        }

        $payload = $otpResult['data']['payload'] ?? [];

        if (! is_array($payload)) {
            return $this->error(
                'Invalid wallet recharge verification data.',
                422
            );
        }

        $payloadAdminId = (int) (
            $payload['admin_id'] ?? 0
        );

        if ($payloadAdminId !== (int) $admin->id) {
            Log::warning(
                'Admin wallet recharge payload owner mismatch',
                [
                    'authenticated_admin_id' => $admin->id,
                    'payload_admin_id' => $payloadAdminId,
                ]
            );

            return $this->error(
                'This recharge request belongs to another administrator.',
                403
            );
        }

        $customerId = (int) (
            $payload['customer_id'] ?? 0
        );

        $amount = round(
            (float) ($payload['amount'] ?? 0),
            2
        );

        $reason = trim(
            (string) ($payload['reason'] ?? '')
        );

        if (
            $customerId <= 0 ||
            $amount <= 0 ||
            $reason === ''
        ) {
            return $this->error(
                'Wallet recharge request data is incomplete.',
                422
            );
        }

        $customer = User::query()->find($customerId);

        if (! $customer) {
            return $this->error(
                'Customer no longer exists.',
                404
            );
        }

        try {
            $result = $walletService->adminRecharge(
                customer: $customer,
                amount: $amount,
                reason: $reason,
                admin: $admin,
                verificationId: (string) (
                    $otpResult['data']['verification_id']
                    ?? $request->input('verification_id')
                )
            );
        } catch (Throwable $e) {
            Log::critical(
                'Verified admin wallet recharge failed',
                [
                    'admin_id' => $admin->id,
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'verification_id' =>
                        $request->input('verification_id'),
                    'error' => $e->getMessage(),
                ]
            );

            return $this->error(
                'OTP was verified, but wallet recharge failed. Please check logs before retrying.',
                500
            );
        }

        if (! ($result['status'] ?? false)) {
            return $this->error(
                $result['message'] ?? 'Unable to recharge wallet.',
                (int) ($result['code'] ?? 422),
                $result['errors'] ?? null
            );
        }try {

    $notificationService->sendToCustomer(
        customer: $customer,
        title: 'Wallet Credited',
        message: '₹' . number_format($amount, 2) .
            ' has been credited to your wallet by Dura Cabs.',
        data: [
            'type' => 'wallet',
            'event' => 'wallet_admin_recharge',
            'amount' => (string) $amount,
            'reason' => $reason,
            'transaction_id' =>
                $result['data']['transaction']->transaction_id ?? '',
            'click_action' => 'OPEN_WALLET',
        ],
        channels: ['push'],
        saveInDatabase: true
    );

} catch (Throwable $e) {

    Log::warning(
        'Wallet recharge notification failed.',
        [
            'customer_id' => $customer->id,
            'amount' => $amount,
            'error' => $e->getMessage(),
        ]
    );

}

        Log::info('Admin wallet recharge completed', [
            'admin_id' => $admin->id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'reason' => $reason,
            'verification_id' =>
                $request->input('verification_id'),
            'ip' => $request->ip(),
        ]);

        return $this->success(
            $result['data'] ?? null,
            $result['message']
                ?? 'Customer wallet recharged successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function adminRechargePurpose(int $adminId): string
    {
        return self::ADMIN_RECHARGE_PURPOSE
            . '_'
            . $adminId;
    }

    private function superAdminMobile(): string
    {
        $mobile = (string) config(
            'services.wallet.super_admin_mobile',
            env(
                'WALLET_SUPER_ADMIN_MOBILE',
                '7088873331'
            )
        );

        $mobile = preg_replace(
            '/\D+/',
            '',
            $mobile
        ) ?? '';

        if (
            strlen($mobile) > 10 &&
            str_starts_with($mobile, '91')
        ) {
            $mobile = substr($mobile, -10);
        }

        return $mobile;
    }

    private function isAuthorizedAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        /*
         * Laravel Gate/Policy support.
         */
        try {
            if (
                method_exists($user, 'can') &&
                (
                    $user->can('admin-wallet-recharge') ||
                    $user->can('manage-wallets')
                )
            ) {
                return true;
            }
        } catch (Throwable) {
            // Continue with role/flag checks.
        }

        /*
         * Common admin columns used by Laravel applications.
         */
        foreach (
            ['is_super_admin', 'is_admin', 'super_admin']
            as $flag
        ) {
            if (
                isset($user->{$flag}) &&
                (bool) $user->{$flag}
            ) {
                return true;
            }
        }

        /*
         * Common role columns.
         */
        foreach (
            ['role', 'user_type', 'account_type']
            as $column
        ) {
            $role = strtolower(
                trim((string) ($user->{$column} ?? ''))
            );

            if (
                in_array(
                    $role,
                    [
                        'admin',
                        'super_admin',
                        'super-admin',
                        'administrator',
                    ],
                    true
                )
            ) {
                return true;
            }
        }

        /*
         * Spatie Laravel Permission support.
         */
        try {
            if (
                method_exists($user, 'hasAnyRole') &&
                $user->hasAnyRole([
                    'admin',
                    'super_admin',
                    'super-admin',
                ])
            ) {
                return true;
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    private function maskMobile(string $mobile): string
    {
        $mobile = preg_replace(
            '/\D+/',
            '',
            $mobile
        ) ?? '';

        if (strlen($mobile) <= 4) {
            return $mobile;
        }

        return str_repeat(
            '*',
            strlen($mobile) - 4
        ) . substr($mobile, -4);
    }
}