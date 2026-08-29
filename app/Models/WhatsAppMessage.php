<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    protected $table = 'whats_app_messages';

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
        'raw_payload' => 'array',
        'errors' => 'array',

        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            WhatsAppConversation::class,
            'whats_app_conversation_id'
        );
    }
}