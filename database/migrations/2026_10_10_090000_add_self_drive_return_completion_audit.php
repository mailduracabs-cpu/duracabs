<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('self_drive_bookings', function (Blueprint $table) {
            $table->json('return_draft')->nullable();
            $table->json('return_completion_audit')->nullable();
            $table->timestamp('return_admin_confirmed_at')->nullable();
            $table->unsignedBigInteger('return_admin_confirmed_by')->nullable();
            $table->text('return_admin_reason')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('self_drive_bookings', fn (Blueprint $table) => $table->dropColumn([
            'return_draft', 'return_completion_audit', 'return_admin_confirmed_at',
            'return_admin_confirmed_by', 'return_admin_reason',
        ]));
    }
};
