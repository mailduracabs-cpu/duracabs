<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** One post row lock is the serialization point for reserve/edit/cancel/capture. */
class PartnerMarketplaceReservations
{
    public const WINDOW_SECONDS = 120;

    /** Caller MUST hold the post's FOR UPDATE lock. No gateway calls in this transaction. */
    public static function reserve(object $post, object $quote): int
    {
        if ($post->active_payment_id) {
            $active = DB::table('partner_market_secure_payments')->find($post->active_payment_id);
            abort_unless($active && (int) $active->payer_vendor_id === (int) $quote->transporter_profile_id, 409, 'Another vendor is paying. Wait for the countdown and gateway confirmation.');
            return (int) $active->id; // Repeated clicks never extend the deadline.
        }
        abort_unless($post->status === 'open' && !$post->assigned_payment_id, 409, 'Booking is no longer available.');
        $fleet = PartnerMarketplaceFleet::requireFor($post, (int) $quote->transporter_profile_id);
        $commission = (int) round((float) $post->vendor_commission * 100);
        abort_unless($commission > 0, 422, 'Commission must be positive.');
        $fee = intdiv($commission * 10 + 50, 100);
        $id = DB::table('partner_market_secure_payments')->insertGetId([
            'fleet_submission_id' => $fleet->id,
            'booking_amount_paise' => isset($quote->amount) ? (int) round((float) $quote->amount * 100) : null,
            'booking_snapshot' => json_encode(array_replace((array) $post, ['status' => 'payment_pending', 'asking_amount' => $quote->amount ?? $post->asking_amount ?? null]), JSON_THROW_ON_ERROR),
            'post_id' => $post->id, 'quote_id' => $quote->id, 'payer_vendor_id' => $quote->transporter_profile_id,
            'payee_vendor_id' => $post->transporter_profile_id, 'commission_paise' => $commission,
            'service_fee_paise' => $fee, 'payout_paise' => $commission - $fee, 'status' => 'pending',
            'reservation_state' => 'reserved', 'reserved_until' => now()->addSeconds(self::WINDOW_SECONDS),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('partner_market_posts')->where('id', $post->id)->update(['status' => 'payment_pending', 'active_payment_id' => $id, 'updated_at' => now()]);
        DB::table('partner_market_quotes')->where('id', $quote->id)->update(['status' => 'reserved', 'updated_at' => now()]);
        return $id;
    }

    public static function checkoutAllowed(object $row, object $post): bool
    {
        return $post->status === 'payment_pending' && (int) $post->active_payment_id === (int) $row->id
            && $row->status === 'pending' && $row->reservation_state === 'reserved'
            && $row->reserved_until && Carbon::parse($row->reserved_until)->isFuture()
            && !$row->admin_hold && !$row->admin_settlement_id;
    }

    public static function snapshot(object $post, int $viewer): array
    {
        $row = $post->active_payment_id ? DB::table('partner_market_secure_payments')->find($post->active_payment_id) : null;
        $seconds = $row?->reserved_until ? max(0, Carbon::parse($row->reserved_until)->getTimestamp() - now()->getTimestamp()) : 0;
        $mine = $row && (int) $row->payer_vendor_id === $viewer;
        return ['booking_status' => $post->status, 'busy' => (bool) $row, 'is_payer' => (bool) $mine,
            'payment_id' => $mine ? $row->id : null, 'remaining_seconds' => $seconds,
            'state' => !$row ? 'available' : ($seconds > 0 && $row->reservation_state === 'reserved' ? 'reserved' : 'checking'),
            'reserved_until' => $row?->reserved_until, 'server_time' => now()->toIso8601String()];
    }

    /** Expiry is not proof of failure. Uncertain/authorized attempts retain the slot. */
    public static function reconcile(int $postId): void
    {
        $claim = DB::transaction(function () use ($postId) {
            $post = DB::table('partner_market_posts')->where('id', $postId)->lockForUpdate()->first();
            if (!$post?->active_payment_id) return null;
            $row = DB::table('partner_market_secure_payments')->where('id', $post->active_payment_id)->lockForUpdate()->first();
            if (!$row || $row->status !== 'pending' || !$row->reserved_until || Carbon::parse($row->reserved_until)->isFuture()) return null;
            // A creation request may still be saving an order; never race its response.
            if ($row->order_creation_started_at && Carbon::parse($row->order_creation_started_at)->gt(now()->subSeconds(90))) return null;
            if ($row->gateway_check_started_at && Carbon::parse($row->gateway_check_started_at)->gt(now()->subSeconds(90))) return null;
            if ($row->gateway_checked_at && Carbon::parse($row->gateway_checked_at)->gt(now()->subSeconds(5))) return null;
            $token = (string) Str::uuid();
            DB::table('partner_market_secure_payments')->where('id', $row->id)->update([
                'reservation_state' => 'checking', 'gateway_check_token' => $token, 'gateway_check_started_at' => now(), 'updated_at' => now(),
            ]);
            $row->gateway_check_token = $token;
            return $row;
        }, 3);
        if (!$claim) return;
        $safe = !$claim->razorpay_order_id;
        if ($claim->razorpay_order_id) {
            $order = RazorpayService::fetchOrder($claim->razorpay_order_id);
            $attempts = RazorpayService::fetchOrderPayments($claim->razorpay_order_id);
            if (($order['status'] ?? false) && ($attempts['status'] ?? false)) {
                $o = $order['data']; $items = $attempts['data']['items'] ?? null;
                if (is_array($items) && ($o['id'] ?? '') === $claim->razorpay_order_id
                    && (int) ($o['amount'] ?? -1) === (int) $claim->commission_paise && ($o['currency'] ?? '') === 'INR') {
                    foreach ($items as $p) {
                        if (($p['status'] ?? '') === 'captured' && !empty($p['id'])) {
                            self::capture((int) $claim->id, $claim->razorpay_order_id, $p['id']);
                            return;
                        }
                    }
                    $safe = in_array($o['status'] ?? '', ['created', 'attempted'], true) && (int) ($o['amount_paid'] ?? -1) === 0
                        && (int) ($attempts['data']['count'] ?? -1) === count($items);
                    foreach ($items as $p) {
                        if (($p['status'] ?? '') !== 'failed' || ($p['order_id'] ?? '') !== $claim->razorpay_order_id) $safe = false;
                    }
                }
            }
        }
        DB::transaction(function () use ($postId, $claim, $safe) {
            $post = DB::table('partner_market_posts')->where('id', $postId)->lockForUpdate()->first();
            $row = DB::table('partner_market_secure_payments')->where('id', $claim->id)->lockForUpdate()->first();
            if ((int) $post->active_payment_id !== (int) $claim->id || $row->status !== 'pending' || $row->gateway_check_token !== $claim->gateway_check_token) return;
            $values = ['gateway_check_token' => null, 'gateway_check_started_at' => null, 'gateway_checked_at' => now(), 'updated_at' => now()];
            // An admin hold also holds the reservation until explicitly released.
            if ($safe && !$row->admin_hold && !$row->admin_settlement_id) {
                $values += ['status' => 'failed', 'reservation_state' => 'failed', 'release_confirmed_at' => now()];
                $reset = ['active_payment_id' => null, 'status' => 'open', 'updated_at' => now()];
                if (($post->pricing_mode ?? '') === 'quote') $reset += ['asking_amount' => null, 'vendor_commission' => 0, 'service_fee' => 0, 'vendor_payout' => 0, 'secure_booking' => false];
                DB::table('partner_market_posts')->where('id', $postId)->update($reset);
                DB::table('partner_market_quotes')->where('id', $row->quote_id)->where('status', 'reserved')->update(['status' => 'pending', 'updated_at' => now()]);
            }
            DB::table('partner_market_secure_payments')->where('id', $claim->id)->update($values);
        }, 3);
    }

    /** Gateway-verified capture; first valid active attempt wins. Late evidence is refunded. */
    public static function capture(int $id, string $orderId, string $paymentId): string
    {
        $payment = RazorpayService::fetchPayment($paymentId); $order = RazorpayService::fetchOrder($orderId);
        abort_unless(($payment['status'] ?? false) && ($order['status'] ?? false), 503, 'Gateway verification unavailable; waiting continues.');
        $p = $payment['data']; $o = $order['data'];
        $initial = DB::table('partner_market_secure_payments')->find($id);
        abort_unless($initial, 404);
        $state = DB::transaction(function () use ($initial, $id, $orderId, $paymentId, $p, $o) {
            $post = DB::table('partner_market_posts')->where('id', $initial->post_id)->lockForUpdate()->first();
            $row = DB::table('partner_market_secure_payments')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row && $row->razorpay_order_id === $orderId && ($p['status'] ?? '') === 'captured'
                && ($p['id'] ?? '') === $paymentId && ($p['order_id'] ?? '') === $orderId && ($p['currency'] ?? '') === 'INR'
                && (int) ($p['amount'] ?? -1) === (int) $row->commission_paise && ($o['id'] ?? '') === $orderId
                && (int) ($o['amount'] ?? -1) === (int) $row->commission_paise && ($o['currency'] ?? '') === 'INR', 422, 'Captured amount/order verification failed.');
            if ($row->razorpay_payment_id === $paymentId && in_array($row->status, ['paid', 'refund_pending', 'refunded'], true)) return $row->status;
            abort_if($row->razorpay_payment_id && $row->razorpay_payment_id !== $paymentId, 409, 'Different captured payment requires admin reconciliation.');
            $winner = $post->status === 'payment_pending' && (int) $post->active_payment_id === $id && !$post->assigned_payment_id && $row->status === 'pending';
            $values = ['razorpay_payment_id' => $paymentId, 'paid_at' => now(), 'updated_at' => now()];
            if ($winner) {
                $values += ['status' => 'paid', 'reservation_state' => 'paid'];
                PartnerMarketplaceAlerts::booked($post, (int) $row->payer_vendor_id, $row->fleet_submission_id ? (int) $row->fleet_submission_id : null);
                DB::table('partner_market_posts')->where('id', $post->id)->update(['status' => 'assigned', 'active_payment_id' => null, 'assigned_payment_id' => $id, 'assigned_fleet_submission_id' => $row->fleet_submission_id, 'updated_at' => now()]);
                DB::table('partner_market_quotes')->where('post_id', $post->id)->whereIn('status', ['pending', 'reserved'])->update(['status' => 'rejected', 'updated_at' => now()]);
                DB::table('partner_market_quotes')->where('id', $row->quote_id)->update(['status' => 'accepted', 'updated_at' => now()]);
            } else {
                $values += ['status' => 'refund_pending', 'reservation_state' => 'late_refund', 'payout_status' => 'blocked',
                    'refund_status' => 'requested', 'refund_request_key' => (string) Str::uuid(), 'refund_requested_at' => now(),
                    'cancelled_at' => now(), 'cancellation_reason' => 'Late capture after reservation released; booking not assigned.'];
            }
            DB::table('partner_market_secure_payments')->where('id', $id)->update($values);
            return $winner ? 'paid' : 'refund_pending';
        }, 3);
        if ($state === 'refund_pending') self::refund($id);
        return (string) DB::table('partner_market_secure_payments')->where('id', $id)->value('status');
    }

    /** One full-refund POST only. Ambiguous responses stay review_required, never blind retry. */
    public static function refund(int $id): object
    {
        $claimed = DB::transaction(function () use ($id) {
            $row = DB::table('partner_market_secure_payments')->where('id', $id)->lockForUpdate()->first();
            if (!$row || $row->status !== 'refund_pending' || $row->refund_status !== 'requested' || $row->admin_hold || $row->admin_settlement_id) return null;
            DB::table('partner_market_secure_payments')->where('id', $id)->update(['refund_status' => 'review_required', 'updated_at' => now()]);
            return $row;
        }, 3);
        if ($claimed) {
            $result = RazorpayService::refund((string) $claimed->razorpay_payment_id, $claimed->commission_paise / 100,
                'Marketplace cancellation or expired reservation', ['market_payment_id' => (string) $id, 'request_key' => $claimed->refund_request_key]);
            if (($result['status'] ?? false) && !empty($result['data']['id'])
                && ($result['data']['payment_id'] ?? '') === $claimed->razorpay_payment_id
                && (int) ($result['data']['amount'] ?? -1) === (int) $claimed->commission_paise) {
                $processed = ($result['data']['status'] ?? '') === 'processed';
                DB::table('partner_market_secure_payments')->where('id', $id)->where('refund_status', 'review_required')->update([
                    'razorpay_refund_id' => $result['data']['id'], 'status' => $processed ? 'refunded' : 'refund_pending',
                    'refund_status' => $processed ? 'processed' : 'initiated', 'refund_initiated_at' => now(),
                    'refunded_at' => $processed ? now() : null, 'updated_at' => now(),
                ]);
            }
        }
        return DB::table('partner_market_secure_payments')->find($id);
    }
}
