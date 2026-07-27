<?php

namespace App\Models\FleetManagement;

use App\Models\MasterCity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporterCity extends Model
{
    use HasFactory;

    protected $table = 'fleet_transporter_cities';

    protected $fillable = [
        'transporter_profile_id',
        'city_id',
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

    public function city(): BelongsTo
    {
        return $this->belongsTo(
            MasterCity::class,
            'city_id'
        );
    }
}