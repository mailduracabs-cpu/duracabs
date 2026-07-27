<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'driver_id',
        'vehicle_id',
        'rating_for',
        'rating',
        'title',
        'review',
        'images',
        'status',
        'admin_reply',
        'meta',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'images' => 'array',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Order::class, 'booking_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}