<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppCampaignRecipient extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'campaign_id',
        'customer_id',
        'name',
        'mobile',
        'wa_id',
        'template_name',
        'variables',
        'status',
        'meta_message_id',
        'meta_response',
        'failure_reason',
        'accepted_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'retry_count',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'customer_id' => 'integer',
        'variables' => 'array',
        'accepted_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'retry_count' => 0,
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(
            WhatsAppCampaign::class,
            'campaign_id'
        );
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_SENT => 'Sent',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_READ => 'Read',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public function markAsProcessing(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PROCESSING,
            'failure_reason' => null,
            'failed_at' => null,
        ])->save();
    }

    public function markAsAccepted(
        ?string $metaMessageId = null,
        array|string|null $metaResponse = null
    ): void {
        $this->forceFill([
            'status' => self::STATUS_ACCEPTED,
            'meta_message_id' => $metaMessageId,
            'meta_response' => $this->prepareMetaResponse($metaResponse),
            'accepted_at' => now(),
            'failure_reason' => null,
            'failed_at' => null,
        ])->save();

        $this->campaign?->refreshCounters();
    }

    public function markAsSent(
        ?string $metaMessageId = null,
        array|string|null $metaResponse = null
    ): void {
        $this->forceFill([
            'status' => self::STATUS_SENT,
            'meta_message_id' => $metaMessageId ?? $this->meta_message_id,
            'meta_response' => $this->prepareMetaResponse($metaResponse)
                ?? $this->meta_response,
            'sent_at' => now(),
            'failure_reason' => null,
            'failed_at' => null,
        ])->save();

        $this->campaign?->refreshCounters();
    }

    public function markAsDelivered(): void
    {
        $this->forceFill([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
            'failure_reason' => null,
        ])->save();

        $this->campaign?->refreshCounters();
    }

    public function markAsRead(): void
    {
        $this->forceFill([
            'status' => self::STATUS_READ,
            'read_at' => now(),
            'delivered_at' => $this->delivered_at ?? now(),
            'failure_reason' => null,
        ])->save();

        $this->campaign?->refreshCounters();
    }

    public function markAsFailed(
        ?string $reason = null,
        array|string|null $metaResponse = null
    ): void {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'meta_response' => $this->prepareMetaResponse($metaResponse)
                ?? $this->meta_response,
            'failed_at' => now(),
        ])->save();

        $this->campaign?->refreshCounters();
    }

    public function incrementRetryCount(): void
    {
        $this->increment('retry_count');
    }

    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->retry_count < 3;
    }

    public function getMaskedMobileAttribute(): string
    {
        $mobile = preg_replace('/\D+/', '', $this->mobile) ?? '';

        if (strlen($mobile) <= 4) {
            return $mobile;
        }

        return str_repeat('*', strlen($mobile) - 4)
            . substr($mobile, -4);
    }

    private function prepareMetaResponse(
        array|string|null $response
    ): ?string {
        if ($response === null) {
            return null;
        }

        if (is_string($response)) {
            return $response;
        }

        return json_encode(
            $response,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}