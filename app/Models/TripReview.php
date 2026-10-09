<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripReview extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['rating' => 'integer'];
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(User::class, 'subject_id'); }
}
