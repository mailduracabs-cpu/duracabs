<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationDeliveryLog extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'channel',
        'recipient',
        'event',
        'status',
        'subject',
        'message',
        'payload',
        'gateway_response',
        'retry_count',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'gateway_response' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
