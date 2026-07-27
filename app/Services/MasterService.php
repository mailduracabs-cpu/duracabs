<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MasterService
{
    /*
    |--------------------------------------------------------------------------
    | Cities
    |--------------------------------------------------------------------------
    */

    public function cities(): array
    {
        foreach (['cities', 'city'] as $table) {

            try {

                if (Schema::hasTable($table)) {

                    return DB::table($table)
                        ->orderBy('name')
                        ->limit(500)
                        ->get()
                        ->map(fn($r) => (array)$r)
                        ->toArray();

                }

            } catch (\Throwable $e) {
            }
        }

        return [
            ['id' => 1, 'name' => 'Agra'],
            ['id' => 2, 'name' => 'Delhi'],
            ['id' => 3, 'name' => 'Jaipur'],
            ['id' => 4, 'name' => 'Mathura'],
            ['id' => 5, 'name' => 'Vrindavan'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Vehicle Categories
    |--------------------------------------------------------------------------
    */

    public function vehicleCategories(): array
    {
        return app(HomeService::class)->vehicleCategories();
    }

    /*
    |--------------------------------------------------------------------------
    | Offers
    |--------------------------------------------------------------------------
    */

    public function offers(): array
    {
        return app(HomeService::class)->offers();
    }

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    public function pages(): array
    {
        try {

            if (Schema::hasTable('pages')) {

                return DB::table('pages')
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get()
                    ->map(fn($r) => (array)$r)
                    ->toArray();

            }

        } catch (\Throwable $e) {
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Coupons
    |--------------------------------------------------------------------------
    */

    public function coupons(): array
    {
        try {

            if (Schema::hasTable('coupons')) {

                return DB::table('coupons')
                    ->where('is_active', 1)
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn($r) => (array)$r)
                    ->toArray();

            }

        } catch (\Throwable $e) {
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Service Types
    |--------------------------------------------------------------------------
    */

   public function serviceTypes(): array
{
    return [

        /*
        |--------------------------------------------------------------------------
        | With Driver
        |--------------------------------------------------------------------------
        */

        [
            'id' => 1,
            'service_group' => 'with_driver',
            'key' => 'one_way',
            'name' => 'One Way',
            'icon' => 'one_way',
        ],

        [
            'id' => 2,
            'service_group' => 'with_driver',
            'key' => 'round_trip',
            'name' => 'Round Trip',
            'icon' => 'round_trip',
        ],

        [
            'id' => 3,
            'service_group' => 'with_driver',
            'key' => 'local',
            'name' => 'Local Taxi',
            'icon' => 'local',
        ],

        [
            'id' => 4,
            'service_group' => 'with_driver',
            'key' => 'airport',
            'name' => 'Airport Transfer',
            'icon' => 'airport',
        ],

        [
            'id' => 5,
            'service_group' => 'with_driver',
            'key' => 'tour',
            'name' => 'Tour Package',
            'icon' => 'tour',
        ],

        /*
        |--------------------------------------------------------------------------
        | Without Driver
        |--------------------------------------------------------------------------
        */

        [
            'id' => 6,
            'service_group' => 'without_driver',
            'key' => 'self_drive_car',
            'name' => 'Car Rental',
            'icon' => 'car',
        ],

        [
            'id' => 7,
            'service_group' => 'without_driver',
            'key' => 'self_drive_bike',
            'name' => 'Bike Rental',
            'icon' => 'bike',
        ],

    ];
}

    /*
    |--------------------------------------------------------------------------
    | States
    |--------------------------------------------------------------------------
    */

    public function states(): array
    {
        try {

            if (Schema::hasTable('states')) {

                return DB::table('states')
                    ->orderBy('name')
                    ->get()
                    ->map(fn($r) => (array)$r)
                    ->toArray();

            }

        } catch (\Throwable $e) {
        }

        return [
            [
                'id' => 1,
                'name' => 'Uttar Pradesh',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FAQ
    |--------------------------------------------------------------------------
    */

    public function faqs(): array
    {
        try {

            if (Schema::hasTable('faqs')) {

                return DB::table('faqs')
                    ->where('is_active', 1)
                    ->get()
                    ->map(fn($r) => (array)$r)
                    ->toArray();

            }

        } catch (\Throwable $e) {
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Tour Categories
    |--------------------------------------------------------------------------
    */

    public function tourCategories(): array
    {
        try {

            if (Schema::hasTable('tour_categories')) {

                return DB::table('tour_categories')
                    ->where('is_active', 1)
                    ->get()
                    ->map(fn($r) => (array)$r)
                    ->toArray();

            }

        } catch (\Throwable $e) {
        }

        return [];
    }
}