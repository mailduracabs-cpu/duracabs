<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            // Additional optional media references for the 10-photo vehicle gallery.
            $table->unsignedBigInteger('left_side_media_id')->nullable()->after('back_media_id');
            $table->unsignedBigInteger('right_side_media_id')->nullable()->after('left_side_media_id');
            $table->unsignedBigInteger('front_left_media_id')->nullable()->after('right_side_media_id');
            $table->unsignedBigInteger('front_right_media_id')->nullable()->after('front_left_media_id');
            $table->unsignedBigInteger('front_seats_media_id')->nullable()->after('interior_media_id');
            $table->unsignedBigInteger('rear_seats_media_id')->nullable()->after('front_seats_media_id');
            $table->unsignedBigInteger('boot_media_id')->nullable()->after('rear_seats_media_id');

            // Legacy paths are kept so existing website/API image handling stays compatible.
            $table->string('left_side_image')->nullable()->after('back_image');
            $table->string('right_side_image')->nullable()->after('left_side_image');
            $table->string('front_left_image')->nullable()->after('right_side_image');
            $table->string('front_right_image')->nullable()->after('front_left_image');
            $table->string('front_seats_image')->nullable()->after('interior_image');
            $table->string('rear_seats_image')->nullable()->after('front_seats_image');
            $table->string('boot_image')->nullable()->after('rear_seats_image');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn([
                'left_side_media_id',
                'right_side_media_id',
                'front_left_media_id',
                'front_right_media_id',
                'front_seats_media_id',
                'rear_seats_media_id',
                'boot_media_id',
                'left_side_image',
                'right_side_image',
                'front_left_image',
                'front_right_image',
                'front_seats_image',
                'rear_seats_image',
                'boot_image',
            ]);
        });
    }
};