<?php

namespace App\Filament\Resources\SelfDriveBookingResource\Pages;

use App\Filament\Resources\SelfDriveBookingResource;
use App\Models\SelfDriveBooking;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateSelfDriveBooking extends CreateRecord
{
    protected static string $resource = SelfDriveBookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $totalAmount = max(
            0,
            (float) ($data['total_amount'] ?? 0)
        );

        $finalAmount = max(
            0,
            (float) ($data['final_amount'] ?? $totalAmount)
        );

        $paymentType = $data['payment_type'] ?? 'advance';

        if (
            $paymentType === 'advance'
            && $finalAmount < 500
        ) {
            throw ValidationException::withMessages([
                'data.payment_type' =>
                    '₹500 advance booking ke liye payable amount kam se kam ₹500 hona chahiye.',
            ]);
        }

        $paidAmount = $paymentType === 'full'
            ? $finalAmount
            : 500;

        $remainingAmount = max(
            0,
            $finalAmount - $paidAmount
        );

        /*
        |--------------------------------------------------------------------------
        | Booking Payment
        |--------------------------------------------------------------------------
        */

        $data['total_amount'] = $totalAmount;
        $data['final_amount'] = $finalAmount;

        $data['advance_amount'] =
            $paymentType === 'advance'
                ? 500
                : 0;

        $data['paid_amount'] = $paidAmount;
        $data['remaining_amount'] = $remainingAmount;
        $data['balance_due'] = $remainingAmount;

        $data['payment_status'] =
            $remainingAmount > 0
                ? 'partial'
                : 'paid';

        $data['payment_completed_at'] =
            $data['payment_status'] === 'paid'
                ? now()
                : null;

        /*
        |--------------------------------------------------------------------------
        | Booking Workflow Defaults
        |--------------------------------------------------------------------------
        */

        $data['status'] =
            SelfDriveBooking::STATUS_PENDING;

        $data['booking_status'] ??=
            'pending_vendor_confirmation';

        $data['vendor_confirmation_status'] ??=
            'pending';

        $data['document_status'] ??=
            'not_uploaded';

        $data['settlement_status'] ??=
            SelfDriveBooking::SETTLEMENT_PENDING;

        $data['refund_status'] ??=
            'not_applicable';

        /*
        |--------------------------------------------------------------------------
        | Duration Defaults
        |--------------------------------------------------------------------------
        */

        $data['booked_hours'] = max(
            1,
            (int) ($data['booked_hours'] ?? 1)
        );

        $data['minimum_booking_hours'] = max(
            1,
            (int) ($data['minimum_booking_hours'] ?? 1)
        );

        $data['total_days'] = max(
            1,
            (int) ($data['total_days'] ?? 1)
        );

        /*
        |--------------------------------------------------------------------------
        | Trip / KM Defaults
        |--------------------------------------------------------------------------
        */

        $data['actual_hours'] =
            (float) ($data['actual_hours'] ?? 0);

        $data['free_km'] =
            (float) ($data['free_km'] ?? 0);

        $data['actual_km'] =
            (float) ($data['actual_km'] ?? 0);

        $data['extra_hours'] =
            (float) ($data['extra_hours'] ?? 0);

        $data['extra_km'] =
            (float) ($data['extra_km'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Charge Defaults
        |--------------------------------------------------------------------------
        */

        $data['extra_hour_rate'] =
            (float) ($data['extra_hour_rate'] ?? 0);

        $data['extra_km_rate'] =
            (float) ($data['extra_km_rate'] ?? 0);

        $data['extra_hour_amount'] =
            (float) ($data['extra_hour_amount'] ?? 0);

        $data['extra_km_amount'] =
            (float) ($data['extra_km_amount'] ?? 0);

        $data['damage_amount'] =
            (float) ($data['damage_amount'] ?? 0);

        $data['fuel_charge'] =
            (float) ($data['fuel_charge'] ?? 0);

        $data['cleaning_charge'] =
            (float) ($data['cleaning_charge'] ?? 0);

        $data['late_return_charge'] =
            (float) ($data['late_return_charge'] ?? 0);

        $data['other_charge'] =
            (float) ($data['other_charge'] ?? 0);

        $data['refund_amount'] =
            (float) ($data['refund_amount'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | OTP Rule
        |--------------------------------------------------------------------------
        | Booking create hote waqt Pickup / Return OTP generate nahi honge.
        */

        $data['pickup_otp'] = null;
        $data['pickup_otp_generated_at'] = null;
        $data['pickup_otp_expires_at'] = null;
        $data['pickup_otp_attempts'] = 0;
        $data['pickup_otp_verified_at'] = null;

        $data['return_otp'] = null;
        $data['return_otp_generated_at'] = null;
        $data['return_otp_expires_at'] = null;
        $data['return_otp_attempts'] = 0;
        $data['return_otp_verified_at'] = null;

        /*
        |--------------------------------------------------------------------------
        | Empty Date Defaults
        |--------------------------------------------------------------------------
        */

        $data['trip_start_datetime'] ??= null;
        $data['trip_end_datetime'] ??= null;
        $data['registration_unlocked_at'] ??= null;
        $data['end_requested_at'] ??= null;
        $data['completed_at'] ??= null;
        $data['final_bill_generated_at'] ??= null;

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Self Drive Booking Created')
            ->body(
                $this->record->payment_type === 'advance'
                    ? '₹500 advance received. Remaining payment pickup ke time liya ja sakta hai.'
                    : 'Full payment received.'
            )
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}