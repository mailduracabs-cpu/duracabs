<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\OtpService;
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