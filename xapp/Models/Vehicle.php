<?php

namespace App\Models;

use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_number',
        'chassis_number',
        'insurance_number',
        'owner_name',
        'car_company_name',
        'car_classification',
        'car_color',
        'insurance_company_name',
        'rc_image',
        'insurance_image',
        'polution_image',
        'is_active',
        'user_id',
       
    ];

    protected $casts = [
        'insurance_image' => 'array'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($vehicle) {
            // Load user relationship
            $vehicle->load('user');
            
            // Send WhatsApp message to transporter when vehicle is created
            if ($vehicle->user_id && $vehicle->user && $vehicle->user->mobile) {
                $message = "Congratulations your Attach Taxi Request  has been generated and it has been successfully received by us. Our team will call or message you shortly.  You can call us for more information. +91 70888 73331";
                
                try {
                    WhatsAppService::send($vehicle->user->mobile, $message);
                } catch (\Exception $e) {
                    \Log::error('WhatsApp vehicle notification failed: ' . $e->getMessage());
                }
            }
            
            // Send WhatsApp message to admin when vehicle is created
            $adminMobile = env('ADMIN_MOBILE');
            if ($adminMobile && $vehicle->user) {
                $adminMessage = "Congratulation! Have received New Vendor ship  Form \n\n\n";
                $adminMessage .= "Kindly find the user id details below :\n\n";
                $adminMessage .= "Name : " . ($vehicle->user->name ?? 'N/A') . "\n\n";
                $adminMessage .= "Mobile Number:" . ($vehicle->user->mobile ?? 'N/A') . "\n\n";
                $adminMessage .= "Email Id:" . ($vehicle->user->email ?? 'N/A') . "\n\n";
                
                // Get address if available
                $userAddress = \App\Models\address::where('user_id', $vehicle->user_id)->first();
                $addressStr = 'N/A';
                if ($userAddress) {
                    $addressParts = array_filter([
                        $userAddress->pickup_address,
                        $userAddress->city,
                        $userAddress->state
                    ]);
                    $addressStr = implode(', ', $addressParts);
                }
                $adminMessage .= "Address: " . $addressStr . "\n\n";
                $adminMessage .= "Company Name : " . ($vehicle->user->company_name ?? 'N/A') . "\n";
                
                try {
                    WhatsAppService::send($adminMobile, $adminMessage);
                } catch (\Exception $e) {
                    \Log::error('WhatsApp admin vehicle notification failed: ' . $e->getMessage());
                }
            }
        });
    }
}


