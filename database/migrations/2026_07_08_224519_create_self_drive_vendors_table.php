<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('self_drive_vendors')) {
            Schema::create('self_drive_vendors', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('vendor_id')->nullable()->index();

                $table->string('office_name')->nullable();
                $table->text('pickup_address')->nullable();

                $table->unsignedBigInteger('city_id')->nullable()->index();

                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();

                $table->decimal('service_radius_km', 8, 2)->default(30);

                $table->boolean('is_active')->default(true)->index();

                $table->timestamps();

                $table->index(['latitude', 'longitude']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('self_drive_vendors');
    }
};