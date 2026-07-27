<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_media_id',
        'usable_type',
        'usable_id',
        'field_name',
        'preferred_variant',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'app_media_id' => 'integer',
            'usable_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(
            AppMedia::class,
            'app_media_id'
        );
    }

    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}