<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Vehicle Images
            |--------------------------------------------------------------------------
            */

            $table->foreignId('front_media_id')
                ->nullable()
                ->after('front_image')
                ->constrained('app_media')
                ->nullOnDelete();

            $table->foreignId('back_media_id')
                ->nullable()
                ->after('back_image')
                ->constrained('app_media')
                ->nullOnDelete();

            $table->foreignId('interior_media_id')
                ->nullable()
                ->after('interior_image')
                ->constrained('app_media')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            $table->foreignId('rc_media_id')
                ->nullable()
                ->after('rc_image')
                ->constrained('app_media')
                ->nullOnDelete();

            $table->foreignId('insurance_media_id')
                ->nullable()
                ->after('insurance_image')
                ->constrained('app_media')
                ->nullOnDelete();

            $table->foreignId('pollution_media_id')
                ->nullable()
                ->after('polution_image')
                ->constrained('app_media')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {

            $table->dropForeign(['front_media_id']);
            $table->dropForeign(['back_media_id']);
            $table->dropForeign(['interior_media_id']);

            $table->dropForeign(['rc_media_id']);
            $table->dropForeign(['insurance_media_id']);
            $table->dropForeign(['pollution_media_id']);

            $table->dropColumn([
                'front_media_id',
                'back_media_id',
                'interior_media_id',
                'rc_media_id',
                'insurance_media_id',
                'pollution_media_id',
            ]);
        });
    }
};