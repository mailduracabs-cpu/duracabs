<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitorPriceRule extends Model
{
    protected $fillable = [
        'price_id',
        'baseline_price',
        'floor_price',
        'undercut_amount',
        'enabled',
        'last_checked_at',
        'last_updated_at',
    ];

    protected $casts = [
        'baseline_price' => 'decimal:2',
        'floor_price' => 'decimal:2',
        'undercut_amount' => 'decimal:2',
        'enabled' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];

    public function price()
    {
        return $this->belongsTo(Price::class);
    }
}
