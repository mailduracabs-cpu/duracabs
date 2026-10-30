<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Support\Facades\DB;

class PartnerMarketplaceAssignmentRequests
{
    /** Caller holds post lock and has authenticated/authorized its owner. */
    public static function create(object $post, int $peer, string $fare, string $commission, ?int $expectedFleet = null): int
    {
        abort_unless($post->kind === 'booking' && $post->pricing_mode === 'quote', 422, 'Quote Best Price booking required.');
        abort_if($peer === (int) $post->transporter_profile_id, 422, 'Cannot assign your own booking to yourself.');
        $amount = PartnerMarketplaceAdminService::paise($fare);
        $fee = PartnerMarketplaceAdminService::paise($commission);
        abort_unless($amount > 0 && $fee > 0 && $fee <= $amount, 422, 'Enter positive booking amount and commission. Commission cannot exceed fare.');
        if ($post->active_payment_id) {
            $active = DB::table('partner_market_secure_payments')->find($post->active_payment_id);
            abort_unless($active && (int) $active->payer_vendor_id === $peer && (int) $active->booking_amount_paise === $amount && (int) $active->commission_paise === $fee && ($expectedFleet === null || (int) $active->fleet_submission_id === $expectedFleet), 409, 'An existing payment request is in progress. Terms cannot change until it is confirmed failed.');
            return (int) $active->id; // Idempotent; neither terms nor deadline change.
        }
        abort_unless($post->status === 'open' && !$post->assigned_payment_id, 409, 'Booking cannot be assigned in this state.');
        $vendor = TransporterProfile::query()->find($peer);
        abort_unless($vendor && $vendor->isActive() && $vendor->isVerified() && $vendor->isVendor(), 422, 'Selected vendor is unavailable.');
        $quote = DB::table('partner_market_quotes')->where('post_id', $post->id)->where('transporter_profile_id', $peer)->first();
        $chatted = DB::table('partner_market_messages')->where('post_id', $post->id)->where(function ($q) use ($post, $peer) {
            $q->where(function ($q) use ($post, $peer) { $q->where('sender_vendor_id', $peer)->where('recipient_vendor_id', $post->transporter_profile_id); })
              ->orWhere(function ($q) use ($post, $peer) { $q->where('sender_vendor_id', $post->transporter_profile_id)->where('recipient_vendor_id', $peer); });
        })->exists();
        abort_unless($chatted || $quote, 422, 'Select a vendor from this booking conversation.');
        $fleet = PartnerMarketplaceFleet::requireFor($post, $peer);
        abort_if($expectedFleet !== null && (int) $fleet->id !== $expectedFleet, 409, 'This vendor changed their driver/taxi details. Review the latest chat card before sending a payment request.');
        $platform = intdiv($fee * 10 + 50, 100);
        $terms = ['asking_amount' => $amount / 100, 'vendor_commission' => $fee / 100,
            'service_fee' => $platform / 100, 'vendor_payout' => ($fee - $platform) / 100,
            'secure_booking' => true, 'updated_at' => now()];
        DB::table('partner_market_posts')->where('id', $post->id)->update($terms);
        $values = ['amount' => $amount / 100, 'post_revision' => $post->revision, 'status' => 'pending', 'updated_at' => now()];
        if ($quote) DB::table('partner_market_quotes')->where('id', $quote->id)->update($values);
        else {
            $id = DB::table('partner_market_quotes')->insertGetId($values + ['post_id' => $post->id, 'transporter_profile_id' => $peer, 'created_at' => now()]);
            $quote = (object) ['id' => $id, 'transporter_profile_id' => $peer];
        }
        $quote->amount = $amount / 100;
        $updated = (object) array_replace((array) $post, $terms);
        $id = PartnerMarketplaceReservations::reserve($updated, $quote);
        DB::table('partner_market_messages')->insert([
            'post_id' => $post->id, 'sender_vendor_id' => $post->transporter_profile_id, 'recipient_vendor_id' => $peer,
            'message' => 'Payment request #' . $id . ': agreed booking fare ₹' . number_format($amount / 100, 2, '.', '')
                . ', commission payable now ₹' . number_format($fee / 100, 2, '.', '')
                . '. Open Booking / Payment Request to review and pay within 2 minutes. Booking is assigned only after verified payment.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }
}
