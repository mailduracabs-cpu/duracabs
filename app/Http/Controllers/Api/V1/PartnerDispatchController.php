<?php

declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PartnerDriverNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PartnerDispatchController extends PartnerController
{
    private const TERMINAL = ['cancelled', 'canceled', 'rejected', 'completed', 'closed', 'refund', 'refunded'];
    private function vendor(Request $request): TransporterProfile
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'vendor', 403, 'Driver and taxi assignment is only available to Vendors.');
        abort_unless(Schema::hasColumn('users', 'partner_driver_profile_id')
            && Schema::hasColumn('users', 'partner_driver_removed_at'), 503,
            'Run the partner driver ownership migration included in SETUP.md.');
        return $profile;
    }
    private function driverQuery(TransporterProfile $profile)
    {
        // No mobile-based takeover or inferred created_by ownership.
        return User::query()->role(User::ROLE_DRIVER, 'web')
            ->where('partner_driver_profile_id', $profile->id)->whereNull('partner_driver_removed_at');
    }
    private function hasDriverMobile(User $driver): bool
    {
        $number = preg_replace('/[\s-]+/', '', (string) $driver->mobile);
        return preg_match('/^(?:\+?91|0)?[6-9][0-9]{9}$/', $number) === 1;
    }
    private function driverData(User $driver): array
    {
        return ['id' => $driver->id, 'name' => $driver->name, 'mobile' => $driver->mobile,
            'email' => $driver->email, 'driving_licence_number' => $driver->driving_licence_number,
            'is_active' => $driver->isActiveAccount()];
    }
    private function page($page, $items)
    {
        return response()->json(['status' => true, 'data' => ['items' => $items,
            'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]]);
    }
    public function drivers(Request $request)
    {
        $profile = $this->vendor($request);
        $query = $this->driverQuery($profile);
        $q = trim((string) $request->input('q', ''));
        if ($q !== '') $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('mobile', 'like', '%'.$q.'%'));
        $page = $query->orderByDesc('id')->paginate(20);
        return $this->page($page, collect($page->items())->map(fn ($driver) => $this->driverData($driver))->values());
    }
    public function createDriver(Request $request)
    {
        $profile = $this->vendor($request);
        $request->merge(['email' => strtolower(trim((string) $request->input('email'))),
            'driving_licence_number' => strtoupper(trim((string) $request->input('driving_licence_number')))]);
        $input = $request->validate(['name' => ['required', 'string', 'max:150'],
            'mobile' => ['required', 'regex:/^[6-9][0-9]{9}$/'], 'email' => ['required', 'email', 'max:255'],
            'driving_licence_number' => ['required', 'string', 'min:5', 'max:50', 'regex:/^[A-Z0-9\s-]+$/']]);
        $role = Role::query()->where('name', User::ROLE_DRIVER)->where('guard_name', 'web')->first();
        abort_unless($role, 503, 'The existing Driver role with web guard must be configured in admin.');
        $lock = Cache::lock('partner_driver_registration', 120);
        abort_unless($lock->get(), 409, 'Another driver is being added. Retry shortly.');
        try {
            $driver = DB::transaction(function () use ($profile, $input, $role) {
                // Check all accounts, not just this Vendor's drivers. Never convert another user's role.
                $mobile = $input['mobile'];
                abort_if(User::query()->where(function ($q) use ($mobile): void {
                    $q->whereIn('mobile', [$mobile, '91'.$mobile, '+91'.$mobile, '+91 '.$mobile, '0'.$mobile])
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '+', '') IN (?, ?, ?)",
                            [$mobile, '91'.$mobile, '0'.$mobile]);
                })->orWhere('email', $input['email'])->exists(), 422,
                    'This mobile or email already belongs to an account. Admin must link or restore the existing Driver; no duplicate account was created.');
                $driver = new User($input + ['password' => Hash::make(Str::random(64)), 'is_active' => true]);
                $driver->forceFill(['partner_driver_profile_id' => $profile->id, 'partner_driver_removed_at' => null]);
                if (Schema::hasColumn('users', 'created_by')) $driver->forceFill(['created_by' => $profile->user_id]);
                $driver->saveQuietly(); // Local workflow: no external registration notification.
                $driver->assignRole($role);
                return $driver;
            });
        } finally { $lock->release(); }
        return response()->json(['status' => true, 'message' => 'Driver added to your fleet.', 'data' => $this->driverData($driver)], 201);
    }

    public function updateDriver(Request $request, int $driver)
    {
        $profile = $this->vendor($request);
        $request->merge(['email' => strtolower(trim((string) $request->input('email'))),
            'driving_licence_number' => strtoupper(trim((string) $request->input('driving_licence_number')))]);
        $input = $request->validate(['name' => ['required', 'string', 'max:150'],
            'mobile' => ['required', 'regex:/^[6-9][0-9]{9}$/'], 'email' => ['required', 'email', 'max:255'],
            'driving_licence_number' => ['required', 'string', 'min:5', 'max:50', 'regex:/^[A-Z0-9\s-]+$/']]);
        $lock = Cache::lock('partner_driver_registration', 120);
        abort_unless($lock->get(), 409, 'Another driver is being saved. Retry shortly.');
        try {
            DB::transaction(function () use ($profile, $driver, $input): void {
                $record = $this->driverQuery($profile)->whereKey($driver)->lockForUpdate()->firstOrFail();
                abort_unless($record->roles()->count() === 1, 409, 'Admin must edit this shared account with additional roles.');
                $mobile = $input['mobile'];
                abort_if(User::query()->whereKeyNot($record->id)->where(function ($q) use ($mobile, $input): void {
                    $q->where('email', $input['email'])->orWhereRaw("REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '+', '') IN (?, ?, ?)",
                        [$mobile, '91'.$mobile, '0'.$mobile]);
                })->exists(), 422, 'This mobile or email belongs to another account.');
                $record->fill($input);
                $changedContact = $record->isDirty(['mobile', 'email']);
                $record->saveQuietly();
                if ($changedContact) $record->tokens()->delete();
            });
        } finally { $lock->release(); }
        return response()->json(['status' => true, 'message' => 'Driver details updated. Existing booking links remain.']);
    }
    private function terminal($row): bool
    {
        return in_array(strtolower((string) ($row->status ?? '')), self::TERMINAL, true)
            || in_array(strtolower((string) ($row->booking_status ?? '')), self::TERMINAL, true);
    }
    public function removeDriver(Request $request, int $driver)
    {
        $profile = $this->vendor($request);
        DB::transaction(function () use ($profile, $driver): void {
            $record = $this->driverQuery($profile)->whereKey($driver)->lockForUpdate()->firstOrFail();
            abort_unless($record->roles()->count() === 1, 409,
                'This Driver has other account roles. Ask admin to unlink this shared account instead of deactivating it.');
            $orders = DB::table('orders')->where('driver_id', $record->id);
            if (Schema::hasColumn('orders', 'deleted_at')) $orders->whereNull('deleted_at');
            foreach ($orders->orderBy('id')->cursor() as $order) {
                abort_unless($this->terminal($order), 409,
                    'This driver has an unfinished booking. Reassign or complete that booking before removing the driver.');
            }
            $record->forceFill(['partner_driver_removed_at' => now(), 'is_active' => false])->saveQuietly();
            $record->tokens()->delete();
        });
        return response()->json(['status' => true, 'message' => 'Driver removed and deactivated. Previous booking history remains.']);
    }
    public static function assignable($row): bool
    {
        return \App\Services\PartnerBookingOfferService::open($row)
            && \App\Services\PartnerBookingOfferService::accepted($row);
    }
    private function order(TransporterProfile $profile, int $id, bool $lock = false)
    {
        $query = DB::table('orders')->where('id', $id)->where('transporter_id', $profile->user_id);
        if (Schema::hasColumn('orders', 'deleted_at')) $query->whereNull('deleted_at');
        if ($lock) $query->lockForUpdate();
        $order = $query->first();
        abort_unless($order, 404, 'Assigned taxi booking not found.');
        abort_unless(self::assignable($order), 409, 'Accept the current Admin offer before assigning a driver and taxi. Cancelled or started trips cannot be assigned.');
        return $order;
    }
    private function revision($order): string
    {
        return PartnerDriverNotificationService::revision($order);
    }
    public function assignment(Request $request, int $booking)
    {
        $profile = $this->vendor($request);
        $order = $this->order($profile, $booking);
        $driver = !empty($order->driver_id) ? User::query()->find($order->driver_id) : null;
        $vehicle = !empty($order->vehicle_id) ? Vehicle::query()->find($order->vehicle_id) : null;
        return response()->json(['status' => true, 'data' => ['id' => $order->id, 'booking_no' => $order->booking_no ?: (string) $order->id,
            'driver_id' => $driver?->id, 'driver_name' => $driver?->name, 'driver_mobile' => $driver?->mobile,
            'vehicle_id' => $vehicle?->id, 'vehicle_number' => $vehicle?->vehicle_number,
            'vehicle_name' => $vehicle ? trim($vehicle->car_company_name.' '.$vehicle->model_name) : null,
            'car_classification' => $vehicle?->car_classification,
            'start' => trim(($order->date ?? '').' '.($order->time ?? '')),
            'end' => trim(($order->dateTo ?: $order->date).' '.($order->endTime ?? '')),
            'notification' => app(PartnerDriverNotificationService::class)->currentSummary($order),
            'availability_note' => empty($order->endTime) ? 'No return time is saved. Availability reserves the driver and taxi through the end of the booking’s last date.' : null,
            'revision' => $this->revision($order)]]);
    }
    public function options(Request $request, int $booking)
    {
        $profile = $this->vendor($request);
        $this->order($profile, $booking);
        $input = $request->validate(['kind' => ['required', 'in:drivers,vehicles']]);
        $q = trim((string) $request->input('q', ''));
        if ($input['kind'] === 'drivers') {
            $query = $this->driverQuery($profile)->where('is_active', true)->whereNotNull('mobile')->where('mobile', '<>', '')->whereNotNull('driving_licence_number')->where('driving_licence_number', '<>', '');
            if ($q !== '') $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('mobile', 'like', '%'.$q.'%'));
            $page = $query->orderBy('name')->paginate(20);
            $items = collect($page->items())->map(fn ($d) => $this->driverData($d))->values();
        } else {
            $query = Vehicle::query()->where(function ($q) use ($profile): void {
                $q->where('transporter_profile_id', $profile->id)->orWhere(function ($q) use ($profile): void {
                    $q->whereNull('transporter_profile_id')->where('user_id', $profile->user_id);
                });
            })->where('service_type', 'taxi')->whereNull('partner_removed_at')->where('is_active', true)
                ->where('verification_status', 'approved')->where('is_verified', true);
            if ($q !== '') $query->where(fn ($query) => $query->where('vehicle_number', 'like', '%'.$q.'%')
                ->orWhere('model_name', 'like', '%'.$q.'%')->orWhere('car_company_name', 'like', '%'.$q.'%'));
            $page = $query->orderBy('vehicle_number')->paginate(20);
            $items = collect($page->items())->map(fn ($v) => ['id' => $v->id, 'vehicle_number' => $v->vehicle_number,
                'name' => trim($v->car_company_name.' '.$v->model_name), 'car_classification' => $v->car_classification, 'seats' => $v->seats])->values();
        }
        return $this->page($page, $items);
    }
    private function taxiWindow($order): ?array
    {
        try {
            if (empty($order->date)) return null;
            $start = CarbonImmutable::parse($order->date.' '.($order->time ?: '00:00:00'), config('app.timezone'));
            $endDate = $order->dateTo ?: $order->date;
            $end = !empty($order->endTime)
                ? CarbonImmutable::parse($endDate.' '.$order->endTime, config('app.timezone'))
                : CarbonImmutable::parse($endDate, config('app.timezone'))->addDay()->startOfDay();
            return $end->greaterThan($start) ? [$start, $end] : null;
        } catch (\Throwable) { return null; }
    }
    private function conflicts($target, int $driver, int $vehicle): void
    {
        $window = $this->taxiWindow($target);
        abort_unless($window, 422, 'Admin must correct this booking’s trip dates/times before assignment.');
        [$start, $end] = $window;
        $query = DB::table('orders')->where('id', '<>', $target->id)
            ->where(fn ($q) => $q->where('driver_id', $driver)->orWhere('vehicle_id', $vehicle));
        if (Schema::hasColumn('orders', 'deleted_at')) $query->whereNull('deleted_at');
        foreach ($query->orderBy('id')->cursor() as $other) {
            if ($this->terminal($other)) continue;
            $otherWindow = $this->taxiWindow($other);
            abort_if(strtolower((string) $other->status) === 'start' || !$otherWindow
                || ($start->lessThan($otherWindow[1]) && $end->greaterThan($otherWindow[0])), 409,
                'The selected driver or taxi has another unfinished trip in this time window, or a trip with missing dates. Choose another resource or correct that trip in admin.');
        }
        if (!Schema::hasTable('self_drive_bookings') || !Schema::hasColumn('self_drive_bookings', 'vehicle_id')) return;
        $query = DB::table('self_drive_bookings')->where('vehicle_id', $vehicle);
        if (Schema::hasColumn('self_drive_bookings', 'deleted_at')) $query->whereNull('deleted_at');
        foreach ($query->orderBy('id')->cursor() as $other) {
            if ($this->terminal($other)) continue;
            try {
                $otherStart = !empty($other->start_datetime) ? CarbonImmutable::parse($other->start_datetime, config('app.timezone')) : null;
                $otherEnd = !empty($other->end_datetime) ? CarbonImmutable::parse($other->end_datetime, config('app.timezone')) : null;
            } catch (\Throwable) { $otherStart = $otherEnd = null; }
            abort_if(strtolower((string) ($other->status ?? '')) === 'running'
                || strtolower((string) ($other->booking_status ?? '')) === 'running' || !$otherStart || !$otherEnd || $otherEnd->lessThanOrEqualTo($otherStart)
                || ($start->lessThan($otherEnd) && $end->greaterThan($otherStart)), 409,
                'This taxi has an unfinished self-drive reservation in the selected period, or one with invalid dates.');
        }
    }
    public function assign(Request $request, int $booking)
    {
        $profile = $this->vendor($request);
        $input = $request->validate(['driver_id' => ['required', 'integer', 'min:1'],
            'vehicle_id' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'string', 'size:64']]);
        $lock = Cache::lock('partner_taxi_dispatch', 120);
        abort_unless($lock->get(), 409, 'Another assignment is being saved. Retry shortly.');
        try {
            $notification = app(PartnerDriverNotificationService::class);
            $logId = DB::transaction(function () use ($profile, $booking, $input, $notification): int {
                // Resource row locks also serialize removal against app assignment.
                $driver = $this->driverQuery($profile)->whereKey($input['driver_id'])->lockForUpdate()->firstOrFail();
                abort_unless($driver->canUseDriverLogin() && !empty($driver->driving_licence_number) && $this->hasDriverMobile($driver), 422, 'Select an active Driver with a DL number and valid Indian mobile for WhatsApp.');
                $vehicle = $this->ownedVehicle($profile, 'vendor', (int) $input['vehicle_id'], true);
                abort_unless($vehicle->is_active && $vehicle->is_verified && $vehicle->verification_status === 'approved', 422,
                    'Select an active, verified and approved taxi. Pending or archived cars cannot be assigned.');
                $order = $this->order($profile, $booking, true);
                abort_unless(hash_equals($this->revision($order), $input['revision']), 409,
                    'This booking changed after you opened it. Refresh the booking before assigning.');
                $this->conflicts($order, $driver->id, $vehicle->id);
                if ((int) $order->driver_id !== $driver->id || (int) $order->vehicle_id !== $vehicle->id) {
                    DB::table('orders')->where('id', $order->id)->where('transporter_id', $profile->user_id)->update([
                        'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'updated_at' => now()]);
                }
                $saved = $this->order($profile, $booking, true);
                // Status, payment, customer identity, fare and transporter remain unchanged.
                // Use only the explicit driver notification below; avoid duplicate customer/admin model hooks.
                return $notification->prepare($profile->id, $saved, $driver, $vehicle);
            });
            try { $notificationStatus = $notification->send($logId); }
            catch (\Throwable) { $notificationStatus = ['status' => 'unknown', 'message' => 'Assignment is saved, but WhatsApp result could not be confirmed. Refresh before retrying.']; }
        } finally { $lock->release(); }
        return response()->json(['status' => true, 'message' => 'Driver and taxi assignment saved.',
            'data' => ['notification' => $notificationStatus]]);
    }
    public function retryNotification(Request $request, int $booking)
    {
        $profile = $this->vendor($request);
        $lock = Cache::lock('partner_taxi_dispatch', 120);
        abort_unless($lock->get(), 409, 'An assignment/notification is being processed. Retry shortly.');
        try {
            $service = app(PartnerDriverNotificationService::class);
            $id = DB::transaction(function () use ($profile, $booking, $service): int {
                $order = $this->order($profile, $booking, true);
                $driver = $this->driverQuery($profile)->whereKey($order->driver_id)->lockForUpdate()->firstOrFail();
                abort_unless($driver->canUseDriverLogin() && $this->hasDriverMobile($driver), 422, 'The assigned Driver needs an active account and valid mobile.');
                $vehicle = $this->ownedVehicle($profile, 'vendor', (int) $order->vehicle_id, true);
                abort_unless($vehicle->is_active && $vehicle->is_verified && $vehicle->verification_status === 'approved', 422,
                    'The assigned taxi needs admin approval before a new notification is sent.');
                return $service->prepare($profile->id, $order, $driver, $vehicle, true);
            });
            try { $notification = $service->send($id); }
            catch (\Throwable) { $notification = ['status' => 'unknown', 'message' => 'Assignment remains saved, but WhatsApp result could not be confirmed. Refresh before retrying.']; }
        } finally { $lock->release(); }
        return response()->json(['status' => true, 'message' => 'Assignment remains saved.', 'data' => ['notification' => $notification]]);
    }

}
