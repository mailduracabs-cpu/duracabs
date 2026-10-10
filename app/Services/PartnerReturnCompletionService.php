<?php

namespace App\Services;

use App\Models\SelfDriveBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Physical return, bill and completion share one locked write. Payments are never invented. */
class PartnerReturnCompletionService
{
    public function mutable(SelfDriveBooking $booking, bool $admin = false): void
    {
        if ($booking->booking_type !== 'car'
            || in_array($booking->status, ['cancelled', 'rejected', 'failed'], true)
            || in_array($booking->booking_status, ['cancelled', 'rejected', 'failed'], true)) {
            $this->fail('This booking cannot be returned.');
        }
        if ($booking->vendorPayoutItem()->exists()) {
            $this->fail('Payout already exists. Return billing cannot be changed here.');
        }
        if ($booking->final_bill_generated_at && ($booking->return_otp_verified_at || $booking->return_admin_confirmed_at)) {
            $this->fail('Return and bill are already recorded. Refresh the booking.');
        }
        if (! $admin && (! $booking->trip_start_datetime || $booking->start_km === null
            || ! in_array($booking->status, ['running', 'return_pending'], true))) {
            $this->fail('Admin must confirm the missing pickup/return details for this booking.');
        }
    }

    public function data(array $input): array
    {
        return Validator::make($input, [
            'returned_at' => ['required', 'date', 'before_or_equal:now'],
            'end_km' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'fuel' => ['required', 'in:empty,quarter,half,three_quarters,full'],
            'note' => ['nullable', 'string', 'max:2000'],
            'damage_amount' => ['sometimes', 'numeric', 'min:0', 'max:1000000', 'decimal:0,2'],
            'fuel_charge' => ['sometimes', 'numeric', 'min:0', 'max:1000000', 'decimal:0,2'],
            'cleaning_charge' => ['sometimes', 'numeric', 'min:0', 'max:1000000', 'decimal:0,2'],
            'other_charge' => ['sometimes', 'numeric', 'min:0', 'max:1000000', 'decimal:0,2'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'started_at' => ['nullable', 'date', 'before_or_equal:now'],
            'start_km' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ])->validate();
    }

    private function apply(SelfDriveBooking $booking, array $data, bool $admin): void
    {
        $start = $booking->trip_start_datetime;
        if (! $start && $admin && ! empty($data['started_at'])) {
            $start = Carbon::parse($data['started_at']);
            $booking->trip_start_datetime = $start;
        }
        if ($booking->start_km === null && $admin && isset($data['start_km'])) {
            $booking->start_km = $data['start_km'];
        }
        if (! $start || $booking->start_km === null) {
            $this->fail('Actual pickup date/time and start KM are required; scheduled dates are not proof of pickup.');
        }
        $returned = Carbon::parse($data['returned_at']);
        if ($returned->lt($start) || (float) $data['end_km'] < (float) $booking->start_km) {
            $this->fail('Return must be after pickup and end KM must be at least start KM.');
        }
        $booking->trip_end_datetime = $returned;
        $booking->end_km = $data['end_km'];
        $booking->drop_fuel_level = $data['fuel'];
        $booking->damage_note = $data['note'] ?? null;
        foreach (['damage_amount', 'fuel_charge', 'cleaning_charge', 'other_charge'] as $key) {
            // Missing fields keep existing charges, rather than silently zeroing them.
            if (array_key_exists($key, $data)) $booking->{$key} = $data[$key];
        }
        if (array_sum(array_map(fn ($key) => (float) $booking->{$key},
            ['damage_amount', 'fuel_charge', 'cleaning_charge', 'other_charge'])) > 0 && blank($booking->damage_note)) {
            $this->fail('A note explaining additional charges is required.');
        }
        $paid = (float) $booking->paid_amount;
        $base = $booking->effectiveRentalAmount();
        $booking->refreshTripAmounts();
        $booking->final_amount = ReturnBillMoney::rental(number_format($base, 2, '.', ''), array_map(
            fn ($key) => number_format((float) $booking->{$key}, 2, '.', ''),
            ['extra_hour_amount', 'extra_km_amount', 'damage_amount', 'fuel_charge',
                'cleaning_charge', 'late_return_charge', 'other_charge']
        ));
        $booking->return_completion_audit = ['base_rental' => $base];
        // Preserve the actual payment; syncPayment must not silently discard excess cash.
        $booking->paid_amount = $paid;
        $booking->syncPayment();
        if (abs((float) $booking->paid_amount - $paid) > 0.009) {
            $this->fail('This bill would change the recorded payment. Admin must reconcile excess payment/refund first.');
        }
    }

    public function preview(SelfDriveBooking $booking, array $input, bool $admin = false): array
    {
        $this->mutable($booking, $admin);
        $data = $this->data($input);
        $copy = clone $booking;
        $this->apply($copy, $data, $admin);
        $bill = $this->bill($copy);
        // Includes current pricing/payment inputs so a stale preview cannot confirm a different bill.
        $bill['confirmation'] = $this->fingerprint($booking, $data, $bill);
        return $bill;
    }

    private function fingerprint(SelfDriveBooking $booking, array $data, array $bill): string
    {
        unset($bill['confirmation']);
        return hash_hmac('sha256', json_encode([$booking->getKey(), $booking->getAttributes(), $data, $bill]), (string) config('app.key'));
    }

    public function bill(SelfDriveBooking $booking): array
    {
        return [
            'rental' => $booking->effectiveRentalAmount(),
            'security_deposit' => (float) $booking->security_deposit,
            'extra_hours' => (float) $booking->extra_hours,
            'extra_hour_amount' => (float) $booking->extra_hour_amount,
            'extra_km' => (float) $booking->extra_km,
            'extra_km_amount' => (float) $booking->extra_km_amount,
            'base_rental' => $booking->return_completion_audit['base_rental'] ?? $booking->effectiveRentalAmount(),
            'damage_amount' => (float) $booking->damage_amount,
            'fuel_charge' => (float) $booking->fuel_charge,
            'cleaning_charge' => (float) $booking->cleaning_charge,
            'late_return_charge' => (float) $booking->late_return_charge,
            'other_charge' => (float) $booking->other_charge,
            'payable' => $booking->payableAmount(),
            'paid' => (float) $booking->paid_amount,
            'balance_due' => (float) $booking->remaining_amount,
            'return_recorded' => (bool) ($booking->trip_end_datetime && $booking->final_bill_generated_at
                && ($booking->return_otp_verified_at || ($booking->return_admin_confirmed_at
                    && $booking->return_admin_confirmed_by && filled($booking->return_admin_reason)))),
            'completed' => $booking->status === 'completed' && $booking->booking_status === 'completed',
        ];
    }

    public function finish(int $id, array $input, int $actorId, bool $admin = false): SelfDriveBooking
    {
        if ($admin) {
            $actor = \App\Models\User::find($actorId);
            abort_unless($actor && $actor->canUseAdminLogin(), 403, 'Active admin account required.');
        }
        return DB::transaction(function () use ($id, $input, $actorId, $admin) {
            $booking = SelfDriveBooking::query()->lockForUpdate()->findOrFail($id);
            if (! $admin) {
                abort_unless(\App\Models\FleetManagement\TransporterProfile::query()
                    ->whereKey($booking->transporter_profile_id)->where('user_id', $actorId)->exists(), 403);
            }
            $this->mutable($booking, $admin);
            $data = $this->data($input);
            $preview = $this->preview($booking, $data, $admin);
            if (! isset($input['confirmation']) || ! hash_equals($preview['confirmation'], (string) $input['confirmation'])) {
                $this->fail('Bill changed or preview is missing. Preview again before confirming.');
            }
            if ($admin) {
                if (mb_strlen(trim($data['reason'] ?? '')) < 10) $this->fail('Admin override reason must contain at least 10 characters.');
                $booking->return_admin_confirmed_at = now();
                $booking->return_admin_confirmed_by = $actorId;
                $booking->return_admin_reason = trim($data['reason']);
                // Never mark an unverified customer OTP as verified.
            } elseif (! $booking->return_otp_verified_at || count(array_filter($booking->return_draft['photos'] ?? [], fn ($path) => \Illuminate\Support\Facades\Storage::disk('local')->exists($path))) < 1) {
                $this->fail('Customer return OTP and at least one return photo are required. Ask admin if these are unavailable.');
            }
            $this->apply($booking, $data, $admin);
            $booking->final_bill_generated_at = now();
            $paid = $booking->payment_status === 'paid' && (float) $booking->remaining_amount <= 0.009;
            $booking->status = $paid ? 'completed' : 'running';
            $booking->booking_status = $paid ? 'completed' : 'final_bill_generated';
            $booking->settlement_status = $paid ? 'completed' : 'balance_due';
            $booking->completed_at = $paid ? ($booking->completed_at ?? now()) : null;
            $booking->return_completion_audit = [
                'base_rental' => $booking->return_completion_audit['base_rental'],
                'actor_id' => $actorId, 'method' => $admin ? 'admin_override' : 'customer_otp',
                'recorded_at' => now()->toIso8601String(), 'details' => $data,
                'photos' => $booking->return_draft['photos'] ?? [],
                'bill' => $this->bill($booking),
            ];
            $booking->save();
            return $booking;
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['return' => $message]);
    }
}
