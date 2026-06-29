<?php

namespace App\Models;

use App\Support\DuraImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'is_active',
        'model',
        'passanger_capacity',
        'luggage_capacity',
        'km_charge',
        'driver_charge',
        'range',
        'in_return',
        'security',
        'new_vehicle',
        'roof_career',
        'pet_friendly',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute()
    {
        return DuraImage::url($this->image);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
