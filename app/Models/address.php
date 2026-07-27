<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class address extends Model
{

    protected $fillable =[
        'order_id',
        'user_id',
        'full_name',
        'email',
        'phone',
        'phone2',
        'pickup_address',
        'drop_address',
        'number_travellers',
        'number_luggage',
        'comments',
        'state',
        'city'
    ];

    public function order(){
        return $this->belongsTo(Order::class);
    }


  

    use HasFactory;
}
