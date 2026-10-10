<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerPayoutPayment extends Model {
    protected $guarded = ['*'];
    protected $casts = ['amount'=>'decimal:2','payment_date'=>'datetime','allocations'=>'array'];
}
