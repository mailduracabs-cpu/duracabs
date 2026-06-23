<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class banners extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'image',
        'ride_type',
        'alt',
        'title',
    ];
}
