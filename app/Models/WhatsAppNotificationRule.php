<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppNotificationRule extends Model
{
    protected $table = 'whatsapp_notification_rules';

    protected $fillable = [
        'name',
        'event_key',
        'template_key',
        'send_customer',
        'send_vendor',
        'send_driver',
        'send_admin',
        'send_sales',
        'send_operations',
        'send_accounts',
        'send_support',
        'is_active',
        'description',
    ];

    protected $casts = [
        'send_customer' => 'boolean',
        'send_vendor' => 'boolean',
        'send_driver' => 'boolean',
        'send_admin' => 'boolean',
        'send_sales' => 'boolean',
        'send_operations' => 'boolean',
        'send_accounts' => 'boolean',
        'send_support' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(
        Builder $query,
        string $eventKey
    ): Builder {
        return $query->where(
            'event_key',
            trim($eventKey)
        );
    }

    public static function activeForEvent(
        string $eventKey
    ): ?self {
        return static::query()
            ->active()
            ->forEvent($eventKey)
            ->first();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            WhatsAppTemplate::class,
            'template_key',
            'template_key'
        );
    }

    /**
     * @return array<int, string>
     */
    public function enabledRecipientTypes(): array
    {
        return collect([
            'customer' => $this->send_customer,
            'vendor' => $this->send_vendor,
            'driver' => $this->send_driver,
            'admin' => $this->send_admin,
            'sales' => $this->send_sales,
            'operations' => $this->send_operations,
            'accounts' => $this->send_accounts,
            'support' => $this->send_support,
        ])
            ->filter()
            ->keys()
            ->values()
            ->all();
    }
}