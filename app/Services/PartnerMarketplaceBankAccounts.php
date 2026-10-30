<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Immutable encrypted account versions; current means the highest owner revision. */
class PartnerMarketplaceBankAccounts
{
    public static function current(int $vendor): ?object
    {
        return DB::table('partner_market_bank_accounts')->where('vendor_id', $vendor)->orderByDesc('revision')->first();
    }

    public static function normalise(array $input, ?array $previous = null): array
    {
        $oneLine = static fn (string $v): string => trim(preg_replace('/\s+/u', ' ', $v));
        $holder = $oneLine((string) ($input['account_holder_name'] ?? ''));
        $bank = $oneLine((string) ($input['bank_name'] ?? ''));
        $ifsc = strtoupper(trim((string) ($input['ifsc'] ?? '')));
        $replace = (bool) ($input['replace_account_number'] ?? false);
        $number = $replace ? trim((string) ($input['account_number'] ?? '')) : ($previous['account_number'] ?? '');
        if ($replace && $number !== trim((string) ($input['account_number_confirmation'] ?? ''))) throw ValidationException::withMessages(['account_number_confirmation' => 'Account numbers do not match.']);
        if (mb_strlen($holder) < 2 || mb_strlen($holder) > 150) throw ValidationException::withMessages(['account_holder_name' => 'Enter the account holder name (2–150 characters).']);
        if (mb_strlen($bank) < 2 || mb_strlen($bank) > 150) throw ValidationException::withMessages(['bank_name' => 'Enter the bank name (2–150 characters).']);
        if (!preg_match('/^\d{6,20}$/', $number)) throw ValidationException::withMessages(['account_number' => 'Enter a bank account number of 6–20 digits.']);
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) throw ValidationException::withMessages(['ifsc' => 'Enter a valid 11-character IFSC format.']);
        return ['account_holder_name' => $holder, 'bank_name' => $bank, 'account_number' => $number, 'ifsc' => $ifsc];
    }

    private static function decode(object $row): array
    {
        return json_decode(Crypt::decryptString($row->encrypted_details), true, 512, JSON_THROW_ON_ERROR);
    }

    /** Caller authenticates this vendor; lock the profile to serialize first-save and edits. */
    public static function save(TransporterProfile $vendor, int $expected, array $input): object
    {
        return DB::transaction(function () use ($vendor, $expected, $input) {
            abort_unless(DB::table('fleet_transporter_profiles')->where('id', $vendor->id)->lockForUpdate()->first(), 404);
            $current = self::current((int) $vendor->id);
            $details = self::normalise($input, $current ? self::decode($current) : null);
            $json = json_encode($details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            // Keyed fingerprint avoids storing a plaintext/hash-searchable account number.
            $fingerprint = hash_hmac('sha256', $json, (string) config('app.key'));
            if ($current && hash_equals($current->details_fingerprint, $fingerprint)) return $current;
            abort_unless(($current ? (int) $current->revision : 0) === $expected, 409, 'Bank details changed in another session. Refresh and edit the latest version.');
            $id = DB::table('partner_market_bank_accounts')->insertGetId([
                'vendor_id' => $vendor->id, 'revision' => $expected + 1, 'encrypted_details' => Crypt::encryptString($json),
                'last_four' => substr($details['account_number'], -4), 'details_fingerprint' => $fingerprint,
                'changed_by_user_id' => $vendor->user_id, 'created_at' => now(),
            ]);
            return DB::table('partner_market_bank_accounts')->find($id);
        }, 3);
    }

    /** Never returns a full account number in normal owner/moderator/payment responses. */
    public static function summary(object $row): array
    {
        $details = self::decode($row);
        unset($details['account_number']);
        return $details + ['id' => $row->id, 'revision' => $row->revision, 'masked_account_number' => '•••• ' . $row->last_four,
            'last_four' => $row->last_four, 'created_at' => $row->created_at, 'bank_verified' => false];
    }

    public static function forPayout(int $vendor): object
    {
        $row = self::current($vendor);
        abort_unless($row, 422, 'Posting vendor must save bank details under Profile → Bank Account before a payout plan can be created.');
        return $row;
    }

    /** Explicit financial-admin-only reveal of a pinned immutable settlement account. */
    public static function revealForSettlement(object $plan, int $payee): array
    {
        PartnerMarketplaceAdminService::actor(true);
        $row = $plan->payout_bank_account_id ? DB::table('partner_market_bank_accounts')->find($plan->payout_bank_account_id) : null;
        abort_unless($row && (int) $row->vendor_id === $payee, 409, 'No bank account is pinned to this plan. Do not use the vendor’s latest account for an old payout.');
        return self::summary($row) + ['account_number' => self::decode($row)['account_number']];
    }
}
