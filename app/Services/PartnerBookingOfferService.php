<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PartnerBookingOfferService
{
    public const PRE_TRIP = ['new', 'confirm', 'reconfirmation', 'modification'];
    public const TERMINAL = ['cancelled', 'canceled', 'rejected', 'completed', 'closed', 'failed'];
    public const TERM_FIELDS = ['partner_offer_amount', 'partner_offer_fare_type',
        'partner_offer_parking_included', 'partner_offer_notes'];

    public static function complete(object $row): bool
    {
        $amount = $row->partner_offer_amount ?? null;
        return !is_bool($amount) && preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', (string) $amount) === 1
            && (float) $amount > 0
            && in_array($row->partner_offer_fare_type ?? '', ['all_inclusive', 'all_exclusive'], true)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                (string) ($row->partner_offer_revision ?? '')) === 1
            && (int) ($row->partner_offer_assigned_by ?? 0) > 0 && (int) ($row->transporter_id ?? 0) > 0;
    }

    public static function open(object $row): bool
    {
        return in_array(strtolower((string) ($row->status ?? '')), self::PRE_TRIP, true)
            && !in_array(strtolower((string) ($row->booking_status ?? '')), self::TERMINAL, true)
            && in_array(strtolower((string) ($row->payment_status ?? '')), ['paid', 'partial', 'pending', 'unpaid'], true)
            && !in_array($row->ride_type ?? '', ['self_drive', 'bike_rental'], true);
    }

    public static function accepted(object $row): bool
    {
        return self::complete($row) && ($row->partner_offer_status ?? '') === 'accepted'
            && (int) ($row->partner_offer_accepted_by ?? 0) === (int) ($row->transporter_id ?? 0)
            && !empty($row->partner_offer_accepted_at);
    }

    public static function view(object $row): array
    {
        $complete = self::complete($row);
        $inclusive = ($row->partner_offer_fare_type ?? '') === 'all_inclusive';
        $parking = $inclusive && (bool) ($row->partner_offer_parking_included ?? false);
        return [
            'partner_offer_amount' => $complete ? (string) $row->partner_offer_amount : null,
            'partner_offer_fare_type' => $complete ? $row->partner_offer_fare_type : null,
            'partner_offer_notes' => $row->partner_offer_notes ?? null,
            'partner_offer_status' => $complete ? ($row->partner_offer_status ?? 'pending') : 'not_set',
            'partner_offer_revision' => $complete ? $row->partner_offer_revision : null,
            'partner_offer_accepted_at' => $row->partner_offer_accepted_at ?? null,
            'partner_offer_parking_included' => $parking,
            'partner_offer_inclusions' => !$complete ? 'Admin must set your amount and fare terms.' : ($inclusive
                ? 'Toll, state tax and driver allowance included. ' . ($parking ? 'Parking included.' : 'Parking extra at actual cost.')
                : 'Toll, parking, state tax and driver allowance extra at actual cost.'),
            'can_accept' => $complete && self::open($row) && ($row->partner_offer_status ?? '') === 'pending',
        ];
    }

    public static function snapshot(object $row): string
    {
        $values = [];
        foreach (array_merge(self::TERM_FIELDS, ['transporter_id', 'partner_offer_status', 'partner_offer_revision',
            'partner_offer_accepted_at', 'partner_offer_accepted_by', 'driver_id', 'vehicle_id', 'payment_status',
            'payment_method', 'grand_total', 'coupon_value', 'tax', 'status', 'date', 'dateTo', 'time', 'endTime',
            'booking_from', 'booking_to', 'cityFrom', 'cityTo', 'ride_type', 'taxi_type', 'productName', 'total_km',
            'updated_at']) as $field) {
            $values[$field] = (string) ($row->{$field} ?? '');
        }
        $extra = $row->extraOptions ?? [];
        $values['extraOptions'] = is_string($extra) ? json_decode($extra, true) : $extra;
        return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
    }

    /** Called by normal model saves; Partner acceptance uses a locked, narrow DB update. */
    public static function prepareSaving(Order $order): void
    {
        if (!Schema::hasColumn('orders', 'partner_offer_revision')) return;
        $ownerChanged = $order->isDirty('transporter_id')
            && ((int) $order->transporter_id > 0 || (int) $order->getOriginal('transporter_id') > 0);
        $termsChanged = $order->isDirty(self::TERM_FIELDS);
        $actor = auth()->user() ?? auth('web')->user();
        if ($ownerChanged || ($termsChanged && !empty($order->transporter_id))) {
            if (!$actor || !$actor->hasRole('Admin')) {
                throw ValidationException::withMessages(['transporter_id' => 'Only Admin can assign a Vendor or change their offer.']);
            }
        }
        if (empty($order->transporter_id)) {
            if ($ownerChanged) {
                $order->forceFill(['partner_offer_status' => null, 'partner_offer_revision' => null,
                    'partner_offer_assigned_at' => null, 'partner_offer_assigned_by' => null,
                    'partner_offer_accepted_at' => null, 'partner_offer_accepted_by' => null,
                    'driver_id' => null, 'vehicle_id' => null]);
            }
            return;
        }
        $tripChanged = $order->isDirty(['date', 'time', 'dateTo', 'endTime', 'booking_from', 'booking_to',
            'cityFrom', 'cityTo', 'ride_type', 'taxi_type', 'productName', 'total_km']);
        if (!$ownerChanged && !$termsChanged && !$tripChanged) return;
        // Preserve historical/legacy rows until Admin explicitly sets an offer.
        if (!$ownerChanged && !$termsChanged && !self::complete($order)) return;
        if (!in_array(strtolower((string) $order->status), self::PRE_TRIP, true)) {
            if ($ownerChanged || $termsChanged) {
                throw ValidationException::withMessages(['partner_offer_amount' => 'Offers can only be changed before the trip starts.']);
            }
            return;
        }
        $amount = $order->partner_offer_amount;
        if (is_bool($amount) || preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', (string) $amount) !== 1 || (float) $amount <= 0) {
            throw ValidationException::withMessages(['partner_offer_amount' => 'Enter a positive Vendor amount with at most two decimal places.']);
        }
        if (!in_array($order->partner_offer_fare_type, ['all_inclusive', 'all_exclusive'], true)) {
            throw ValidationException::withMessages(['partner_offer_fare_type' => 'Select All Inclusive or All Exclusive.']);
        }
        if (!self::open($order)) {
            throw ValidationException::withMessages(['transporter_id' => 'Failed/refunded or cancelled bookings cannot receive a Vendor offer.']);
        }
        $profiles = TransporterProfile::query()->where('user_id', $order->transporter_id)->limit(2)->get();
        $profile = $profiles->first();
        if ($profiles->count() !== 1 || !$profile->isActive() || !$profile->isVerified() || !$profile->isVendor()
            || !$profile->user || !$profile->user->canUseTransporterLogin()) {
            throw ValidationException::withMessages(['transporter_id' => 'Link exactly one active, verified Vendor profile to this Transporter account first.']);
        }
        if (!$order->partner_offer_parking_included || $order->partner_offer_fare_type === 'all_exclusive') {
            $order->partner_offer_parking_included = false;
        }
        $order->forceFill(['partner_offer_status' => 'pending', 'partner_offer_revision' => (string) Str::uuid(),
            'partner_offer_assigned_at' => now(), 'partner_offer_assigned_by' => $actor?->id ?? $order->partner_offer_assigned_by,
            'partner_offer_accepted_at' => null, 'partner_offer_accepted_by' => null]);
        if ($ownerChanged) $order->forceFill(['driver_id' => null, 'vehicle_id' => null]);
    }
}
