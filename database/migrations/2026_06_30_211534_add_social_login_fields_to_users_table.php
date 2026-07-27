<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('mobile');
            }

            if (!Schema::hasColumn('users', 'facebook_id')) {
                $table->string('facebook_id')->nullable()->after('google_id');
            }

            if (!Schema::hasColumn('users', 'apple_id')) {
                $table->string('apple_id')->nullable()->after('facebook_id');
            }

            if (!Schema::hasColumn('users', 'whatsapp_id')) {
                $table->string('whatsapp_id')->nullable()->after('apple_id');
            }

            if (!Schema::hasColumn('users', 'photo')) {
                $table->string('photo')->nullable()->after('whatsapp_id');
            }

            if (!Schema::hasColumn('users', 'device_token')) {
                $table->text('device_token')->nullable()->after('photo');
            }

            if (!Schema::hasColumn('users', 'login_type')) {
                $table->string('login_type')->default('otp')->after('device_token');
            }

            if (!Schema::hasColumn('users', 'otp')) {
                $table->string('otp', 10)->nullable()->after('login_type');
            }

            if (!Schema::hasColumn('users', 'otp_expire_at')) {
                $table->timestamp('otp_expire_at')->nullable()->after('otp');
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'google_id',
                'facebook_id',
                'apple_id',
                'whatsapp_id',
                'photo',
                'device_token',
                'login_type',
                'otp',
                'otp_expire_at'
            ]);

        });
    }
};