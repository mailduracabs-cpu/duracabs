<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConversation extends Model
{
    protected $table = 'whats_app_conversations';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
        'last_customer_message_at' => 'datetime',
        'service_window_expires_at' => 'datetime',
        'read_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(
            WhatsAppMessage::class,
            'whats_app_conversation_id'
        );
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function markAsRead(): void
    {
        $this->update([
            'unread_count' => 0,
            'read_at' => now(),
        ]);
    }

    public function isServiceWindowOpen(): bool
    {
        return $this->service_window_expires_at !== null
            && now()->lessThanOrEqualTo(
                $this->service_window_expires_at
            );
    }
}