<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'content_type')) {
                $table->string('content_type', 30)
                    ->default('seo_page')
                    ->after('slug')
                    ->index();
            }

            if (! Schema::hasColumn('products', 'url_type')) {
                $table->string('url_type', 20)
                    ->default('route')
                    ->after('content_type')
                    ->index();
            }

            if (! Schema::hasColumn('products', 'content_links')) {
                $table->json('content_links')
                    ->nullable()
                    ->after('description');
            }

            if (! Schema::hasColumn('products', 'fare_cards')) {
                $table->json('fare_cards')
                    ->nullable()
                    ->after('content_links');
            }
        });

        // This only adds classification metadata. It never modifies a slug.
        DB::table('products')
            ->whereNull('content_type')
            ->update(['content_type' => 'seo_page']);

        DB::table('products')
            ->whereNull('url_type')
            ->update(['url_type' => 'route']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            foreach (['fare_cards', 'content_links', 'url_type', 'content_type'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};