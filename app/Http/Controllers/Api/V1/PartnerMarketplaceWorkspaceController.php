<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\PartnerMarketplaceReservations as Reservations;
use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** B2B workspace only: never reads or writes the customer order ledger. */
class PartnerMarketplaceWorkspaceController extends PartnerController
{
    private function vendor(Request $request): TransporterProfile
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'vendor' && $profile->isActive(), 403, 'Active verified vendor required.');
        return $profile;
    }

    public function bookings(Request $request)
    {
        $vendor = $this->vendor($request);
        $input = $request->validate(['scope' => ['required', Rule::in(['posted', 'received'])],
            'q' => ['nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1']]);
        $query = DB::table('partner_market_posts as p')->where('p.kind', 'booking');
        if ($input['scope'] === 'posted') $query->where('p.transporter_profile_id', $vendor->id);
        else $query->where(function ($scope) use ($vendor) {
            $scope->whereExists(function ($q) use ($vendor) {
                $q->selectRaw('1')->from('partner_market_quotes as q')->whereColumn('q.post_id', 'p.id')
                    ->where('q.transporter_profile_id', $vendor->id)->where('q.status', 'accepted');
            })->orWhereExists(function ($q) use ($vendor) {
                $q->selectRaw('1')->from('partner_market_secure_payments as l')->whereColumn('l.id', 'p.active_payment_id')
                    ->where('l.payer_vendor_id', $vendor->id)->where('l.status', 'pending');
            });
        });
        if (!empty($input['q'])) {
            $term = '%' . $input['q'] . '%';
            $query->where(function ($q) use ($term) { $q->where('p.id', 'like', $term)->orWhere('p.pickup_city', 'like', $term)->orWhere('p.drop_city', 'like', $term); });
        }
        $page = $query->select('p.*')->orderByDesc('p.id')->paginate(20);
        return response()->json(['status' => true, 'data' => [
            'items' => $page->items(), 'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(),
        ]]);
    }

    public function details(Request $request, int $post)
    {
        $vendor = $this->vendor($request);
        Reservations::reconcile($post);
        $listing = DB::table('partner_market_posts')->where('id', $post)->first();
        abort_unless($listing, 404);
        \App\Services\PartnerMarketplaceAlerts::gate($listing, (int) $vendor->id);
        $listing->posting_company = DB::table('fleet_transporter_profiles')->where('id', $listing->transporter_profile_id)->value('company_name');
        $owner = (int) $listing->transporter_profile_id === (int) $vendor->id;
        $participated = DB::table('partner_market_quotes')->where('post_id', $post)->where('transporter_profile_id', $vendor->id)->exists();
        $chatted = DB::table('partner_market_messages')->where('post_id', $post)->where(function ($q) use ($vendor) {
            $q->where('sender_vendor_id', $vendor->id)->orWhere('recipient_vendor_id', $vendor->id);
        })->exists();
        abort_unless($owner || in_array($listing->status, ['open', 'payment_pending'], true) || $participated || $chatted, 403);
        $query = DB::table('partner_market_quotes as q')->join('fleet_transporter_profiles as v', 'v.id', '=', 'q.transporter_profile_id')
            ->where('q.post_id', $post)->select('q.*', 'v.company_name', 'v.contact_person');
        if (!$owner) $query->where('q.transporter_profile_id', $vendor->id);
        $quotes = $query->orderBy('q.id')->get();
        $payment = DB::table('partner_market_secure_payments')->where('post_id', $post)
            ->where(function ($q) use ($vendor) { $q->where('payer_vendor_id', $vendor->id)->orWhere('payee_vendor_id', $vendor->id); })
            ->orderByRaw('CASE WHEN id = ? THEN 0 WHEN id = ? THEN 1 ELSE 2 END', [$listing->assigned_payment_id ?? 0, $listing->active_payment_id ?? 0])->orderByDesc('id')
            ->select('id', 'payer_vendor_id', 'payee_vendor_id', 'status', 'refund_status', 'payout_status')->first();
        $ownFleet = \App\Services\PartnerMarketplaceFleet::latest($post, (int) $vendor->id, (int) $listing->revision);
        $ownFleet = $ownFleet ? \App\Services\PartnerMarketplaceFleet::summary($ownFleet) : null;
        $interested = null;
        if ($owner && $listing->pricing_mode === 'quote') {
            $pages = $request->validate(['interested_page' => ['sometimes', 'integer', 'min:1']]);
            $peers = DB::table('partner_market_messages')->where('post_id', $post)
                ->where(function ($q) use ($vendor) { $q->where('sender_vendor_id', $vendor->id)->orWhere('recipient_vendor_id', $vendor->id); })
                ->selectRaw('CASE WHEN sender_vendor_id = ? THEN recipient_vendor_id ELSE sender_vendor_id END as vendor_id', [$vendor->id]);
            $bidders = DB::table('partner_market_quotes')->where('post_id', $post)->select('transporter_profile_id as vendor_id');
            $page = DB::query()->fromSub($peers->union($bidders), 'peers')->join('fleet_transporter_profiles as v', 'v.id', '=', 'peers.vendor_id')
                ->where('v.id', '!=', $vendor->id)->whereNull('v.deleted_at')
                ->select('v.id', 'v.company_name', 'v.contact_person', 'v.status', 'v.verification_status')
                ->orderBy('v.company_name')->orderBy('v.id')->paginate(20, ['*'], 'interested_page', $pages['interested_page'] ?? 1);
            foreach ($page->items() as $peer) {
                $submission = \App\Services\PartnerMarketplaceFleet::latest($post, (int) $peer->id, (int) $listing->revision);
                $peer->fleet_details = $submission ? \App\Services\PartnerMarketplaceFleet::summary($submission) : null;
            }
            $interested = ['items' => $page->items(), 'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()];
        }
        return response()->json(['status' => true, 'data' => ['post' => $listing, 'is_owner' => $owner, 'own_fleet_details' => $ownFleet, 'interested_vendors' => $interested, 'quotes' => $quotes, 'payment' => $payment, 'reservation' => Reservations::snapshot($listing, (int) $vendor->id)]]);
    }

    public function inbox(Request $request)
    {
        $vendor = $this->vendor($request); $self = (int) $vendor->id;
        $regular = DB::table('partner_market_messages')
            ->where(function ($q) use ($self) { $q->where('sender_vendor_id', $self)->orWhere('recipient_vendor_id', $self); })
            ->selectRaw('post_id, CASE WHEN sender_vendor_id = ? THEN recipient_vendor_id ELSE sender_vendor_id END as peer_vendor_id, created_at as sent_at', [$self])
            ->selectRaw('CASE WHEN recipient_vendor_id = ? AND read_at IS NULL THEN 1 ELSE 0 END as unread', [$self]);
        $admin = DB::table('partner_market_admin_messages as a')->join('partner_market_posts as p', 'p.id', '=', 'a.post_id')
            ->where(function ($q) use ($self) { $q->where('p.transporter_profile_id', $self)->orWhere('a.peer_vendor_id', $self); })
            ->selectRaw('a.post_id, CASE WHEN p.transporter_profile_id = ? THEN a.peer_vendor_id ELSE p.transporter_profile_id END as peer_vendor_id, a.created_at as sent_at', [$self])
            ->selectRaw('CASE WHEN p.transporter_profile_id = ? AND a.owner_read_at IS NULL THEN 1 WHEN a.peer_vendor_id = ? AND a.peer_read_at IS NULL THEN 1 ELSE 0 END as unread', [$self, $self]);
        $grouped = DB::query()->fromSub($regular->unionAll($admin), 'events')->select('post_id', 'peer_vendor_id')
            ->selectRaw('MAX(sent_at) as latest_at, SUM(unread) as unread_count')->groupBy('post_id', 'peer_vendor_id');
        $page = DB::query()->fromSub($grouped, 'c')->join('partner_market_posts as p', 'p.id', '=', 'c.post_id')
            ->join('fleet_transporter_profiles as v', 'v.id', '=', 'c.peer_vendor_id')
            ->select('c.post_id', 'c.peer_vendor_id', 'c.unread_count', 'c.latest_at as created_at', 'p.pickup_city', 'p.drop_city', 'p.status', 'p.transporter_profile_id', 'v.company_name', 'v.contact_person')
            ->orderByDesc('c.latest_at')->orderByDesc('c.post_id')->paginate(20);
        foreach ($page->items() as $item) {
            $peer = (int) $item->peer_vendor_id;
            $last = DB::table('partner_market_messages')->where('post_id', $item->post_id)->where(function ($q) use ($self, $peer) {
                $q->where(function ($q) use ($self, $peer) { $q->where('sender_vendor_id', $self)->where('recipient_vendor_id', $peer); })
                  ->orWhere(function ($q) use ($self, $peer) { $q->where('sender_vendor_id', $peer)->where('recipient_vendor_id', $self); });
            })->orderByDesc('created_at')->orderByDesc('id')->first();
            $threadPeer = (int) $item->transporter_profile_id === $self ? $peer : $self;
            $adminLast = DB::table('partner_market_admin_messages')->where('post_id', $item->post_id)->where('peer_vendor_id', $threadPeer)->orderByDesc('created_at')->orderByDesc('id')->first();
            $item->message = $adminLast && (!$last || $adminLast->created_at >= $last->created_at) ? 'Admin: ' . $adminLast->message : ($last->message ?? '');
            unset($item->transporter_profile_id);
        }
        return response()->json(['status' => true, 'data' => ['items' => $page->items(), 'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]]);
    }

    public function readMessages(Request $request, int $post)
    {
        $vendor = $this->vendor($request);
        $input = $request->validate(['peer_vendor_id' => ['required', 'integer', 'min:1'], 'through_id' => ['nullable', 'integer', 'min:1'], 'through_admin_id' => ['nullable', 'integer', 'min:1']]);
        $listing = DB::table('partner_market_posts')->where('id', $post)->first();
        abort_unless($listing, 404);
        abort_unless((int) $listing->transporter_profile_id === (int) $vendor->id || (int) $listing->transporter_profile_id === (int) $input['peer_vendor_id'], 403);
        if (isset($input['through_id'])) {
            DB::table('partner_market_messages')->where('post_id', $post)->where('recipient_vendor_id', $vendor->id)
                ->where('sender_vendor_id', $input['peer_vendor_id'])->where('id', '<=', $input['through_id'])->whereNull('read_at')->update(['read_at' => now()]);
        }
        if (isset($input['through_admin_id'])) {
            $owner = (int) $listing->transporter_profile_id === (int) $vendor->id;
            $peer = $owner ? (int) $input['peer_vendor_id'] : (int) $vendor->id;
            $column = $owner ? 'owner_read_at' : 'peer_read_at';
            DB::table('partner_market_admin_messages')->where('post_id', $post)->where('peer_vendor_id', $peer)
                ->where('id', '<=', $input['through_admin_id'])->whereNull($column)->update([$column => now()]);
        }
        return response()->json(['status' => true]);
    }
}
