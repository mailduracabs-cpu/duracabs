<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideInquiry extends Model
{
    protected $fillable = [
        'inquiry_no',
        'user_id',
        'mobile',
        'customer_name',
        'pickup_city_id',
        'drop_city_id',
        'pickup_name',
        'drop_name',
        'trip_type',
        'travel_date',
        'travel_time',
        'return_date',
        'estimated_fare_from',
        'source',
        'landing_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'gclid',
        'fbclid',
        'status',
        'assigned_to',
        'last_follow_up_at',
        'last_activity_at',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'return_date' => 'date',
        'estimated_fare_from' => 'decimal:2',
        'last_follow_up_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (RideInquiry $inquiry): void {
            if (blank($inquiry->inquiry_no)) {
                do {
                    $number = 'RI-' . now()->format('ymd') . '-' . random_int(10000, 99999);
                } while (static::query()->where('inquiry_no', $number)->exists());

                $inquiry->inquiry_no = $number;
            }

            $inquiry->status ??= 'new';
            $inquiry->source ??= 'website';
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}