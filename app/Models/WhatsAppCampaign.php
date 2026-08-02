<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppCampaign extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    public const TYPE_TEMPLATE = 'template';
    public const TYPE_MARKETING = 'marketing';
    public const TYPE_UTILITY = 'utility';
    public const TYPE_AUTHENTICATION = 'authentication';

    public const AUDIENCE_ALL_CUSTOMERS = 'all_customers';
    public const AUDIENCE_SELECTED_CUSTOMERS = 'selected_customers';
    public const AUDIENCE_CITY = 'city';
    public const AUDIENCE_SELF_DRIVE = 'self_drive';
    public const AUDIENCE_TAXI = 'taxi';
    public const AUDIENCE_MANUAL = 'manual';
    public const AUDIENCE_CSV = 'csv';

    protected $fillable = [
        'whatsapp_template_id',
        'campaign_name',
        'campaign_type',
        'template_name',
        'language',
        'header_type',
        'header_media',
        'body',
        'footer',
        'button_payload',
        'template_variables',
        'audience_type',
        'audience_data',
        'total_recipients',
        'pending_count',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'failure_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'button_payload' => 'array',
        'template_variables' => 'array',
        'audience_data' => 'array',

        'total_recipients' => 'integer',
        'pending_count' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'read_count' => 'integer',
        'failed_count' => 'integer',

        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',

        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $attributes = [
        'campaign_type' => self::TYPE_TEMPLATE,
        'language' => 'en_US',
        'audience_type' => self::AUDIENCE_MANUAL,
        'status' => self::STATUS_DRAFT,
        'total_recipients' => 0,
        'pending_count' => 0,
        'sent_count' => 0,
        'delivered_count' => 0,
        'read_count' => 0,
        'failed_count' => 0,
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(
            WhatsAppCampaignRecipient::class,
            'campaign_id'
        );
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            WhatsAppTemplate::class,
            'whatsapp_template_id'
        );
    }

    public function syncSelectedTemplate(): void
    {
        if (! $this->whatsapp_template_id) {
            return;
        }

        $template = $this->template()->first();

        if (! $template) {
            return;
        }

        $this->forceFill([
            'template_name' => $template->template_name,
            'language' => $template->language ?: 'en',
            'header_type' => $template->header_type ?: 'none',
            'header_media' => $template->header_media,
            'body' => $template->body,
            'footer' => $template->footer,
            'button_payload' => $template->buttons ?: [],
            'template_variables' => $this->mapTemplateVariables(
                $template->variables ?: []
            ),
        ]);
    }

    protected function mapTemplateVariables(array $variables): array
    {
        $mapped = [];

        foreach ($variables as $index => $variable) {
            if (! is_array($variable)) {
                continue;
            }

            $position = (int) (
                $variable['position']
                ?? $variable['index']
                ?? ($index + 1)
            );

            if ($position <= 0) {
                continue;
            }

            $mapped[(string) $position] = (string) (
                $variable['sample']
                ?? $variable['key']
                ?? '-'
            );
        }

        ksort($mapped, SORT_NATURAL);

        return $mapped;
    }

    protected static function booted(): void
    {
        static::saving(function (self $campaign): void {
            if (
                $campaign->isDirty('whatsapp_template_id')
                || (
                    $campaign->whatsapp_template_id
                    && empty($campaign->template_name)
                )
            ) {
                $campaign->syncSelectedTemplate();
            }
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public static function campaignTypeOptions(): array
    {
        return [
            self::TYPE_TEMPLATE => 'Template Message',
            self::TYPE_MARKETING => 'Marketing',
            self::TYPE_UTILITY => 'Utility',
            self::TYPE_AUTHENTICATION => 'Authentication',
        ];
    }

    public static function audienceTypeOptions(): array
    {
        return [
            self::AUDIENCE_ALL_CUSTOMERS => 'All Customers',
            self::AUDIENCE_SELECTED_CUSTOMERS => 'Selected Customers',
            self::AUDIENCE_CITY => 'City Wise Customers',
            self::AUDIENCE_SELF_DRIVE => 'Self Drive Customers',
            self::AUDIENCE_TAXI => 'Taxi Customers',
            self::AUDIENCE_MANUAL => 'Manual Mobile Numbers',
            self::AUDIENCE_CSV => 'CSV Upload',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
        ], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
            self::STATUS_PROCESSING,
        ], true);
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_recipients <= 0) {
            return 0;
        }

        $processed = $this->sent_count + $this->failed_count;

        return min(
            100,
            (int) round(($processed / $this->total_recipients) * 100)
        );
    }

    public function getDeliveryPercentageAttribute(): int
    {
        if ($this->sent_count <= 0) {
            return 0;
        }

        return min(
            100,
            (int) round(($this->delivered_count / $this->sent_count) * 100)
        );
    }

    public function getReadPercentageAttribute(): int
    {
        if ($this->delivered_count <= 0) {
            return 0;
        }

        return min(
            100,
            (int) round(($this->read_count / $this->delivered_count) * 100)
        );
    }

    public function refreshCounters(): void
    {
        $counts = $this->recipients()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = $this->recipients()->count();

        $pending = (int) ($counts['pending'] ?? 0);
        $processing = (int) ($counts['processing'] ?? 0);
        $accepted = (int) ($counts['accepted'] ?? 0);
        $sent = (int) ($counts['sent'] ?? 0);
        $delivered = (int) ($counts['delivered'] ?? 0);
        $read = (int) ($counts['read'] ?? 0);
        $failed = (int) ($counts['failed'] ?? 0);

        $this->forceFill([
            'total_recipients' => $total,
            'pending_count' => $pending + $processing,
            'sent_count' => $accepted + $sent + $delivered + $read,
            'delivered_count' => $delivered + $read,
            'read_count' => $read,
            'failed_count' => $failed,
        ])->saveQuietly();
    }
}