<?php

namespace App\Models\SelfDrive;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use SoftDeletes;

    protected $table = 'self_drive_brands';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'logo',
        'image',
        'country',
        'description',
        'sort_order',
        'is_featured',
        'status',
        'meta_data',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'meta_data'   => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }
}