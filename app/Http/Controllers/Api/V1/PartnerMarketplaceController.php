<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
use App\Services\PartnerMarketplacePlaces;
use App\Services\PartnerMarketplaceReservations as Reservations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PartnerMarketplaceController extends PartnerController
{
    private function vendor(Request $request): TransporterProfile
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'vendor' && $profile->isActive(), 403, 'Active, verified Vendor account required.');
        return $profile;
    }

    public function index(Request $request)
    {
        $profile = $this->vendor($request);
        $data = $request->validate([
            'kind' => ['sometimes', Rule::in(['booking', 'free_vehicle'])],
            'mine' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $query = DB::table('partner_market_posts as p')
            ->join('fleet_transporter_profiles as v', 'v.id', '=', 'p.transporter_profile_id')
            ->whereNull('v.deleted_at')->where('v.status', true)->where('v.verification_status', 'verified')
            ->select('p.*', 'v.company_name as posting_company');
        if (!empty($data['kind'])) $query->where('p.kind', $data['kind']);
        if (!empty($data['mine'])) $query->where('p.transporter_profile_id', $profile->id);
        else $query->whereIn('p.status', ['open', 'payment_pending']);
        $query->where(function ($q) use ($profile) {
            $q->where('p.transporter_profile_id', $profile->id)->orWhereNull('p.priority_until')->orWhere('p.priority_until', '<=', now())->orWhereExists(function ($a) use ($profile) {
                $a->selectRaw('1')->from('partner_market_alerts as a')->whereColumn('a.post_id', 'p.id')->whereColumn('a.post_revision', 'p.revision')->where('a.vendor_id', $profile->id)->where('a.tier', 0);
            });
        });
        $page = $query->orderByDesc('p.created_at')->paginate(20);
        return response()->json(['status' => true, 'data' => [
            'items' => array_map(function ($item) use ($profile) { $item->reservation = Reservations::snapshot($item, (int) $profile->id); return $item; }, $page->items()), 'page' => $page->currentPage(),
            'last_page' => $page->lastPage(), 'total' => $page->total(),
        ]]);
    }

    private function listingInput(Request $request, ?object $existing = null): array
    {
        $input = $request->validate([
            'kind' => ['required', Rule::in(['booking', 'free_vehicle'])],
            'trip_type' => ['nullable', Rule::in(['one_way', 'round_trip'])],
            'vehicle_class' => ['required', 'string', 'max:80'],
            'pickup_city' => ['required', 'string', 'max:150'],
            'drop_city' => ['nullable', 'string', 'max:150'],
            'pickup_at' => ['required', 'date', 'after:now'],
            'return_at' => ['nullable', 'date', 'after:pickup_at'],
            'asking_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999'],
            'pricing_mode' => ['sometimes', Rule::in(['quote', 'fixed'])],
            'negotiable' => ['sometimes', 'boolean'],
            'inclusions' => ['nullable', 'array', 'max:15'],
            'inclusions.*' => ['string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'vendor_commission' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'secure_booking' => ['sometimes', 'boolean'],
            'availability_vehicle_id' => ['nullable', 'integer', 'min:1'],
            'availability_alerts' => ['sometimes', 'boolean'],
            'pickup_place_id' => ['nullable', 'string', 'max:255'],
            'drop_place_id' => ['nullable', 'string', 'max:255'],
        ]);
        if ($input['kind'] === 'booking' && (empty($input['trip_type']) || empty($input['drop_city']))) {
            throw ValidationException::withMessages(['trip_type' => 'Booking requires trip type and drop city.']);
        }
        if (($input['trip_type'] ?? null) === 'round_trip' && empty($input['return_at'])) {
            throw ValidationException::withMessages(['return_at' => 'Round trip requires a return date/time.']);
        }
        if (($input['pricing_mode'] ?? 'quote') === 'fixed' && empty($input['asking_amount'])) {
            throw ValidationException::withMessages(['asking_amount' => 'Fixed price requires an amount.']);
        }
        if ($input['kind'] === 'booking' && ($input['pricing_mode'] ?? 'quote') === 'quote') {
            // Negotiated in chat; financial terms are set only by an authenticated owner request.
            $input['asking_amount'] = null; $input['vendor_commission'] = 0;
            $input['secure_booking'] = false; $input['negotiable'] = true;
        } elseif ($input['kind'] === 'booking') {
            if (!isset($input['asking_amount'])) throw ValidationException::withMessages(['asking_amount' => 'Booking amount required.']);
            if (!isset($input['vendor_commission'])) throw ValidationException::withMessages(['vendor_commission' => 'Commission required.']);
            if ((float) $input['vendor_commission'] > (float) $input['asking_amount']) throw ValidationException::withMessages(['vendor_commission' => 'Commission cannot exceed booking amount.']);
            if (!empty($input['secure_booking']) && (float) $input['vendor_commission'] <= 0) throw ValidationException::withMessages(['vendor_commission' => 'Secure booking requires positive commission.']);
        }

        foreach (['pickup', 'drop'] as $side) {
            if ($side === 'drop' && $input['kind'] !== 'booking') {
                $input['drop_city'] = null; $input['drop_place_id'] = null; continue;
            }
            $idKey = $side . '_place_id'; $cityKey = $side . '_city';
            $id = $input[$idKey] ?? null;
            $unchanged = $existing && ($existing->{$idKey} ?? null) === $id && $existing->{$cityKey} === $input[$cityKey];
            if ($unchanged) { if ($side === 'pickup') { $input['_pickup_latitude'] = $existing->pickup_latitude; $input['_pickup_longitude'] = $existing->pickup_longitude; } continue; } // Existing manually entered cities can stay unchanged when correcting other fields.
            if (!$id) throw ValidationException::withMessages([$cityKey => 'Select this location from Google search suggestions.']);
            $place = PartnerMarketplacePlaces::details($id);
            $input[$cityKey] = $place['label'];
            if ($side === 'pickup') {
                if ($place['latitude'] === null || $place['longitude'] === null) throw ValidationException::withMessages([$cityKey => 'Google pickup coordinates are unavailable. Select another suggestion or ask admin to enable location in Place Details.']);
                $input['_pickup_latitude'] = $place['latitude']; $input['_pickup_longitude'] = $place['longitude'];
            }
        }
        return $input;
    }

    private function listingValues(array $input): array
    {
        $commissionPaise = (int) round(((float) ($input['vendor_commission'] ?? 0)) * 100);
        $feePaise = intdiv($commissionPaise * 10 + 50, 100);
        return [
            'pickup_latitude' => $input['_pickup_latitude'] ?? null, 'pickup_longitude' => $input['_pickup_longitude'] ?? null,
            'priority_until' => $input['kind'] === 'booking' && isset($input['_pickup_latitude'], $input['_pickup_longitude']) ? now()->addSeconds(max(0, (int) config('partner_market.priority_seconds', 60))) : null,
            'availability_vehicle_id' => $input['kind'] === 'free_vehicle' ? ($input['availability_vehicle_id'] ?? null) : null,
            'kind' => $input['kind'], 'trip_type' => $input['kind'] === 'booking' ? $input['trip_type'] : null,
            'vehicle_class' => $input['vehicle_class'], 'pickup_city' => $input['pickup_city'],
            'drop_city' => $input['kind'] === 'booking' ? $input['drop_city'] : null,
            'pickup_at' => \Carbon\Carbon::parse($input['pickup_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'return_at' => $input['kind'] === 'booking' && ($input['trip_type'] ?? null) === 'round_trip'
                ? \Carbon\Carbon::parse($input['return_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') : null, 'asking_amount' => $input['asking_amount'] ?? null,
            'pricing_mode' => $input['pricing_mode'] ?? 'quote', 'negotiable' => $input['negotiable'] ?? true,
            'inclusions' => json_encode($input['inclusions'] ?? [], JSON_THROW_ON_ERROR),
            'notes' => $input['notes'] ?? null,
            'vendor_commission' => $commissionPaise / 100, 'service_fee' => $feePaise / 100,
            'vendor_payout' => ($commissionPaise - $feePaise) / 100,
            'secure_booking' => $input['kind'] === 'booking' && (bool) ($input['secure_booking'] ?? false),
            'pickup_place_id' => $input['pickup_place_id'] ?? null, 'drop_place_id' => $input['drop_place_id'] ?? null,
            'updated_at' => now(),
        ];
    }

    public function store(Request $request)
    {
        $profile = $this->vendor($request);
        $input = $this->listingInput($request);
        $id = DB::transaction(function () use ($profile, $input) {
            $vehicle = null;
            if ($input['kind'] === 'free_vehicle') {
                $vehicle = \App\Services\PartnerMarketplaceFleet::vehicles($profile)->whereKey($input['availability_vehicle_id'] ?? 0)->lockForUpdate()->first();
                abort_unless($vehicle, 422, 'Select an approved saved taxi before marking it free.');
                abort_unless(isset($input['_pickup_latitude'], $input['_pickup_longitude']), 422, 'Select a free-cab city with Google coordinates.');
            }
            $values = $this->listingValues($input);
            if ($vehicle) $values['vehicle_class'] = $vehicle->car_classification;
            $id = DB::table('partner_market_posts')->insertGetId($values + ['transporter_profile_id' => $profile->id, 'status' => 'open', 'revision' => 1, 'created_at' => now()]);
            if ($vehicle) {
                $old = DB::table('partner_market_free_cabs')->where('vehicle_id', $vehicle->id)->first();
                if ($old?->post_id) DB::table('partner_market_posts')->where('id', $old->post_id)->where('status', 'open')->update(['status' => 'cancelled', 'updated_at' => now()]);
                DB::table('partner_market_free_cabs')->updateOrInsert(['vehicle_id' => $vehicle->id], ['vendor_id' => $profile->id, 'post_id' => $id, 'city' => $input['pickup_city'],
                    'latitude' => $input['_pickup_latitude'], 'longitude' => $input['_pickup_longitude'], 'is_free' => true,
                    'available_from' => \Carbon\Carbon::parse($input['pickup_at']), 'available_until' => \Carbon\Carbon::parse($input['pickup_at'])->addHours(12), 'created_at' => now(), 'updated_at' => now()]);
                DB::table('partner_market_presence')->insertOrIgnore(['vendor_id' => $profile->id, 'app_alerts' => (bool) ($input['availability_alerts'] ?? true), 'created_at' => now(), 'updated_at' => now()]);
                if (!empty($input['availability_alerts'])) DB::table('partner_market_presence')->where('vendor_id', $profile->id)->where('app_alerts', false)->update(['app_alerts' => true, 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);
            }
            return $id;
        }, 3);
        if ($input['kind'] === 'booking') {
            \App\Services\PartnerMarketplaceAlerts::safePlan($id);
        }
        return response()->json(['status' => true, 'data' => ['id' => $id]], 201);
    }

    public function places(Request $request)
    {
        $this->vendor($request);
        $input = $request->validate(['keyword' => ['required', 'string', 'min:2', 'max:100']]);
        return response()->json(['status' => true, 'data' => ['items' => PartnerMarketplacePlaces::suggestions(trim($input['keyword']))]]);
    }

    public function edit(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        $existing = DB::table('partner_market_posts')->where('id', $post)->first();
        abort_unless($existing && (int) $existing->transporter_profile_id === (int) $profile->id, 403, 'Only the posting vendor can edit.');
        abort_if($existing->kind === 'free_vehicle' && $existing->availability_vehicle_id, 409, 'Close this free-cab post and publish updated availability.');
        abort_unless($existing->status === 'open', 409, 'Bookings cannot be edited while paying, assigned or cancelled.');
        $revision = $request->validate(['expected_revision' => ['required', 'integer', 'min:1']])['expected_revision'];
        $input = $this->listingInput($request, $existing);
        abort_unless($input['kind'] === $existing->kind, 422, 'Post type cannot be changed.');
        return DB::transaction(function () use ($profile, $post, $input, $revision) {
            $row = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
            abort_unless($row && (int) $row->transporter_profile_id === (int) $profile->id && $row->status === 'open', 409, 'Booking state changed; refresh before editing.');
            abort_unless((int) $row->revision === (int) $revision, 409, 'A newer edit exists. Refresh and edit the latest booking.');
            abort_if(DB::table('partner_market_secure_payments')->where('post_id', $post)->whereNotIn('status', ['failed'])->exists()
                || DB::table('partner_market_quotes')->where('post_id', $post)->where('status', 'accepted')->exists(), 409, 'Assigned/payment-linked bookings cannot be edited.');
            $bidders = DB::table('partner_market_quotes')->where('post_id', $post)->where('status', 'pending')->pluck('transporter_profile_id');
            $values = $this->listingValues($input) + ['revision' => (int) $revision + 1, 'alerts_planned_at' => null];
            DB::table('partner_market_post_edits')->insert([
                'post_id' => $post, 'actor_vendor_id' => $profile->id, 'from_revision' => (int) $revision, 'to_revision' => (int) $revision + 1,
                'before_data' => json_encode((array) $row, JSON_THROW_ON_ERROR),
                'after_data' => json_encode(array_replace((array) $row, $values), JSON_THROW_ON_ERROR),
                'previous_quotes' => json_encode(DB::table('partner_market_quotes')->where('post_id', $post)->get()->all(), JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
            DB::table('partner_market_posts')->where('id', $post)->update($values);
            DB::table('partner_market_quotes')->where('post_id', $post)->where('status', 'pending')->update(['status' => 'superseded', 'updated_at' => now()]);
            foreach ($bidders as $bidder) {
                DB::table('partner_market_messages')->insert([
                    'post_id' => $post, 'sender_vendor_id' => $profile->id, 'recipient_vendor_id' => $bidder,
                    'message' => 'Booking updated by the posting vendor. Your previous quote is no longer valid. Review the latest details and submit a new quote.',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if ($input['kind'] === 'booking') DB::afterCommit(fn () => \App\Services\PartnerMarketplaceAlerts::safePlan($post));
            return response()->json(['status' => true, 'data' => ['id' => $post, 'revision' => (int) $revision + 1],
                'message' => 'Booking updated. Existing pending quotes require fresh confirmation.']);
        }, 3);
    }

    public function quote(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        $input = $request->validate(['amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:1000'], 'expected_revision' => ['required', 'integer', 'min:1']]);
        return DB::transaction(function () use ($profile, $post, $input) {
            $listing = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
            abort_unless($listing && $listing->kind === 'booking' && $listing->status === 'open', 404, 'Open booking not found.');
            \App\Services\PartnerMarketplaceAlerts::gate($listing, (int) $profile->id);
            abort_if((int) $listing->transporter_profile_id === (int) $profile->id, 403, 'Cannot quote on your own booking.');
            abort_if($listing->pricing_mode === 'quote', 422, 'Discuss this booking in chat. The posting vendor will set fare and commission and send a payment request.');
            abort_unless((int) $listing->revision === (int) $input['expected_revision'], 409, 'Booking changed. Refresh its details before quoting.');
            if ($listing->pricing_mode === 'fixed' && !(bool) $listing->negotiable) {
                abort_unless((int) round((float) $input['amount'] * 100) === (int) round((float) $listing->asking_amount * 100), 422, 'This booking amount is fixed and not negotiable.');
            }
            $existing = DB::table('partner_market_quotes')->where('post_id', $post)->where('transporter_profile_id', $profile->id)->first();
            abort_if($existing && $existing->status !== 'superseded', 409, 'Quote already submitted.');
            $values = ['amount' => $input['amount'], 'notes' => $input['notes'] ?? null, 'post_revision' => $listing->revision,
                'status' => 'pending', 'updated_at' => now()];
            if ($existing) {
                $id = $existing->id;
                DB::table('partner_market_quotes')->where('id', $id)->update($values);
            } else {
                $id = DB::table('partner_market_quotes')->insertGetId($values + [
                    'post_id' => $post, 'transporter_profile_id' => $profile->id, 'created_at' => now(),
                ]);
            }
            return response()->json(['status' => true, 'data' => ['id' => $id]], 201);
        });
    }

    public function close(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        return DB::transaction(function () use ($profile, $post) {
            $listing = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
            abort_unless($listing && (int) $listing->transporter_profile_id === (int) $profile->id, 403);
            abort_if($listing->active_payment_id || $listing->status === 'payment_pending', 409, 'Payment is in progress. Cancellation is locked.');
            abort_if($listing->assigned_payment_id, 409, 'Use Payment / Refund to cancel this paid booking and refund the payer.');
            abort_unless(in_array($listing->status, ['open', 'assigned', 'cancelled'], true), 409, 'Booking cannot be cancelled at this stage.');
            DB::table('partner_market_free_cabs')->where('post_id', $post)->where('vendor_id', $profile->id)->update(['is_free' => false, 'updated_at' => now()]);
            DB::table('partner_market_posts')->where('id', $post)->update(['status' => 'cancelled', 'updated_at' => now()]);
            DB::table('partner_market_quotes')->where('post_id', $post)->where('status', 'pending')->update(['status' => 'rejected', 'updated_at' => now()]);
            return response()->json(['status' => true, 'message' => 'Booking cancelled.']);
        }, 3);
    }
    // Private conversation between post owner and a specific interested vendor.
    public function messages(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        $input = $request->validate(['peer_vendor_id' => ['required', 'integer', 'min:1']]);
        $listing = DB::table('partner_market_posts')->where('id', $post)->first();
        abort_unless($listing, 404);
        \App\Services\PartnerMarketplaceAlerts::gate($listing, (int) $profile->id);
        $owner = (int) $listing->transporter_profile_id;
        $self = (int) $profile->id;
        $peer = (int) $input['peer_vendor_id'];
        abort_unless($self === $owner || $peer === $owner, 403);
        abort_if($self === $peer, 422, 'Cannot chat with yourself.');
        $partner = DB::table('fleet_transporter_profiles')->where('id', $peer)->exists();
        abort_unless($partner, 404, 'Conversation vendor not found.');
        $items = DB::table('partner_market_messages')->where('post_id', $post)
            ->where(function ($q) use ($self, $peer) {
                $q->where(function ($q) use ($self, $peer) { $q->where('sender_vendor_id', $self)->where('recipient_vendor_id', $peer); })
                  ->orWhere(function ($q) use ($self, $peer) { $q->where('sender_vendor_id', $peer)->where('recipient_vendor_id', $self); });
            })->orderByDesc('id')->limit(100)->get()->reverse()->values();
        $threadPeer = $self === $owner ? $peer : $self;
        $admin = DB::table('partner_market_admin_messages')->where('post_id', $post)->where('peer_vendor_id', $threadPeer)
            ->select('id as admin_message_id', DB::raw('NULL as id'), DB::raw('NULL as sender_vendor_id'), 'admin_name as author_name', 'message', 'created_at', DB::raw("'admin' as author_type"))
            ->orderByDesc('admin_message_id')->limit(100)->get();
        \App\Services\PartnerMarketplaceFleet::enrich($items);
        $items = $items->concat($admin)->sortBy('created_at')->take(-100)->values();
        return response()->json(['status' => true, 'data' => ['items' => $items]]);
    }

    public function sendMessage(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        $input = $request->validate([
            'recipient_vendor_id' => ['required', 'integer', 'min:1'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $listing = DB::table('partner_market_posts')->where('id', $post)->first();
        abort_unless($listing, 404, 'Listing not found.');
        \App\Services\PartnerMarketplaceAlerts::gate($listing, (int) $profile->id);
        $self = (int) $profile->id;
        $peer = (int) $input['recipient_vendor_id'];
        $owner = (int) $listing->transporter_profile_id;
        abort_if($self === $peer, 422, 'Cannot message yourself.');
        abort_unless($self === $owner || $peer === $owner, 403);
        if (!in_array($listing->status, ['open', 'payment_pending'], true)) {
            $receiver = $self === $owner ? $peer : $self;
            $selected = DB::table('partner_market_quotes')->where('post_id', $post)
                ->where('transporter_profile_id', $receiver)->where('status', 'accepted')->exists();
            $cancelled = DB::table('partner_market_secure_payments')->where('post_id', $post)
                ->where('id', $listing->assigned_payment_id)->whereIn('status', ['cancelled', 'refund_pending', 'refunded'])->exists();
            abort_unless($selected && !$cancelled, 409, 'Conversation is read-only after closing or cancellation.');
        }
        abort_if(trim($input['message']) === '', 422, 'Message cannot be blank.');
        $partner = DB::table('fleet_transporter_profiles')->where('id', $peer)
            ->whereNull('deleted_at')->where('status', true)->where('verification_status', 'verified')->exists();
        abort_unless($partner, 404, 'Vendor unavailable.');
        $id = DB::table('partner_market_messages')->insertGetId([
            'post_id' => $post, 'sender_vendor_id' => $self, 'recipient_vendor_id' => $peer,
            'message' => trim($input['message']), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['status' => true, 'data' => ['id' => $id]], 201);
    }

}
