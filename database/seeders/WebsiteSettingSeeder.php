<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        WebsiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Dura Cabs',
                'tagline' => 'Reliable Taxi and Car Rental Services',

                'default_meta_title' =>
                    'Dura Cabs | Taxi, Self Drive Car and Cab Booking',

                'default_meta_description' =>
                    'Book reliable taxi, one-way cab, outstation taxi, airport transfer and self-drive car rental services with Dura Cabs.',

                'robots' => 'index, follow',
                'twitter_username' => 'duracabs',

                'business_name' => 'Dura Cabs Services',
                'business_type' => 'TaxiService',

                'business_description' =>
                    'Dura Cabs provides taxi booking, one-way cab, outstation taxi, airport transfer, tour and self-drive car rental services.',

                'phone' => '+91-7088873331',
                'email' => 'info@duracabs.com',

                'street_address' =>
                    'First Floor, Shop No. 16, Kripadham Complex, Fatehabad Road',

                'city' => 'Agra',
                'state' => 'Uttar Pradesh',
                'postal_code' => '282001',
                'country_code' => 'IN',

                'google_map_url' =>
                    'https://maps.google.com/maps?cid=1793277674320271921',

                'price_range' => '₹₹',
                'open_24_hours' => true,

                'facebook_url' => 'https://m.facebook.com/duracabs',
                'instagram_url' => 'https://www.instagram.com/duracabs/',
                'linkedin_url' => 'https://www.linkedin.com/in/duracabs',
                'twitter_url' => 'https://x.com/duracabs',
                'pinterest_url' => 'https://in.pinterest.com/duracabs321/',

                'rating_value' => 4.60,
                'review_count' => 44,
                'best_rating' => 5,

                'google_tag_manager_id' => 'GTM-NSMPPGR8',
                'google_ads_id' => 'AW-17337545197',

                'google_site_verification' =>
                    'te9rQrXG97xHhXzAdAByTS6B834N6tT8lCST5DEU5jo',

                'google_site_verification_secondary' =>
                    'DBTRhntLtjAVleyg3gbcNFOkUztX-x774vwe7jQv6MQ',

                'yandex_verification' => 'df32f3caa9d74bb4',

                'pinterest_domain_verification' =>
                    '259d6abf77e85d24ce8d289b1d2c2b64',

                'is_active' => true,
            ]
        );
    }
}