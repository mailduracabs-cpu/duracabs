<?php
namespace App\Models;
use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Database\Eloquent\Model;
class TaxiVendorPayout extends Model {
    protected $guarded = ['id'];
    protected $casts = ['payout_amount'=>'decimal:2','paid_amount'=>'decimal:2','remaining_amount'=>'decimal:2','paid_at'=>'datetime','period_from'=>'date','period_to'=>'date'];
    public function transporter() { return $this->belongsTo(TransporterProfile::class, 'transporter_profile_id'); }
    public function order() { return $this->belongsTo(Order::class); }
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(PartnerPayoutPayment::class, 'payout_id')->where('account', 'vendor');
    }
}
