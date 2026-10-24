<?php

namespace App\Repositories;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletRepository
{
    /**
     * Get or Create Wallet
     */
    public function getWallet(int $userId, string $walletType = 'customer'): Wallet
    {
        return Wallet::firstOrCreate(
            [
                'user_id' => $userId,
                'wallet_type' => $walletType,
            ],
            [
                'balance' => 0,
                'hold_balance' => 0,
                'currency' => 'INR',
                'is_active' => true,
            ]
        );
    }

    /**
     * Wallet Balance
     */
    public function balance(int $userId): array
    {
        $wallet = $this->getWallet($userId);

        return [
            'wallet_id' => $wallet->id,
            'balance' => (float) $wallet->balance,
            'hold_balance' => (float) $wallet->hold_balance,
            'available_balance' => (float) $wallet->available_balance,
            'currency' => $wallet->currency,
        ];
    }

    /**
     * Wallet History
     */
    public function history(int $userId, int $limit = 20)
    {
        return WalletTransaction::where('user_id', $userId)
            ->latest()
            ->paginate($limit);
    }

    /**
     * Credit Wallet
     */
    public function credit(
        int $userId,
        float $amount,
        string $remarks = '',
        ?string $paymentMethod = null,
        ?int $bookingId = null
    ): WalletTransaction {

        return DB::transaction(function () use (
            $userId,
            $amount,
            $remarks,
            $paymentMethod,
            $bookingId
        ) {

            if (! is_finite($amount) || $amount <= 0 || round($amount, 2) <= 0) {
                throw new \InvalidArgumentException('Wallet amount must be positive.');
            }
            $amount = round($amount, 2);
            // Lock an existing user first, including the first wallet creation.
            if (! DB::table('users')->where('id', $userId)->lockForUpdate()->first()) {
                throw new \RuntimeException('Wallet customer not found.');
            }
            $walletId = $this->getWallet($userId)->id;
            $wallet = Wallet::query()->whereKey($walletId)->lockForUpdate()->firstOrFail();
            if (! $wallet->is_active || $wallet->currency !== 'INR') {
                throw new \RuntimeException('Wallet is unavailable.');
            }

            $opening = (float) $wallet->balance;
            $closing = $opening + $amount;

            $wallet->update([
                'balance' => $closing,
            ]);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'booking_id' => $bookingId,
                'transaction_id' => uniqid('CRD'),
                'type' => 'credit',
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'opening_balance' => $opening,
                'closing_balance' => $closing,
                'status' => 'success',
                'remarks' => $remarks,
            ]);
        });
    }

    /**
     * Debit Wallet
     */
    public function debit(
        int $userId,
        float $amount,
        string $remarks = '',
        ?string $paymentMethod = null,
        ?int $bookingId = null
    ) {

        return DB::transaction(function () use (
            $userId,
            $amount,
            $remarks,
            $paymentMethod,
            $bookingId
        ) {

            if (! is_finite($amount) || $amount <= 0 || round($amount, 2) <= 0) {
                throw new \InvalidArgumentException('Wallet amount must be positive.');
            }
            $amount = round($amount, 2);
            // Lock an existing user first, including the first wallet creation.
            if (! DB::table('users')->where('id', $userId)->lockForUpdate()->first()) {
                throw new \RuntimeException('Wallet customer not found.');
            }
            $walletId = $this->getWallet($userId)->id;
            $wallet = Wallet::query()->whereKey($walletId)->lockForUpdate()->firstOrFail();
            if (! $wallet->is_active || $wallet->currency !== 'INR') {
                throw new \RuntimeException('Wallet is unavailable.');
            }

            if (round((float) $wallet->balance - (float) $wallet->hold_balance, 2) < $amount) {
                return false;
            }

            $opening = (float) $wallet->balance;
            $closing = $opening - $amount;

            $wallet->update([
                'balance' => $closing,
            ]);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'booking_id' => $bookingId,
                'transaction_id' => uniqid('DBT'),
                'type' => 'debit',
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'opening_balance' => $opening,
                'closing_balance' => $closing,
                'status' => 'success',
                'remarks' => $remarks,
            ]);
        });
    }
}