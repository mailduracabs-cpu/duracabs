<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'name',
        'brand_id',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'links',
        'image',
        'link_products',
        'schema'
    ];

    public function brand(){
        return $this->belongsTo(Brand::class);
    }

    public function product(){
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
            'links' => 'array',
            'link_products' => 'array',
        ];
    }

    use HasFactory;
}
