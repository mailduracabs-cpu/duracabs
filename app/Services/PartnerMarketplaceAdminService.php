<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Every entry point rechecks the real admin session; all sums use integer paise. */
class PartnerMarketplaceAdminService
{
    public static function actor(bool $financial = false): User
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user instanceof User && $user->canUseAdminLogin(), 403, 'Active admin-panel account required.');
        abort_unless(!$financial || $user->isAdmin(), 403, 'Only the Admin role can adjust money.');
        return $user;
    }

    public static function paise(string $amount): int
    {
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', trim($amount))) {
            throw ValidationException::withMessages(['amount' => 'Use a non-negative rupee amount with at most 2 decimals.']);
        }
        [$whole, $fraction] = array_pad(explode('.', trim($amount), 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function allocation(int $held, string $refund, string $payout, string $fee, string $against, string $penalty): array
    {
        $r = self::paise($refund); $p = self::paise($payout); $f = self::paise($fee); $n = self::paise($penalty);
        if ($r + $p + $f !== $held) throw ValidationException::withMessages(['allocation' => 'Receiver refund + poster payout + platform fee must equal captured commission.']);
        if (!in_array($against, ['none', 'payer', 'payee'], true) || ($against === 'none' && $n !== 0)) {
            throw ValidationException::withMessages(['penalty' => 'Choose the penalized party for a non-zero penalty.']);
        }
        if (($against === 'payer' && $n > $r) || ($against === 'payee' && $n > $p)) {
            throw ValidationException::withMessages(['penalty' => 'Penalty cannot exceed that party’s allocation from the held commission.']);
        }
        if ($against === 'payer') { $r -= $n; $p += $n; }
        if ($against === 'payee') { $p -= $n; $r += $n; }
        return ['payer_refund_paise' => $r, 'payee_payout_paise' => $p, 'platform_fee_paise' => $f, 'penalty_paise' => $n, 'penalty_against' => $against];
    }

    private static function reason(string $value): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 1000) throw ValidationException::withMessages(['reason' => 'Enter a reason within 1000 characters.']);
        return $value;
    }

    private static function locked(int $payment, int $revision): object
    {
        $row = DB::table('partner_market_secure_payments')->where('id', $payment)->lockForUpdate()->first();
        abort_unless($row, 404);
        abort_unless((int) $row->admin_revision === $revision, 409, 'Another admin changed this payment. Refresh first.');
        return $row;
    }

    private static function audit(object $row, string $action, string $reason, array $details = [], ?User $actor = null): void
    {
        DB::table('partner_market_admin_audits')->insert([
            'post_id' => $row->post_id, 'payment_id' => $row->id, 'actor_user_id' => $actor?->id,
            'actor_name' => $actor ? mb_substr((string) $actor->name, 0, 150) : 'Payment gateway',
            'action' => $action, 'reason' => $reason, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }

    public static function hold(int $payment, int $revision, string $reason): void
    {
        $actor = self::actor(true); $reason = self::reason($reason);
        DB::transaction(function () use ($payment, $revision, $reason, $actor): void {
            $row = self::locked($payment, $revision);
            abort_unless(in_array($row->status, ['pending', 'paid'], true) && !in_array($row->payout_status, ['paid', 'processing'], true), 409, 'Payment is already refunded, cancelled or in bank settlement.');
            abort_unless($row->admin_settlement_id || $row->refund_status === 'none', 409, 'An existing refund has already started.');
            DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                'admin_hold' => true, 'hold_previous_payout_status' => $row->admin_hold ? $row->hold_previous_payout_status : $row->payout_status,
                'payout_status' => 'blocked', 'admin_revision' => $revision + 1, 'updated_at' => now(),
            ]);
            if ($row->admin_settlement_id) DB::table('partner_market_admin_settlements')->where('id', $row->admin_settlement_id)->update(['payout_status' => 'blocked', 'updated_at' => now()]);
            self::audit($row, 'hold', $reason, [], $actor);
        }, 3);
    }

    public static function plan(int $payment, int $revision, array $input): void
    {
        $actor = self::actor(true); $reason = self::reason((string) ($input['reason'] ?? ''));
        DB::transaction(function () use ($payment, $revision, $input, $reason, $actor): void {
            $row = self::locked($payment, $revision);
            abort_unless($row->status === 'paid' && $row->refund_status === 'none' && !in_array($row->payout_status, ['paid', 'processing'], true), 409, 'Only captured, unrefunded and unsettled commission can be allocated.');
            if ($row->admin_settlement_id) {
                $prior = DB::table('partner_market_admin_settlements')->where('id', $row->admin_settlement_id)->lockForUpdate()->first();
                abort_unless($prior && $prior->status === 'planned' && in_array($prior->refund_status, ['not_required', 'planned'], true), 409, 'An approved/executed plan is immutable.');
                DB::table('partner_market_admin_settlements')->where('id', $prior->id)->update(['status' => 'superseded', 'updated_at' => now()]);
            }
            $values = self::allocation((int) $row->commission_paise, (string) ($input['refund'] ?? ''), (string) ($input['payout'] ?? ''),
                (string) ($input['fee'] ?? ''), (string) ($input['penalty_against'] ?? 'none'), (string) ($input['penalty'] ?? '0'));
            $bank = $values['payee_payout_paise'] > 0 ? PartnerMarketplaceBankAccounts::forPayout((int) $row->payee_vendor_id) : null;
            $id = DB::table('partner_market_admin_settlements')->insertGetId($values + [
                'payout_bank_account_id' => $bank?->id,
                'payment_id' => $payment, 'reason' => $reason, 'created_by' => $actor->id, 'status' => 'planned',
                'refund_status' => $values['payer_refund_paise'] > 0 ? 'planned' : 'not_required',
                'payout_status' => 'blocked', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                'admin_hold' => true, 'admin_settlement_id' => $id, 'payout_status' => 'blocked',
                'hold_previous_payout_status' => $row->admin_hold ? $row->hold_previous_payout_status : $row->payout_status,
                'admin_revision' => $revision + 1, 'updated_at' => now(),
            ]);
            self::audit($row, 'allocation_planned', $reason, $values + ['settlement_id' => $id, 'bank_account_id' => $bank?->id], $actor);
        }, 3);
    }

    public static function release(int $payment, int $revision, string $reason): void
    {
        $actor = self::actor(true); $reason = self::reason($reason);
        DB::transaction(function () use ($payment, $revision, $reason, $actor): void {
            $row = self::locked($payment, $revision);
            abort_unless($row->admin_hold, 409, 'Payment is not on admin hold.');
            $payout = $row->hold_previous_payout_status === 'eligible' ? 'eligible' : 'blocked';
            $refund = $row->refund_status;
            if ($row->admin_settlement_id) {
                $plan = DB::table('partner_market_admin_settlements')->where('id', $row->admin_settlement_id)->lockForUpdate()->first();
                abort_unless($plan && in_array($plan->status, ['planned', 'approved'], true), 409);
                $ready = in_array($plan->refund_status, ['processed', 'not_required'], true);
                $payout = $ready ? ((int) $plan->payee_payout_paise > 0 ? 'eligible' : 'not_required') : 'blocked';
                $refund = $plan->refund_status === 'processed' ? 'admin_processed' : ((int) $plan->payer_refund_paise > 0 ? 'admin_requested' : 'admin_not_required');
                DB::table('partner_market_admin_settlements')->where('id', $plan->id)->update(['status' => 'approved', 'payout_status' => $payout, 'updated_at' => now()]);
            }
            DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                'admin_hold' => false, 'payout_status' => $payout, 'refund_status' => $refund, 'admin_revision' => $revision + 1, 'updated_at' => now(),
            ]);
            self::audit($row, 'hold_released', $reason, ['settlement_id' => $row->admin_settlement_id], $actor);
        }, 3);
    }

    public static function issueRefund(int $payment, int $revision, string $reason): void
    {
        $actor = self::actor(true); $reason = self::reason($reason);
        $plan = DB::transaction(function () use ($payment, $revision, $reason, $actor) {
            $row = self::locked($payment, $revision);
            abort_unless(!$row->admin_hold && $row->status === 'paid' && $row->admin_settlement_id, 409, 'Release the approved settlement hold first.');
            $plan = DB::table('partner_market_admin_settlements')->where('id', $row->admin_settlement_id)->lockForUpdate()->first();
            abort_unless($plan && $plan->status === 'approved' && $plan->refund_status === 'planned' && (int) $plan->payer_refund_paise > 0, 409, 'Refund already started or not required.');
            $key = (string) Str::uuid();
            DB::table('partner_market_admin_settlements')->where('id', $plan->id)->update(['refund_status' => 'review_required', 'refund_request_key' => $key, 'updated_at' => now()]);
            DB::table('partner_market_secure_payments')->where('id', $payment)->update(['admin_revision' => $revision + 1, 'updated_at' => now()]);
            self::audit($row, 'refund_claimed', $reason, ['settlement_id' => $plan->id, 'amount_paise' => $plan->payer_refund_paise, 'request_key' => $key], $actor);
            return (object) ['id' => $plan->id, 'payment_id' => $payment, 'gateway_payment_id' => $row->razorpay_payment_id, 'amount' => $plan->payer_refund_paise, 'key' => $key];
        }, 3);
        // Exactly one outbound POST. A timeout stays review_required; never retry blindly.
        $result = RazorpayService::refund((string) $plan->gateway_payment_id, $plan->amount / 100, $reason,
            ['market_admin_settlement_id' => (string) $plan->id, 'request_key' => $plan->key]);
        if (!($result['status'] ?? false) || empty($result['data']['id'])) {
            throw ValidationException::withMessages(['refund' => 'Refund outcome is uncertain. Reconcile the gateway refund ID; do not issue a second refund.']);
        }
        self::applyRefund($plan->id, $result['data']);
        // GET-only recovery for a fast webhook that preceded storage of the refund ID.
        $fresh = RazorpayService::fetchRefund((string) $result['data']['id']);
        if ($fresh['status'] ?? false) self::applyRefund($plan->id, $fresh['data']);
    }

    public static function reconcile(int $payment, int $revision, string $refundId, string $reason): void
    {
        $actor = self::actor(true); $reason = self::reason($reason);
        abort_unless(preg_match('/^rfnd_[A-Za-z0-9]+$/', trim($refundId)), 422, 'Enter a valid Razorpay refund ID.');
        $snapshot = DB::transaction(function () use ($payment, $revision) {
            return self::locked($payment, $revision);
        });
        $result = RazorpayService::fetchRefund(trim($refundId));
        abort_unless($result['status'] ?? false, 503, 'Could not retrieve refund. No new refund was issued.');
        if ($snapshot->admin_settlement_id) {
            self::applyRefund((int) $snapshot->admin_settlement_id, $result['data']);
        } else {
            $entity = $result['data'];
            DB::transaction(function () use ($payment, $revision, $entity): void {
                $row = self::locked($payment, $revision);
                abort_unless(!$row->admin_settlement_id && in_array($row->status, ['refund_pending', 'refunded'], true), 409, 'No legacy cancellation refund is pending.');
                abort_unless(($entity['payment_id'] ?? '') === $row->razorpay_payment_id && (int) ($entity['amount'] ?? -1) === (int) $row->commission_paise && !empty($entity['id']), 422, 'Gateway refund does not match the cancelled payment.');
                abort_if($row->razorpay_refund_id && $row->razorpay_refund_id !== $entity['id'], 409, 'A different refund is already linked.');
                $processed = ($entity['status'] ?? '') === 'processed';
                abort_if($row->status === 'refunded' && !$processed, 409, 'Do not regress a processed refund.');
                DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                    'razorpay_refund_id' => $entity['id'], 'status' => $processed ? 'refunded' : 'refund_pending',
                    'refund_status' => $processed ? 'processed' : (($entity['status'] ?? '') === 'failed' ? 'review_required' : 'initiated'),
                    'refunded_at' => $processed ? now() : null, 'admin_revision' => $revision + 1, 'updated_at' => now(),
                ]);
            }, 3);
        }
        $row = DB::table('partner_market_secure_payments')->find($payment);
        self::audit($row, 'refund_reconciled', $reason, ['refund_id' => trim($refundId)], $actor);
    }

    public static function reconcileLateCapture(int $payment, int $revision, string $reason): void
    {
        $actor = self::actor(true); $reason = self::reason($reason);
        $snapshot = DB::table('partner_market_secure_payments')->find($payment);
        abort_unless($snapshot && $snapshot->status === 'cancelled' && $snapshot->refund_status === 'review_required' && $snapshot->razorpay_payment_id && $snapshot->razorpay_order_id, 409, 'No recorded late capture awaits reconciliation.');
        $paymentResult = RazorpayService::fetchPayment($snapshot->razorpay_payment_id);
        $orderResult = RazorpayService::fetchOrder($snapshot->razorpay_order_id);
        abort_unless(($paymentResult['status'] ?? false) && ($orderResult['status'] ?? false), 503, 'Gateway capture verification unavailable. No funds were moved.');
        $p = $paymentResult['data']; $o = $orderResult['data'];
        DB::transaction(function () use ($payment, $revision, $reason, $actor, $p, $o): void {
            $row = self::locked($payment, $revision);
            abort_unless($row->status === 'cancelled' && $row->refund_status === 'review_required' && !$row->admin_settlement_id, 409);
            abort_unless(($p['status'] ?? '') === 'captured' && ($p['id'] ?? '') === $row->razorpay_payment_id
                && ($p['order_id'] ?? '') === $row->razorpay_order_id && ($p['currency'] ?? '') === 'INR'
                && (int) ($p['amount'] ?? -1) === (int) $row->commission_paise
                && ($o['id'] ?? '') === $row->razorpay_order_id && ($o['currency'] ?? '') === 'INR'
                && (int) ($o['amount'] ?? -1) === (int) $row->commission_paise, 422, 'Captured order/currency/amount mismatch.');
            DB::table('partner_market_secure_payments')->where('id', $payment)->update([
                'status' => 'paid', 'refund_status' => 'none', 'admin_hold' => true, 'payout_status' => 'blocked',
                'paid_at' => $row->paid_at ?: now(), 'admin_revision' => $revision + 1, 'updated_at' => now(),
            ]);
            self::audit($row, 'late_capture_reconciled_on_hold', $reason, ['payment_id' => $row->razorpay_payment_id], $actor);
        }, 3);
    }

    private static function applyRefund(int $id, array $entity): void
    {
        $preview = DB::table('partner_market_admin_settlements')->find($id);
        abort_unless($preview, 404);
        DB::transaction(function () use ($id, $entity, $preview): void {
            $row = DB::table('partner_market_secure_payments')->where('id', $preview->payment_id)->lockForUpdate()->first();
            $plan = DB::table('partner_market_admin_settlements')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row && $plan && (int) $row->admin_settlement_id === $id && $plan->status === 'approved', 409);
            abort_unless(($entity['payment_id'] ?? '') === $row->razorpay_payment_id
                && (int) ($entity['amount'] ?? -1) === (int) $plan->payer_refund_paise && !empty($entity['id']), 422, 'Refund ID/payment/amount do not match this decision.');
            abort_if($plan->razorpay_refund_id && $plan->razorpay_refund_id !== $entity['id'], 409, 'A different refund is already linked.');
            abort_unless(in_array($plan->refund_status, ['review_required', 'initiated', 'processed'], true), 409, 'Refund has not been authorized for execution.');
            $processed = ($entity['status'] ?? '') === 'processed';
            if ($plan->refund_status === 'processed') return;
            $refundState = $processed ? 'processed' : (($entity['status'] ?? '') === 'failed' ? 'review_required' : 'initiated');
            $payout = !$row->admin_hold && $processed ? ((int) $plan->payee_payout_paise > 0 ? 'eligible' : 'not_required') : 'blocked';
            if ($plan->payout_status === 'paid') $payout = 'paid';
            DB::table('partner_market_admin_settlements')->where('id', $id)->update([
                'razorpay_refund_id' => $entity['id'], 'refund_status' => $refundState, 'payout_status' => $payout, 'updated_at' => now(),
            ]);
            DB::table('partner_market_secure_payments')->where('id', $row->id)->update([
                'refund_status' => $processed ? 'admin_processed' : 'admin_requested', 'payout_status' => $payout,
                'admin_revision' => (int) $row->admin_revision + 1, 'updated_at' => now(),
            ]);
            self::audit($row, 'refund_' . $refundState, 'Verified gateway refund state.', ['refund_id' => $entity['id'], 'amount_paise' => $entity['amount']]);
        }, 3);
    }

    public static function handleRefundEvent(string $event, array $entity): bool
    {
        $plan = DB::table('partner_market_admin_settlements')->where('razorpay_refund_id', $entity['id'] ?? '')->first();
        if (!$plan) return false;
        $entity['status'] = $event === 'refund.processed' ? 'processed' : 'failed';
        self::applyRefund((int) $plan->id, $entity);
        return true;
    }

    /** Legacy unpaid plans may explicitly bind once; an existing destination is never overwritten. */
    public static function pinLegacyBank(int $payment, int $revision, string $reason): void
    {
        $actor = self::actor(true); $reason = self::reason($reason);
        DB::transaction(function () use ($payment, $revision, $reason, $actor): void {
            $row = self::locked($payment, $revision);
            abort_unless($row->status === 'paid' && $row->admin_settlement_id && !in_array($row->payout_status, ['paid', 'processing'], true), 409, 'Only an unpaid settlement can bind an account.');
            $plan = DB::table('partner_market_admin_settlements')->where('id', $row->admin_settlement_id)->lockForUpdate()->first();
            abort_unless($plan && !$plan->payout_bank_account_id && (int) $plan->payee_payout_paise > 0 && in_array($plan->status, ['planned', 'approved'], true) && !in_array($plan->payout_status, ['paid', 'processing'], true), 409, 'This plan already has an account or cannot be changed.');
            $bank = PartnerMarketplaceBankAccounts::forPayout((int) $row->payee_vendor_id);
            DB::table('partner_market_admin_settlements')->where('id', $plan->id)->update(['payout_bank_account_id' => $bank->id, 'updated_at' => now()]);
            DB::table('partner_market_secure_payments')->where('id', $payment)->update(['admin_revision' => $revision + 1, 'updated_at' => now()]);
            self::audit($row, 'legacy_payout_bank_pinned', $reason, ['bank_account_id' => $bank->id, 'bank_revision' => $bank->revision, 'bank_last_four' => $bank->last_four], $actor);
        }, 3);
    }

    public static function recordPayout(int $payment, int $revision, string $reference, string $reason): void
    {
        $actor = self::actor(true); $reason = self::reason($reason); $reference = trim($reference);
        abort_unless($reference !== '' && mb_strlen($reference) <= 150, 422, 'A completed bank-transfer reference is required.');
        DB::transaction(function () use ($payment, $revision, $reference, $reason, $actor): void {
            $row = self::locked($payment, $revision);
            abort_unless(!$row->admin_hold && $row->status === 'paid' && $row->admin_settlement_id && $row->payout_status === 'eligible', 409, 'Payout is not eligible or is on hold.');
            $plan = DB::table('partner_market_admin_settlements')->where('id', $row->admin_settlement_id)->lockForUpdate()->first();
            abort_unless($plan && $plan->status === 'approved' && in_array($plan->refund_status, ['not_required', 'processed'], true) && $plan->payout_status === 'eligible', 409, 'Refund must finish before payout is recorded.');
            $bank = $plan->payout_bank_account_id ? DB::table('partner_market_bank_accounts')->find($plan->payout_bank_account_id) : null;
            abort_unless($bank && (int) $bank->vendor_id === (int) $row->payee_vendor_id, 409, 'This plan has no pinned payout bank account. Reconcile its destination with admin before recording a transfer.');
            DB::table('partner_market_admin_settlements')->where('id', $plan->id)->update(['payout_status' => 'paid', 'payout_reference' => $reference, 'payout_recorded_at' => now(), 'updated_at' => now()]);
            DB::table('partner_market_secure_payments')->where('id', $payment)->update(['payout_status' => 'paid', 'admin_revision' => $revision + 1, 'updated_at' => now()]);
            self::audit($row, 'manual_bank_payout_recorded', $reason, ['reference' => $reference, 'amount_paise' => $plan->payee_payout_paise, 'bank_account_id' => $bank->id, 'bank_revision' => $bank->revision, 'bank_last_four' => $bank->last_four], $actor);
        }, 3);
    }

    public static function reply(int $post, int $peer, string $message): void
    {
        $actor = self::actor(); $message = trim($message);
        abort_unless($message !== '' && mb_strlen($message) <= 2000, 422, 'Enter a reply within 2000 characters.');
        $listing = DB::table('partner_market_posts')->find($post);
        abort_unless($listing && (int) $listing->transporter_profile_id !== $peer, 422);
        $participant = DB::table('partner_market_quotes')->where('post_id', $post)->where('transporter_profile_id', $peer)->exists()
            || DB::table('partner_market_messages')->where('post_id', $post)->where(function ($q) use ($peer) { $q->where('sender_vendor_id', $peer)->orWhere('recipient_vendor_id', $peer); })->exists();
        abort_unless($participant, 403, 'Choose a vendor already participating in this booking.');
        DB::transaction(function () use ($post, $peer, $message, $actor, $listing): void {
            $id = DB::table('partner_market_admin_messages')->insertGetId(['post_id' => $post, 'peer_vendor_id' => $peer,
                'admin_user_id' => $actor->id, 'admin_name' => mb_substr((string) $actor->name, 0, 150), 'message' => $message, 'created_at' => now()]);
            self::audit((object) ['post_id' => $post, 'id' => null], 'admin_chat_reply', 'Support reply visible to both vendors.', ['message_id' => $id, 'peer_vendor_id' => $peer], $actor);
        });
    }
}
