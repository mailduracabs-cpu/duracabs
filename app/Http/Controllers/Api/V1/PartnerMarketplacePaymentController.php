<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\RazorpayService;
use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** Marketplace payment ledger. Never writes to customer booking/payment tables. */
class PartnerMarketplacePaymentController extends PartnerController
{
    private function vendor(Request $request): TransporterProfile
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'vendor' && $profile->isActive(), 403, 'Verified vendor required.');
        return $profile;
    }

    /** Posting vendor selects exactly one quote; payment is not yet received. */
    public function selectQuote(Request $request, int $post, int $quote)
    {
        $vendor = $this->vendor($request);
        $ledger = DB::transaction(function () use ($post, $quote, $vendor) {
            $listing = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
            abort_unless($listing && $listing->kind === 'booking' && $listing->status === 'open'
                && (int) $listing->transporter_profile_id === (int) $vendor->id, 403, 'Open booking owned by you required.');
            abort_unless((bool) $listing->secure_booking, 422, 'This endpoint is for secure bookings only.');
            $selected = DB::table('partner_market_quotes')->where('id', $quote)->where('post_id', $post)->first();
            abort_unless($selected && $selected->status === 'pending', 422, 'Pending quote required.');
            abort_if(DB::table('partner_market_secure_payments')->where('post_id', $post)->exists(), 409, 'Booking already has a payment selection.');
            $commission = (int) round((float) $listing->vendor_commission * 100);
            abort_unless($commission > 0, 422, 'Commission must be positive.');
            $fee = intdiv($commission * 10 + 50, 100);
            $id = DB::table('partner_market_secure_payments')->insertGetId([
                'post_id' => $post, 'quote_id' => $quote,
                'payer_vendor_id' => $selected->transporter_profile_id,
                'payee_vendor_id' => $listing->transporter_profile_id,
                'commission_paise' => $commission, 'service_fee_paise' => $fee,
                'payout_paise' => $commission - $fee,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('partner_market_quotes')->where('id', $quote)->update(['status' => 'accepted', 'updated_at' => now()]);
            DB::table('partner_market_posts')->where('id', $post)->update(['status' => 'closed', 'updated_at' => now()]);
            return $id;
        }, 3);
        return response()->json(['status' => true, 'data' => ['payment_id' => $ledger, 'payment_status' => 'pending']], 201);
    }

    /** Only selected Vendor B can start checkout. Amount always comes from DB. */
    public function createOrder(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $ledger = DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        abort_unless($ledger && (int) $ledger->payer_vendor_id === (int) $vendor->id, 403);
        abort_unless($ledger->status === 'pending', 409, 'Payment is no longer pending.');
        if ($ledger->razorpay_order_id) {
            $existing = RazorpayService::fetchOrder($ledger->razorpay_order_id);
            abort_unless($existing['status'] ?? false, 503, 'Unable to retrieve existing order.');
            return response()->json(['status' => true, 'data' => [
                'order' => $existing['data'], 'key' => RazorpayService::publicKey(),
                'payment_id' => $ledger->id, 'amount_paise' => $ledger->commission_paise,
            ]]);
        }
        $result = RazorpayService::createOrder($ledger->commission_paise / 100,
            'b2b_' . $ledger->id, ['source' => 'partner_marketplace', 'market_payment_id' => (string) $ledger->id]);
        abort_unless($result['status'] ?? false, 503, 'Razorpay order creation failed.');
        $order = $result['data'];
        // Conditional write: concurrent requests may create an unused gateway order, but never overwrite selected order.
        $updated = DB::table('partner_market_secure_payments')->where('id', $ledger->id)
            ->where('status', 'pending')->whereNull('razorpay_order_id')
            ->update(['razorpay_order_id' => $order['id'], 'updated_at' => now()]);
        if (!$updated) {
            $fresh = DB::table('partner_market_secure_payments')->where('id', $ledger->id)->first();
            abort_unless($fresh && $fresh->status === 'pending' && $fresh->razorpay_order_id, 409, 'Payment state changed.');
            $existing = RazorpayService::fetchOrder($fresh->razorpay_order_id);
            abort_unless($existing['status'] ?? false, 503);
            $order = $existing['data'];
        }
        return response()->json(['status' => true, 'data' => [
            'order' => $order, 'key' => RazorpayService::publicKey(),
            'payment_id' => $ledger->id, 'amount_paise' => $ledger->commission_paise,
        ]]);
    }

    /** Verified checkout callback. Payment must be captured and match ledger amount exactly. */
    public function verify(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string'],
        ]);
        $ledger = DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        abort_unless($ledger && (int) $ledger->payer_vendor_id === (int) $vendor->id, 403);
        abort_unless($ledger->razorpay_order_id === $data['razorpay_order_id'], 422, 'Order mismatch.');
        $signature = RazorpayService::verifyPayment($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature']);
        abort_unless($signature['status'] ?? false, 422, 'Invalid payment signature.');
        $status = $this->confirmCaptured($payment, $data['razorpay_order_id'], $data['razorpay_payment_id']);
        return response()->json(['status' => true, 'data' => ['payment_status' => $status]]);
    }

    /** Public dedicated endpoint: HMAC validated before any processing. */
    public function webhook(Request $request)
    {
        if (!RazorpayService::verifyWebhook($request->getContent(), (string) $request->header('X-Razorpay-Signature', ''))) {
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 400);
        }
        $event = (string) $request->input('event');
        if (in_array($event, ['refund.processed', 'refund.failed'], true)) {
            $refundId = (string) $request->input('payload.refund.entity.id', '');
            $entity = (array) $request->input('payload.refund.entity', []);
            $row = $refundId !== '' ? DB::table('partner_market_secure_payments')->where('razorpay_refund_id', $refundId)->first() : null;
            if (!$row) return response()->json(['status' => true, 'message' => 'Refund not matched; reconcile if needed']);
            DB::transaction(function () use ($row, $event, $refundId, $entity) {
                $locked = DB::table('partner_market_secure_payments')->where('id', $row->id)->lockForUpdate()->first();
                if (!$locked || $locked->razorpay_refund_id !== $refundId || $locked->status === 'refunded') return;
                if ($event === 'refund.processed' && (int) ($entity['amount'] ?? -1) === (int) $locked->commission_paise) {
                    DB::table('partner_market_secure_payments')->where('id', $row->id)->update([
                        'status' => 'refunded', 'refund_status' => 'processed', 'refunded_at' => now(), 'updated_at' => now(),
                    ]);
                } elseif ($event === 'refund.failed') {
                    DB::table('partner_market_secure_payments')->where('id', $row->id)->update([
                        'refund_status' => 'review_required', 'updated_at' => now(),
                    ]);
                }
            }, 3);
            return response()->json(['status' => true, 'message' => 'Refund event handled']);
        }
        if ($event !== 'payment.captured') {
            return response()->json(['status' => true, 'message' => 'Event acknowledged']);
        }
        $orderId = (string) ($request->input('payload.payment.entity.order_id') ?: $request->input('payload.order.entity.id'));
        $paymentId = (string) $request->input('payload.payment.entity.id');
        $ledger = DB::table('partner_market_secure_payments')->where('razorpay_order_id', $orderId)->first();
        if (!$ledger) return response()->json(['status' => true, 'message' => 'Not a marketplace payment']);
        if (!$paymentId) return response()->json(['status' => false, 'message' => 'Missing payment entity'], 503);
        try {
            $status = $this->confirmCaptured((int) $ledger->id, $orderId, $paymentId);
            return response()->json(['status' => true, 'payment_status' => $status]);
        } catch (\Throwable $e) {
            Log::error('Marketplace capture verification needs retry', ['ledger_id' => $ledger->id, 'error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Verification retry required'], 503);
        }
    }

    private function confirmCaptured(int $id, string $orderId, string $paymentId): string
    {
        $payment = RazorpayService::fetchPayment($paymentId);
        $order = RazorpayService::fetchOrder($orderId);
        if (!($payment['status'] ?? false) || !($order['status'] ?? false)) {
            throw ValidationException::withMessages(['payment' => 'Gateway verification unavailable.']);
        }
        $p = $payment['data']; $o = $order['data'];
        return DB::transaction(function () use ($id, $orderId, $paymentId, $p, $o) {
            $row = DB::table('partner_market_secure_payments')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row && $row->razorpay_order_id === $orderId, 422, 'Order mismatch.');
            if ($row->status === 'paid' && $row->razorpay_payment_id === $paymentId) return 'paid';
            if ($row->status === 'cancelled') {
                // Captured after cancellation: retain evidence and require a full refund reconciliation.
                DB::table('partner_market_secure_payments')->where('id', $id)->update([
                    'razorpay_payment_id' => $paymentId, 'refund_status' => 'review_required', 'updated_at' => now(),
                ]);
                return 'cancelled_capture_review_required';
            }
            abort_unless($row->status === 'pending', 409, 'Payment cannot be applied to this booking state.');
            abort_unless(($p['status'] ?? '') === 'captured'
                && ($p['id'] ?? '') === $paymentId && ($p['order_id'] ?? '') === $orderId
                && ($p['currency'] ?? '') === 'INR'
                && (int) ($p['amount'] ?? -1) === (int) $row->commission_paise
                && ($o['id'] ?? '') === $orderId && (int) ($o['amount'] ?? -1) === (int) $row->commission_paise
                && ($o['currency'] ?? '') === 'INR', 422, 'Captured amount/order verification failed.');
            DB::table('partner_market_secure_payments')->where('id', $id)->update([
                'status' => 'paid', 'razorpay_payment_id' => $paymentId,
                'paid_at' => now(), 'updated_at' => now(),
            ]);
            return 'paid';
        }, 3);
    }
}
