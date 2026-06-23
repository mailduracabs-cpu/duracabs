<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class price extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'category_id',
        'price',
        'max_price'
    ];

    public function category(){
        return $this->belongsTo(Category::class);
    }

}
