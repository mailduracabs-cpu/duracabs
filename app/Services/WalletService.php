<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

class WalletService
{
    public function __construct(
        private readonly WalletRepository $walletRepository
    ) {
    }

    public function balance(?User $user): array
    {
        if (! $user) {
            return $this->failure('Unauthenticated.', 401);
        }

        try {
            return $this->success(
                $this->walletRepository->balance((int) $user->id),
                'Wallet balance fetched successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Wallet balance fetch failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Unable to fetch wallet balance.', 500);
        }
    }

    public function history(?User $user, int $limit = 20): array
    {
        if (! $user) {
            return $this->failure('Unauthenticated.', 401);
        }

        $limit = max(1, min($limit, 100));

        try {
            return $this->success(
                $this->walletRepository->history((int) $user->id, $limit),
                'Wallet history fetched successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Wallet history fetch failed', [
                'user_id' => $user->id,
                'limit' => $limit,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Unable to fetch wallet history.', 500);
        }
    }

    public function addMoney(
        ?User $user,
        float $amount,
        ?string $paymentMethod = null,
        ?string $reference = null,
        array $meta = []
    ): array {
        if (! $user) {
            return $this->failure('Unauthenticated.', 401);
        }

        $amount = $this->normalizeAmount($amount);

        if ($amount <= 0) {
            return $this->failure('Amount must be greater than zero.', 422);
        }

        $paymentMethod = $this->cleanMethod(
            $paymentMethod,
            WalletTransaction::METHOD_MANUAL
        );

        if ($reference && ($existing = WalletTransaction::findByReference($reference))) {
            return $this->success(
                $existing,
                'Wallet transaction already processed.'
            );
        }

        try {
            $transaction = $this->walletRepository->credit(
                userId: (int) $user->id,
                amount: $amount,
                remarks: 'Wallet recharge',
                paymentMethod: $paymentMethod
            );

            $this->applyTransactionMetadata($transaction, $reference, $meta);

            return $this->success(
                $transaction->fresh(),
                'Money added to wallet successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Wallet add money failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Unable to add money to wallet.', 500);
        }
    }

    public function pay(
        ?User $user,
        float $amount,
        ?int $bookingId = null,
        ?string $reference = null,
        array $meta = []
    ): array {
        if (! $user) {
            return $this->failure('Unauthenticated.', 401);
        }

        $amount = $this->normalizeAmount($amount);

        if ($amount <= 0) {
            return $this->failure('Payment amount must be greater than zero.', 422);
        }

        if ($reference && ($existing = WalletTransaction::findByReference($reference))) {
            return $this->success(
                $existing,
                'Wallet payment already processed.'
            );
        }

        try {
            $transaction = $this->walletRepository->debit(
                userId: (int) $user->id,
                amount: $amount,
                remarks: $bookingId
                    ? "Wallet payment for booking #{$bookingId}"
                    : 'Wallet payment',
                paymentMethod: WalletTransaction::METHOD_WALLET,
                bookingId: $bookingId
            );

            if (! $transaction) {
                $balance = $this->walletRepository->balance((int) $user->id);

                return $this->failure(
                    'Insufficient wallet balance.',
                    422,
                    [
                        'available_balance' => (float) (
                            $balance['available_balance']
                            ?? $balance['balance']
                            ?? 0
                        ),
                        'required_amount' => $amount,
                    ]
                );
            }

            $this->applyTransactionMetadata($transaction, $reference, $meta);

            return $this->success(
                $transaction->fresh(),
                'Wallet payment successful.'
            );
        } catch (Throwable $e) {
            Log::error('Wallet payment failed', [
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'amount' => $amount,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Wallet payment failed.', 500);
        }
    }

    public function refund(
        ?User $user,
        float $amount,
        ?int $bookingId = null,
        ?string $reference = null,
        array $meta = []
    ): array {
        if (! $user) {
            return $this->failure('Unauthenticated.', 401);
        }

        $amount = $this->normalizeAmount($amount);

        if ($amount <= 0) {
            return $this->failure('Refund amount must be greater than zero.', 422);
        }

        $reference ??= $bookingId
            ? "wallet_refund_booking_{$bookingId}_" . number_format($amount, 2, '.', '')
            : null;

        if ($reference && ($existing = WalletTransaction::findByReference($reference))) {
            return $this->success(
                $existing,
                'Wallet refund already processed.'
            );
        }

        try {
            $transaction = $this->walletRepository->credit(
                userId: (int) $user->id,
                amount: $amount,
                remarks: $bookingId
                    ? "Refund for booking #{$bookingId}"
                    : 'Wallet refund',
                paymentMethod: WalletTransaction::METHOD_REFUND,
                bookingId: $bookingId
            );

            $this->applyTransactionMetadata($transaction, $reference, $meta);

            return $this->success(
                $transaction->fresh(),
                'Refund credited to wallet successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Wallet refund failed', [
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'amount' => $amount,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Unable to process wallet refund.', 500);
        }
    }

    public function cashback(
        ?User $user,
        float $amount,
        ?int $bookingId = null,
        ?string $reference = null,
        array $meta = []
    ): array {
        if (! $user) {
            return $this->failure('Unauthenticated.', 401);
        }

        $amount = $this->normalizeAmount($amount);

        if ($amount <= 0) {
            return $this->failure('Cashback amount must be greater than zero.', 422);
        }

        $reference ??= $bookingId
            ? "cashback_booking_{$bookingId}_" . number_format($amount, 2, '.', '')
            : null;

        if ($reference && ($existing = WalletTransaction::findByReference($reference))) {
            return $this->success(
                $existing,
                'Cashback already credited.'
            );
        }

        try {
            $transaction = $this->walletRepository->credit(
                userId: (int) $user->id,
                amount: $amount,
                remarks: $bookingId
                    ? "Cashback for booking #{$bookingId}"
                    : 'Wallet cashback',
                paymentMethod: WalletTransaction::METHOD_CASHBACK,
                bookingId: $bookingId
            );

            $this->applyTransactionMetadata($transaction, $reference, $meta);

            return $this->success(
                $transaction->fresh(),
                'Cashback credited successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Wallet cashback failed', [
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'amount' => $amount,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Unable to credit cashback.', 500);
        }
    }

    public function adminRecharge(
        User $customer,
        float $amount,
        string $reason,
        User $admin,
        string $verificationId
    ): array {
        $amount = $this->normalizeAmount($amount);
        $reason = trim($reason);
        $verificationId = trim($verificationId);

        if ($amount <= 0) {
            return $this->failure('Recharge amount must be greater than zero.', 422);
        }

        if ($reason === '') {
            return $this->failure('Recharge reason is required.', 422);
        }

        if ($verificationId === '') {
            return $this->failure('Wallet recharge verification ID is required.', 422);
        }

        $reference = 'admin_wallet_recharge:' . $verificationId;

        if ($existing = WalletTransaction::findByReference($reference)) {
            return $this->success(
                [
                    'transaction' => $existing,
                    'wallet' => $this->walletRepository->balance((int) $customer->id),
                ],
                'Customer wallet was already recharged for this verification.'
            );
        }

        try {
            $transaction = $this->walletRepository->credit(
                userId: (int) $customer->id,
                amount: $amount,
                remarks: $reason,
                paymentMethod: WalletTransaction::METHOD_MANUAL
            );

            $this->applyTransactionMetadata(
                $transaction,
                $reference,
                [
                    'operation' => 'admin_wallet_recharge',
                    'admin_id' => (int) $admin->id,
                    'admin_name' => (string) ($admin->name ?? ''),
                    'customer_id' => (int) $customer->id,
                    'verification_id' => $verificationId,
                    'reason' => $reason,
                ]
            );

            return $this->success(
                [
                    'transaction' => $transaction->fresh(),
                    'wallet' => $this->walletRepository->balance((int) $customer->id),
                ],
                'Customer wallet recharged successfully.'
            );
        } catch (Throwable $e) {
            Log::critical('Admin wallet recharge failed', [
                'admin_id' => $admin->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'verification_id' => $verificationId,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Unable to recharge customer wallet.', 500);
        }
    }

    private function applyTransactionMetadata(
        WalletTransaction $transaction,
        ?string $reference = null,
        array $meta = []
    ): void {
        $updates = [];

        if ($reference !== null && trim($reference) !== '') {
            $updates['reference'] = trim($reference);
        }

        if ($meta !== []) {
            $updates['meta'] = array_replace_recursive(
                is_array($transaction->meta) ? $transaction->meta : [],
                $meta
            );
        }

        if ($updates !== []) {
            $transaction->update($updates);
        }
    }

    private function normalizeAmount(float|int|string|null $amount): float
    {
        return round(max(0, (float) $amount), 2);
    }

    private function cleanMethod(?string $method, string $fallback): string
    {
        $method = strtolower(trim((string) $method));

        return $method !== '' ? $method : $fallback;
    }

    private function success(mixed $data, string $message): array
    {
        return [
            'status' => true,
            'message' => $message,
            'data' => $data,
            'code' => 200,
            'errors' => null,
        ];
    }

    private function failure(
        string $message,
        int $code = 422,
        mixed $errors = null
    ): array {
        return [
            'status' => false,
            'message' => $message,
            'data' => null,
            'code' => $code,
            'errors' => $errors,
        ];
    }
}