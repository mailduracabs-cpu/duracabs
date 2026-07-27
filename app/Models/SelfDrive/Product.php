<?php

namespace App\Models\SelfDrive;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'self_drive_products';

    protected $fillable = [
        'uuid',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'model_name',
        'model_year',
        'fuel_type',
        'transmission',
        'seat_capacity',
        'bag_capacity',
        'primary_image',
        'banner_image',
        'short_description',
        'description',
        'has_ac',
        'is_featured',
        'is_verified',
        'status',
        'sort_order',
        'default_features',
        'meta_data',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'has_ac' => 'boolean',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'default_features' => 'array',
        'meta_data' => 'array',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }
}