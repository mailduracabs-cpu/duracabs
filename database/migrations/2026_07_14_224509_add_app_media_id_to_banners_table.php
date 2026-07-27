<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {

            if (! Schema::hasColumn('banners', 'app_media_id')) {

                $table->foreignId('app_media_id')
                    ->nullable()
                    ->after('image')
                    ->constrained('app_media')
                    ->nullOnDelete();

            }

        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {

            if (Schema::hasColumn('banners', 'app_media_id')) {

                $table->dropConstrainedForeignId('app_media_id');

            }

        });
    }
};