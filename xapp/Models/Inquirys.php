<?php

namespace App\Models;

use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class inquirys extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cab_name',
        'mobile',
        'email',
        'message',
        'oraganization_name',
        'address',
        'city',
        'state',
        'pincode',
        'type',
        'service',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($inquiry) {
            // Send WhatsApp message to user when inquiry is created
            if ($inquiry->mobile) {
                $message = "Congratulations your inquiry has been generated and it has been successfully received by us. Our team will call or message you shortly.  You can call us for more information. +91 70888 73331";
                
                WhatsAppService::send($inquiry->mobile, $message);
            }
        });
    }
}
