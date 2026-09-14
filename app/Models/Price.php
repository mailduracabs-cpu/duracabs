<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $table = 'prices';

    protected $fillable = [
        'product_id',
        'category_id',
        'price',
        'max_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function competitorPricingRule()
    {
        return $this->hasOne(CompetitorPriceRule::class);
    }

    public function competitorPriceChecks()
    {
        return $this->hasMany(CompetitorPriceCheck::class);
    }
}
