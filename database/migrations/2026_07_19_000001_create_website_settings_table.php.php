<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | General
            |--------------------------------------------------------------------------
            */

            $table->string('site_name')->default('Dura Cabs');
            $table->string('tagline')->nullable();

            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Default SEO
            |--------------------------------------------------------------------------
            */

            $table->string('default_meta_title')->nullable();
            $table->text('default_meta_description')->nullable();
            $table->string('default_meta_keywords')->nullable();
            $table->string('default_og_image')->nullable();

            $table->string('robots')->default('index, follow');
            $table->string('twitter_username')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Business information
            |--------------------------------------------------------------------------
            */

            $table->string('business_name')->nullable();
            $table->string('business_type')->default('TaxiService');
            $table->text('business_description')->nullable();

            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();

            $table->text('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 5)->default('IN');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->text('google_map_url')->nullable();
            $table->string('price_range')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Opening hours
            |--------------------------------------------------------------------------
            */

            $table->boolean('open_24_hours')->default(true);
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Social profiles
            |--------------------------------------------------------------------------
            */

            $table->text('facebook_url')->nullable();
            $table->text('instagram_url')->nullable();
            $table->text('linkedin_url')->nullable();
            $table->text('twitter_url')->nullable();
            $table->text('youtube_url')->nullable();
            $table->text('pinterest_url')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Ratings
            |--------------------------------------------------------------------------
            */

            $table->decimal('rating_value', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->decimal('best_rating', 3, 2)->default(5);

            /*
            |--------------------------------------------------------------------------
            | Analytics and verification
            |--------------------------------------------------------------------------
            */

            $table->string('google_tag_manager_id')->nullable();
            $table->string('google_analytics_id')->nullable();
            $table->string('google_ads_id')->nullable();

            $table->string('google_site_verification')->nullable();
            $table->string('google_site_verification_secondary')->nullable();
            $table->string('bing_site_verification')->nullable();
            $table->string('yandex_verification')->nullable();
            $table->string('pinterest_domain_verification')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};