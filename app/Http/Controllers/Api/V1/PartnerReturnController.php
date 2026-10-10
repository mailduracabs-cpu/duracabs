<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SelfDriveBooking;
use App\Services\PartnerReturnCompletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PartnerReturnController extends PartnerController
{
    private function booking(Request $request, int $id): SelfDriveBooking
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'host', 403, 'Self Drive host account required.');
        return SelfDriveBooking::query()->where('transporter_profile_id', $profile->getKey())
            ->where('booking_type', 'car')->findOrFail($id);
    }

    private function service(): PartnerReturnCompletionService
    {
        return app(PartnerReturnCompletionService::class);
    }

    public function show(Request $request, int $booking)
    {
        $row = $this->booking($request, $booking);
        $draft = $row->return_draft ?? [];
        $photos = [];
        foreach ($draft['photos'] ?? [] as $slot => $path) {
            if (Storage::disk('local')->exists($path)) $photos[] = $slot;
        }
        return response()->json(['data' => [
            'booking_no' => $row->booking_no, 'status' => $row->status,
            'start_km' => $row->start_km, 'started_at' => $row->trip_start_datetime?->toIso8601String(),
            'details' => $draft['details'] ?? [
                'returned_at' => $row->trip_end_datetime?->toIso8601String(),
                'end_km' => $row->end_km, 'fuel' => $row->drop_fuel_level,
                'damage_amount' => (float) $row->damage_amount, 'fuel_charge' => (float) $row->fuel_charge,
                'cleaning_charge' => (float) $row->cleaning_charge, 'other_charge' => (float) $row->other_charge,
                'note' => $row->damage_note,
            ], 'photos' => $photos,
            'otp_verified' => (bool) $row->return_otp_verified_at,
            'admin_requested' => isset($draft['admin_request']),
            'admin_request' => $draft['admin_request'] ?? null,
            'admin_confirmed' => (bool) $row->return_admin_confirmed_at,
            'bill' => $this->service()->bill($row),
        ]]);
    }

    public function draft(Request $request, int $booking)
    {
        $owned = $this->booking($request, $booking);
        $data = $this->service()->data($request->all());
        DB::transaction(function () use ($owned, $data) {
            $row = SelfDriveBooking::query()->where('transporter_profile_id', $owned->transporter_profile_id)->lockForUpdate()->findOrFail($owned->id);
            $this->service()->mutable($row);
            $draft = $row->return_draft ?? [];
            $draft['details'] = $data;
            $row->return_draft = $draft;
            $row->save();
        });
        return response()->json(['message' => 'Return details saved.']);
    }

    public function photo(Request $request, int $booking)
    {
        $owned = $this->booking($request, $booking);
        $request->validate(['slot' => 'required|in:front,back,left,right', 'file' => 'required|image|mimes:jpeg,jpg,png,webp|max:10240']);
        $this->service()->mutable($owned);
        $slot = $request->string('slot')->toString();
        $path = $request->file('file')->store("partner-return/{$owned->id}", 'local');
        $old = null;
        try {
            DB::transaction(function () use ($owned, $slot, $path, &$old) {
                $row = SelfDriveBooking::query()->where('transporter_profile_id', $owned->transporter_profile_id)->lockForUpdate()->findOrFail($owned->id);
                $this->service()->mutable($row);
                $draft = $row->return_draft ?? [];
                $old = $draft['photos'][$slot] ?? null;
                $draft['photos'][$slot] = $path;
                $row->return_draft = $draft;
                $row->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        if ($old) Storage::disk('local')->delete($old);
        return response()->json(['message' => 'Return photo uploaded.']);
    }

    public function photoView(Request $request, int $booking, string $slot)
    {
        $row = $this->booking($request, $booking);
        abort_unless(in_array($slot, ['front','back','left','right'], true), 404);
        $path = $row->return_draft['photos'][$slot] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function adminPhoto(Request $request, int $booking, string $slot)
    {
        abort_unless($request->user()?->canUseAdminLogin(), 403);
        $row = SelfDriveBooking::query()->where('booking_type', 'car')->findOrFail($booking);
        abort_unless(in_array($slot, ['front','back','left','right'], true), 404);
        $path = $row->return_draft['photos'][$slot] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function otp(Request $request, int $booking)
    {
        $owned = $this->booking($request, $booking);
        $row = DB::transaction(function () use ($owned) {
            $row = SelfDriveBooking::query()->where('transporter_profile_id', $owned->transporter_profile_id)->lockForUpdate()->findOrFail($owned->id);
            $this->service()->mutable($row);
            if ($row->return_otp_verified_at) abort(422, 'Return OTP already verified.');
            if ($row->return_otp_generated_at && $row->return_otp_generated_at->gt(now()->subMinute())) abort(429, 'Wait one minute before resending.');
            // Keep failed attempts across resend requests to prevent brute-force resets.
            if ((int) $row->return_otp_attempts >= 5) abort(422, 'OTP attempts exhausted. Request admin completion.');
            $row->forceFill([
                'return_otp' => (string) random_int(1000, 9999),
                'return_otp_generated_at' => now(), 'return_otp_expires_at' => now()->addMinutes(30),
                'end_requested_at' => now(), 'booking_status' => 'end_otp_generated',
            ])->save();
            return $row;
        });
        try {
            $sent = $row->sendSelfDriveTemplate('selfdrive_return_otp', ['otp' => $row->return_otp]);
        } catch (\Throwable $e) {
            report($e); $sent = false;
        }
        return response()->json(['message' => $sent
            ? 'Return OTP sent to the customer.'
            : 'OTP generated but delivery failed. Retry later or request admin completion.', 'sent' => $sent]);
    }

    public function verify(Request $request, int $booking)
    {
        $owned = $this->booking($request, $booking);
        $request->validate(['otp' => 'required|string|regex:/^[0-9]{4}$/']);
        // Return errors after transaction commits, otherwise failed attempts roll back.
        $error = DB::transaction(function () use ($owned, $request) {
            $row = SelfDriveBooking::query()->where('transporter_profile_id', $owned->transporter_profile_id)->lockForUpdate()->findOrFail($owned->id);
            $this->service()->mutable($row);
            if ($row->return_otp_verified_at) return null;
            if ((int) $row->return_otp_attempts >= 5) return 'OTP attempts exhausted. Request admin completion.';
            if (! $row->return_otp_expires_at || now()->gt($row->return_otp_expires_at)) return 'OTP expired. Send a fresh OTP.';
            $row->return_otp_attempts = (int) $row->return_otp_attempts + 1;
            $valid = filled($row->return_otp) && hash_equals((string) $row->return_otp, (string) $request->input('otp'));
            if ($valid) $row->return_otp_verified_at = now();
            $row->save();
            return $valid ? null : 'Incorrect OTP.';
        });
        if ($error) throw ValidationException::withMessages(['otp' => $error]);
        return response()->json(['message' => 'Customer return OTP verified.']);
    }

    public function preview(Request $request, int $booking)
    {
        $row = $this->booking($request, $booking);
        return response()->json(['data' => $this->service()->preview($row, $request->all())]);
    }

    public function complete(Request $request, int $booking)
    {
        $row = $this->booking($request, $booking);
        $done = $this->service()->finish($row->id, $request->all(), (int) $request->user()->id);
        return response()->json(['data' => $this->service()->bill($done), 'message' => $done->status === 'completed'
            ? 'Return recorded, final bill generated and trip completed.'
            : 'Return and bill recorded. Customer balance is pending; admin must record payment before payout eligibility.']);
    }

    public function adminRequest(Request $request, int $booking)
    {
        $owned = $this->booking($request, $booking);
        $request->validate(['reason' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($owned, $request) {
            $row = SelfDriveBooking::query()->where('transporter_profile_id', $owned->transporter_profile_id)->lockForUpdate()->findOrFail($owned->id);
            $this->service()->mutable($row, true);
            $draft = $row->return_draft ?? [];
            $draft['admin_request'] = [
                'reason' => $request->input('reason'), 'requested_at' => now()->toIso8601String(),
                'requested_by' => (int) $request->user()->id,
            ];
            $row->return_draft = $draft;
            $row->save();
        });
        return response()->json(['message' => 'Completion request is visible in admin Self Drive bookings.']);
    }
}
