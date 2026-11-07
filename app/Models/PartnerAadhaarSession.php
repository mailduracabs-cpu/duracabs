<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class PartnerAadhaarSession extends Model
{
    protected $table = 'partner_aadhaar_sessions';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['provider_session','authorization_url'];
    protected $casts = ['provider_session'=>'encrypted','authorization_url'=>'encrypted','consented_at'=>'datetime','expires_at'=>'datetime'];
}
