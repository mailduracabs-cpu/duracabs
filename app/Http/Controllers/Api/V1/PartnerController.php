<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\AppMedia;
use App\Models\MediaUsage;
use App\Enums\MediaType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\OtpService;
use App\Services\PartnerBookingOfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

class PartnerController extends Controller
{
    private const PURPOSE = 'partner_login';
    private const ABILITY = 'partner:access';

    private function eligible(User $user): TransporterProfile
    {
        abort_unless($user->canUseTransporterLogin(), 403, 'Active Transporter account required. Contact DuraCabs admin.');
        $profiles = TransporterProfile::query()->where('user_id', $user->getKey())->limit(2)->get();
        abort_unless($profiles->count() === 1, 403, 'A unique partner profile must be linked by admin.');
        $profile = $profiles->first();
        abort_unless($profile->isActive(), 403, 'Partner account is inactive. Contact admin.');
        abort_unless(in_array($profile->partner_type, ['vendor', 'host', 'both'], true), 403, 'Partner role is not configured.');
        return $profile;
    }

    private function mobile(Request $request): string
    {
        // Accept national number, 91 prefix or +91 prefix, then validate strictly.
        $raw = trim((string) $request->input('mobile', ''));
        if (preg_match('/^(?:\+91|91)([6-9]\d{9})$/', $raw, $m)) {
            $raw = $m[1];
        }
        $request->merge(['mobile' => $raw]);
        $request->validate(['mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/']]);
        return $raw;
    }

    private function account(string $mobile): User
    {
        $users = User::query()->where('mobile', $mobile)->limit(2)->get();
        abort_unless($users->count() === 1, 403, 'Partner login unavailable. Contact admin to check your account.');
        $user = $users->first();
        $this->eligible($user);
        return $user;
    }

    private function limit(string $action, string $mobile, int $maximum): void
    {
        // Account budget persists across resends and IP changes.
        $key = 'partner:' . $action . ':' . hash('sha256', $mobile);
        abort_if(RateLimiter::tooManyAttempts($key, $maximum), 429,
            'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.');
        RateLimiter::hit($key, 900);
    }

    public function sendOtp(Request $request, OtpService $otp)
    {
        $mobile = $this->mobile($request);
        $this->limit('send', $mobile, 5);
        $user = $this->account($mobile);
        // Shared lock also prevents concurrent verify/resend on this account.
        $lock = Cache::lock('partner:otp-lock:' . hash('sha256', $mobile), 120);
        abort_unless($lock->get(), 429, 'An OTP request is already processing. Try again shortly.');
        try {
            $result = $otp->sendPurposeOtp($mobile, self::PURPOSE, ['user_id' => $user->getKey()]);
            if (!($result['status'] ?? false)) {
                return response()->json(['status' => false, 'message' => $result['message'] ?? 'OTP delivery failed.'], 422);
            }
            return response()->json(['status' => true, 'message' => $result['message'], 'data' => [
                'verification_id' => $result['verification_id'],
                'expires_in' => $result['expires_in'],
                'resend_after' => $result['resend_after'],
            ]]);
        } finally {
            $lock->release();
        }
    }

    public function verifyOtp(Request $request, OtpService $otp)
    {
        $mobile = $this->mobile($request);
        $this->limit('verify', $mobile, 15);
        $request->validate(['otp' => ['required', 'string', 'regex:/^\d{4}$/'], 'verification_id' => ['required', 'uuid']]);
        $user = $this->account($mobile);
        $lock = Cache::lock('partner:otp-lock:' . hash('sha256', $mobile), 120);
        abort_unless($lock->get(), 429, 'An OTP request is already processing. Try again shortly.');
        try {
            $result = $otp->verifyPurposeOtp($mobile, self::PURPOSE,
                (string) $request->input('otp'), (string) $request->input('verification_id'));
            if (!($result['status'] ?? false)) {
                return response()->json(['status' => false, 'message' => $result['message'] ?? 'Invalid OTP.'], 422);
            }
            abort_unless((int) ($result['data']['payload']['user_id'] ?? 0) === (int) $user->getKey(), 403, 'Account changed. Request a new OTP.');
            $user->refresh();
            $profile = $this->eligible($user);
            $token = $user->createToken('duracabs_partner_app', [self::ABILITY]);
            // Requires standard modern Sanctum personal_access_tokens.expires_at.
            $token->accessToken->forceFill(['expires_at' => now()->addDays(30)])->save();
            return response()->json(['status' => true, 'data' => [
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
                'partner' => $this->profileData($profile),
            ]]);
        } finally {
            $lock->release();
        }
    }

    protected function authenticatedProfile(Request $request): TransporterProfile
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        // Require our scoped personal token; customer tokens and web sessions fail.
        abort_unless($token instanceof PersonalAccessToken
            && $token->name === 'duracabs_partner_app'
            && in_array(self::ABILITY, $token->abilities ?? [], true), 403, 'Partner login required.');
        return $this->eligible($user);
    }

    private function profileData(TransporterProfile $profile): array
    {
        return [
            'id' => $profile->getKey(),
            'company_name' => $profile->company_name,
            'contact_person' => $profile->contact_person,
            'mobile' => $profile->mobile,
            'city' => $profile->city,
            'partner_type' => $profile->partner_type,
            'verification_status' => $profile->verification_status,
            'roles' => array_values(array_filter([
                $profile->isVendor() ? 'vendor' : null,
                $profile->isHost() ? 'host' : null,
            ])),
        ];
    }

    public function me(Request $request)
    {
        return response()->json(['status' => true, 'data' => $this->profileData($this->authenticatedProfile($request))]);
    }

    protected function context(Request $request): array
    {
        $profile = $this->authenticatedProfile($request);
        $request->validate([
            'role' => ['required', 'in:vendor,host'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $role = (string) $request->input('role');
        abort_unless($role === 'vendor' ? $profile->isVendor() : $profile->isHost(), 403, 'This service is not enabled for your account.');
        abort_unless($profile->isVerified(), 403, 'Partner verification is required. Contact admin.');
        return [$profile, $role];
    }

    private function bookingQuery(TransporterProfile $profile, string $role)
    {
        // Read persisted rows without loading the damaged SelfDriveBooking model.
        // No request-supplied user/profile IDs are used for ownership.
        $table = $role === 'vendor' ? 'orders' : 'self_drive_bookings';
        $query = DB::table($table)->where(
            $role === 'vendor' ? 'transporter_id' : 'transporter_profile_id',
            $role === 'vendor' ? $profile->user_id : $profile->getKey()
        );
        if ($role === 'vendor') {
            abort_unless(Schema::hasColumn('orders', 'partner_offer_revision'), 503,
                'Run the Partner offer migration included in SETUP.md.');
            // Existing paid assignments remain visible. An explicit Admin offer may
            // also expose a pending cash/manual booking; ordinary unpaid API rows do not.
            $query->where(function ($q): void {
                $q->whereIn('payment_status', ['paid', 'partial'])->orWhere(function ($offer): void {
                    $offer->whereIn('payment_status', ['pending', 'unpaid'])
                        ->where('partner_offer_amount', '>', 0)
                        ->whereIn('partner_offer_fare_type', ['all_inclusive', 'all_exclusive'])
                        ->whereNotNull('partner_offer_revision')->whereNotNull('partner_offer_assigned_by')
                        ->whereIn('partner_offer_status', ['pending', 'accepted']);
                });
            })->where(function ($q): void {
                $q->whereNull('ride_type')->orWhereNotIn('ride_type', ['self_drive', 'bike_rental']);
            });
        } else {
            $query->whereIn('payment_status', ['paid', 'partial']);
        }
        if ($role === 'host') {
            $query->where('paid_amount', '>', 0)->where('booking_type', 'car');
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        return $query;
    }

    private function vehicleQuery(TransporterProfile $profile, string $role)
    {
        abort_unless(Schema::hasColumn('vehicles', 'partner_removed_at'), 503,
            'Run the partner_removed_at vehicle migration included in SETUP.md.');
        // Explicit profile ownership takes precedence over legacy user_id.
        return Vehicle::query()->where(function ($q) use ($profile): void {
            $q->where('transporter_profile_id', $profile->getKey())
                ->orWhere(function ($legacy) use ($profile): void {
                    $legacy->whereNull('transporter_profile_id')->where('user_id', $profile->user_id);
                });
        })->where('service_type', $role === 'vendor' ? 'taxi' : 'self_drive')
            ->whereNull('partner_removed_at');
    }

    public function dashboard(Request $request)
    {
        [$profile, $role] = $this->context($request);
        return response()->json(['status' => true, 'data' => [
            'role' => $role,
            'bookings_total' => $this->bookingQuery($profile, $role)->count(),
            'vehicles_total' => $this->vehicleQuery($profile, $role)->count(),
            'document_reminders' => $this->reminderData($profile, $role),
        ]]);
    }

    public function bookings(Request $request)
    {
        [$profile, $role] = $this->context($request);
        $query = $this->bookingQuery($profile, $role);
        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where('booking_no', 'like', '%' . $search . '%');
        }
        $page = $query->orderByDesc('id')->paginate(20);
        $rows = collect($page->items());
        $customerKey = $role === 'vendor' ? 'user_id' : 'customer_id';
        $customers = User::query()->whereIn('id', $rows->pluck($customerKey)->filter())
            ->get(['id', 'name', 'mobile'])->keyBy('id');
        $vehicles = Vehicle::query()->whereIn('id', $rows->pluck('vehicle_id')->filter())
            ->with('frontMedia')->get()->keyBy('id');
        $drivers = $role === 'vendor' ? User::query()->whereIn('id', $rows->pluck('driver_id')->filter())
            ->get(['id', 'name', 'mobile'])->keyBy('id') : collect();
        $items = $rows->map(function ($row) use ($role, $customerKey, $customers, $vehicles, $drivers): array {
            $customer = $customers->get($row->{$customerKey});
            $vehicle = $vehicles->get($row->vehicle_id ?? null);
            // Persisted status is authoritative; dates never imply running.
            $status = trim((string) ($row->status ?? ''));
            $workflow = trim((string) ($row->booking_status ?? ''));
            $terminal = ['cancelled', 'canceled', 'rejected', 'completed', 'closed'];
            if (in_array(strtolower($status), $terminal, true)) {
                $displayStatus = $status;
            } elseif (in_array(strtolower($workflow), $terminal, true)) {
                $displayStatus = $workflow;
            } elseif ($role === 'host' && strtolower($workflow) === 'return_pending') {
                $displayStatus = 'return_pending';
            } else {
                $displayStatus = $status !== '' ? $status : ($workflow ?: 'unknown');
            }
            return array_merge([
                'id' => $row->id,
                'driver_id' => $role === 'vendor' ? ($row->driver_id ?? null) : null,
                'driver_name' => $role === 'vendor' ? $drivers->get($row->driver_id ?? null)?->name : null,
                'driver_mobile' => $role === 'vendor' ? $drivers->get($row->driver_id ?? null)?->mobile : null,
                'can_assign' => $role === 'vendor' && PartnerDispatchController::assignable($row),
                'booking_no' => $row->booking_no ?: (string) $row->id,
                'status' => $displayStatus,
                'saved_status' => $status,
                'booking_status' => $workflow,
                'customer_name' => $customer?->name,
                'customer_mobile' => $customer?->mobile,
                'vehicle_name' => $vehicle ? trim($vehicle->car_company_name . ' ' . $vehicle->model_name) : ($row->productName ?? null),
                'vehicle_number' => $vehicle?->vehicle_number,
                'image_url' => $vehicle ? $this->photoUrl($vehicle, 'front') : null,
                'pickup' => $role === 'vendor' ? (trim((string) ($row->booking_from ?? '')) ?: ($row->cityFrom ?? null)) : ($row->pickup_location ?? null),
                'drop' => $role === 'vendor' ? (trim((string) ($row->booking_to ?? '')) ?: ($row->cityTo ?? null)) : ($row->pickup_address ?? null),
                'start' => $role === 'vendor' ? trim(($row->date ?? '') . ' ' . ($row->time ?? '')) : ($row->start_datetime ?? null),
                'end' => $role === 'vendor' ? ($row->dateTo ?? null) : ($row->end_datetime ?? null),
                'city_from' => $role === 'vendor' ? (trim((string) ($row->cityFrom ?? '')) ?: ($row->booking_from ?? null)) : ($row->pickup_location ?? null),
                'city_to' => $role === 'vendor' ? (trim((string) ($row->cityTo ?? '')) ?: ($row->booking_to ?? null)) : null,
                'booked_category' => $role === 'vendor' ? ($row->taxi_type ?? null) : ($vehicle?->car_classification ?? null),
                'assigned_vehicle_category' => $vehicle?->car_classification,
                'start_date' => $role === 'vendor' ? ($row->date ?? null) : (empty($row->start_datetime) ? null : substr($row->start_datetime, 0, 10)),
                'start_time' => $role === 'vendor' ? ($row->time ?? null) : (empty($row->start_datetime) ? null : substr($row->start_datetime, 11)),
                'end_date' => $role === 'vendor' ? ($row->dateTo ?? null) : (empty($row->end_datetime) ? null : substr($row->end_datetime, 0, 10)),
                'end_time' => $role === 'vendor' ? ($row->endTime ?? null) : (empty($row->end_datetime) ? null : substr($row->end_datetime, 11)),
                'service' => $role === 'vendor' ? ($row->ride_type ?? 'taxi') : 'self_drive',
                // Stored totals only. Never recalculate tax, balance or refunds here.
                'total_amount' => $role === 'vendor' ? ($row->grand_total ?? null) : ($row->total_amount ?? null),
                'payment_status' => $row->payment_status ?? null,
                'paid_amount' => $role === 'host' ? ($row->paid_amount ?? null) : null,
                'security_deposit' => $role === 'host' ? ($row->security_deposit ?? null) : null,
                'security_refund' => $role === 'host' ? \App\Services\SelfDriveSecurityRefundService::summary($row) : null,
                'final_amount' => $role === 'host' ? ($row->final_amount ?? null) : null,
            ], $role === 'vendor' ? PartnerBookingOfferService::view($row) : []);
        });
        return response()->json(['status' => true, 'data' => [
            'items' => $items->values(), 'page' => $page->currentPage(),
            'last_page' => $page->lastPage(), 'total' => $page->total(),
        ]]);
    }

    public function acceptBooking(Request $request, int $booking)
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'vendor', 422, 'This offer is for a taxi Vendor.');
        $input = $request->validate(['revision' => ['required', 'uuid']]);
        $result = DB::transaction(function () use ($profile, $booking, $input): array {
            $row = $this->bookingQuery($profile, 'vendor')->where('id', $booking)->lockForUpdate()->first();
            abort_unless($row, 404, 'Assigned booking not found. Check the Vendor account and payment status with Admin.');
            abort_unless(PartnerBookingOfferService::complete($row), 409, 'Admin must set your amount and fare terms first.');
            abort_unless(hash_equals((string) $row->partner_offer_revision, $input['revision']), 409,
                'Admin changed this offer. Refresh and review the latest amount and terms before accepting.');
            abort_unless(PartnerBookingOfferService::open($row), 409, 'This booking can no longer be accepted.');
            if (PartnerBookingOfferService::accepted($row)) return PartnerBookingOfferService::view($row);
            abort_unless($row->partner_offer_status === 'pending', 409, 'This offer is no longer pending.');
            // Narrow update: customer status, payment and fare remain untouched.
            DB::table('orders')->where('id', $row->id)->where('transporter_id', $profile->user_id)->update([
                'partner_offer_status' => 'accepted', 'partner_offer_accepted_at' => now(),
                'partner_offer_accepted_by' => $profile->user_id, 'updated_at' => now(),
            ]);
            return PartnerBookingOfferService::view(DB::table('orders')->where('id', $row->id)->first());
        });
        return response()->json(['status' => true, 'message' => 'Booking accepted. You can now assign your driver and taxi.', 'data' => $result]);
    }


    public function createVehicle(Request $request)
    {
        [$profile, $role] = $this->context($request);
        $request->merge(['vehicle_number' => strtoupper(preg_replace('/[\s-]+/', '', (string) $request->input('vehicle_number')))]);
        $input = $request->validate([
            'vehicle_number' => ['required', 'regex:/^[A-Z0-9]{5,20}$/'],
            'car_company_name' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:100'],
            'owner_name' => ['required', 'string', 'max:150'],
            'car_classification' => [$role === 'vendor' ? 'required' : 'nullable', Rule::in($this->categoryNames())],
            'fuel_type' => ['required', 'in:petrol,diesel,cng,electric,hybrid'],
            'transmission' => ['required', 'in:manual,automatic'],
            'seats' => ['required', 'integer', 'min:1', 'max:20'],
            'manufacture_year' => ['required', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'daily_price' => ['nullable', 'numeric', 'min:0', 'max:500000', 'decimal:0,2'],
            'hourly_price' => ['nullable', 'numeric', 'min:0', 'max:500000', 'decimal:0,2'],
        ]);
        if ($role === 'host') {
            abort_unless((float) ($input['daily_price'] ?? 0) > 0 || (float) ($input['hourly_price'] ?? 0) > 0,
                422, 'Enter a positive daily or hourly rental price.');
        } else {
            unset($input['daily_price'], $input['hourly_price']);
        }
        // Serialize partner submissions, including submissions from other accounts.
        $lock = Cache::lock('partner_vehicle_registration', 120);
        abort_unless($lock->get(), 409, 'Another vehicle is being submitted. Please retry.');
        try {
            $vehicle = DB::transaction(function () use ($input, $profile, $role) {
                abort_if(Vehicle::query()->whereRaw("UPPER(REPLACE(REPLACE(vehicle_number, ' ', ''), '-', '')) = ?",
                    [$input['vehicle_number']])->exists(), 422,
                    'This registration is already recorded. Contact admin to restore or transfer the existing vehicle.');
                $record = new Vehicle($input + [
                    'user_id' => $profile->user_id, 'transporter_profile_id' => $profile->id,
                    'service_type' => $role === 'vendor' ? 'taxi' : 'self_drive', 'vehicle_type' => 'car',
                    'verification_status' => $role === 'vendor' ? 'approved' : 'pending',
                    'is_verified' => $role === 'vendor', 'is_active' => true, 'is_live' => $role === 'vendor',
                    'minimum_booking_hours' => 1,
                ]);
                // Local app submissions must not trigger the model's WhatsApp hooks.
                // All required car defaults and ownership are supplied above.
                $record->saveQuietly();
                return $record;
            });
        } finally { $lock->release(); }
        return response()->json(['status' => true, 'message' => $role === 'vendor' ? 'Taxi saved and approved. Upload its photos and documents next.' : 'Vehicle saved offline for admin review. Upload its photos and documents next.',
            'data' => ['id' => $vehicle->id]], 201);
    }


    protected function categoryNames(): array
    {
        $names = ['Hatchback', 'Sedan', 'SUV', 'MUV', 'Luxury'];
        if (Schema::hasTable('categories')) {
            $query = DB::table('categories')->whereNotNull('name');
            if (Schema::hasColumn('categories', 'is_active')) $query->where('is_active', true);
            if (Schema::hasColumn('categories', 'service_group')) $query->where('service_group', 'with_driver');
            if (Schema::hasColumn('categories', 'vehicle_type')) $query->where('vehicle_type', 'car');
            $names = array_merge($names, $query->orderBy('name')->pluck('name')->all());
        }
        return array_values(array_unique(array_filter(array_map('trim', $names))));
    }
    public function vehicleCategories(Request $request)
    {
        $this->context($request);
        return response()->json(['status' => true, 'data' => ['items' => $this->categoryNames()]]);
    }
    public function updateVehicle(Request $request, int $vehicle)
    {
        [$profile, $role] = $this->context($request);
        $request->merge(['vehicle_number' => strtoupper(preg_replace('/[\s-]+/', '', (string) $request->input('vehicle_number')))]);
        $input = $request->validate([
            'vehicle_number' => ['required', 'regex:/^[A-Z0-9]{5,20}$/'],
            'car_company_name' => ['required', 'string', 'max:100'], 'model_name' => ['required', 'string', 'max:100'],
            'owner_name' => ['required', 'string', 'max:150'],
            'car_classification' => ['required', Rule::in($this->categoryNames())],
            'fuel_type' => ['required', 'in:petrol,diesel,cng,electric,hybrid'],
            'transmission' => ['required', 'in:manual,automatic'], 'seats' => ['required', 'integer', 'min:1', 'max:20'],
            'manufacture_year' => ['required', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
        ]);
        $lock = Cache::lock('partner_vehicle_registration', 120);
        abort_unless($lock->get(), 409, 'Another vehicle is being saved. Retry shortly.');
        try {
            DB::transaction(function () use ($profile, $role, $vehicle, $input): void {
                $record = $this->ownedVehicle($profile, $role, $vehicle, true);
                abort_if(Vehicle::query()->whereKeyNot($record->id)->whereRaw("UPPER(REPLACE(REPLACE(vehicle_number, ' ', ''), '-', '')) = ?",
                    [$input['vehicle_number']])->exists(), 422, 'This registration belongs to another recorded vehicle.');
                $record->fill($input);
                if ($record->isDirty()) {
                    if ($role === 'host') $record->forceFill(['verification_status' => 'pending', 'is_verified' => false, 'is_live' => false]);
                    $record->save();
                }
            });
        } finally { $lock->release(); }
        return response()->json(['status' => true, 'message' => $role === 'vendor' ? 'Taxi details saved.' : 'Vehicle details saved. Changed details require admin review before new assignments.']);
    }

    public function removeVehicle(Request $request, int $vehicle)
    {
        [$profile, $role] = $this->context($request);
        DB::transaction(function () use ($profile, $role, $vehicle): void {
            $record = $this->ownedVehicle($profile, $role, $vehicle, true);
            // Include unpaid bookings and both services. A paid-only list is not a removal guard.
            $terminal = ['cancelled', 'canceled', 'rejected', 'completed', 'closed'];
            foreach (['orders', 'self_drive_bookings'] as $table) {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'vehicle_id')) continue;
                $query = DB::table($table)->where('vehicle_id', $record->id);
                if (Schema::hasColumn($table, 'deleted_at')) $query->whereNull('deleted_at');
                foreach ($query->orderBy('id')->cursor() as $booking) {
                    $status = strtolower(trim((string) ($booking->status ?? '')));
                    $workflow = strtolower(trim((string) ($booking->booking_status ?? '')));
                    abort_unless(in_array($status, $terminal, true) || in_array($workflow, $terminal, true), 409,
                        'This vehicle has an unfinished booking. Complete or cancel it before removing the vehicle.');
                }
            }
            $record->forceFill(['partner_removed_at' => now(), 'is_active' => false, 'is_live' => false])->save();
        });
        return response()->json(['status' => true, 'message' => 'Vehicle removed from your fleet. Booking and earnings history is retained. Admin can restore it.']);
    }

    public function vehicles(Request $request)
    {
        [$profile, $role] = $this->context($request);
        $query = $this->vehicleQuery($profile, $role)->with('frontMedia');
        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('vehicle_number', 'like', '%' . $search . '%')
                    ->orWhere('model_name', 'like', '%' . $search . '%')
                    ->orWhere('car_company_name', 'like', '%' . $search . '%');
            });
        }
        $page = $query->orderByDesc('id')->paginate(20);
        $items = collect($page->items())->map(fn (Vehicle $v): array => [
            'id' => $v->id, 'name' => trim($v->car_company_name . ' ' . $v->model_name),
            'vehicle_number' => $v->vehicle_number, 'car_classification' => $v->car_classification, 'service_type' => $v->service_type,
            'image_url' => $this->photoUrl($v, 'front'), 'fuel_type' => $v->fuel_type,
            'transmission' => $v->transmission, 'seats' => $v->seats,
            'verification_status' => $v->verification_status,
            'is_active' => (bool) $v->is_active, 'is_live' => (bool) $v->is_live,
            'daily_price' => $role === 'host' ? $v->daily_price : null,
            'hourly_price' => $role === 'host' ? $v->hourly_price : null,
            'security_deposit' => $role === 'host' ? $v->security_deposit : null,
        ]);
        return response()->json(['status' => true, 'data' => [
            'items' => $items->values(), 'page' => $page->currentPage(),
            'last_page' => $page->lastPage(), 'total' => $page->total(),
        ]]);
    }

    private const ASSETS = [
        'front' => ['Front view', 'front_image', 'front_media_id', 'frontMedia', false],
        'back' => ['Back view', 'back_image', 'back_media_id', 'backMedia', false],
        'left_side' => ['Left side', 'left_side_image', 'left_side_media_id', 'leftSideMedia', false],
        'right_side' => ['Right side', 'right_side_image', 'right_side_media_id', 'rightSideMedia', false],
        'front_left' => ['Front left angle', 'front_left_image', 'front_left_media_id', 'frontLeftMedia', false],
        'front_right' => ['Front right angle', 'front_right_image', 'front_right_media_id', 'frontRightMedia', false],
        'interior' => ['Interior / dashboard', 'interior_image', 'interior_media_id', 'interiorMedia', false],
        'front_seats' => ['Front seats', 'front_seats_image', 'front_seats_media_id', 'frontSeatsMedia', false],
        'rear_seats' => ['Rear seats', 'rear_seats_image', 'rear_seats_media_id', 'rearSeatsMedia', false],
        'boot' => ['Boot', 'boot_image', 'boot_media_id', 'bootMedia', false],
        'rc' => ['Registration certificate (RC)', 'rc_image', 'rc_media_id', 'rcMedia', true],
        'insurance' => ['Insurance', 'insurance_image', 'insurance_media_id', 'insuranceMedia', true],
        'puc' => ['Pollution certificate (PUC)', 'polution_image', 'pollution_media_id', 'pollutionMedia', true],
    ];

    protected function ownedVehicle(TransporterProfile $profile, string $role, int $id, bool $lock = false): Vehicle
    {
        $query = $this->vehicleQuery($profile, $role)->whereKey($id);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->firstOrFail();
    }

    private function photoUrl(Vehicle $vehicle, string $slot): ?string
    {
        [$disk, $path] = $this->assetSource($vehicle, $slot);
        if ($disk === 'public' && filled($path) && !str_contains($path, '://')
            && Storage::disk('public')->exists($path)) {
            // Use this API server for local files even when APP_URL still points live.
            $storageUrl = Storage::disk('public')->url($path);
            return parse_url($storageUrl, PHP_URL_PATH) ?: $storageUrl;
        }
        $url = $vehicle->getAttribute($slot . '_image_url');
        if (!filled($url)) {
            return null;
        }
        // Relative storage paths use the API's origin in Flutter, not port 5173.
        return (string) $url;
    }

    private function normalizedLocalPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if (str_contains($path, '://')) {
            // Resolve an existing local copy; never make a remote HTTP request.
            $urlPath = (string) parse_url($path, PHP_URL_PATH);
            if (!str_starts_with($urlPath, '/storage/')) {
                return $path;
            }
            $path = substr($urlPath, strlen('/storage/'));
        } else {
            foreach (['storage/app/public/', 'public/storage/', '/storage/', 'storage/', 'public/'] as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $path = substr($path, strlen($prefix));
                    break;
                }
            }
        }
        return $path;
    }

    private function safeLocalPath(string $path): bool
    {
        return $path !== '' && !str_contains($path, '..') && !str_contains($path, '://')
            && !str_starts_with($path, '/') && !str_contains($path, ':');
    }

    private function assetSource(Vehicle $vehicle, string $slot): array
    {
        [, $legacy, , $relation] = self::ASSETS[$slot];
        $media = $vehicle->{$relation};
        $sources = [];
        if ($media instanceof AppMedia) {
            foreach ($media->allStoredPaths() as $path) {
                $sources[] = [$media->disk ?: 'public', $this->normalizedLocalPath($path),
                    $media->mime_type, $media->original_name ?: basename($path)];
            }
        }
        $legacyPath = trim((string) $vehicle->getAttribute($legacy));
        if ($legacyPath !== '') {
            $sources[] = ['public', $this->normalizedLocalPath($legacyPath), null, basename($legacyPath)];
        }
        foreach ($sources as [$disk, $path, $mime, $name]) {
            if ($this->safeLocalPath($path) && Storage::disk($disk)->exists($path)) {
                return [$disk, $path, Storage::disk($disk)->mimeType($path) ?: $mime, $name];
            }
        }
        return $sources[0] ?? ['public', '', null, ''];
    }

    private function vehicleAssetsData(Vehicle $vehicle): array
    {
        $assets = [];
        foreach (self::ASSETS as $slot => [$label, $legacy, $mediaId, $relation, $document]) {
            // Existing installations may not have all optional gallery migrations.
            $supported = Schema::hasColumn('vehicles', $legacy) && Schema::hasColumn('vehicles', $mediaId);
            [$disk, $path, $mime, $name] = $this->assetSource($vehicle, $slot);
            $assets[] = [
                'slot' => $slot, 'label' => $label, 'document' => $document,
                'supported' => $supported, 'uploaded' => filled($path),
                'available_locally' => $this->safeLocalPath($path) && Storage::disk($disk)->exists($path),
                'mime_type' => $mime, 'file_name' => $name,
                // Documents are fetched through the owner-authenticated download route.
                'image_url' => $document ? null : $this->photoUrl($vehicle, $slot),
            ];
        }
        return [
            'id' => $vehicle->id, 'name' => trim($vehicle->car_company_name . ' ' . $vehicle->model_name),
            'vehicle_number' => $vehicle->vehicle_number, 'car_classification' => $vehicle->car_classification,
            'car_company_name' => $vehicle->car_company_name, 'model_name' => $vehicle->model_name,
            'owner_name' => $vehicle->owner_name, 'manufacture_year' => $vehicle->manufacture_year,
            'service_type' => $vehicle->service_type, 'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission, 'seats' => $vehicle->seats,
            'daily_price' => $vehicle->daily_price, 'hourly_price' => $vehicle->hourly_price,
            'weekly_price' => $vehicle->weekly_price, 'monthly_price' => $vehicle->monthly_price,
            'expiry_fields_ready' => Schema::hasColumn('vehicles', 'insurance_expiry_date') && Schema::hasColumn('vehicles', 'puc_expiry_date'),
            'security_deposit' => $vehicle->security_deposit,
            'verification_status' => $vehicle->verification_status,
            'is_live' => (bool) $vehicle->is_live, 'assets' => $assets,
            'document_details' => [
                'owner_name' => $vehicle->owner_name,
                'chassis_number' => $vehicle->chassis_number,
                'engine_number' => $vehicle->engine_number,
                'insurance_number' => $vehicle->insurance_number,
                'insurance_company_name' => $vehicle->insurance_company_name,
                'insurance_expiry_date' => $vehicle->insurance_expiry_date?->format('Y-m-d'),
                'puc_expiry_date' => $vehicle->puc_expiry_date?->format('Y-m-d'),
            ],
        ];
    }

    public function vehicleAssets(Request $request, int $vehicle)
    {
        [$profile, $role] = $this->context($request);
        return response()->json(['status' => true, 'data' =>
            $this->vehicleAssetsData($this->ownedVehicle($profile, $role, $vehicle))]);
    }

    private function mediaType(bool $document, string $mime): MediaType
    {
        // Resolve the application's actual enum values instead of inventing a value.
        $names = $document && $mime === 'application/pdf' ? ['pdf', 'document', 'file'] : ['image'];
        foreach ($names as $name) {
            foreach (MediaType::cases() as $case) {
                if (strtolower($case->name) === $name || strtolower((string) $case->value) === $name) {
                    return $case;
                }
            }
        }
        abort(422, 'This file type is not configured in the media library.');
    }

    public function updateVehicleDocuments(Request $request, int $vehicle)
    {
        [$profile, $role] = $this->context($request);
        $fields = ['owner_name', 'chassis_number', 'engine_number', 'insurance_number', 'insurance_company_name'];
        $rules = array_fill_keys($fields, ['sometimes', 'nullable', 'string', 'max:150']);
        foreach (['insurance_expiry_date', 'puc_expiry_date'] as $field) {
            $rules[$field] = ['sometimes', 'nullable', 'date_format:Y-m-d'];
        }
        $input = $request->validate($rules);
        abort_if(count($input) === 0, 422, 'No document details were supplied.');
        DB::transaction(function () use ($profile, $role, $vehicle, $input): void {
            $record = $this->ownedVehicle($profile, $role, $vehicle, true);
            foreach ($input as $field => $value) {
                abort_unless(Schema::hasColumn('vehicles', $field), 422, 'This document field is not configured.');
                $record->setAttribute($field, filled($value) ? trim($value) : null);
            }
            if ($record->isDirty()) {
                if ($role === 'host') {
                    $record->verification_status = Vehicle::STATUS_PENDING;
                    $record->is_verified = false;
                    $record->is_live = false;
                }
                $record->save();
            }
        });
        return response()->json(['status' => true, 'message' => $role === 'vendor' ? 'Taxi document details saved.' : 'Document details saved. Changed details require admin review.']);
    }

    public function uploadVehicleAsset(Request $request, int $vehicle)
    {
        [$profile, $role] = $this->context($request);
        $request->validate(['slot' => ['required', Rule::in(array_keys(self::ASSETS))]]);
        $slot = (string) $request->input('slot');
        [$label, $legacy, $mediaId, , $document] = self::ASSETS[$slot];
        $this->ownedVehicle($profile, $role, $vehicle);
        abort_unless(Schema::hasColumn('vehicles', $legacy) && Schema::hasColumn('vehicles', $mediaId),
            422, 'This upload field is not configured. Ask admin to check vehicle gallery migrations.');
        $request->validate(['file' => $document
            ? ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240']
            : ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240']]);
        $file = $request->file('file');
        $mime = $file->getMimeType();
        // MIME content and server-generated extension are authoritative.
        $extension = match ($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            'application/pdf' => 'pdf', default => abort(422, 'Unsupported file content.'),
        };
        $type = $this->mediaType($document, $mime);
        $uuid = (string) Str::uuid();
        $disk = $document ? 'local' : 'public';
        $directory = 'partner-vehicles/' . $vehicle . '/' . ($document ? 'documents' : 'photos');
        $path = null;
        $image = null;
        try {
            if (!$document) {
                $dimensions = @getimagesize($file->getRealPath());
                abort_unless($dimensions && $dimensions[0] * $dimensions[1] <= 24000000,
                    422, 'Photo is too large. Use an image below 24 megapixels.');
                // Uploads still work on XAMPP without GD/WebP. Preserve a validated
                // JPG/PNG/WebP as one file; optimize when the encoder is available.
                $canOptimize = function_exists('imagecreatefromstring') && function_exists('imagewebp');
                $image = $canOptimize ? @imagecreatefromstring(file_get_contents($file->getRealPath())) : null;
                if ($image) {
                    ob_start();
                    try {
                        $encoded = imagewebp($image, null, 85);
                        $bytes = ob_get_contents();
                    } finally {
                        ob_end_clean();
                    }
                    abort_unless($encoded && $bytes !== '', 422, 'The image could not be processed.');
                    $path = $directory . '/' . $uuid . '.webp';
                    abort_unless(Storage::disk($disk)->put($path, $bytes), 500, 'Photo could not be stored.');
                    $storedMime = 'image/webp';
                    $storedSize = strlen($bytes);
                } else {
                    $path = $file->storeAs($directory, $uuid . '.' . $extension, $disk);
                    abort_unless($path, 500, 'Photo could not be stored.');
                    $storedMime = $mime;
                    $storedSize = $file->getSize();
                }
            } else {
                $path = $file->storeAs($directory, $uuid . '.' . $extension, $disk);
                abort_unless($path, 500, 'Document could not be stored.');
                $storedMime = $mime;
                $storedSize = $file->getSize();
            }
            DB::transaction(function () use ($profile, $role, $vehicle, $slot, $label, $legacy, $mediaId,
                $document, $disk, $directory, $path, $type, $file, $uuid, $storedMime, $storedSize): void {
                // Recheck owner and service under a row lock after uploading bytes.
                $record = $this->ownedVehicle($profile, $role, $vehicle, true);
                $media = AppMedia::create([
                    'uuid' => $uuid, 'name' => $record->vehicle_number . ' - ' . $label,
                    'slug' => $uuid, 'media_type' => $type, 'module' => 'vehicles',
                    'disk' => $disk, 'directory' => $directory, 'original_path' => $path,
                    'original_name' => basename($file->getClientOriginalName()),
                    'original_extension' => $file->extension(), 'mime_type' => $storedMime,
                    'original_size' => $file->getSize(), 'optimized_size' => $storedSize,
                    'uploaded_by' => $profile->user_id, 'is_public' => !$document,
                    'reference_count' => 1, 'metadata' => ['storage_mode' => 'single_file', 'partner_slot' => $slot],
                ]);
                $oldId = $record->getAttribute($mediaId);
                $record->setAttribute($mediaId, $media->id);
                $record->setAttribute($legacy, $path);
                if ($role === 'host') {
                    $record->verification_status = Vehicle::STATUS_PENDING;
                    $record->is_verified = false;
                    $record->is_live = false;
                }
                $record->save();
                if ($oldId) {
                    MediaUsage::query()->where('app_media_id', $oldId)
                        ->where('usable_type', $record->getMorphClass())->where('usable_id', $record->id)
                        ->where('field_name', $mediaId)->delete();
                    AppMedia::query()->whereKey($oldId)->where('reference_count', '>', 0)->decrement('reference_count');
                }
                MediaUsage::firstOrCreate([
                    'app_media_id' => $media->id, 'usable_type' => $record->getMorphClass(),
                    'usable_id' => $record->id, 'field_name' => $mediaId,
                ], ['preferred_variant' => 'original']);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk($disk)->delete($path);
            }
            throw $e;
        } finally {
            if ($image) {
                imagedestroy($image);
            }
        }
        return response()->json(['status' => true, 'message' =>
            $label . ($role === 'vendor' ? ' uploaded. Taxi approval unchanged.' : ' uploaded. Vehicle sent for admin review.'), 'data' => ['slot' => $slot]]);
    }

    public function downloadVehicleAsset(Request $request, int $vehicle, string $slot)
    {
        [$profile, $role] = $this->context($request);
        abort_unless(isset(self::ASSETS[$slot]), 404);
        $record = $this->ownedVehicle($profile, $role, $vehicle);
        [$disk, $path, $mime, $name] = $this->assetSource($record, $slot);
        // Never fetch arbitrary remote URLs or allow paths outside the storage disk.
        abort_unless($this->safeLocalPath($path), 404, 'This file has no usable local storage path. Restore its local copy or upload a new file.');
        abort_unless(Storage::disk($disk)->exists($path), 404, 'The saved file is missing from local storage. Restore the original file or replace it with a new upload.');
        return Storage::disk($disk)->download($path, basename($name ?: $path), [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function adminVehicleDocument(Request $request, int $media)
    {
        // This route uses the existing Filament/web guard, never a partner token.
        $user = $request->user('web');
        abort_unless($user instanceof User && $user->canUseAdminLogin(), 403);
        $record = AppMedia::query()->findOrFail($media);
        $path = (string) $record->storedPath();
        abort_unless($record->disk === 'local' && !$record->is_public
            && str_starts_with($path, 'partner-vehicles/') && str_contains($path, '/documents/')
            && !str_contains($path, '..') && !str_contains($path, '://'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, basename($record->original_name ?: $path), [
            'Content-Type' => $record->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function updateVehiclePricing(Request $request, int $vehicle)
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'host', 422, 'Taxi route fares are managed by admin. These rental prices apply to self-drive cars.');
        $fields = ['daily_price', 'hourly_price', 'weekly_price', 'monthly_price', 'security_deposit'];
        $rules = array_fill_keys($fields, ['required', 'numeric', 'min:0', 'max:500000', 'decimal:0,2']);
        $input = $request->validate($rules);
        abort_unless((float) $input['daily_price'] > 0 || (float) $input['hourly_price'] > 0,
            422, 'Daily or hourly price must be greater than zero.');
        DB::transaction(function () use ($profile, $role, $vehicle, $input): void {
            $record = $this->ownedVehicle($profile, $role, $vehicle, true);
            foreach ($input as $field => $value) {
                $record->setAttribute($field, $value);
            }
            $record->save();
        });
        return response()->json(['status' => true, 'message' => 'Rental prices saved for future quotes. Existing booking prices are unchanged.']);
    }

    private function reminderData(TransporterProfile $profile, string $role): array
    {
        $fields = ['insurance_expiry_date' => 'Insurance', 'puc_expiry_date' => 'PUC'];
        foreach (array_keys($fields) as $field) {
            if (!Schema::hasColumn('vehicles', $field)) {
                return ['configured' => false, 'total' => 0, 'items' => []];
            }
        }
        $today = CarbonImmutable::now('Asia/Kolkata')->startOfDay();
        $limit = $today->addDays(30)->toDateString();
        $query = $this->vehicleQuery($profile, $role);
        $alerts = [];
        $query->chunkById(200, function ($vehicles) use (&$alerts, $fields, $today, $limit): void {
            foreach ($vehicles as $vehicle) {
                foreach ($fields as $field => $label) {
                    $expiry = $vehicle->getAttribute($field);
                    if (!$expiry || $expiry->format('Y-m-d') > $limit) {
                        continue;
                    }
                    $date = CarbonImmutable::parse($expiry->format('Y-m-d'), 'Asia/Kolkata')->startOfDay();
                    $days = (int) $today->diffInDays($date, false);
                    $alerts[] = [
                        'vehicle_id' => $vehicle->id, 'vehicle_number' => $vehicle->vehicle_number,
                        'document' => $label, 'expiry_date' => $date->toDateString(), 'days_left' => $days,
                        'message' => $days < 0 ? $label . ' expired ' . abs($days) . ' days ago'
                            : ($days === 0 ? $label . ' expires today' : $label . ' expires in ' . $days . ' days'),
                    ];
                }
            }
        });
        usort($alerts, fn ($a, $b) => $a['days_left'] <=> $b['days_left']);
        return ['configured' => true, 'total' => count($alerts), 'items' => array_slice($alerts, 0, 50)];
    }

    public function documentReminders(Request $request)
    {
        [$profile, $role] = $this->context($request);
        return response()->json(['status' => true, 'data' => $this->reminderData($profile, $role)]);
    }

    // Integer paise avoid adding binary floating-point amounts in report totals.
    private function cents(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $raw = (string) $value;
        if (!preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $raw, $m)) {
            return 0;
        }
        $fraction = str_pad($m[3] ?? '', 3, '0');
        $amount = ((int) $m[2] * 100) + (int) substr($fraction, 0, 2);
        if ((int) $fraction[2] >= 5) {
            $amount++;
        }
        return ($m[1] ?? '') === '-' ? -$amount : $amount;
    }

    private function moneyString(int $cents): string
    {
        return ($cents < 0 ? '-' : '') . intdiv(abs($cents), 100) . '.'
            . str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public function earnings(Request $request)
    {
        [$profile, $role] = $this->context($request);
        $request->validate(['month' => ['required', 'date_format:Y-m'], 'batch_page' => ['sometimes', 'integer', 'min:1'], 'payment_page' => ['sometimes', 'integer', 'min:1']]);
        // MySQL's normal repeatable-read transaction keeps totals and pages coherent
        // while an admin records a payout during this report request.
        return DB::transaction(fn () => $this->buildEarnings($request, $profile, $role));
    }

    private function buildEarnings(Request $request, TransporterProfile $profile, string $role)
    {
        $month = CarbonImmutable::createFromFormat('!Y-m', $request->input('month'), config('app.timezone'));
        $from = $month->startOfMonth()->format('Y-m-d H:i:s');
        $to = $month->addMonth()->startOfMonth()->format('Y-m-d H:i:s');
        $host = $role === 'host';
        $payoutReady = $host
            ? Schema::hasTable('self_drive_vendor_payouts') && Schema::hasTable('self_drive_vendor_payout_items')
            : Schema::hasTable('taxi_vendor_payouts');
        $payoutTable = $host ? 'self_drive_vendor_payouts' : 'taxi_vendor_payouts';
        $table = $host ? 'self_drive_bookings' : 'orders';
        $owner = $host ? 'transporter_profile_id' : 'transporter_id';
        $date = $host ? 'start_datetime' : 'date';
        $query = DB::table($table . ' as b')->where('b.' . $owner, $host ? $profile->id : $profile->user_id)
            ->where(function ($q) use ($host): void {
                if ($host) $q->whereIn('b.payment_status', ['paid', 'partial']);
                else $q->where('b.partner_offer_status', 'accepted');
            })
            ->where('b.' . $date, '>=', $host ? $from : substr($from, 0, 10))
            ->where('b.' . $date, '<', $host ? $to : substr($to, 0, 10));
        if ($host) {
            $query->where('b.booking_type', 'car')->where('b.paid_amount', '>', 0)
                ->whereNotIn('b.status', ['cancelled','rejected','failed'])
                ->whereNotIn('b.booking_status', ['cancelled','rejected','failed'])
                ->where(function ($q): void { $q->where('b.status','completed')->orWhere('b.booking_status','completed'); });
        } else {
            $query->whereIn('b.status', ['closed','completed'])->whereNotIn('b.ride_type',['self_drive','bike','bike_rental']);
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('b.deleted_at');
        }
        $query->leftJoin('vehicles as v', 'v.id', '=', 'b.vehicle_id');
        $columns = ['b.*', 'v.vehicle_number as report_vehicle_number', 'v.car_company_name as report_brand', 'v.model_name as report_model'];
        if ($payoutReady) {
            if ($host) {
                $query->leftJoin('self_drive_vendor_payout_items as i', 'i.self_drive_booking_id', '=', 'b.id')
                    ->leftJoin($payoutTable.' as p', function ($join) use ($profile): void {
                        $join->on('p.id','=','i.self_drive_vendor_payout_id')->where('p.transporter_profile_id',$profile->id);
                    });
                $columns[] = 'i.payout_amount as report_payout_amount';
                $columns[] = 'i.commission_percentage as report_commission';
                $columns[] = Schema::hasColumn('self_drive_vendor_payout_items','received_amount')
                    ? 'i.received_amount as report_item_received' : DB::raw('NULL as report_item_received');
            } else {
                $query->leftJoin($payoutTable.' as p', function ($join) use ($profile): void {
                    $join->on('p.order_id','=','b.id')->where('p.transporter_profile_id',$profile->id);
                });
                $columns[] = 'p.payout_amount as report_payout_amount';
                $columns[] = DB::raw('NULL as report_commission');
                $columns[] = 'p.paid_amount as report_item_received';
            }
            $columns = array_merge($columns, [
                'p.id as report_payout_id', 'p.payout_no as report_payout_no', 'p.status as report_payout_status',
                'p.paid_amount as report_batch_paid', 'p.remaining_amount as report_batch_remaining',
                'p.payout_amount as report_batch_total',
            ]);
        }
        $query->select($columns);
        $map = function ($row) use ($host, $payoutReady): array {
            $extra = $host ? [] : json_decode((string) ($row->extraOptions ?? '{}'), true);
            $extra = is_array($extra) ? $extra : [];
            $customerPaid = $host ? ($row->paid_amount ?? null) : ($extra['paid_amount'] ??
                ($row->payment_status === 'paid' ? $row->grand_total : null));
            if ($customerPaid !== null && (is_bool($customerPaid)
                || !preg_match('/^-?\d+(?:\.\d+)?$/', (string) $customerPaid))) {
                $customerPaid = null;
            }
            $validPayout = $payoutReady && ($row->report_payout_id ?? null)
                && in_array($row->report_payout_status, ['pending', 'partial', 'paid'], true);
            $fullyPaid = $validPayout && $row->report_payout_status === 'paid'
                && $this->cents($row->report_batch_remaining) === 0
                && $this->cents($row->report_batch_paid) >= $this->cents($row->report_batch_total);
            // Partial batch receipts do not specify which booking received money.
            $received = $validPayout && ($row->report_item_received ?? null) !== null
                ? $row->report_item_received
                : ($fullyPaid ? $row->report_payout_amount
                    : ($validPayout && $this->cents($row->report_batch_paid) === 0 ? '0.00' : null));
            $displayStatus = trim((string) ($row->status ?? ''));
            $workflowStatus = trim((string) ($row->booking_status ?? ''));
            $terminalStatuses = ['cancelled', 'canceled', 'rejected', 'completed', 'closed', 'failed'];
            if (!in_array(strtolower($displayStatus), $terminalStatuses, true)
                && in_array(strtolower($workflowStatus), $terminalStatuses, true)) {
                $displayStatus = $workflowStatus;
            }
            return [
                'id' => $row->id, 'booking_no' => $row->booking_no ?: (string) $row->id,
                'vehicle_id' => $row->vehicle_id,
                'vehicle_number' => $row->report_vehicle_number,
                'vehicle_name' => trim(($row->report_brand ?? '') . ' ' . ($row->report_model ?? '')),
                'start' => $host ? $row->start_datetime : $row->date, 'status' => $displayStatus,
                'booking_total' => $host ? $row->total_amount : $row->grand_total,
                'customer_paid' => $customerPaid, 'payment_status' => $row->payment_status,
                'customer_refund' => $host ? ($row->refund_amount ?? null) : ($extra['refund_amount'] ?? null),
                'partner_amount' => $validPayout ? $row->report_payout_amount : null,
                'partner_received' => $received,
                'commission_percentage' => $validPayout ? $row->report_commission : null,
                'payout_no' => $validPayout ? $row->report_payout_no : null,
                'batch_paid_amount' => $validPayout ? $row->report_batch_paid : null,
                'batch_remaining_amount' => $validPayout ? $row->report_batch_remaining : null,
                'payout_status' => $validPayout ? $row->report_payout_status : 'not_recorded',
                'receipt_note' => $validPayout && $received === null
                    ? 'Partially paid batch: booking-wise payment split is not recorded.'
                    : (!$validPayout ? 'Partner payout is not recorded for this booking.' : null),
            ];
        };
        $summary = ['booking_count' => 0, 'booking_total' => 0, 'customer_paid' => 0,
            'partner_amount' => 0, 'partner_received' => 0, 'unallocated_bookings' => 0, 'unknown_receipt_bookings' => 0, 'unknown_customer_payments' => 0];
        $cars = [];
        // Totals cover the entire filtered month, not only the current page.
        (clone $query)->orderBy('b.id')->chunk(200, function ($rows) use (&$summary, &$cars, $map): void {
            foreach ($rows as $row) {
                $item = $map($row);
                $key = (string) ($item['vehicle_id'] ?? 'unassigned');
                $cars[$key] ??= ['vehicle_number' => $item['vehicle_number'], 'vehicle_name' => $item['vehicle_name'],
                    'booking_count' => 0, 'booking_total' => 0, 'partner_amount' => 0, 'partner_received' => 0,
                    'unallocated_bookings' => 0, 'unknown_receipt_bookings' => 0];
                $summary['booking_count']++;
                $cars[$key]['booking_count']++;
                foreach (['booking_total', 'customer_paid', 'partner_amount', 'partner_received'] as $field) {
                    $summary[$field] += $this->cents($item[$field]);
                    if ($field !== 'customer_paid') {
                        $cars[$key][$field] += $this->cents($item[$field]);
                    }
                }
                if ($item['customer_paid'] === null) {
                    $summary['unknown_customer_payments']++;
                }
                if ($item['partner_amount'] === null) {
                    $summary['unallocated_bookings']++; $cars[$key]['unallocated_bookings']++;
                }
                if ($item['partner_received'] === null) {
                    $summary['unknown_receipt_bookings']++; $cars[$key]['unknown_receipt_bookings']++;
                }
            }
        });
        foreach (['booking_total', 'customer_paid', 'partner_amount', 'partner_received'] as $field) {
            $summary[$field] = $this->moneyString($summary[$field]);
        }
        foreach ($cars as &$car) {
            foreach (['booking_total', 'partner_amount', 'partner_received'] as $field) {
                $car[$field] = $this->moneyString($car[$field]);
            }
        }
        unset($car);
        $page = (clone $query)->orderByDesc('b.id')->paginate(20);
        $batchQuery = $payoutReady ? DB::table($payoutTable)
            ->where('transporter_profile_id', $profile->id)->whereIn('status', ['pending', 'partial', 'paid'])
            ->where('period_from', '>=', substr($from, 0, 10))->where('period_from', '<', substr($to, 0, 10)) : null;
        $batchPage = $batchQuery?->orderByDesc('id')->paginate(20, ['*'], 'batch_page');
        $paymentQuery = Schema::hasTable('partner_payout_payments') ? DB::table('partner_payout_payments')
            ->where('transporter_profile_id',$profile->id)->where('account',$role)
            ->where('payment_date','>=',$from)->where('payment_date','<',$to)
            ->selectRaw('MIN(id) as id, SUM(amount) as amount, payment_date, method, reference, notes')
            ->groupBy('request_key','payment_date','method','reference','notes') : null;
        $paymentPage = $paymentQuery?->orderByDesc('payment_date')->orderByDesc('id')->paginate(20,['*'],'payment_page');
        return response()->json(['status' => true, 'data' => [
            'payments' => $paymentPage ? collect($paymentPage->items())->map(fn ($payment): array => [
                'id'=>$payment->id,'payment_date'=>$payment->payment_date,'amount'=>$payment->amount,
                'payment_mode'=>$payment->method,'payment_reference'=>$payment->reference,'note'=>$payment->notes,
            ])->values() : [],
            'payment_page'=>$paymentPage?->currentPage() ?? 1, 'payment_last_page'=>$paymentPage?->lastPage() ?? 1,
            'payment_basis'=>'Actual payments dated in this month. Legacy cumulative payments have no individual transaction history.',
            'month' => $request->input('month'), 'role' => $role, 'payout_available' => $payoutReady,
            'basis' => 'Completed bookings starting in this month. Received totals are allocations against these bookings, not monthly bank cash flow.',
            'summary' => $summary, 'cars' => array_values($cars),
            'items' => collect($page->items())->map($map)->values(), 'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'batches' => $batchPage ? collect($batchPage->items())->map(fn ($p): array => [
                'payout_no' => $p->payout_no, 'period_from' => $p->period_from, 'period_to' => $p->period_to,
                'payout_amount' => $p->payout_amount, 'paid_amount' => $p->paid_amount,
                'remaining_amount' => $p->remaining_amount, 'status' => $p->status,
                'payment_reference' => $p->payment_reference, 'paid_at' => $p->paid_at,
                'payment_mode' => $p->payment_method ?? null,
                'note' => $p->notes ?? null,
            ])->values() : [],
            'batch_page' => $batchPage?->currentPage() ?? 1, 'batch_last_page' => $batchPage?->lastPage() ?? 1,
            'batch_basis' => 'Payout batches whose period starts in this month. Paid amounts are cumulative, not necessarily paid during this month.',
            'note' => $host ? 'Saved payout amounts follow the existing daily-rate and commission calculation. Legacy partial allocations remain unknown. Deposits are not income.'
                : 'With Driver earnings use the accepted admin partner offer saved at completion, not the customer fare. Deposits are not income.',
        ]]);
    }

    public function logout(Request $request)
    {
        // Permit logout even if admin disabled the profile during the session.
        $token = $request->user()?->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken && $token->name === 'duracabs_partner_app', 403);
        $token->delete();
        return response()->json(['status' => true, 'message' => 'Logged out.']);
    }
}
