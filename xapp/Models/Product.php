<?php

namespace App\Models;

use App\Support\DuraImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'images',
        'description',
        'price',
        'is_active',
        'is_featured',
        'in_stock',
        'on_sale',
        'max_price',
        'ride_type',
        'booking_to',
        'km_limit',
        'hr_limit',
        'extra_km_charge',
        'extra_hr_charge',
        'toll_tax',
        'border_tax',
        'driver_allowances',
        'plan',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    protected $appends = [
        'primary_image',
        'image_url',
    ];

    public function getPrimaryImageAttribute()
    {
        return DuraImage::first($this->images);
    }

    public function getImageUrlAttribute()
    {
        return $this->primary_image;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function links()
    {
        return $this->hasMany(Links::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
