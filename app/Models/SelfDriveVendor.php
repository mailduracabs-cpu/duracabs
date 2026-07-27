<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelfDriveVendor extends Model
{
    use HasFactory;

    protected $fillable = [
    'vendor_id',
    'office_name',
    'mobile',
    'pickup_address',
    'city_id',
    'latitude',
    'longitude',
    'service_radius_km',
    'is_active',
];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'service_radius_km' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    

    public function bookings()
    {
        return $this->hasMany(SelfDriveBooking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}