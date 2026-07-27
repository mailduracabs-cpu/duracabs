<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_media_id')
                ->constrained('app_media')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Polymorphic Usage
            |--------------------------------------------------------------------------
            |
            | Example:
            | usable_type = App\Models\Banners
            | usable_id   = 15
            |
            | usable_type = App\Models\SelfDriveVehicle
            | usable_id   = 22
            |
            */

            $table->string('usable_type');
            $table->unsignedBigInteger('usable_id');

            /*
            |--------------------------------------------------------------------------
            | Field / Position
            |--------------------------------------------------------------------------
            |
            | Example:
            | field_name = image
            | field_name = gallery
            | field_name = rc_document
            |
            */

            $table->string('field_name', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Variant Preference
            |--------------------------------------------------------------------------
            |
            | original, large, medium, thumbnail
            |
            */

            $table->string('preferred_variant', 30)
                ->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(
                ['usable_type', 'usable_id'],
                'media_usages_usable_idx'
            );

            $table->index(
                ['app_media_id', 'field_name'],
                'media_usages_media_field_idx'
            );

            $table->unique(
                [
                    'app_media_id',
                    'usable_type',
                    'usable_id',
                    'field_name',
                ],
                'media_usages_unique_relation'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
    }
};