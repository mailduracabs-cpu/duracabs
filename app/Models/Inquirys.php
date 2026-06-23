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
                // Parse message to extract trip details
                $tripType = ucfirst(str_replace('_', ' ', $inquiry->service ?? 'one_way'));
                $fromLocation = '';
                $toLocation = '';
                $date = 'N/A';
                $time = 'N/A';
                
                // Parse message field to extract trip details
                if ($inquiry->message) {
                    $messageText = $inquiry->message;
                    
                    // Extract date (format: Date: YYYY-MM-DD or Start Date: YYYY-MM-DD)
                    if (preg_match('/(?:Start )?Date:?\s*(\d{4}-\d{2}-\d{2})/', $messageText, $dateMatches)) {
                        $date = date('d-m-Y', strtotime($dateMatches[1]));
                    } elseif (preg_match('/(?:Start )?Date:?\s*(\d{2}-\d{2}-\d{4})/', $messageText, $dateMatches)) {
                        $date = $dateMatches[1];
                    }
                    
                    // Extract time (format: Time: HH:MM or Start Time: HH:MM)
                    if (preg_match('/(?:Start )?Time:?\s*(\d{1,2}:\d{2})/', $messageText, $timeMatches)) {
                        $timeStr = $timeMatches[1];
                        // Try to format time properly
                        if (strpos($timeStr, ':') !== false) {
                            $timeParts = explode(':', $timeStr);
                            $hour = (int)$timeParts[0];
                            $minute = $timeParts[1] ?? '00';
                            $time = date('h:i A', strtotime("$hour:$minute"));
                        } else {
                            $time = $timeStr;
                        }
                    }
                    
                    // Extract From and To locations
                    // Pattern 1: "From X To Y" or "X <-to-> Y"
                    if (preg_match('/(?:From\s+)?([^<]+?)\s*(?:<-to->|To|➝)\s*(.+?)(?:\s+Date|$)/i', $messageText, $routeMatches)) {
                        $fromLocation = trim($routeMatches[1]);
                        $toLocation = trim($routeMatches[2]);
                    }
                    // Pattern 2: "X TO Y" or "X ➝ Y"
                    elseif (preg_match('/([^:]+?)\s*(?:TO|➝)\s*(.+?)(?:\s+Date|$)/i', $messageText, $routeMatches)) {
                        $fromLocation = trim($routeMatches[1]);
                        $toLocation = trim($routeMatches[2]);
                    }
                    // Pattern 3: Just "From X" (for local/self drive)
                    elseif (preg_match('/From\s+(.+?)(?:\s+Start Date|$)/i', $messageText, $fromMatches)) {
                        $fromLocation = trim($fromMatches[1]);
                    }
                }
                
                // Format trip type display name
                $tripTypeDisplay = '';
                switch($inquiry->service) {
                    case 'one_way':
                        $tripTypeDisplay = 'One Way';
                        break;
                    case 'round_trip':
                        $tripTypeDisplay = 'Round Trip';
                        break;
                    case 'local':
                        $tripTypeDisplay = 'Local';
                        break;
                    case 'self_drive':
                        $tripTypeDisplay = 'Self Drive';
                        break;
                    default:
                        $tripTypeDisplay = $tripType;
                }
                
                // Build route string
                $route = '';
                if ($fromLocation && $toLocation) {
                    $route = $fromLocation . ' ➝ ' . $toLocation;
                } elseif ($fromLocation) {
                    $route = $fromLocation;
                } else {
                    $route = 'N/A';
                }
                
                // Build WhatsApp message
                $whatsappMessage = "🌟 🎉*CONGRATULATIONS!*\n\n\n";
                $whatsappMessage .= "Your inquiry has been successfully received by *Dura Cabs*.\n\n";
                $whatsappMessage .= "🚘 *TRIP DETAILS*\n\n";
                $whatsappMessage .= "*• Trip Type:* " . $tripTypeDisplay . "\n\n";
                
                if ($route !== 'N/A') {
                    $whatsappMessage .= "*• Route:* " . $route . "\n\n";
                }
                
                $whatsappMessage .= "*• Date:* " . $date . "\n\n";
                $whatsappMessage .= "*• Time:* " . $time . "\n\n";
                $whatsappMessage .= "✅ *BOOKING CONFIRMATION*\n\n";
                $whatsappMessage .= "Your booking request has been recorded successfully.\n\n";
                $whatsappMessage .= "Our team will contact you shortly via call or message.\n\n";
                $whatsappMessage .= "📞 *For More Details:* +91 70888 73331\n\n";
                $whatsappMessage .= "🌐 *Visit:* www.duracabs.com";
                
                try {
                    WhatsAppService::send($inquiry->mobile, $whatsappMessage);
                } catch (\Exception $e) {
                    \Log::error('WhatsApp inquiry notification failed: ' . $e->getMessage());
                }
            }
            
            // Send WhatsApp notification to admin when inquiry is created
            $adminMobile = env('ADMIN_MOBILE');
            if ($adminMobile && $inquiry->mobile) {
                // Parse message to extract trip details for admin notification
                $tripType = ucfirst(str_replace('_', ' ', $inquiry->service ?? 'one_way'));
                $fromLocation = '';
                $toLocation = '';
                $date = 'N/A';
                $time = 'N/A';
                
                if ($inquiry->message) {
                    $messageText = $inquiry->message;
                    
                    // Extract date
                    if (preg_match('/(?:Start )?Date:?\s*(\d{4}-\d{2}-\d{2})/', $messageText, $dateMatches)) {
                        $date = date('d/m/Y', strtotime($dateMatches[1]));
                    } elseif (preg_match('/(?:Start )?Date:?\s*(\d{2}-\d{2}-\d{4})/', $messageText, $dateMatches)) {
                        $date = $dateMatches[1];
                    } elseif (preg_match('/(?:Start )?Date:?\s*(\d{2}\/\d{2}\/\d{4})/', $messageText, $dateMatches)) {
                        $date = $dateMatches[1];
                    }
                    
                    // Extract time
                    if (preg_match('/(?:Start )?Time:?\s*(\d{1,2}:\d{2})/', $messageText, $timeMatches)) {
                        $timeStr = $timeMatches[1];
                        if (strpos($timeStr, ':') !== false) {
                            $timeParts = explode(':', $timeStr);
                            $hour = (int)$timeParts[0];
                            $minute = $timeParts[1] ?? '00';
                            $time = date('h:i A', strtotime("$hour:$minute"));
                        } else {
                            $time = $timeStr;
                        }
                    }
                    
                    // Extract From and To locations
                    if (preg_match('/(?:From\s+)?([^<]+?)\s*(?:<-to->|To|➝)\s*(.+?)(?:\s+Date|$)/i', $messageText, $routeMatches)) {
                        $fromLocation = trim($routeMatches[1]);
                        $toLocation = trim($routeMatches[2]);
                    } elseif (preg_match('/([^:]+?)\s*(?:TO|➝)\s*(.+?)(?:\s+Date|$)/i', $messageText, $routeMatches)) {
                        $fromLocation = trim($routeMatches[1]);
                        $toLocation = trim($routeMatches[2]);
                    } elseif (preg_match('/From\s+(.+?)(?:\s+Start Date|$)/i', $messageText, $fromMatches)) {
                        $fromLocation = trim($fromMatches[1]);
                    }
                }
                
                // Format trip type display name
                $tripTypeDisplay = '';
                switch($inquiry->service) {
                    case 'one_way':
                        $tripTypeDisplay = 'One Way';
                        break;
                    case 'round_trip':
                        $tripTypeDisplay = 'Round Trip';
                        break;
                    case 'local':
                        $tripTypeDisplay = 'Local';
                        break;
                    case 'self_drive':
                        $tripTypeDisplay = 'Self Drive';
                        break;
                    default:
                        $tripTypeDisplay = ucfirst($inquiry->service ?? 'one_way');
                }
                
                // Build route string
                $route = '';
                if ($fromLocation && $toLocation) {
                    $route = $fromLocation . ' ➝ ' . $toLocation;
                } elseif ($fromLocation) {
                    $route = $fromLocation;
                } else {
                    $route = 'N/A';
                }
                
                $adminMessage = "⚠️ *Attention! New Booking Enquiry Received*\n\n";
                $adminMessage .= "Dura Cabs has received a new *booking enquiry.*\n\n";
                $adminMessage .= "📋 *Booking Details:*\n\n";
                $adminMessage .= "*• Booking Type:* " . $tripTypeDisplay . "\n\n";
                
                if ($route !== 'N/A') {
                    $adminMessage .= "*• Trip Route:* " . $route . "\n\n";
                }
                
                $adminMessage .= "*• Date:* " . $date . "\n\n";
                $adminMessage .= "*• Time:* " . $time . "\n\n";
                $adminMessage .= "*• Mobile Number:* " . $inquiry->mobile . "\n\n";
                $adminMessage .= "Please review and take necessary action.\n\n";
                $adminMessage .= "📞 *For any assistance:* +91 70888 73331";
                
                try {
                    WhatsAppService::send($adminMobile, $adminMessage);
                } catch (\Exception $e) {
                    \Log::error('WhatsApp admin inquiry notification failed: ' . $e->getMessage());
                }
            }
        });
    }
}
