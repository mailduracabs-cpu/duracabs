<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
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
            ->select('p.*');
        if (!empty($data['kind'])) $query->where('p.kind', $data['kind']);
        if (!empty($data['mine'])) $query->where('p.transporter_profile_id', $profile->id);
        else $query->where('p.status', 'open');
        $page = $query->orderByDesc('p.created_at')->paginate(20);
        return response()->json(['status' => true, 'data' => [
            'items' => $page->items(), 'page' => $page->currentPage(),
            'last_page' => $page->lastPage(), 'total' => $page->total(),
        ]]);
    }

    public function store(Request $request)
    {
        $profile = $this->vendor($request);
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
        if ($input['kind'] === 'booking') {
            if (!isset($input['asking_amount'])) throw ValidationException::withMessages(['asking_amount' => 'Booking amount required.']);
            if (!isset($input['vendor_commission'])) throw ValidationException::withMessages(['vendor_commission' => 'Commission required.']);
            if ((float) $input['vendor_commission'] > (float) $input['asking_amount']) throw ValidationException::withMessages(['vendor_commission' => 'Commission cannot exceed booking amount.']);
            if (!empty($input['secure_booking']) && (float) $input['vendor_commission'] <= 0) throw ValidationException::withMessages(['vendor_commission' => 'Secure booking requires positive commission.']);
        }
        $commissionPaise = (int) round(((float) ($input['vendor_commission'] ?? 0)) * 100);
        $feePaise = intdiv($commissionPaise * 10 + 50, 100);
        $id = DB::table('partner_market_posts')->insertGetId([
            'transporter_profile_id' => $profile->id,
            'kind' => $input['kind'], 'trip_type' => $input['kind'] === 'booking' ? $input['trip_type'] : null,
            'vehicle_class' => $input['vehicle_class'], 'pickup_city' => $input['pickup_city'],
            'drop_city' => $input['drop_city'] ?? null, 'pickup_at' => $input['pickup_at'],
            'return_at' => $input['return_at'] ?? null, 'asking_amount' => $input['asking_amount'] ?? null,
            'pricing_mode' => $input['pricing_mode'] ?? 'quote', 'negotiable' => $input['negotiable'] ?? true,
            'inclusions' => json_encode($input['inclusions'] ?? [], JSON_THROW_ON_ERROR),
            'notes' => $input['notes'] ?? null,
            'vendor_commission' => $commissionPaise / 100, 'service_fee' => $feePaise / 100,
            'vendor_payout' => ($commissionPaise - $feePaise) / 100,
            'secure_booking' => (bool) ($input['secure_booking'] ?? false),
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['status' => true, 'data' => ['id' => $id]], 201);
    }

    public function quote(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        $input = $request->validate(['amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:1000']]);
        return DB::transaction(function () use ($profile, $post, $input) {
            $listing = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
            abort_unless($listing && $listing->kind === 'booking' && $listing->status === 'open', 404, 'Open booking not found.');
            abort_if((int) $listing->transporter_profile_id === (int) $profile->id, 403, 'Cannot quote on your own booking.');
            abort_if(DB::table('partner_market_quotes')->where('post_id', $post)
                ->where('transporter_profile_id', $profile->id)->exists(), 409, 'Quote already submitted.');
            $id = DB::table('partner_market_quotes')->insertGetId([
                'post_id' => $post, 'transporter_profile_id' => $profile->id,
                'amount' => $input['amount'], 'notes' => $input['notes'] ?? null,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            return response()->json(['status' => true, 'data' => ['id' => $id]], 201);
        });
    }

    public function close(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        $updated = DB::table('partner_market_posts')->where('id', $post)
            ->where('transporter_profile_id', $profile->id)->where('status', 'open')
            ->update(['status' => 'closed', 'updated_at' => now()]);
        abort_unless($updated, 404, 'Your open listing was not found.');
        return response()->json(['status' => true, 'message' => 'Listing closed.']);
    }
    // Private conversation between post owner and a specific interested vendor.
    public function messages(Request $request, int $post)
    {
        $profile = $this->vendor($request);
        $input = $request->validate(['peer_vendor_id' => ['required', 'integer', 'min:1']]);
        $listing = DB::table('partner_market_posts')->where('id', $post)->first();
        abort_unless($listing, 404);
        $owner = (int) $listing->transporter_profile_id;
        $self = (int) $profile->id;
        $peer = (int) $input['peer_vendor_id'];
        abort_unless($self === $owner || $peer === $owner, 403);
        abort_if($self === $peer, 422, 'Cannot chat with yourself.');
        $partner = DB::table('fleet_transporter_profiles')->where('id', $peer)
            ->whereNull('deleted_at')->where('status', true)->where('verification_status', 'verified')->exists();
        abort_unless($partner, 404, 'Vendor unavailable.');
        $items = DB::table('partner_market_messages')->where('post_id', $post)
            ->where(function ($q) use ($self, $peer) {
                $q->where(function ($q) use ($self, $peer) { $q->where('sender_vendor_id', $self)->where('recipient_vendor_id', $peer); })
                  ->orWhere(function ($q) use ($self, $peer) { $q->where('sender_vendor_id', $peer)->where('recipient_vendor_id', $self); });
            })->orderByDesc('id')->limit(100)->get()->reverse()->values();
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
        abort_unless($listing && $listing->status === 'open', 404, 'Open listing not found.');
        $self = (int) $profile->id;
        $peer = (int) $input['recipient_vendor_id'];
        $owner = (int) $listing->transporter_profile_id;
        abort_if($self === $peer, 422, 'Cannot message yourself.');
        abort_unless($self === $owner || $peer === $owner, 403);
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
