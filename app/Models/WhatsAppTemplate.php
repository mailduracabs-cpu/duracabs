<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    public const CATEGORY_UTILITY = 'UTILITY';
    public const CATEGORY_MARKETING = 'MARKETING';
    public const CATEGORY_AUTHENTICATION = 'AUTHENTICATION';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const META_STATUS_NOT_SUBMITTED = 'not_submitted';

    public const META_STATUS_PENDING = 'pending';

    public const META_STATUS_APPROVED = 'approved';

    public const META_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'template_name',
        'category',
        'language',
        'header_type',
        'header_text',
        'header_media',
        'body',
        'footer',
        'variables',
        'buttons',
        'status',
        'meta_status',
        'meta_rejection_reason',
        'meta_template_id',
        'is_active',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'last_synced_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'buttons' => 'array',
        'is_active' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where(
            'meta_status',
            self::META_STATUS_APPROVED
        );
    }

    public function scopeReadyToSend(Builder $query): Builder
    {
        return $query
            ->active()
            ->approved();
    }

    public function isApproved(): bool
    {
        return $this->meta_status === self::META_STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->meta_status === self::META_STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->meta_status === self::META_STATUS_REJECTED;
    }

    public function isReadyToSend(): bool
    {
        return $this->is_active
            && $this->status === self::STATUS_ACTIVE
            && $this->isApproved();
    }

    public function markAsSubmitted(
        ?string $metaTemplateId = null
    ): void {
        $this->forceFill([
            'status' => self::STATUS_ACTIVE,
            'meta_status' => self::META_STATUS_PENDING,
            'meta_template_id' => $metaTemplateId,
            'submitted_at' => now(),
            'approved_at' => null,
            'rejected_at' => null,
            'meta_rejection_reason' => null,
            'last_synced_at' => now(),
        ])->save();
    }

    public function markAsApproved(): void
    {
        $this->forceFill([
            'status' => self::STATUS_ACTIVE,
            'meta_status' => self::META_STATUS_APPROVED,
            'approved_at' => now(),
            'rejected_at' => null,
            'meta_rejection_reason' => null,
            'last_synced_at' => now(),
        ])->save();
    }

    public function markAsRejected(
        ?string $reason = null
    ): void {
        $this->forceFill([
            'meta_status' => self::META_STATUS_REJECTED,
            'rejected_at' => now(),
            'approved_at' => null,
            'meta_rejection_reason' => $reason,
            'last_synced_at' => now(),
        ])->save();
    }

    public function markAsSynced(): void
    {
        $this->forceFill([
            'last_synced_at' => now(),
        ])->save();
    }

    public static function categories(): array
    {
        return [
            self::CATEGORY_UTILITY => 'Utility',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_AUTHENTICATION => 'Authentication',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public static function metaStatuses(): array
    {
        return [
            self::META_STATUS_NOT_SUBMITTED => 'Not Submitted',
            self::META_STATUS_PENDING => 'Pending',
            self::META_STATUS_APPROVED => 'Approved',
            self::META_STATUS_REJECTED => 'Rejected',
        ];
    }

    public static function headerTypes(): array
    {
        return [
            'none' => 'None',
            'text' => 'Text',
            'image' => 'Image',
            'video' => 'Video',
            'document' => 'Document',
        ];
    }
}