<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
use App\Services\RazorpayService;
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
        return response()->json(['status' => true, 'data' => $this->summary($row)]);
    }

    /** Vendor B marks trip ended; no transfer is attempted. */
    public function end(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $row = DB::transaction(function () use ($payment, $vendor) {
            $row = DB::table('partner_market_secure_payments')->where('id', $payment)->lockForUpdate()->first();
            abort_unless($row && (int) $row->payer_vendor_id === (int) $vendor->id, 403);
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
            $row = DB::table('partner_market_secure_payments')->where('id', $payment)->lockForUpdate()->first();
            abort_unless($row && (int) $row->payee_vendor_id === (int) $vendor->id, 403);
            abort_unless($row->status === 'paid' && $row->refund_status === 'none' && $row->ended_at, 409, 'Vendor B must end a paid booking first.');
            if (!$row->completion_confirmed_at) {
                DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                    'completion_confirmed_at' => now(), 'settlement_eligible_at' => now(),
                    'payout_status' => 'eligible', 'updated_at' => now(),
                ]);
            }
            return DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        }, 3);
        return response()->json(['status' => true, 'data' => $this->summary($row)]);
    }

    /** Either vendor can cancel before end/confirmation; once requested payout is blocked. */
    public function cancel(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $row = DB::transaction(function () use ($payment, $vendor, $data) {
            $row = DB::table('partner_market_secure_payments')->where('id', $payment)->lockForUpdate()->first();
            $this->authorizeParticipant($row, (int) $vendor->id);
            abort_if($row->ended_at || $row->completion_confirmed_at || $row->settlement_eligible_at || $row->payout_status !== 'blocked', 409, 'Trip completion/settlement has started; manual dispute required.');
            if (in_array($row->status, ['cancelled', 'refund_pending', 'refunded'], true)) {
                return $row;
            }
            abort_unless(in_array($row->status, ['pending', 'paid'], true), 409, 'Cannot cancel this payment state.');
            // An order may have been captured while webhook is delayed. Never claim no refund is due.
            $needsReview = $row->status === 'pending' && (bool) $row->razorpay_order_id;
            $newStatus = $row->status === 'paid' ? 'refund_pending' : 'cancelled';
            DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                'status' => $newStatus,
                'refund_status' => $newStatus === 'refund_pending' ? 'requested' : ($needsReview ? 'review_required' : 'none'),
                'refund_request_key' => $newStatus === 'refund_pending' ? (string) Str::uuid() : null,
                'refund_requested_at' => $newStatus === 'refund_pending' ? now() : null,
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
        // Claim request exactly once. Network timeouts remain review_required, never automatically double-refunded.
        $claimed = DB::table('partner_market_secure_payments')->where('id', $row->id)
            ->where('status', 'refund_pending')->where('refund_status', 'requested')
            ->update(['refund_status' => 'review_required', 'updated_at' => now()]);
        if (!$claimed) {
            $fresh = DB::table('partner_market_secure_payments')->where('id', $row->id)->first();
            return response()->json(['status' => true, 'data' => $this->summary($fresh), 'message' => 'Refund already being processed or requires review.']);
        }
        $result = RazorpayService::refund((string) $row->razorpay_payment_id, $row->commission_paise / 100,
            'Marketplace booking cancelled', ['market_payment_id' => (string) $row->id, 'request_key' => $row->refund_request_key]);
        if (!($result['status'] ?? false) || empty($result['data']['id'])) {
            Log::warning('Marketplace refund requires reconciliation', ['ledger_id' => $row->id, 'gateway_status' => $result['http_status'] ?? null]);
            return response()->json(['status' => false, 'message' => 'Cancellation recorded. Refund initiation requires manual gateway reconciliation; do not retry payment.',
                'data' => $this->summary(DB::table('partner_market_secure_payments')->where('id', $row->id)->first())], 202);
        }
        DB::table('partner_market_secure_payments')->where('id', $row->id)
            ->where('status', 'refund_pending')->where('refund_status', 'review_required')
            ->update(['razorpay_refund_id' => $result['data']['id'], 'refund_status' => 'initiated',
                'refund_initiated_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => true, 'message' => 'Full refund initiated; bank credit timing depends on Razorpay/bank.',
            'data' => $this->summary(DB::table('partner_market_secure_payments')->where('id', $row->id)->first())]);
    }

    private function authorizeParticipant(?object $row, int $vendorId): void
    {
        abort_unless($row && in_array($vendorId, [(int) $row->payer_vendor_id, (int) $row->payee_vendor_id], true), 403);
    }

    private function summary(object $row): array
    {
        return [
            'payment_id' => $row->id, 'post_id' => $row->post_id, 'status' => $row->status,
            'commission_paise' => $row->commission_paise, 'service_fee_paise' => $row->service_fee_paise,
            'payout_paise' => $row->payout_paise, 'payout_status' => $row->payout_status,
            'refund_status' => $row->refund_status, 'razorpay_refund_id' => $row->razorpay_refund_id,
            'ended_at' => $row->ended_at, 'completion_confirmed_at' => $row->completion_confirmed_at,
            'settlement_eligible_at' => $row->settlement_eligible_at,
            'cancelled_at' => $row->cancelled_at, 'refund_initiated_at' => $row->refund_initiated_at,
        ];
    }
}
