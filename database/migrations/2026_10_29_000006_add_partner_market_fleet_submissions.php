<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('partner_market_fleet_submissions')) Schema::create('partner_market_fleet_submissions', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('post_id'); $t->unsignedBigInteger('vendor_id');
            $t->unsignedInteger('post_revision'); $t->unsignedBigInteger('driver_id'); $t->unsignedBigInteger('vehicle_id');
            $t->string('driver_name', 255); $t->string('taxi_name', 255); $t->string('taxi_number', 40); $t->string('car_classification', 100);
            $t->string('photo_path', 255); $t->string('photo_mime', 40); $t->string('client_request_id', 80);
            $t->dateTime('created_at')->nullable();
            $t->unique(['vendor_id', 'client_request_id'], 'pm_fleet_client_once');
            $t->index(['post_id', 'vendor_id', 'post_revision'], 'pm_fleet_thread_revision');
        });
        if (!Schema::hasColumn('partner_market_posts', 'assigned_fleet_submission_id')) Schema::table('partner_market_posts', fn (Blueprint $t) => $t->unsignedBigInteger('assigned_fleet_submission_id')->nullable());
        foreach (['partner_market_messages', 'partner_market_secure_payments'] as $table) {
            if (!Schema::hasColumn($table, 'fleet_submission_id')) Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('fleet_submission_id')->nullable()->index());
        }
    }
    public function down(): void { /* Keep sent photos and dispatch evidence. */ }
};
