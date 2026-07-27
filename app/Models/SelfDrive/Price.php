<?php

namespace App\Models\SelfDrive;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Price extends Model
{
    use HasFactory;

    protected $table = 'self_drive_prices';

    protected $fillable = [
        'product_id',
        'hourly_price',
        'daily_price',
        'weekly_price',
        'monthly_price',
        'security_deposit',
        'daily_km_limit',
        'extra_km_charge',
        'extra_hour_charge',
        'minimum_booking_hours',
        'maximum_booking_days',
        'pickup_charge',
        'delivery_charge',
        'fuel_policy',
        'transmission',
        'home_delivery',
        'doorstep_pickup',
        'driver_available',
        'is_active',
    ];

    protected $casts = [
        'hourly_price' => 'decimal:2',
        'daily_price' => 'decimal:2',
        'weekly_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'extra_km_charge' => 'decimal:2',
        'extra_hour_charge' => 'decimal:2',
        'home_delivery' => 'boolean',
        'doorstep_pickup' => 'boolean',
        'driver_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getBestPriceAttribute()
    {
        if ($this->hourly_price > 0) {
            return $this->hourly_price;
        }

        if ($this->daily_price > 0) {
            return $this->daily_price;
        }

        if ($this->weekly_price > 0) {
            return $this->weekly_price;
        }

        return $this->monthly_price;
    }
}