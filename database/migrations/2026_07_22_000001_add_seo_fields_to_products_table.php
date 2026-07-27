<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'focus_keyword')) {
                $table
                    ->string('focus_keyword', 150)
                    ->nullable()
                    ->after('meta_description');
            }

            if (! Schema::hasColumn('products', 'canonical_url')) {
                $table
                    ->string('canonical_url', 500)
                    ->nullable()
                    ->after('focus_keyword');
            }

            if (! Schema::hasColumn('products', 'robots_index')) {
                $table
                    ->boolean('robots_index')
                    ->default(true)
                    ->after('canonical_url');
            }

            if (! Schema::hasColumn('products', 'robots_follow')) {
                $table
                    ->boolean('robots_follow')
                    ->default(true)
                    ->after('robots_index');
            }

            if (! Schema::hasColumn('products', 'seo_score')) {
                $table
                    ->unsignedTinyInteger('seo_score')
                    ->default(0)
                    ->after('robots_follow');
            }

            if (! Schema::hasColumn('products', 'readability_score')) {
                $table
                    ->unsignedTinyInteger('readability_score')
                    ->default(0)
                    ->after('seo_score');
            }

            if (! Schema::hasColumn('products', 'seo_analysis')) {
                $table
                    ->json('seo_analysis')
                    ->nullable()
                    ->after('readability_score');
            }

            if (! Schema::hasColumn('products', 'og_title')) {
                $table
                    ->string('og_title', 255)
                    ->nullable()
                    ->after('seo_analysis');
            }

            if (! Schema::hasColumn('products', 'og_description')) {
                $table
                    ->text('og_description')
                    ->nullable()
                    ->after('og_title');
            }

            if (! Schema::hasColumn('products', 'og_image')) {
                $table
                    ->string('og_image', 500)
                    ->nullable()
                    ->after('og_description');
            }

            if (! Schema::hasColumn('products', 'twitter_title')) {
                $table
                    ->string('twitter_title', 255)
                    ->nullable()
                    ->after('og_image');
            }

            if (! Schema::hasColumn('products', 'twitter_description')) {
                $table
                    ->text('twitter_description')
                    ->nullable()
                    ->after('twitter_title');
            }

            if (! Schema::hasColumn('products', 'twitter_image')) {
                $table
                    ->string('twitter_image', 500)
                    ->nullable()
                    ->after('twitter_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $columns = [
                'focus_keyword',
                'canonical_url',
                'robots_index',
                'robots_follow',
                'seo_score',
                'readability_score',
                'seo_analysis',
                'og_title',
                'og_description',
                'og_image',
                'twitter_title',
                'twitter_description',
                'twitter_image',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};