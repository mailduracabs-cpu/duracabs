<?php

namespace App\Models\FleetManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporterService extends Model
{
    use HasFactory;

    protected $table = 'fleet_transporter_services';

    protected $fillable = [
        'transporter_profile_id',
        'service',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(
            TransporterProfile::class,
            'transporter_profile_id'
        );
    }
}