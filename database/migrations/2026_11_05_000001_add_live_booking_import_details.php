<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('live_bookings')) {
            throw new RuntimeException('live_bookings table is missing. Run its original migration first.');
        }

        Schema::table('live_bookings', function (Blueprint $table): void {
            if (!Schema::hasColumn('live_bookings', 'booking_amount')) {
                $table->decimal('booking_amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('live_bookings', 'extra_distance_km')) {
                $table->decimal('extra_distance_km', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('live_bookings', 'extra_requirements')) {
                $table->text('extra_requirements')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Preserve imported booking details during rollback; remove columns manually only after backup.
    }
};
