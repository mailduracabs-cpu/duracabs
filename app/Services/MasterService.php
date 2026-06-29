<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MasterService
{
    public function cities(): array
    {
        foreach (['cities', 'city'] as $table) {
            try {
                if (Schema::hasTable($table)) {
                    return DB::table($table)->orderBy('name')->limit(100)->get()->map(fn ($r) => (array) $r)->toArray();
                }
            } catch (\Throwable $e) {}
        }
        return [
            ['id' => 1, 'name' => 'Agra'],
            ['id' => 2, 'name' => 'Delhi'],
            ['id' => 3, 'name' => 'Jaipur'],
            ['id' => 4, 'name' => 'Mathura'],
            ['id' => 5, 'name' => 'Vrindavan'],
        ];
    }

    public function vehicleCategories(): array
    {
        return app(HomeService::class)->vehicleCategories();
    }

    public function offers(): array
    {
        return app(HomeService::class)->offers();
    }

    public function pages(): array
    {
        foreach (['pages'] as $table) {
            try {
                if (Schema::hasTable($table)) {
                    return DB::table($table)->orderByDesc('id')->limit(50)->get()->map(fn ($r) => (array) $r)->toArray();
                }
            } catch (\Throwable $e) {}
        }
        return [];
    }
}
