<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
use App\Services\RazorpayService;
use App\Services\PartnerMarketplaceReservations as Reservations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Secure marketplace lifecycle only. Never modifies customer bookings. */
class PartnerMarketplaceLifecycleController extends PartnerController
{
    private function vendor(Request $request): TransporterProfile
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'vendor' && $profile->isActive(), 403, 'Verified vendor required.');
        return $profile;
    }

    public function show(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $row = DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        $this->authorizeParticipant($row, (int) $vendor->id);
        Reservations::reconcile((int) $row->post_id);
        $row = DB::table('partner_market_secure_payments')->find($payment);
        return response()->json(['status' => true, 'data' => $this->summary($row, (int) $vendor->id)]);
    }

    /** Vendor B marks trip ended; no transfer is attempted. */
    public function end(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $row = DB::transaction(function () use ($payment, $vendor) {
            $initial = DB::table('partner_market_secure_payments')->find($payment);
            abort_unless($initial, 404);
            $post = DB::table('partner_market_posts')->where('id', $initial->post_id)->lockForUpdate()->first();
            $row = DB::table('partner_market_secure_payments')->where('id', $payment)->lockForUpdate()->first();
            abort_unless($row && (int) $row->payer_vendor_id === (int) $vendor->id, 403);
            abort_if($row->admin_hold || $row->admin_settlement_id, 409, 'Admin mediation controls this settlement.');
            abort_unless($row->status === 'paid' && $row->refund_status === 'none', 409, 'Captured secure payment required.');
            if (!$row->ended_at) {
                DB::table('partner_market_secure_payments')->where('id', $payment)->update(['ended_at' => now(), 'updated_at' => now()]);
            }
            return DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        }, 3);
        return response()->json(['status' => true, 'data' => $this->summary($row)]);
    }

    /** Vendor A confirms trip completion; creates payout eligibility, not a bank transfer. */
    public function confirmCompletion(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $row = DB::transaction(function () use ($payment, $vendor) {
            $initial = DB::table('partner_market_secure_payments')->find($payment);
            abort_unless($initial, 404);
            $post = DB::table('partner_market_posts')->where('id', $initial->post_id)->lockForUpdate()->first();
            $row = DB::table('partner_market_secure_payments')->where('id', $payment)->lockForUpdate()->first();
            abort_unless($row && (int) $row->payee_vendor_id === (int) $vendor->id, 403);
            abort_if($row->admin_hold || $row->admin_settlement_id, 409, 'Admin mediation controls this settlement.');
            abort_unless($row->status === 'paid' && $row->refund_status === 'none' && $row->ended_at, 409, 'Vendor B must end a paid booking first.');
            if (!$row->completion_confirmed_at) {
                DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                    'completion_confirmed_at' => now(), 'settlement_eligible_at' => now(),
                    'payout_status' => 'eligible', 'updated_at' => now(),
                ]);
            }
            DB::table('partner_market_posts')->where('id', $post->id)->update(['status' => 'completed', 'updated_at' => now()]);
            return DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        }, 3);
        return response()->json(['status' => true, 'data' => $this->summary($row)]);
    }

    /** Paid booking cancellation refunds full commission; active payment windows cannot cancel. */
    public function cancel(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $row = DB::transaction(function () use ($payment, $vendor, $data) {
            $initial = DB::table('partner_market_secure_payments')->find($payment);
            abort_unless($initial, 404);
            $post = DB::table('partner_market_posts')->where('id', $initial->post_id)->lockForUpdate()->first();
            $row = DB::table('partner_market_secure_payments')->where('id', $payment)->lockForUpdate()->first();
            $this->authorizeParticipant($row, (int) $vendor->id);
            abort_if($post->active_payment_id || $post->status === 'payment_pending', 409, 'A commission payment is in progress. Cancellation is locked until gateway confirmation.');
            abort_if($row->admin_hold || $row->admin_settlement_id, 409, 'Admin mediation controls this settlement.');
            abort_if($row->ended_at || $row->completion_confirmed_at || $row->settlement_eligible_at || $row->payout_status !== 'blocked', 409, 'Trip completion/settlement has started; manual dispute required.');
            if (in_array($row->status, ['cancelled', 'refund_pending', 'refunded'], true)) {
                return $row;
            }
            abort_unless($row->status === 'paid' && (int) $post->assigned_payment_id === (int) $row->id, 409, 'Cannot cancel this payment state.');
            DB::table('partner_market_posts')->where('id', $post->id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                'status' => 'refund_pending', 'refund_status' => 'requested',
                'refund_request_key' => (string) Str::uuid(), 'refund_requested_at' => now(),
                'cancellation_reason' => $data['reason'] ?? null,
                'cancelled_at' => now(), 'updated_at' => now(),
            ]);
            return DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        }, 3);
        // No external side effects inside DB transaction. Initiate refund immediately when applicable.
        if ($row->status === 'refund_pending' && $row->refund_status === 'requested') {
            return $this->initiateRefund($row);
        }
        return response()->json(['status' => true, 'data' => $this->summary($row),
            'message' => $row->refund_status === 'review_required' ? 'Cancellation recorded; payment capture reconciliation required.' : 'Booking cancelled.']);
    }

    /** Only authorized participant may retry a failed initiation; processing states are not retried blindly. */
    public function retryRefund(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $row = DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        $this->authorizeParticipant($row, (int) $vendor->id);
        abort_unless($row->status === 'refund_pending' && $row->refund_status === 'requested', 409, 'Refund is not safely retryable.');
        return $this->initiateRefund($row);
    }

    private function initiateRefund(object $row)
    {
        $fresh = Reservations::refund((int) $row->id);
        return response()->json(['status' => true, 'data' => $this->summary($fresh),
            'message' => $fresh->refund_status === 'review_required' ? 'Booking cancelled. Refund requires gateway reconciliation.' : 'Booking cancelled. Full commission refund is processing.']);
    }

    private function authorizeParticipant(?object $row, int $vendorId): void
    {
        abort_unless($row && in_array($vendorId, [(int) $row->payer_vendor_id, (int) $row->payee_vendor_id], true), 403);
    }

    private function summary(object $row, ?int $viewer = null): array
    {
        $decision = $row->admin_settlement_id ? DB::table('partner_market_admin_settlements')->where('id', $row->admin_settlement_id)
            ->select('id', 'payer_refund_paise', 'payee_payout_paise', 'platform_fee_paise', 'penalty_paise', 'penalty_against', 'reason', 'status', 'refund_status', 'payout_status', 'razorpay_refund_id', 'payout_reference', 'payout_recorded_at', 'payout_bank_account_id')->first() : null;
        $payoutBank = null;
        if ($decision?->payout_bank_account_id && $viewer === (int) $row->payee_vendor_id) {
            $bank = DB::table('partner_market_bank_accounts')->find($decision->payout_bank_account_id);
            if ($bank && (int) $bank->vendor_id === (int) $row->payee_vendor_id) $payoutBank = \App\Services\PartnerMarketplaceBankAccounts::summary($bank);
        }
        $fleet = $row->fleet_submission_id ? DB::table('partner_market_fleet_submissions')->find($row->fleet_submission_id) : null;
        return [
            'payout_bank_account' => $payoutBank, 'refund_destination' => 'original_payment_source',
            'fleet_details' => $fleet ? \App\Services\PartnerMarketplaceFleet::summary($fleet) : null,
            'reservation' => Reservations::snapshot(DB::table('partner_market_posts')->find($row->post_id), $viewer ?? (int) $row->payer_vendor_id),
            'admin_hold' => (bool) $row->admin_hold, 'admin_settlement' => $decision,
            'admin_hold_reason' => $row->admin_hold ? DB::table('partner_market_admin_audits')->where('payment_id', $row->id)->whereIn('action', ['hold', 'allocation_planned'])->orderByDesc('id')->value('reason') : null,
            'booking_amount_paise' => $row->booking_amount_paise,
            'booking_snapshot' => $row->booking_snapshot ? json_decode($row->booking_snapshot, true) : null,
            'payment_id' => $row->id, 'post_id' => $row->post_id, 'status' => $row->status,
            'payer_vendor_id' => $row->payer_vendor_id, 'payee_vendor_id' => $row->payee_vendor_id,
            'commission_paise' => $row->commission_paise, 'service_fee_paise' => $row->service_fee_paise,
            'payout_paise' => $row->payout_paise, 'payout_status' => $row->payout_status,
            'refund_status' => $row->refund_status, 'razorpay_refund_id' => $row->razorpay_refund_id,
            'ended_at' => $row->ended_at, 'completion_confirmed_at' => $row->completion_confirmed_at,
            'settlement_eligible_at' => $row->settlement_eligible_at,
            'cancelled_at' => $row->cancelled_at, 'refund_initiated_at' => $row->refund_initiated_at,
        ];
    }
}
