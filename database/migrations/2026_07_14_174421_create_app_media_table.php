<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_media', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('slug')->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Classification
            |--------------------------------------------------------------------------
            */

            $table->string('media_type', 50)->index();
            $table->string('module', 100)->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Storage
            |--------------------------------------------------------------------------
            */

            $table->string('disk', 50)->default('public');
            $table->string('directory')->nullable();

            $table->string('original_path')->nullable();
            $table->string('large_path')->nullable();
            $table->string('medium_path')->nullable();
            $table->string('thumbnail_path')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Original File Information
            |--------------------------------------------------------------------------
            */

            $table->string('original_name')->nullable();
            $table->string('original_extension', 20)->nullable();
            $table->string('mime_type', 100)->nullable();

            $table->unsignedBigInteger('original_size')->default(0);
            $table->unsignedBigInteger('optimized_size')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Image Information
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->unsignedTinyInteger('quality')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Duplicate Detection
            |--------------------------------------------------------------------------
            */

            $table->string('file_hash', 64)
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Content
            |--------------------------------------------------------------------------
            */

            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();

            /*
            |--------------------------------------------------------------------------
            | State
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->boolean('is_public')
                ->default(true)
                ->index();

            $table->unsignedInteger('reference_count')
                ->default(0);

            $table->unsignedInteger('sort_order')
                ->default(0)
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Ownership
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('uploaded_by')->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Extra Information
            |--------------------------------------------------------------------------
            */

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Useful Compound Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['media_type', 'module', 'is_active'],
                'app_media_type_module_active_idx'
            );

            $table->index(
                ['is_public', 'is_active', 'sort_order'],
                'app_media_public_active_sort_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_media');
    }
};