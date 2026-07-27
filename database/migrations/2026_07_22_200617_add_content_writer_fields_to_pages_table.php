<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            /*
            |--------------------------------------------------------------------------
            | Content Writer
            |--------------------------------------------------------------------------
            */

            $table->string('content_type', 50)
                ->default('page')
                ->after('description');

            $table->text('excerpt')
                ->nullable()
                ->after('content_type');

            $table->string('author_name')
                ->nullable()
                ->after('excerpt');

            $table->unsignedInteger('reading_time')
                ->nullable()
                ->after('author_name');

            /*
            |--------------------------------------------------------------------------
            | SEO
            |--------------------------------------------------------------------------
            */

            $table->string('focus_keyword')
                ->nullable()
                ->after('meta_keywords');

            $table->json('secondary_keywords')
                ->nullable()
                ->after('focus_keyword');

            $table->text('canonical_url')
                ->nullable()
                ->after('secondary_keywords');

            $table->string('robots', 100)
                ->default('index,follow')
                ->after('canonical_url');

            $table->unsignedTinyInteger('seo_score')
                ->default(0)
                ->after('robots');

            $table->unsignedTinyInteger('readability_score')
                ->default(0)
                ->after('seo_score');

            /*
            |--------------------------------------------------------------------------
            | Open Graph
            |--------------------------------------------------------------------------
            */

            $table->string('og_title')
                ->nullable()
                ->after('readability_score');

            $table->text('og_description')
                ->nullable()
                ->after('og_title');

            $table->string('og_image')
                ->nullable()
                ->after('og_description');

            /*
            |--------------------------------------------------------------------------
            | Twitter / X
            |--------------------------------------------------------------------------
            */

            $table->string('twitter_title')
                ->nullable()
                ->after('og_image');

            $table->text('twitter_description')
                ->nullable()
                ->after('twitter_title');

            $table->string('twitter_image')
                ->nullable()
                ->after('twitter_description');

            /*
            |--------------------------------------------------------------------------
            | Schema
            |--------------------------------------------------------------------------
            */

            $table->string('schema_type', 100)
                ->default('WebPage')
                ->after('twitter_image');

            $table->json('faq_schema')
                ->nullable()
                ->after('schema_type');

            $table->json('breadcrumb_schema')
                ->nullable()
                ->after('faq_schema');

            $table->json('custom_schema')
                ->nullable()
                ->after('breadcrumb_schema');

            /*
            |--------------------------------------------------------------------------
            | CTA and related content
            |--------------------------------------------------------------------------
            */

            $table->json('cta')
                ->nullable()
                ->after('custom_schema');

            $table->json('internal_links')
                ->nullable()
                ->after('cta');

            $table->json('related_pages')
                ->nullable()
                ->after('internal_links');

            $table->json('related_products')
                ->nullable()
                ->after('related_pages');

            $table->json('related_blogs')
                ->nullable()
                ->after('related_products');

            /*
            |--------------------------------------------------------------------------
            | Publishing
            |--------------------------------------------------------------------------
            */

            $table->timestamp('published_at')
                ->nullable()
                ->after('related_blogs');

            $table->unsignedBigInteger('updated_by')
                ->nullable()
                ->after('published_at');

            $table->index('content_type');
            $table->index('focus_keyword');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropIndex(['content_type']);
            $table->dropIndex(['focus_keyword']);
            $table->dropIndex(['published_at']);

            $table->dropColumn([
                'content_type',
                'excerpt',
                'author_name',
                'reading_time',
                'focus_keyword',
                'secondary_keywords',
                'canonical_url',
                'robots',
                'seo_score',
                'readability_score',
                'og_title',
                'og_description',
                'og_image',
                'twitter_title',
                'twitter_description',
                'twitter_image',
                'schema_type',
                'faq_schema',
                'breadcrumb_schema',
                'custom_schema',
                'cta',
                'internal_links',
                'related_pages',
                'related_products',
                'related_blogs',
                'published_at',
                'updated_by',
            ]);
        });
    }
};