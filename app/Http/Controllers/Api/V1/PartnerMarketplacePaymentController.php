<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\RazorpayService;
use App\Services\PartnerMarketplaceAssignmentRequests as AssignmentRequests;
use App\Services\PartnerMarketplaceReservations as Reservations;
use App\Services\PartnerMarketplaceAdminService;
use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/** Marketplace payment ledger. Never writes to customer booking/payment tables. */
class PartnerMarketplacePaymentController extends PartnerController
{
    private function vendor(Request $request): TransporterProfile
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'vendor' && $profile->isActive(), 403, 'Verified vendor required.');
        return $profile;
    }

    /** Selection reserves payment; assignment happens only on verified capture. */
    public function selectQuote(Request $request, int $post, int $quote)
    {
        $vendor = $this->vendor($request);
        $input = $request->validate(['expected_revision' => ['required', 'integer', 'min:1'],
            'booking_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'commission' => ['nullable', 'numeric', 'gt:0', 'max:9999999999', 'regex:/^\d+(?:\.\d{1,2})?$/']]);
        Reservations::reconcile($post);
        $ledger = DB::transaction(function () use ($post, $quote, $vendor, $input) {
            $listing = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
            abort_unless($listing && $listing->kind === 'booking' && (int) $listing->transporter_profile_id === (int) $vendor->id, 403);
            $selected = DB::table('partner_market_quotes')->where('id', $quote)->where('post_id', $post)->first();
            abort_unless($selected && in_array($selected->status, ['pending', 'reserved'], true), 422, 'Pending quote required.');
            abort_unless((int) $listing->revision === (int) $input['expected_revision'] && (int) $selected->post_revision === (int) $listing->revision, 409, 'Booking changed. Refresh before selecting.');
            $receiver = TransporterProfile::query()->find($selected->transporter_profile_id);
            abort_unless($receiver && $receiver->isActive() && $receiver->isVerified() && $receiver->isVendor(), 422, 'Selected vendor unavailable.');
            abort_if((int) $selected->transporter_profile_id === (int) $vendor->id, 422);
            if ($listing->pricing_mode === 'quote') {
                abort_unless(isset($input['booking_amount'], $input['commission']), 422, 'Enter final booking amount and commission before sending a payment request.');
                return AssignmentRequests::create($listing, (int) $selected->transporter_profile_id, (string) $input['booking_amount'], (string) $input['commission']);
            }
            if ((bool) $listing->secure_booking) return Reservations::reserve($listing, $selected);
            abort_unless($listing->status === 'open' && !$listing->active_payment_id, 409, 'Booking unavailable.');
            $fleet = \App\Services\PartnerMarketplaceFleet::requireFor($listing, (int) $selected->transporter_profile_id);
            DB::table('partner_market_quotes')->where('post_id', $post)->where('status', 'pending')->update(['status' => 'rejected', 'updated_at' => now()]);
            DB::table('partner_market_quotes')->where('id', $quote)->update(['status' => 'accepted', 'updated_at' => now()]);
            \App\Services\PartnerMarketplaceAlerts::booked($listing, (int) $selected->transporter_profile_id, (int) $fleet->id);
            DB::table('partner_market_posts')->where('id', $post)->update(['status' => 'assigned', 'assigned_fleet_submission_id' => $fleet->id, 'updated_at' => now()]);
            return null;
        }, 3);
        return response()->json(['status' => true, 'data' => ['payment_id' => $ledger, 'payment_status' => $ledger ? 'pending' : 'not_required']], 201);
    }

    /** Owner sends agreed fare/commission request to one vendor from the conversation. */
    public function assignmentRequest(Request $request, int $post)
    {
        $vendor = $this->vendor($request);
        $input = $request->validate([
            'fleet_submission_id' => ['required', 'integer', 'min:1'],
            'vendor_id' => ['required', 'integer', 'min:1'], 'expected_revision' => ['required', 'integer', 'min:1'],
            'booking_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'commission' => ['required', 'numeric', 'gt:0', 'max:9999999999', 'regex:/^\d+(?:\.\d{1,2})?$/'],
        ]);
        Reservations::reconcile($post);
        $id = DB::transaction(function () use ($post, $vendor, $input) {
            $listing = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
            abort_unless($listing && (int) $listing->transporter_profile_id === (int) $vendor->id, 403, 'Only the posting vendor may send a payment request.');
            abort_unless((int) $listing->revision === (int) $input['expected_revision'], 409, 'Booking changed. Refresh before requesting payment.');
            return AssignmentRequests::create($listing, (int) $input['vendor_id'], (string) $input['booking_amount'], (string) $input['commission'], (int) $input['fleet_submission_id']);
        }, 3);
        return response()->json(['status' => true, 'data' => ['payment_id' => $id, 'payment_status' => 'pending'],
            'message' => 'Payment request sent. Booking will be assigned only after successful payment.'], 201);
    }

    /** Fixed non-negotiated fare can be taken directly; quote fares require owner selection. */
    public function claim(Request $request, int $post)
    {
        $vendor = $this->vendor($request);
        $input = $request->validate(['expected_revision' => ['required', 'integer', 'min:1']]);
        Reservations::reconcile($post);
        $id = DB::transaction(function () use ($post, $vendor, $input) {
            $listing = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
            abort_unless($listing && $listing->kind === 'booking' && (bool) $listing->secure_booking && $listing->pricing_mode === 'fixed', 422, 'Direct payment requires a secure fixed-fare booking.');
            \App\Services\PartnerMarketplaceAlerts::gate($listing, (int) $vendor->id);
            abort_if((int) $listing->transporter_profile_id === (int) $vendor->id, 403);
            abort_unless((int) $listing->revision === (int) $input['expected_revision'], 409, 'Booking changed. Review latest terms.');
            // Block another payer before touching their quote or creating a payment attempt.
            if ($listing->active_payment_id) {
                $active = DB::table('partner_market_secure_payments')->find($listing->active_payment_id);
                abort_unless($active && (int) $active->payer_vendor_id === (int) $vendor->id, 409, 'Another vendor is paying. Please wait.');
                return (int) $active->id;
            }
            abort_unless($listing->status === 'open', 409, 'Booking unavailable.');
            $quote = DB::table('partner_market_quotes')->where('post_id', $post)->where('transporter_profile_id', $vendor->id)->first();
            $values = ['amount' => $listing->asking_amount, 'post_revision' => $listing->revision, 'status' => 'pending', 'updated_at' => now()];
            if ($quote) DB::table('partner_market_quotes')->where('id', $quote->id)->update($values);
            else {
                $quoteId = DB::table('partner_market_quotes')->insertGetId($values + ['post_id' => $post, 'transporter_profile_id' => $vendor->id, 'created_at' => now()]);
                $quote = (object) ['id' => $quoteId, 'transporter_profile_id' => $vendor->id];
            }
            return Reservations::reserve($listing, $quote);
        }, 3);
        return response()->json(['status' => true, 'data' => ['payment_id' => $id]], 201);
    }

    /** Public booking availability for authenticated vendors; no other payer's identity exposed. */
    public function reservation(Request $request, int $post)
    {
        $vendor = $this->vendor($request);
        abort_unless(DB::table('partner_market_posts')->where('id', $post)->exists(), 404);
        Reservations::reconcile($post);
        return response()->json(['status' => true, 'data' => Reservations::snapshot(DB::table('partner_market_posts')->find($post), (int) $vendor->id)])
            ->header('Cache-Control', 'no-store, private');
    }

    /** Only selected Vendor B can start checkout. Amount always comes from DB. */
    public function createOrder(Request $request, int $payment)
    {
        $vendor = $this->vendor($request);
        $initial = DB::table('partner_market_secure_payments')->find($payment);
        abort_unless($initial && (int) $initial->payer_vendor_id === (int) $vendor->id, 403);
        Reservations::reconcile((int) $initial->post_id);
        $ledger = DB::transaction(function () use ($payment, $initial) {
            $post = DB::table('partner_market_posts')->where('id', $initial->post_id)->lockForUpdate()->first();
            $row = DB::table('partner_market_secure_payments')->where('id', $payment)->lockForUpdate()->first();
            abort_unless(Reservations::checkoutAllowed($row, $post), 409, 'Payment window expired or booking is locked. Refresh booking status.');
            if (!$row->razorpay_order_id) {
                abort_if($row->order_creation_started_at, 409, 'Checkout preparation already started. Refresh; do not create a second order.');
                DB::table('partner_market_secure_payments')->where('id', $payment)->update(['order_creation_started_at' => now(), 'updated_at' => now()]);
            }
            return $row;
        }, 3);
        if ($ledger->razorpay_order_id) $result = RazorpayService::fetchOrder($ledger->razorpay_order_id);
        else $result = RazorpayService::createOrder($ledger->commission_paise / 100, 'b2b_' . $ledger->id,
            ['source' => 'partner_marketplace', 'market_payment_id' => (string) $ledger->id]);
        abort_unless(($result['status'] ?? false) && !empty($result['data']['id']), 503, 'Gateway preparation unavailable. Refresh payment status.');
        $order = $result['data'];
        DB::transaction(function () use ($ledger, $order) {
            $post = DB::table('partner_market_posts')->where('id', $ledger->post_id)->lockForUpdate()->first();
            $row = DB::table('partner_market_secure_payments')->where('id', $ledger->id)->lockForUpdate()->first();
            // Persist evidence even if deadline passed; never expose a released order to checkout.
            if (!$row->razorpay_order_id) DB::table('partner_market_secure_payments')->where('id', $row->id)->update([
                'razorpay_order_id' => $order['id'], 'order_creation_started_at' => null, 'updated_at' => now(),
            ]);
        }, 3);
        $fresh = DB::table('partner_market_secure_payments')->find($payment);
        abort_unless(Reservations::checkoutAllowed($fresh, DB::table('partner_market_posts')->find($fresh->post_id)), 409, 'Payment window expired. Refresh the booking.');
        return response()->json(['status' => true, 'data' => ['order' => $order, 'key' => RazorpayService::publicKey(),
            'payment_id' => $payment, 'amount_paise' => $fresh->commission_paise, 'reserved_until' => $fresh->reserved_until]]);
    }

    /** Issues a short-lived link only to the selected authenticated payer. */
    public function checkoutLink(Request $request, int $payment)
    {
        $this->createOrder($request, $payment);
        $expires = \Carbon\Carbon::parse(DB::table('partner_market_secure_payments')->where('id', $payment)->value('reserved_until'));
        $checkout = URL::temporarySignedRoute('partner.market.checkout', $expires, ['payment' => $payment]);
        return response()->json(['status' => true, 'data' => ['checkout_url' => $checkout, 'expires_at' => $expires->toIso8601String()]]);
    }

    /** Signed URL delegates only this ledger's checkout; never general account access. */
    public function checkout(Request $request, int $payment)
    {
        $row = DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        abort_unless($row && $row->status === 'pending' && $row->razorpay_order_id, 409, 'Checkout no longer pending. Return to the app and refresh.');
        abort_if($row->admin_hold || $row->admin_settlement_id, 409, 'Checkout is on admin hold. Return to the app.');
        abort_unless(Reservations::checkoutAllowed($row, DB::table('partner_market_posts')->find($row->post_id)), 409, 'Payment window expired. Return to app.');
        $payer = TransporterProfile::query()->find($row->payer_vendor_id);
        abort_unless($payer && $payer->isActive() && $payer->isVerified() && $payer->isVendor(), 403);
        $key = RazorpayService::publicKey();
        abort_unless($key, 503, 'Payment gateway unavailable.');
        $callback = URL::temporarySignedRoute('partner.market.checkout.complete',
            \Carbon\Carbon::createFromTimestamp((int) $request->query('expires'))->addMinutes(20), ['payment' => $payment]);
        return response()->view('partner.market-checkout', [
            'bookingAmountPaise' => $row->booking_amount_paise,
            'commissionPaise' => (int) $row->commission_paise,
            'options' => ['key' => $key, 'order_id' => $row->razorpay_order_id,
                'amount' => (int) $row->commission_paise, 'currency' => 'INR',
                'name' => 'DuraCabs Partner', 'description' => 'Booking commission #' . $row->post_id,
                'callback_url' => $callback, 'redirect' => true,
                'timeout' => max(1, \Carbon\Carbon::parse($row->reserved_until)->getTimestamp() - now()->getTimestamp())],
            'remainingSeconds' => max(0, \Carbon\Carbon::parse($row->reserved_until)->getTimestamp() - now()->getTimestamp()),
        ])->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    /** Signed callback still requires gateway HMAC AND captured order/amount verification. */
    public function completeCheckout(Request $request, int $payment)
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'max:512'],
        ]);
        $row = DB::table('partner_market_secure_payments')->where('id', $payment)->first();
        abort_unless($row && $row->razorpay_order_id === $data['razorpay_order_id'], 422, 'Order mismatch.');
        $result = RazorpayService::verifyPayment($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature']);
        abort_unless($result['status'] ?? false, 422, 'Invalid payment signature.');
        $state = $this->confirmCaptured($payment, $data['razorpay_order_id'], $data['razorpay_payment_id']);
        $message = $state === 'paid' ? 'Payment verified. Return to DuraCabs Partner and refresh the booking.'
            : 'This attempt could not take the booking. Full commission refund is processing; return to the app for its status.';
        return response('<!doctype html><html lang="en"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DuraCabs payment</title><body><main><h1>DuraCabs Partner</h1><p>' . e($message) . '</p></main></body></html>')
            ->header('Content-Type', 'text/html; charset=UTF-8')->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
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
            if (PartnerMarketplaceAdminService::handleRefundEvent($event, $entity)) return response()->json(['status' => true, 'message' => 'Admin settlement refund reconciled']);
            $row = $refundId !== '' ? DB::table('partner_market_secure_payments')->where('razorpay_refund_id', $refundId)->first() : null;
            if (!$row) return response()->json(['status' => true, 'message' => 'Refund not matched; reconcile if needed']);
            DB::transaction(function () use ($row, $event, $refundId, $entity) {
                $locked = DB::table('partner_market_secure_payments')->where('id', $row->id)->lockForUpdate()->first();
                if (!$locked || $locked->razorpay_refund_id !== $refundId || $locked->status === 'refunded') return;
                if ($event === 'refund.processed' && (int) ($entity['amount'] ?? -1) === (int) $locked->commission_paise
                    && ($entity['payment_id'] ?? '') === $locked->razorpay_payment_id) {
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
        return Reservations::capture($id, $orderId, $paymentId);
    }
}
