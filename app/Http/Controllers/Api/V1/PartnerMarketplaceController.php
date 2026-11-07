<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
use App\Services\PartnerMarketplacePlaces;
use App\Services\PartnerMarketplaceReservations as Reservations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\PartnerMarketplaceSchedule as Schedule;
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
        Schedule::expireDue();
        $data = $request->validate([
            'kind' => ['sometimes', Rule::in(['booking', 'free_vehicle'])],
            'mine' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ] + \App\Services\PartnerMarketplaceFilters::rules());
        $query = DB::table('partner_market_posts as p')
            ->join('fleet_transporter_profiles as v', 'v.id', '=', 'p.transporter_profile_id')
            ->whereNull('v.deleted_at')->where('v.status', true)->where('v.verification_status', 'verified')
            ->leftJoin('partner_market_free_cab_duties as d','d.post_id','=','p.id')->select('p.*', 'v.company_name as posting_company','d.source as duty_source','d.received_at as duty_received_at');
        if (!empty($data['kind'])) $query->where('p.kind', $data['kind']);
        if (!empty($data['mine'])) $query->where('p.transporter_profile_id', $profile->id);
        else $query->whereIn('p.status', ['open', 'payment_pending']);
        $query->where(function ($q) use ($profile) {
            $q->where('p.transporter_profile_id', $profile->id)->orWhereNull('p.priority_until')->orWhere('p.priority_until', '<=', now())->orWhereExists(function ($a) use ($profile) {
                $a->selectRaw('1')->from('partner_market_alerts as a')->whereColumn('a.post_id', 'p.id')->whereColumn('a.post_revision', 'p.revision')->where('a.vendor_id', $profile->id)->where('a.tier', 0);
            });
        });
        // An edited booking is fresh market activity: surface it at the top again.
        // New posts already have matching created_at/updated_at values.
        \App\Services\PartnerMarketplaceFilters::apply($query, $data);
        $page = $query->orderByDesc('p.updated_at')->orderByDesc('p.id')->paginate(20);
        return response()->json(['status' => true, 'data' => [
            'items' => array_map(function ($item) use ($profile) { $item = Schedule::stamp($item); $item->reservation = Reservations::snapshot($item, (int) $profile->id); return $item; }, $page->items()), 'page' => $page->currentPage(),
            'filters_version' => 1, 'last_page' => $page->lastPage(), 'total' => $page->total(), 'server_time' => now()->toIso8601String(),
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
            'drop_cities' => ['required_if:kind,free_vehicle', 'array', 'min:1', 'max:20'],
            'drop_cities.*.place_id' => ['required', 'string', 'max:255', 'distinct'],
            'pickup_at' => ['required', 'date'],
            'pickup_now' => ['sometimes', 'boolean'],
            'return_at' => ['nullable', 'date'],
            'total_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'asking_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999'],
            'pricing_mode' => ['sometimes', Rule::in(['quote', 'fixed'])],
            'negotiable' => ['sometimes', 'boolean'],
            'inclusions' => ['nullable', 'array', 'max:15'],
            'inclusions.*' => ['string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'vendor_commission' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'secure_booking' => ['sometimes', 'boolean'],
            'communication_mode' => ['sometimes', Rule::in(['chat_only', 'call_chat'])],
            'availability_vehicle_id' => ['nullable', 'integer', 'min:1'],
            'availability_alerts' => ['sometimes', 'boolean'],
            'pickup_place_id' => ['nullable', 'string', 'max:255'],
            'drop_place_id' => ['nullable', 'string', 'max:255'],
        ]);
        $input = \App\Services\PartnerMarketplacePostPolicy::prepare($input, $existing);
        if ($input['kind'] === 'free_vehicle') {
            $input['drop_cities'] = array_map(function ($city) {
                $place = PartnerMarketplacePlaces::details($city['place_id']);
                return ['place_id' => $city['place_id'], 'label' => $place['label']];
            }, $input['drop_cities']);
            $input['pricing_mode'] = 'quote'; $input['asking_amount'] = null;
            $input['vendor_commission'] = 0; $input['secure_booking'] = false;
            $input['notes'] = null; $input['inclusions'] = [];
        }
        if (!empty($input['pickup_now'])) $input['pickup_at'] = now()->toIso8601String();
        if (Schedule::pastMinute($input['pickup_at'])) throw ValidationException::withMessages(['pickup_at' => 'Select the current minute or a future pickup time.']);
        if ($input['kind'] === 'booking' && (empty($input['trip_type']) || empty($input['drop_city']))) {
            throw ValidationException::withMessages(['trip_type' => 'Booking requires trip type and drop city.']);
        }
        if ($input['kind'] === 'booking' && ($input['trip_type'] ?? null) === 'round_trip') {
            try {
                $days = isset($input['total_days']) ? (int) $input['total_days'] : (!empty($input['return_at']) ? Schedule::days($input['pickup_at'], $input['return_at']) : 0);
                $input['return_at'] = Schedule::returnAt($input['pickup_at'], $days); $input['total_days'] = $days;
            } catch (\InvalidArgumentException $e) { throw ValidationException::withMessages(['total_days' => $e->getMessage()]); }
        } else { $input['total_days'] = null; $input['return_at'] = null; }
        if (($input['pricing_mode'] ?? 'quote') === 'fixed' && empty($input['asking_amount'])) {
            throw ValidationException::withMessages(['asking_amount' => 'Fixed price requires an amount.']);
        }
        if ($input['kind'] === 'booking' && ($input['pricing_mode'] ?? 'quote') === 'quote') {
            // Posting fare is visible; commission is set only by an authenticated owner request.
            if (!isset($input['asking_amount'])) throw ValidationException::withMessages(['asking_amount' => 'Booking amount required.']);
            $input['vendor_commission'] = 0;
            $input['secure_booking'] = false; $input['negotiable'] = true;
        } elseif ($input['kind'] === 'booking') {
            if (!isset($input['asking_amount'])) throw ValidationException::withMessages(['asking_amount' => 'Booking amount required.']);
            if (!isset($input['vendor_commission'])) throw ValidationException::withMessages(['vendor_commission' => 'Commission required.']);
            if ((float) $input['vendor_commission'] > (float) $input['asking_amount']) throw ValidationException::withMessages(['vendor_commission' => 'Commission cannot exceed booking amount.']);
            if (!empty($input['secure_booking'])) \App\Services\PartnerMarketplacePostPolicy::requireCommission($input['vendor_commission']);
        }

        if (in_array('Inc Airport Parking', $input['inclusions'] ?? [], true) && in_array('Airport Parking Extra', $input['inclusions'] ?? [], true)) throw ValidationException::withMessages(['inclusions' => 'Choose either included airport parking or airport parking extra.']);

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
        // Re-check after Google/API processing. Explicit Now uses the server clock at commit.
        if (!empty($input['pickup_now'])) $input['pickup_at'] = now()->toIso8601String();
        if (Schedule::pastMinute($input['pickup_at'])) throw ValidationException::withMessages(['pickup_at' => 'This pickup minute has passed. Select Now or a current/future time.']);
        if ($input['kind'] === 'booking' && ($input['trip_type'] ?? '') === 'round_trip') $input['return_at'] = Schedule::returnAt($input['pickup_at'], (int) $input['total_days']);
        $commissionPaise = (int) round(((float) ($input['vendor_commission'] ?? 0)) * 100);
        $feePaise = intdiv($commissionPaise * 10 + 50, 100);
        return [
            'pickup_latitude' => $input['_pickup_latitude'] ?? null, 'pickup_longitude' => $input['_pickup_longitude'] ?? null,
            'priority_until' => $input['kind'] === 'booking' && isset($input['_pickup_latitude'], $input['_pickup_longitude']) ? now()->addSeconds(max(0, (int) config('partner_market.priority_seconds', 60))) : null,
            'availability_vehicle_id' => $input['kind'] === 'free_vehicle' ? ($input['availability_vehicle_id'] ?? null) : null,
            'drop_cities' => $input['kind'] === 'free_vehicle' ? json_encode($input['drop_cities'], JSON_THROW_ON_ERROR) : null,
            'kind' => $input['kind'], 'trip_type' => $input['kind'] === 'booking' ? $input['trip_type'] : null,
            'vehicle_class' => $input['vehicle_class'], 'pickup_city' => $input['pickup_city'],
            'drop_city' => $input['kind'] === 'booking' ? $input['drop_city'] : null,
            'pickup_at' => \Carbon\Carbon::parse($input['pickup_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'total_days' => $input['total_days'] ?? null,
            'return_at' => $input['kind'] === 'booking' && ($input['trip_type'] ?? null) === 'round_trip'
                ? \Carbon\Carbon::parse($input['return_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') : null, 'asking_amount' => $input['asking_amount'] ?? null,
            'pricing_mode' => $input['pricing_mode'] ?? 'quote', 'negotiable' => $input['negotiable'] ?? true,
            'inclusions' => json_encode($input['inclusions'] ?? [], JSON_THROW_ON_ERROR),
            'notes' => $input['notes'] ?? null,
            'communication_mode' => $input['communication_mode'] ?? 'chat_only',
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
                DB::table('partner_market_free_cabs')->updateOrInsert(['vehicle_id' => $vehicle->id], ['vendor_id' => $profile->id, 'post_id' => $id, 'city' => $input['pickup_city'], 'drop_cities' => $values['drop_cities'],
                    'latitude' => $input['_pickup_latitude'], 'longitude' => $input['_pickup_longitude'], 'is_free' => true,
                    'until_duty' => true, 'available_from' => \Carbon\Carbon::parse($values['pickup_at']), 'available_until' => \Carbon\Carbon::parse($values['pickup_at'])->addHours(12), 'created_at' => now(), 'updated_at' => now()]);
                DB::table('partner_market_presence')->insertOrIgnore(['vendor_id' => $profile->id, 'app_alerts' => (bool) ($input['availability_alerts'] ?? true), 'created_at' => now(), 'updated_at' => now()]);
                if (!empty($input['availability_alerts'])) DB::table('partner_market_presence')->where('vendor_id', $profile->id)->where('app_alerts', false)->update(['app_alerts' => true, 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);
            }
            return $id;
        }, 3);
        if ($input['kind'] === 'booking') {
            \App\Services\PartnerMarketplaceAlerts::safePlan($id);
            \App\Services\PartnerMarketplaceWhatsAppGroups::queue($id, 'new');
        }
        return response()->json(['status' => true, 'data' => ['id' => $id]], 201);
    }

    public function places(Request $request)
    {
        $this->vendor($request);
        $input = $request->validate(['keyword' => ['required', 'string', 'min:2', 'max:100']]);
        return response()->json(['status' => true, 'data' => ['items' => PartnerMarketplacePlaces::suggestions(trim($input['keyword']))]]);
    }

    /**
     * Recent, privacy-safe market guidance for the booking form.
     * Only paid/assigned deals for the exact Google route, trip and vehicle are used.
     */
    public function priceEstimate(Request $request)
    {
        $this->vendor($request);
        $input = $request->validate([
            'pickup_place_id' => ['required', 'string', 'max:255'],
            'drop_place_id' => ['required', 'string', 'max:255'],
            'trip_type' => ['required', Rule::in(['one_way', 'round_trip'])],
            'vehicle_class' => ['required', 'string', 'max:80'],
        ]);

        $base = DB::table('partner_market_posts')
            ->where('kind', 'booking')
            ->whereIn('status', ['assigned', 'completed'])
            ->whereNotNull('assigned_payment_id')
            ->where('pickup_place_id', $input['pickup_place_id'])
            ->where('drop_place_id', $input['drop_place_id'])
            ->where('trip_type', $input['trip_type'])
            ->whereRaw('LOWER(TRIM(vehicle_class)) = ?', [mb_strtolower(trim($input['vehicle_class']))])
            ->whereNotNull('asking_amount')
            ->where('asking_amount', '>', 0);

        $recent = (clone $base)->where('updated_at', '>=', now()->subHours(6))
            ->orderByDesc('updated_at')->limit(20)->pluck('asking_amount');
        $amounts = $recent;
        $window = 'last_6_hours';

        // A quiet route still gets guidance from its latest completed market history.
        if ($amounts->count() < 3) {
            $amounts = (clone $base)->where('updated_at', '>=', now()->subDays(30))
                ->orderByDesc('updated_at')->limit(20)->pluck('asking_amount');
            $window = 'latest_30_days';
        }

        $values = $amounts->map(fn ($amount) => (float) $amount)->filter(fn ($amount) => $amount > 0)->sort()->values();
        // Fewer samples can mislead vendors; the app hides the box in this case.
        if ($values->count() < 3) {
            return response()->json(['status' => true, 'data' => ['visible' => false, 'sample_count' => $values->count()]]);
        }

        // Trim one extreme at each side when enough observations exist.
        $clean = $values->count() >= 10 ? $values->slice(1, $values->count() - 2)->values() : $values;
        $middle = intdiv($clean->count(), 2);
        $median = $clean->count() % 2
            ? (float) $clean[$middle]
            : ((float) $clean[$middle - 1] + (float) $clean[$middle]) / 2;

        return response()->json(['status' => true, 'data' => [
            'visible' => true,
            'recommended_amount' => (int) (round($median / 50) * 50),
            'average_amount' => (int) round($clean->avg()),
            'minimum_amount' => (int) round($clean->min()),
            'maximum_amount' => (int) round($clean->max()),
            'sample_count' => $values->count(),
            'window' => $window,
        ]]);
    }

    public function edit(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        Schedule::expireDue($post);
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
            Schedule::assertAccepting($row);
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
            // The incremented revision makes this a fresh alert campaign. This reuses
            // exactly the same location/priority notification flow as a new booking.
            if ($input['kind'] === 'booking') DB::afterCommit(function () use ($post) {
                \App\Services\PartnerMarketplaceAlerts::safePlan($post);
                \App\Services\PartnerMarketplaceWhatsAppGroups::queue($post, 'updated');
            });
            return response()->json(['status' => true, 'data' => ['id' => $post, 'revision' => (int) $revision + 1],
                'message' => 'Booking updated and sent again as a fresh booking alert. Existing pending quotes require fresh confirmation.']);
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
            Schedule::assertAccepting($listing);
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
            abort_if($listing->kind === 'free_vehicle', 422, 'Use Duty mil gayi, select Taxi Sathi or Other, and submit to end free availability.');
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
        Schedule::expireDue($post);
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
        Schedule::expireDue($post);
        $listing = DB::table('partner_market_posts')->where('id', $post)->first();
        abort_unless($listing, 404, 'Listing not found.');
        \App\Services\PartnerMarketplaceAlerts::gate($listing, (int) $profile->id);
        abort_if($listing->status === 'expired', 409, 'Expired bookings are read-only.');
        if ($listing->status === 'open') Schedule::assertAccepting($listing);
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
        \App\Services\PartnerMarketSavedAlerts::chat((int) $id, $peer, $post);
        return response()->json(['status' => true, 'data' => ['id' => $id]], 201);
    }

}
