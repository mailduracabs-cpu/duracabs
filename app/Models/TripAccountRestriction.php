<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripAccountRestriction extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['resolved_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function review(): BelongsTo { return $this->belongsTo(TripReview::class, 'review_id'); }
}
