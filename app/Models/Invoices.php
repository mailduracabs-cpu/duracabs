<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    use HasFactory;

    protected $fillable = [

        'user_id',
        'order_id',
        'ammount',
        'date',
        'status',
        'image',
    ];

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
