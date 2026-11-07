<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class PartnerAadhaarIdentity extends Model
{
    protected $table = 'partner_aadhaar_identities';
    protected $guarded = [];
    protected $hidden = ['identity'];
    protected $casts = ['identity'=>'encrypted:array','verified_at'=>'datetime'];
}
