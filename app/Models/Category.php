<?php

namespace App\Models;

use App\Support\DuraImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    const SERVICE_WITH_DRIVER = 'with_driver';
    const SERVICE_WITHOUT_DRIVER = 'without_driver';

    const TYPE_CAR = 'car';
    const TYPE_BIKE = 'bike';
    const TYPE_TEMPO = 'tempo';

    protected $fillable = [

        'name',
        'slug',
        'image',

        'is_active',

        // New
        'service_group',     // with_driver / without_driver
        'vehicle_type',      // car / bike / tempo

        // Existing
        'model',
        'passanger_capacity',
        'luggage_capacity',

        'km_charge',
        'driver_charge',
        'range',

        'in_return',

        // Self Drive Features

        'security',
        'new_vehicle',
        'roof_career',
        'pet_friendly',
    ];

    protected $casts = [

        'is_active' => 'boolean',
        'security' => 'boolean',
        'new_vehicle' => 'boolean',
        'roof_career' => 'boolean',
        'pet_friendly' => 'boolean',

    ];

    protected $appends = [
        'image_url',
    ];

    /*
    |--------------------------------------------------------------------------
    | Image
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute()
    {
        return DuraImage::url($this->image);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithDriver($query)
    {
        return $query->where(
            'service_group',
            self::SERVICE_WITH_DRIVER
        );
    }

    public function scopeWithoutDriver($query)
    {
        return $query->where(
            'service_group',
            self::SERVICE_WITHOUT_DRIVER
        );
    }

    public function scopeCars($query)
    {
        return $query->where(
            'vehicle_type',
            self::TYPE_CAR
        );
    }

    public function scopeBikes($query)
    {
        return $query->where(
            'vehicle_type',
            self::TYPE_BIKE
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isSelfDrive()
    {
        return $this->service_group == self::SERVICE_WITHOUT_DRIVER;
    }

    public function isWithDriver()
    {
        return $this->service_group == self::SERVICE_WITH_DRIVER;
    }
}