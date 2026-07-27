<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('self_drive_bookings')) {
            return;
        }

        Schema::table('self_drive_bookings', function (Blueprint $table): void {
            /*
            |--------------------------------------------------------------------------
            | Workflow Status
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'booking_status'
            )) {
                $table->string('booking_status', 60)
                    ->default('pending_payment')
                    ->index();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'vendor_confirmation_status'
            )) {
                $table->string(
                    'vendor_confirmation_status',
                    30
                )->default('pending')->index();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'document_status'
            )) {
                $table->string('document_status', 30)
                    ->default('not_uploaded')
                    ->index();
            }

            /*
            |--------------------------------------------------------------------------
            | Vendor Confirmation
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'vendor_confirmed_at'
            )) {
                $table->timestamp('vendor_confirmed_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'vendor_rejected_at'
            )) {
                $table->timestamp('vendor_rejected_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'vendor_rejection_reason'
            )) {
                $table->text('vendor_rejection_reason')
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'payment_type'
            )) {
                $table->string('payment_type', 30)
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'payment_status'
            )) {
                $table->string('payment_status', 30)
                    ->default('pending')
                    ->index();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'payment_method'
            )) {
                $table->string('payment_method', 50)
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'payment_reference'
            )) {
                $table->string('payment_reference', 255)
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'advance_amount'
            )) {
                $table->decimal('advance_amount', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'paid_amount'
            )) {
                $table->decimal('paid_amount', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'remaining_amount'
            )) {
                $table->decimal('remaining_amount', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'payment_completed_at'
            )) {
                $table->timestamp('payment_completed_at')
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | Customer Documents
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'aadhaar_front'
            )) {
                $table->string('aadhaar_front')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'aadhaar_back'
            )) {
                $table->string('aadhaar_back')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'driving_licence_front'
            )) {
                $table->string('driving_licence_front')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'driving_licence_back'
            )) {
                $table->string('driving_licence_back')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'customer_selfie'
            )) {
                $table->string('customer_selfie')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'documents_uploaded_at'
            )) {
                $table->timestamp('documents_uploaded_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'documents_verified_at'
            )) {
                $table->timestamp('documents_verified_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'documents_rejected_at'
            )) {
                $table->timestamp('documents_rejected_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'document_rejection_reason'
            )) {
                $table->text('document_rejection_reason')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'booking_confirmed_at'
            )) {
                $table->timestamp('booking_confirmed_at')
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | Pickup OTP Security
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'pickup_otp_generated_at'
            )) {
                $table->timestamp('pickup_otp_generated_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'pickup_otp_expires_at'
            )) {
                $table->timestamp('pickup_otp_expires_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'pickup_otp_attempts'
            )) {
                $table->unsignedTinyInteger(
                    'pickup_otp_attempts'
                )->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'registration_unlocked_at'
            )) {
                $table->timestamp(
                    'registration_unlocked_at'
                )->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | Return / End OTP
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'return_otp_generated_at'
            )) {
                $table->timestamp('return_otp_generated_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'return_otp_expires_at'
            )) {
                $table->timestamp('return_otp_expires_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'return_otp_attempts'
            )) {
                $table->unsignedTinyInteger(
                    'return_otp_attempts'
                )->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'end_requested_at'
            )) {
                $table->timestamp('end_requested_at')
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | Inspection Files
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'pickup_images'
            )) {
                $table->json('pickup_images')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'drop_images'
            )) {
                $table->json('drop_images')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'pickup_fuel_level'
            )) {
                $table->string('pickup_fuel_level', 50)
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'drop_fuel_level'
            )) {
                $table->string('drop_fuel_level', 50)
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | Final Charges
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'extra_hour_rate'
            )) {
                $table->decimal('extra_hour_rate', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'extra_km_rate'
            )) {
                $table->decimal('extra_km_rate', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'fuel_charge'
            )) {
                $table->decimal('fuel_charge', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'cleaning_charge'
            )) {
                $table->decimal('cleaning_charge', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'late_return_charge'
            )) {
                $table->decimal('late_return_charge', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'other_charge'
            )) {
                $table->decimal('other_charge', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'other_charge_note'
            )) {
                $table->text('other_charge_note')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'balance_due'
            )) {
                $table->decimal('balance_due', 12, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'final_bill_generated_at'
            )) {
                $table->timestamp('final_bill_generated_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'final_invoice_path'
            )) {
                $table->string('final_invoice_path')
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | Refund
            |--------------------------------------------------------------------------
            */

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'refund_status'
            )) {
                $table->string('refund_status', 30)
                    ->default('not_applicable')
                    ->index();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'refund_reference'
            )) {
                $table->string('refund_reference', 255)
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'refund_initiated_at'
            )) {
                $table->timestamp('refund_initiated_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'refunded_at'
            )) {
                $table->timestamp('refunded_at')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'self_drive_bookings',
                'completed_at'
            )) {
                $table->timestamp('completed_at')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('self_drive_bookings')) {
            return;
        }

        $columns = [
            'booking_status',
            'vendor_confirmation_status',
            'document_status',
            'vendor_confirmed_at',
            'vendor_rejected_at',
            'vendor_rejection_reason',
            'payment_type',
            'payment_status',
            'payment_method',
            'payment_reference',
            'advance_amount',
            'paid_amount',
            'remaining_amount',
            'payment_completed_at',
            'aadhaar_front',
            'aadhaar_back',
            'driving_licence_front',
            'driving_licence_back',
            'customer_selfie',
            'documents_uploaded_at',
            'documents_verified_at',
            'documents_rejected_at',
            'document_rejection_reason',
            'booking_confirmed_at',
            'pickup_otp_generated_at',
            'pickup_otp_expires_at',
            'pickup_otp_attempts',
            'registration_unlocked_at',
            'return_otp_generated_at',
            'return_otp_expires_at',
            'return_otp_attempts',
            'end_requested_at',
            'pickup_images',
            'drop_images',
            'pickup_fuel_level',
            'drop_fuel_level',
            'extra_hour_rate',
            'extra_km_rate',
            'fuel_charge',
            'cleaning_charge',
            'late_return_charge',
            'other_charge',
            'other_charge_note',
            'balance_due',
            'final_bill_generated_at',
            'final_invoice_path',
            'refund_status',
            'refund_reference',
            'refund_initiated_at',
            'refunded_at',
            'completed_at',
        ];

        $existingColumns = array_values(
            array_filter(
                $columns,
                fn (string $column): bool =>
                    Schema::hasColumn(
                        'self_drive_bookings',
                        $column
                    )
            )
        );

        if ($existingColumns === []) {
            return;
        }

        Schema::table(
            'self_drive_bookings',
            function (Blueprint $table) use (
                $existingColumns
            ): void {
                $table->dropColumn($existingColumns);
            }
        );
    }
};