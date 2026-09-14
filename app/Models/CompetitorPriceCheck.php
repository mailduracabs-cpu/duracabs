<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitorPriceCheck extends Model
{
    protected $fillable = [
        'product_id',
        'price_id',
        'category_id',
        'source_prices',
        'lowest_source',
        'lowest_price',
        'old_price',
        'calculated_price',
        'applied_price',
        'status',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'source_prices' => 'array',
        'lowest_price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'calculated_price' => 'decimal:2',
        'applied_price' => 'decimal:2',
        'checked_at' => 'datetime',
    ];

    public function price()
    {
        return $this->belongsTo(Price::class);
    }
}
