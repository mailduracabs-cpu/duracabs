<?php

namespace App\Services;

use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WalletService
{
    private const MAX_WALLET_AMOUNT = 500000;

    public function __construct(
        private readonly WalletRepository $walletRepository
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Wallet Balance
    |--------------------------------------------------------------------------
    */

    public function balance($user): array
    {
        if (! $user) {
            return $this->unauthenticated();
        }

        try {
            return [
                'status' => true,
                'message' => 'Wallet balance fetched successfully',
                'data' => $this->walletRepository->balance(
                    (int) $user->id
                ),
            ];
        } catch (Throwable $e) {
            $this->logError('Wallet Balance Error', $e, [
                'user_id' => $user->id,
            ]);

            return $this->failure(
                'Unable to fetch wallet balance',
                500,
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Wallet History
    |--------------------------------------------------------------------------
    */

    public function history($user, int $limit = 20): array
    {
        if (! $user) {
            return $this->unauthenticated();
        }

        $limit = min(max($limit, 1), 100);

        try {
            return [
                'status' => true,
                'message' => 'Wallet history fetched successfully',
                'data' => $this->walletRepository->history(
                    (int) $user->id,
                    $limit
                ),
            ];
        } catch (Throwable $e) {
            $this->logError('Wallet History Error', $e, [
                'user_id' => $user->id,
                'limit' => $limit,
            ]);

            return $this->failure(
                'Unable to fetch wallet history',
                500,
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Add Money
    |--------------------------------------------------------------------------
    */

    public function addMoney(
        $user,
        float $amount,
        string $paymentMethod = 'manual'
    ): array {
        if (! $user) {
            return $this->unauthenticated();
        }

        $amount = $this->normalizeAmount($amount);

        if ($amount <= 0) {
            return $this->invalidAmount('Invalid amount');
        }

        if ($amount > self::MAX_WALLET_AMOUNT) {
            return $this->invalidAmount(
                'Maximum wallet credit amount is ₹'
                . number_format(self::MAX_WALLET_AMOUNT, 2)
            );
        }

        $paymentMethod = $this->normalizePaymentMethod(
            $paymentMethod,
            'manual'
        );

        try {
            $transaction = $this->walletRepository->credit(
                userId: (int) $user->id,
                amount: $amount,
                remarks: 'Wallet money added',
                paymentMethod: $paymentMethod
            );

            if (! $transaction) {
                return $this->failure(
                    'Unable to add money',
                    422
                );
            }

            return [
                'status' => true,
                'message' => 'Money added to wallet successfully',
                'data' => $transaction,
            ];
        } catch (Throwable $e) {
            $this->logError('Wallet Add Money Error', $e, [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]);

            return $this->failure(
                'Unable to add money',
                500,
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Wallet Payment
    |--------------------------------------------------------------------------
    */

    public function pay(
        $user,
        float $amount,
        ?int $bookingId = null
    ): array {
        if (! $user) {
            return $this->unauthenticated();
        }

        $amount = $this->normalizeAmount($amount);

        if ($amount <= 0) {
            return $this->invalidAmount(
                'Invalid payment amount'
            );
        }

        if ($amount > self::MAX_WALLET_AMOUNT) {
            return $this->invalidAmount(
                'Payment amount exceeds the allowed limit'
            );
        }

        try {
            $transaction = $this->walletRepository->debit(
                userId: (int) $user->id,
                amount: $amount,
                remarks: 'Booking payment from wallet',
                paymentMethod: 'wallet',
                bookingId: $bookingId
            );

            if (! $transaction) {
                return [
                    'status' => false,
                    'message' => 'Insufficient wallet balance',
                    'code' => 422,
                ];
            }

            return [
                'status' => true,
                'message' => 'Wallet payment successful',
                'data' => $transaction,
            ];
        } catch (Throwable $e) {
            $this->logError('Wallet Payment Error', $e, [
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'amount' => $amount,
            ]);

            return $this->failure(
                'Unable to process wallet payment',
                500,
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Wallet Refund
    |--------------------------------------------------------------------------
    */

    public function refund(
        $user,
        float $amount,
        ?int $bookingId = null
    ): array {
        if (! $user) {
            return $this->unauthenticated();
        }

        $amount = $this->normalizeAmount($amount);

        if ($amount <= 0) {
            return $this->invalidAmount(
                'Invalid refund amount'
            );
        }

        if ($amount > self::MAX_WALLET_AMOUNT) {
            return $this->invalidAmount(
                'Refund amount exceeds the allowed limit'
            );
        }

        try {
            $transaction = $this->walletRepository->credit(
                userId: (int) $user->id,
                amount: $amount,
                remarks: 'Booking refund credited',
                paymentMethod: 'refund',
                bookingId: $bookingId
            );

            if (! $transaction) {
                return $this->failure(
                    'Unable to credit wallet refund',
                    422
                );
            }

            return [
                'status' => true,
                'message' => 'Refund credited to wallet successfully',
                'data' => $transaction,
            ];
        } catch (Throwable $e) {
            $this->logError('Wallet Refund Error', $e, [
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'amount' => $amount,
            ]);

            return $this->failure(
                'Unable to credit wallet refund',
                500,
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cashback
    |--------------------------------------------------------------------------
    */

    public function cashback(
        $user,
        float $amount,
        ?int $bookingId = null
    ): array {
        if (! $user) {
            return $this->unauthenticated();
        }

        $amount = $this->normalizeAmount($amount);

        if ($amount <= 0) {
            return $this->invalidAmount(
                'Invalid cashback amount'
            );
        }

        if ($amount > self::MAX_WALLET_AMOUNT) {
            return $this->invalidAmount(
                'Cashback amount exceeds the allowed limit'
            );
        }

        try {
            $transaction = $this->walletRepository->credit(
                userId: (int) $user->id,
                amount: $amount,
                remarks: 'Cashback credited',
                paymentMethod: 'cashback',
                bookingId: $bookingId
            );

            if (! $transaction) {
                return $this->failure(
                    'Unable to credit cashback',
                    422
                );
            }

            return [
                'status' => true,
                'message' => 'Cashback credited successfully',
                'data' => $transaction,
            ];
        } catch (Throwable $e) {
            $this->logError('Wallet Cashback Error', $e, [
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'amount' => $amount,
            ]);

            return $this->failure(
                'Unable to credit cashback',
                500,
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Wallet Recharge
    |--------------------------------------------------------------------------
    |
    | OTP verification WalletController me hoti hai.
    | Yahan existing wallet repository aur current transaction schema ko reuse
    | karke customer wallet credit kiya jata hai.
    |
    */

    public function adminRecharge(
        $customer,
        float $amount,
        string $reason,
        $admin,
        string $verificationId
    ): array {
        if (! $customer || empty($customer->id)) {
            return [
                'status' => false,
                'message' => 'Customer not found',
                'code' => 404,
            ];
        }

        if (! $admin || empty($admin->id)) {
            return [
                'status' => false,
                'message' => 'Administrator not found',
                'code' => 401,
            ];
        }

        $amount = $this->normalizeAmount($amount);
        $reason = $this->normalizeReason($reason);
        $verificationId = trim($verificationId);

        if ($amount <= 0) {
            return $this->invalidAmount(
                'Invalid wallet recharge amount'
            );
        }

        if ($amount > self::MAX_WALLET_AMOUNT) {
            return $this->invalidAmount(
                'Maximum wallet recharge amount is ₹'
                . number_format(self::MAX_WALLET_AMOUNT, 2)
            );
        }

        if ($reason === '') {
            return [
                'status' => false,
                'message' => 'Wallet recharge reason is required',
                'code' => 422,
            ];
        }

        if ($verificationId === '') {
            return [
                'status' => false,
                'message' => 'OTP verification reference is required',
                'code' => 422,
            ];
        }

        if ((int) $customer->id === (int) $admin->id) {
            Log::warning(
                'Admin attempted wallet recharge on own account',
                [
                    'admin_id' => $admin->id,
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'verification_id' => $verificationId,
                ]
            );
        }

        $remarks = $this->adminRechargeRemarks(
            admin: $admin,
            reason: $reason,
            verificationId: $verificationId
        );

        try {
            /*
             * WalletRepository::credit() already wraps wallet balance update
             * and wallet transaction creation inside one DB transaction.
             */
            $transaction = $this->walletRepository->credit(
                userId: (int) $customer->id,
                amount: $amount,
                remarks: $remarks,
                paymentMethod: 'admin_recharge',
                bookingId: null
            );

            if (! $transaction) {
                return $this->failure(
                    'Unable to recharge customer wallet',
                    422
                );
            }

            $walletBalance = $this->walletRepository->balance(
                (int) $customer->id
            );

            Log::info('Customer wallet recharged by administrator', [
                'admin_id' => (int) $admin->id,
                'admin_name' => (string) (
                    $admin->name ?? 'Admin'
                ),
                'customer_id' => (int) $customer->id,
                'customer_name' => (string) (
                    $customer->name ?? 'Customer'
                ),
                'amount' => $amount,
                'reason' => $reason,
                'verification_id' => $verificationId,
                'wallet_transaction_id' => $transaction->id ?? null,
                'wallet_reference' =>
                    $transaction->transaction_id ?? null,
                'closing_balance' =>
                    $transaction->closing_balance ?? null,
            ]);

            return [
                'status' => true,
                'message' => 'Customer wallet recharged successfully',
                'data' => [
                    'transaction' => $transaction,
                    'wallet' => $walletBalance,
                    'recharge' => [
                        'customer_id' => (int) $customer->id,
                        'customer_name' => (string) (
                            $customer->name ?? 'Customer'
                        ),
                        'amount' => $amount,
                        'reason' => $reason,
                        'payment_method' => 'admin_recharge',
                        'verification_id' => $verificationId,
                        'approved_by' => [
                            'id' => (int) $admin->id,
                            'name' => (string) (
                                $admin->name ?? 'Admin'
                            ),
                        ],
                    ],
                ],
            ];
        } catch (Throwable $e) {
            $this->logError(
                'Admin Wallet Recharge Error',
                $e,
                [
                    'admin_id' => $admin->id,
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'reason' => $reason,
                    'verification_id' => $verificationId,
                ]
            );

            return $this->failure(
                'Unable to recharge customer wallet',
                500,
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Normalization Helpers
    |--------------------------------------------------------------------------
    */

    private function normalizeAmount(float $amount): float
    {
        if (! is_finite($amount)) {
            return 0;
        }

        return round(max(0, $amount), 2);
    }

    private function normalizePaymentMethod(
        string $paymentMethod,
        string $fallback
    ): string {
        $paymentMethod = strtolower(
            trim($paymentMethod)
        );

        if ($paymentMethod === '') {
            return $fallback;
        }

        $paymentMethod = preg_replace(
            '/[^a-z0-9_\-]/',
            '_',
            $paymentMethod
        ) ?? $fallback;

        return substr($paymentMethod, 0, 50);
    }

    private function normalizeReason(string $reason): string
    {
        $reason = trim(
            preg_replace('/\s+/', ' ', $reason) ?? ''
        );

        return Str::limit($reason, 150, '');
    }

    private function adminRechargeRemarks(
        $admin,
        string $reason,
        string $verificationId
    ): string {
        $adminName = trim(
            (string) ($admin->name ?? 'Admin')
        );

        $adminName = preg_replace(
            '/\s+/',
            ' ',
            $adminName
        ) ?? 'Admin';

        $shortVerificationId = substr(
            $verificationId,
            0,
            12
        );

        $remarks = sprintf(
            'Admin recharge | By: %s (#%d) | Reason: %s | OTP Ref: %s',
            $adminName,
            (int) $admin->id,
            $reason,
            $shortVerificationId
        );

        /*
         * Existing remarks column varchar ho sakta hai, isliye safe limit.
         */
        return Str::limit($remarks, 250, '');
    }

    /*
    |--------------------------------------------------------------------------
    | Common Responses
    |--------------------------------------------------------------------------
    */

    private function unauthenticated(): array
    {
        return [
            'status' => false,
            'message' => 'Unauthenticated',
            'code' => 401,
        ];
    }

    private function invalidAmount(string $message): array
    {
        return [
            'status' => false,
            'message' => $message,
            'code' => 422,
        ];
    }

    private function failure(
        string $message,
        int $code,
        ?Throwable $e = null
    ): array {
        return [
            'status' => false,
            'message' => $message,
            'code' => $code,
            'errors' => $e && config('app.debug')
                ? $e->getMessage()
                : null,
        ];
    }

    private function logError(
        string $message,
        Throwable $e,
        array $context = []
    ): void {
        Log::error(
            $message,
            array_merge($context, [
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ])
        );
    }
}