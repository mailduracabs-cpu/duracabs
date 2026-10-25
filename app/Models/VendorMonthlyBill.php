<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorMonthlyBill extends Model
{
    protected $guarded = ['*'];
    protected $casts = ['terms' => 'array', 'period_start' => 'date', 'period_end' => 'date', 'bill_date' => 'date', 'paid_at' => 'datetime', 'amount' => 'decimal:2', 'monthly_amount' => 'decimal:2'];
}
