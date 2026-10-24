<?php
namespace App\Services;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Repositories\WalletRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
class CustomerReferralService
{
    private function ready(): void {
        if (! config('customer_referrals.enabled') || ! Schema::hasTable('customer_referrals')) throw new RuntimeException('Refer & Earn is not available yet.');
    }
    private function hasBookings(int $userId): bool {
        foreach (['orders' => 'user_id', 'self_drive_bookings' => 'customer_id'] as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && DB::table($table)->where($column, $userId)->exists()) return true;
        }
        return false;
    }
    public function canApply(User $user): bool {
        return ! DB::table('customer_referrals')->where('customer_id', $user->id)->exists() && ! $this->hasBookings((int) $user->id);
    }
    public function summary(User $user): array {
        $this->ready();
        $code = DB::transaction(function () use ($user) {
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            $row = DB::table('customer_referral_codes')->where('user_id', $user->id)->first();
            if ($row) return $row->code;
            do { $code = 'DURA' . Str::upper(Str::random(8)); } while (DB::table('customer_referral_codes')->where('code', $code)->exists());
            DB::table('customer_referral_codes')->insert(['user_id' => $user->id, 'code' => $code, 'created_at' => now(), 'updated_at' => now()]);
            return $code;
        }, 3);
        $rewards = DB::table('customer_referral_rewards')->where('referrer_id', $user->id);
        return ['code' => $code, 'reward_amount' => 100, 'can_apply_code' => $this->canApply($user),
            'referred_customers' => DB::table('customer_referrals')->where('referrer_id', $user->id)->count(),
            'earned_amount' => (float) (clone $rewards)->where('status', 'credited')->sum('amount'),
            'history' => (clone $rewards)->latest('id')->limit(50)->get(['booking_source', 'booking_id', 'amount', 'status', 'created_at']),
            'share_text' => 'Book with DuraCabs! Use my referral code ' . $code . ' in Profile > Refer & Earn before your first booking. Download: ' . config('customer_referrals.store_url'),
            'terms' => 'Earn ₹100 on every fully paid and completed booking by a new customer you refer. New customers must apply your code before any booking. Cancelled or refunded trips do not qualify. Rewards are wallet credit; refunded rewards are reversed.'];
    }
    public function apply(User $user, string $code): void {
        $this->ready();
        DB::transaction(function () use ($user, $code) {
            DB::table('customer_referral_program')->where('id', 1)->lockForUpdate()->first();
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if (! $this->canApply($user)) throw new RuntimeException('Referral code can only be applied once by a new customer before their first booking.');
            $referrer = DB::table('customer_referral_codes')->where('code', strtoupper(trim($code)))->first();
            if (! $referrer || ! DB::table('users')->where('id', $referrer->user_id)->exists()) throw new RuntimeException('Invalid referral code.');
            if ((int) $referrer->user_id === (int) $user->id) throw new RuntimeException('You cannot use your own referral code.');
            // Prevent an existing referral chain from looping back to this customer.
            $ancestor = (int) $referrer->user_id; $seen = [];
            while ($ancestor > 0) {
                if ($ancestor === (int) $user->id || isset($seen[$ancestor])) throw new RuntimeException('This referral would create a circular referral.');
                $seen[$ancestor] = true;
                $ancestor = (int) DB::table('customer_referrals')->where('customer_id', $ancestor)->value('referrer_id');
            }
            DB::table('customer_referrals')->insert(['referrer_id' => $referrer->user_id, 'customer_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        }, 3);
    }
    public function syncBooking(string $source, int $id): void {
        if (! Schema::hasTable('customer_referrals')) return;
        $table = match ($source) { 'order' => 'orders', 'self_drive' => 'self_drive_bookings', default => throw new RuntimeException('Invalid booking source.') };
        if (! Schema::hasTable($table)) return;
        DB::transaction(function () use ($source, $id, $table) {
            $booking = DB::table($table)->where('id', $id)->lockForUpdate()->first();
            if (! $booking) return;
            $customer = (int) ($source === 'self_drive' ? ($booking->customer_id ?? 0) : ($booking->user_id ?? 0));
            $referral = DB::table('customer_referrals')->where('customer_id', $customer)->lockForUpdate()->first();
            if (! $referral || ! $booking->created_at || Carbon::parse($booking->created_at)->lt(Carbon::parse($referral->created_at))) return;
            $reward = DB::table('customer_referral_rewards')->where('booking_source', $source)->where('booking_id', $id)->first();
            $state = CustomerReferralRules::bookingState($booking);
            if ($reward && $state === 'reverse' && $reward->status === 'credited') {
                // A spent reward becomes wallet debt, recovered from future credits. Never silently skip reversal.
                DB::table('users')->where('id', $referral->referrer_id)->lockForUpdate()->first();
                $repo = app(WalletRepository::class); $walletId = $repo->getWallet((int) $referral->referrer_id)->id;
                $wallet = \App\Models\Wallet::query()->whereKey($walletId)->lockForUpdate()->firstOrFail();
                $opening = (float) $wallet->balance; $closing = round($opening - (float) $reward->amount, 2);
                $wallet->update(['balance' => $closing]);
                $tx = WalletTransaction::create(['wallet_id' => $wallet->id, 'user_id' => $referral->referrer_id,
                    'booking_id' => $id, 'type' => 'debit', 'amount' => $reward->amount, 'payment_method' => 'referral_reversal',
                    'opening_balance' => $opening, 'closing_balance' => $closing, 'status' => 'success',
                    'reference' => 'referral_reversal:' . $source . ':' . $id, 'remarks' => 'Referral reward reversed for cancelled/refunded booking', 'meta' => ['booking_source' => $source]]);
                DB::table('customer_referral_rewards')->where('id', $reward->id)->update(['status' => 'reversed', 'reversal_transaction_id' => $tx->id, 'updated_at' => now()]);
            }
            if ($reward || $state !== 'credit' || ! config('customer_referrals.enabled')) return;
            $tx = app(WalletRepository::class)->credit((int) $referral->referrer_id, 100, 'Referral reward for completed booking', 'referral', $id);
            $tx->update(['reference' => 'referral_reward:' . $source . ':' . $id, 'meta' => ['booking_source' => $source, 'referred_customer_id' => $customer]]);
            DB::table('customer_referral_rewards')->insert(['referral_id' => $referral->id, 'referrer_id' => $referral->referrer_id,
                'booking_source' => $source, 'booking_id' => $id, 'amount' => 100, 'wallet_transaction_id' => $tx->id,
                'status' => 'credited', 'created_at' => now(), 'updated_at' => now()]);
        }, 3);
    }
}
