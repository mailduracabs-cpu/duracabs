<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_type',
        'balance',
        'hold_balance',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'hold_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function getAvailableBalanceAttribute()
    {
        return max(0, (float) $this->balance - (float) $this->hold_balance);
    }
}