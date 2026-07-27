<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SmartHomeBlock extends Model
{
    protected $fillable = [
        'block_type',
        'service_type',
        'from_city_id',
        'to_city_id',
        'title',
        'subtitle',
        'priority',
        'is_dynamic',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_dynamic' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBlockType(Builder $query, string $type): Builder
    {
        return $query->where('block_type', $type);
    }

    public function scopeService(Builder $query, ?string $service): Builder
    {
        if (!$service) {
            return $query;
        }

        return $query->where('service_type', $service);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('priority')
            ->orderByDesc('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isAvailable(): bool
    {
        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return $this->is_active;
    }
}