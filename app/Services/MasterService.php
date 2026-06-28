<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MasterService
{
    public function cities(): array
    {
        if (!Schema::hasTable('products')) {
            return [];
        }

        $routes = DB::table('products')
            ->where('is_active', 1)
            ->select('id', 'name')
            ->limit(2000)
            ->get();

        $cities = [];

        foreach ($routes as $route) {
            $name = (string) $route->name;

            if (str_contains($name, ' To ')) {
                [$from, $to] = explode(' To ', $name, 2);
                $cities[] = trim($from);
                $cities[] = trim($to);
            } elseif (str_contains($name, ' to ')) {
                [$from, $to] = explode(' to ', $name, 2);
                $cities[] = trim($from);
                $cities[] = trim($to);
            }
        }

        $cities = collect($cities)
            ->filter()
            ->unique()
            ->values()
            ->map(function ($city, $index) {
                return [
                    'id' => $index + 1,
                    'name' => $city,
                    'slug' => Str::slug($city),
                ];
            })
            ->toArray();

        return $cities;
    }

    public function vehicleCategories()
    {
        if (!Schema::hasTable('categories')) {
            return [];
        }

        return DB::table('categories')
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();
    }

    public function offers()
    {
        if (!Schema::hasTable('coupons')) {
            return [];
        }

        return DB::table('coupons')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    public function pages()
    {
        if (!Schema::hasTable('pages')) {
            return [];
        }

        return DB::table('pages')
            ->select('id', 'name', 'slug', 'title')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }
}
