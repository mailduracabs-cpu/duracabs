<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $table = 'prices';

    protected $fillable = [
        'product_id',
        'category_id',
        'price',
        'max_price',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
