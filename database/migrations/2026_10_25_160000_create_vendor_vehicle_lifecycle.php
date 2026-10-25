<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preflight before DDL: never delete/merge existing duplicates automatically.
        $seen = [];
        foreach (DB::table('vehicles')->select('id', 'vehicle_number')->orderBy('id')->cursor() as $row) {
            $key = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $row->vehicle_number));
            if ($key === '') continue;
            if (isset($seen[$key])) throw new RuntimeException("Duplicate registration {$key}: vehicle IDs {$seen[$key]} and {$row->id}. Resolve ownership before migration.");
            $seen[$key] = $row->id;
        }

        foreach (['first_registration_date', 'partner_agreement_expires_on', 'rc_expiry_date', 'gps_expiry_date', 'insurance_expiry_date', 'puc_expiry_date'] as $field) {
            if (! Schema::hasColumn('vehicles', $field)) Schema::table('vehicles', fn (Blueprint $t) => $t->date($field)->nullable());
        }
        if (! Schema::hasColumn('vehicles', 'vehicle_registration_key')) {
            Schema::table('vehicles', fn (Blueprint $t) => $t->string('vehicle_registration_key', 64)->nullable()->unique('vehicles_registration_key_unique'));
        }
        foreach ($seen as $key => $id) DB::table('vehicles')->where('id', $id)->update(['vehicle_registration_key' => $key]);

        if (! Schema::hasTable('vendor_monthly_bills')) Schema::create('vendor_monthly_bills', function (Blueprint $t): void {
            $t->id(); $t->string('bill_no')->unique();
            $t->unsignedBigInteger('vehicle_id')->index(); $t->unsignedBigInteger('transporter_profile_id')->index();
            $t->date('period_start'); $t->date('period_end'); $t->date('bill_date')->index();
            $t->decimal('monthly_amount', 12, 2); $t->decimal('amount', 12, 2);
            $t->string('status')->default('pending'); $t->timestamp('paid_at')->nullable();
            $t->string('payment_reference')->nullable(); $t->unsignedBigInteger('paid_by')->nullable();
            $t->json('terms'); $t->timestamps();
            $t->unique(['vehicle_id', 'period_start'], 'vendor_bill_vehicle_period_unique');
        });
        if (! Schema::hasTable('vendor_delivery_logs')) Schema::create('vendor_delivery_logs', function (Blueprint $t): void {
            $t->id(); $t->string('event_key', 191)->unique(); $t->string('channel', 20);
            $t->string('recipient'); $t->json('payload');
            $t->string('status', 30)->default('pending')->index();
            $t->unsignedInteger('attempts')->default(0); $t->text('last_error')->nullable();
            $t->string('provider_id')->nullable(); $t->timestamp('sent_at')->nullable(); $t->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This migration preserves agreement and bill history. Use a reviewed restore instead of dropping financial/reminder records.');
    }
};
