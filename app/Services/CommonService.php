<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommonService
{
    public function contactDetails(): array
    {
        return [
            'company_name' => 'Dura Cabs Services',
            'support_mobile' => env('ADMIN_MOBILE', '7088873331'),
            'whatsapp_mobile' => env('WHATSAPP_SENDER', '917088873331'),
            'email' => env('MAIL_FROM_ADDRESS', 'no-reply@duracabs.com'),
            'website' => config('app.url', 'https://www.duracabs.com'),
            'address' => 'Agra, Uttar Pradesh, India',
        ];
    }

    public function settings(): array
    {
        $settings = [
            'app_name' => 'Dura Cabs',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'country_code' => '+91',
            'maintenance_mode' => false,
            'force_update' => false,
            'minimum_android_version' => '1.0.0',
            'minimum_ios_version' => '1.0.0',
        ];

        if (Schema::hasTable('settings')) {
            $rows = DB::table('settings')->get();
            foreach ($rows as $row) {
                if (isset($row->key) && isset($row->value)) {
                    $settings[$row->key] = $row->value;
                }
            }
        }

        return $settings;
    }
}
