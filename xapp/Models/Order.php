<?php

namespace App\Models;

use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'extraOptions' => 'array',
    ];
    protected $fillable =[
        'user_id',
        'driver_id',
        'vehicle_id',
        'transporter_id',
        'user_id',
        'grand_total',
        'payment_method',
        'payment_status',
        'date',
        'dateTo',
        'time',
        'booking_from',
        'booking_to',
        'status',
        'currency',
        'shipping_ammount', 
        'taxi_type',
        'notes',
        'cityFrom',
        'cityTo',
        'image',
        'productName',
        'coupon_value',
        'coupon_name',
        'ride_type',
        'taxi_type',
        'endTime',
        'plan',

    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }

    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function address() {
        return $this->hasOne(address::class);
    }

    public function invoices() {
        return $this->hasMany(Invoices::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($order) {
            // Send WhatsApp message to user when order is created
            if ($order->user_id && $order->user && $order->user->mobile) {
                $message = "Thank you for choosing us, we will update your booking on our Duracabs portal shortly. For more information you can call us :+91 70888 73331 and you can chat with us on WhatsApp by clicking on the link. Http//:chat.whatsaap.com   Thanks!";
                
                WhatsAppService::send($order->user->mobile, $message);
            }
        });

        static::updated(function ($order) {
            // Send WhatsApp booking confirmation when status changes to 'confirm'
            if ($order->wasChanged('status') && $order->status === 'confirm') {
                // Refresh to get latest data
                $order->refresh();
                
                // Load relationships - ensure they're loaded
                $order->load(['user', 'address', 'items']);
                
                $mobile = null;
                $customerName = null;
                
                // Get address explicitly if not loaded
                $address = $order->address;
                if (!$address) {
                    $address = \App\Models\address::where('order_id', $order->id)->first();
                }
                
                // Get mobile and name from address first, then user
                if ($address) {
                    $mobile = $address->phone ?? null;
                    $customerName = $address->full_name ?? null;
                }
                
                if (!$mobile && $order->user) {
                    $mobile = $order->user->mobile ?? null;
                }
                
                if (!$customerName && $order->user) {
                    $customerName = $order->user->name ?? 'N/A';
                }
                
                if ($mobile) {
                    // Amounts
                    $bookingAmount = $order->grand_total ?? 0;
                    
                    // Calculate advance (if payment_status is paid, advance = full amount, else 0 or partial)
                    $advance = 0;
                    if ($order->payment_status === 'paid') {
                        $advance = $bookingAmount;
                    } elseif ($order->payment_status === 'partial') {
                        // You may need to adjust this based on your payment logic
                        $advance = $bookingAmount * 0.2; // 20% as example
                    }
                    
                    $balance = $bookingAmount - $advance;

                    // Invoice link
                    $invoiceLink = route('my-orders.show', $order->id);

                    // Contact number
                    $contactNumber = env('CONTACT_NUMBER', '+91 7088873331');

                    // Format time
                    $timeStr = $order->time ? date('h:i A', strtotime($order->time)) : 'N/A';

                    // Taxi type
                    $taxiType = $order->productName ?? 'N/A';
                    $taxiCount = $order->items->count() ?? 1;

                    // Build message based on ride type
                    $message = '';
                    
                    switch($order->ride_type) {
                        case 'one_way':
                            // One Way format
                            $bookingFor = strtoupper(($order->cityFrom ?? '') . ' TO ' . ($order->cityTo ?? ''));
                            $dateStr = $order->date ? date('d/m/Y', strtotime($order->date)) : 'N/A';
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "Booking confirmation notification \n\n";
                            $message .= "Congratulations Your Booking Is Confirmed\n\n";
                            $message .= "Your Booking ID : " . $order->id . "\n\n";
                            $message .= "Name: " . $customerName . "\n\n";
                            $message .= "Mobile Number : " . $mobile . "\n\n";
                            $message .= "Booking Type: ONE WAY\n\n";
                            $message .= "Booking For: " . $bookingFor . "\n\n";
                            $message .= "Date: " . $dateStr . "\n\n";
                            $message .= "Time: " . $timeStr . "\n\n";
                            $message .= "Taxi Type : " . $taxiType . "\n\n";
                            $message .= "Pick up : " . $pickup . "\n\n";
                            $message .= "Drop : " . $drop . "\n\n";
                            $message .= "Booking Amount: Rs." . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "Advance: Rs." . number_format($advance, 2) . "/-\n\n";
                            $message .= "Balance: Rs." . number_format($balance, 2) . "/-\n\n";
                            $message .= "Please Note: Parking Extra If Applicable ( as per Actual)\n\n";
                            $message .= "Invoice: " . $invoiceLink . "\n\n";
                            $message .= "If Need Any Help Please Call: " . $contactNumber;
                            break;
                            
                        case 'return':
                            // Return/Multi trip format
                            $cities = [];
                            if ($order->cityFrom) $cities[] = $order->cityFrom;
                            if ($order->cityTo) $cities[] = $order->cityTo;
                            // You might have more cities in booking_from and booking_to
                            $bookingFor = implode(' :', $cities);
                            
                            $dateStr = '';
                            $totalDays = 0;
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' To ' . date('d/m/Y', strtotime($order->dateTo));
                                    $start = strtotime($order->date);
                                    $end = strtotime($order->dateTo);
                                    $totalDays = ceil(($end - $start) / (60 * 60 * 24));
                                }
                            }
                            
                            $message = "Booking confirmation notification \n\n\n";
                            $message .= "congratulations Your Booking Is Confirmed\n\n";
                            $message .= "Your Booking ID : " . $order->id . "\n\n";
                            $message .= "Name:" . $customerName . "\n\n";
                            $message .= "Mobile Number : " . $mobile . "\n\n";
                            $message .= "Booking Type : Local\n\n";
                            $message .= "Booking For : " . $bookingFor . "\n\n";
                            $message .= "Date : " . $dateStr . "\n\n";
                            if ($totalDays > 0) {
                                $message .= "Total Day Trip : " . $totalDays . "\n\n";
                            }
                            $message .= "Time : " . $timeStr . "\n\n";
                            $message .= "Taxi Type : " . $taxiType . "\n\n";
                            $message .= "Booking Amount : Rs." . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "Advance : Rs." . number_format($advance, 2) . "/-\n\n";
                            $message .= "Balance : Rs." . number_format($balance, 2) . "/-\n\n";
                            $message .= "Please Note: Toll And Parking ,State Tax Extra ( as per Actual)\n\n";
                            $message .= "Invoice: " . $invoiceLink . "\n\n";
                            $message .= "If Need Any Help Please Call : " . $contactNumber;
                            break;
                            
                        case 'self_drive':
                            // Self Drive format
                            $plan = $order->plan ?? 'N/A';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            $dateStr = '';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' To ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = 'Duracab Office';
                            $drop = 'Duracab Office';
                            
                            $message = "Booking confirmation notification \n\n";
                            $message .= "congratulations Your Booking Is Confirmed\n\n";
                            $message .= "Your Booking ID : " . $order->id . "\n\n";
                            $message .= "Name:" . $customerName . "\n\n";
                            $message .= "Mobile Number : " . $mobile . "\n\n";
                            $message .= "Booking Type : Self Drive ($plan)\n\n";
                            $message .= "Booking For : " . $bookingFor . "\n\n";
                            $message .= "Date : " . $dateStr . "\n\n";
                            $message .= "Time : " . $timeStr . "\n\n";
                            $message .= "Taxi Type : " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "Pick up : " . $pickup . "\n\n";
                            $message .= "Drop: " . $drop . "\n\n";
                            $message .= "Booking Amount : Rs." . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "Advance : Rs." . number_format($advance, 2) . "/-\n\n";
                            $message .= "Balance : Rs." . number_format($balance, 2) . "/-\n\n";
                            $message .= "Please Note: Toll And Parking Extra If Applicable  ( as per Actual)\n\n";
                            $message .= "Invoice: " . $invoiceLink . "\n\n";
                            $message .= "If Need Any Help Please Call : " . $contactNumber;
                            break;
                            
                        case 'local':
                        default:
                            // Local trip format (default)
                            $plan = $order->plan ?? '';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            $dateStr = '';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' To ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "congratulations Your Booking Is Confirmed\n\n";
                            $message .= "Your Booking ID : " . $order->id . "\n\n";
                            $message .= "Name:" . $customerName . "\n\n";
                            $message .= "Mobile Number :" . $mobile . "\n\n";
                            $message .= "Booking Type : Local Trip ($plan)\n\n";
                            $message .= "Booking For : " . $bookingFor . "\n\n";
                            $message .= "Date : " . $dateStr . "\n\n";
                            $message .= "Time : " . $timeStr . "\n\n";
                            $message .= "Taxi Type : " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "Pick up : " . $pickup . "\n\n";
                            $message .= "Drop: " . $drop . "\n\n";
                            $message .= "Booking Amount : Rs." . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "Advance : Rs." . number_format($advance, 2) . "/-\n\n";
                            $message .= "Balance : Rs." . number_format($balance, 2) . "/-\n\n";
                            $message .= "Please Note: Toll And Parking Extra If Applicable  ( as per Actual)\n\n";
                            $message .= "Invoice: " . $invoiceLink . "\n\n";
                            $message .= "If Need Any Help Please Call : " . $contactNumber;
                            break;
                    }

                    try {
                        WhatsAppService::send($mobile, $message);
                    } catch (\Exception $e) {
                        \Log::error('WhatsApp confirmation failed for order ' . $order->id . ': ' . $e->getMessage());
                    }
                } else {
                    \Log::warning('WhatsApp confirmation skipped for order ' . $order->id . ': No mobile number found');
                }
            }

            // Send WhatsApp cancellation notification when status changes to 'cancelled'
            if ($order->wasChanged('status') && $order->status === 'cancelled') {
                // Load relationships
                $order->load(['user', 'address']);
                
                $mobile = null;
                
                // Get mobile from address first, then user
                if ($order->address) {
                    $mobile = $order->address->phone ?? null;
                }
                
                if (!$mobile && $order->user) {
                    $mobile = $order->user->mobile ?? null;
                }
                
                if ($mobile) {
                    // Check if cancelled by customer or admin
                    $notes = strtolower($order->notes ?? '');
                    $cancelledByCustomer = strpos($notes, 'cancelled by customer') !== false;
                    
                    $contactNumber = env('CONTACT_NUMBER', '+91 70888 73331');
                    
                    if ($cancelledByCustomer) {
                        // Booking canceled by customer
                        $dateStr = '';
                        if ($order->date) {
                            $dateStr = date('d/m/Y', strtotime($order->date));
                            if ($order->dateTo) {
                                $dateStr .= ' To ' . date('d/m/Y', strtotime($order->dateTo));
                            }
                        }
                        
                        // Format booking type
                        $bookingType = '';
                        switch($order->ride_type) {
                            case 'local':
                                $bookingType = "local Trip";
                                break;
                            case 'one_way':
                                $bookingType = "One Way Trip";
                                break;
                            case 'return':
                                $bookingType = "Round Trip";
                                break;
                            case 'self_drive':
                                $bookingType = "Self Drive Trip";
                                break;
                            default:
                                $bookingType = ucfirst($order->ride_type ?? 'Trip');
                        }
                        
                        $message = "Booking canceled by customer\n\n";
                        $message .= "Dear Sir, Your ticket ID: " . $order->id . " Date: " . $dateStr . " for " . $bookingType . " has been successfully canceled!\n\n";
                        $message .= "IF ANY QUESTIONS PLEASE CALL US: " . $contactNumber;
                    } else {
                        // Booking canceled by Duracabs/Admin
                        $message = "Booking canceled by Duracabs\n\n";
                        $message .= "Dear Sir, We wish you a very sincere condolences.  We are unable to accept your booking due to excessive booking.  You are requested.  Please do try again after some time.\n\n";
                        $message .= "IF ANY QUESTIONS PLEASE CALL US: " . $contactNumber;
                    }
                    
                    WhatsAppService::send($mobile, $message);
                }
            }

            // Send WhatsApp notification when driver is assigned to order
            if ($order->wasChanged('driver_id') && $order->driver_id && $order->vehicle_id) {
                // Load relationships
                $order->load(['user', 'address', 'vehicle']);
                
                // Get driver (driver_id points to User model)
                $driver = User::where('id', $order->driver_id)->first();
                
                $mobile = null;
                
                // Get mobile from address first, then user
                if ($order->address) {
                    $mobile = $order->address->phone ?? null;
                }
                
                if (!$mobile && $order->user) {
                    $mobile = $order->user->mobile ?? null;
                }
                
                if ($mobile && $driver && $order->vehicle) {
                    $contactNumber = env('CONTACT_NUMBER', '+91 70888 73331');
                    
                    $driverName = $driver->name ?? 'N/A';
                    $driverMobile = $driver->mobile ?? 'N/A';
                    $taxiType = $order->productName ?? $order->vehicle->car_classification ?? 'N/A';
                    $taxiNumber = $order->vehicle->vehicle_number ?? 'N/A';
                    
                    $message = "Dear Sir! Please Kindly Note Driver Details For Ticket ID :" . $order->id . "\n\n";
                    $message .= "Driver Name: " . $driverName . "\n\n";
                    $message .= "Mobile Number:" . $driverMobile . "\n\n";
                    $message .= "Taxi Type:" . $taxiType . "\n\n";
                    $message .= "Taxi Number: " . $taxiNumber . "\n\n";
                    $message .= "IF ANY QUESTIONS PLEASE CALL US: " . $contactNumber;
                    
                    WhatsAppService::send($mobile, $message);
                }
            }

            // Send WhatsApp feedback request when status changes to 'closed'
            if ($order->wasChanged('status') && $order->status === 'closed') {
                // Load relationships
                $order->load(['user', 'address']);
                
                $mobile = null;
                
                // Get mobile from address first, then user
                if ($order->address) {
                    $mobile = $order->address->phone ?? null;
                }
                
                if (!$mobile && $order->user) {
                    $mobile = $order->user->mobile ?? null;
                }
                
                if ($mobile) {
                    $message = "Feedback\n\n";
                    $message .= "1.Your feedback means a lot to us! If you have a minute you'll appreciate you leaving a review on our Google My Business profile too.\n\n";
                    $message .= "https://g.page/r/CWqfncXMaRxyEAE";
                    
                    WhatsAppService::send($mobile, $message);
                }
            }

            // Send WhatsApp notification to transporter when booking is assigned (transporter_id is set)
            if ($order->wasChanged('transporter_id') && $order->transporter_id) {
                $order->load('user');
                $transporter = User::where('id', $order->transporter_id)->first();
                
                if ($transporter && $transporter->mobile) {
                    // Format booking type for message
                    $bookingTypeLabel = '';
                    switch($order->ride_type) {
                        case 'one_way':
                            $bookingTypeLabel = 'one way';
                            break;
                        case 'return':
                            $bookingTypeLabel = 'round trip';
                            break;
                        case 'local':
                            $bookingTypeLabel = 'local';
                            break;
                        case 'self_drive':
                            $bookingTypeLabel = 'self drive';
                            break;
                        default:
                            $bookingTypeLabel = $order->ride_type ?? 'booking';
                    }
                    
                    $city = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                    $dateStr = $order->date ? date('d/m/Y', strtotime($order->date)) : 'N/A';
                    $contactNumber = env('CONTACT_NUMBER', '+91 70888 73331');
                    
                    $message = "Booking  Transfer " . $bookingTypeLabel . "\n\n";
                    $message .= "Attention!  " . $city . "  : " . $bookingTypeLabel . " booking has been sent to you by Dura cab services for dated " . $dateStr . " Please log in to your panel and view and reply as soon as possible You can also answer us on call Our number is  : " . $contactNumber;
                    
                    try {
                        WhatsAppService::send($transporter->mobile, $message);
                    } catch (\Exception $e) {
                        \Log::error('WhatsApp transporter assignment failed: ' . $e->getMessage());
                    }
                }
            }

            // Send WhatsApp confirmation to transporter when status changes to 'confirm'
            if ($order->wasChanged('status') && $order->status === 'confirm' && $order->transporter_id) {
                $order->load(['user', 'address', 'items']);
                $transporter = User::where('id', $order->transporter_id)->first();
                
                if ($transporter && $transporter->mobile) {
                    $address = $order->address;
                    if (!$address) {
                        $address = \App\Models\address::where('order_id', $order->id)->first();
                    }
                    
                    $customerName = $address->full_name ?? $order->user->name ?? 'N/A';
                    $customerMobile = $address->phone ?? $order->user->mobile ?? 'N/A';
                    $bookingAmount = $order->grand_total ?? 0;
                    
                    $advance = 0;
                    if ($order->payment_status === 'paid') {
                        $advance = $bookingAmount;
                    } elseif ($order->payment_status === 'partial') {
                        $advance = $bookingAmount * 0.2;
                    }
                    $balance = $bookingAmount - $advance;
                    
                    $invoiceLink = route('my-orders.show', $order->id);
                    $contactNumber = env('CONTACT_NUMBER', '+91 70888 73331');
                    $timeStr = $order->time ? date('h:i A', strtotime($order->time)) : 'N/A';
                    $taxiType = $order->productName ?? 'N/A';
                    $taxiCount = $order->items->count() ?? 1;
                    
                    $message = '';
                    
                    switch($order->ride_type) {
                        case 'one_way':
                            $bookingFor = strtoupper(($order->cityFrom ?? '') . ' TO ' . ($order->cityTo ?? ''));
                            $dateStr = $order->date ? date('d/m/Y', strtotime($order->date)) : 'N/A';
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "Booking confirmation notification \n\n\n";
                            $message .= "Congratulations Your Booking Is Confirmed\n\n";
                            $message .= "Your Booking ID : " . $order->id . "\n\n";
                            $message .= "Name: " . $customerName . "\n\n";
                            $message .= "Mobile Number : " . $customerMobile . "\n\n";
                            $message .= "Booking Type: ONE WAY\n\n";
                            $message .= "Booking For: " . $bookingFor . "\n\n";
                            $message .= "Date: " . $dateStr . "\n\n";
                            $message .= "Time: " . $timeStr . "\n\n";
                            $message .= "Taxi Type : " . $taxiType . "\n\n";
                            $message .= "Pick up : " . $pickup . "\n\n";
                            $message .= "Drop : " . $drop . "\n\n";
                            $message .= "Booking Amount: Rs." . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "Advance: Rs." . number_format($advance, 2) . "/-\n\n";
                            $message .= "Balance: Rs." . number_format($balance, 2) . "/-\n\n";
                            $message .= "Please Note: Parking Extra If Applicable ( as per Actual)\n\n";
                            $message .= "Invoice: " . $invoiceLink . "\n\n";
                            $message .= "If Need Any Help Please Call: " . $contactNumber;
                            break;
                            
                        case 'return':
                            $cities = [];
                            if ($order->cityFrom) $cities[] = $order->cityFrom;
                            if ($order->cityTo) $cities[] = $order->cityTo;
                            $bookingFor = implode(' :', $cities);
                            
                            $dateStr = '';
                            $totalDays = 0;
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' To ' . date('d/m/Y', strtotime($order->dateTo));
                                    $start = strtotime($order->date);
                                    $end = strtotime($order->dateTo);
                                    $totalDays = ceil(($end - $start) / (60 * 60 * 24));
                                }
                            }
                            
                            $message = "Booking confirmation notification \n\n\n";
                            $message .= "congratulations Your Booking Is Confirmed\n\n";
                            $message .= "Your Booking ID : " . $order->id . "\n\n";
                            $message .= "Name:" . $customerName . "\n\n";
                            $message .= "Mobile Number : " . $customerMobile . "\n\n";
                            $message .= "Booking Type : Multi trip\n\n";
                            $message .= "Booking For : " . $bookingFor . "\n\n";
                            $message .= "Date : " . $dateStr . "\n\n";
                            if ($totalDays > 0) {
                                $message .= "Total Day Trip : " . $totalDays . "\n\n";
                            }
                            $message .= "Time : " . $timeStr . "\n\n";
                            $message .= "Taxi Type : " . $taxiType . "\n\n";
                            $message .= "Booking Amount : Rs." . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "Advance : Rs." . number_format($advance, 2) . "/-\n\n";
                            $message .= "Balance : Rs." . number_format($balance, 2) . "/-\n\n";
                            $message .= "Please Note: Toll And Parking ,State Tax Extra ( as per Actual)\n\n";
                            $message .= "Invoice: " . $invoiceLink . "\n\n";
                            $message .= "If Need Any Help Please Call : " . $contactNumber;
                            break;
                            
                        case 'self_drive':
                            $plan = $order->plan ?? 'N/A';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            $dateStr = '';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' To ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = 'Duracab Office';
                            $drop = 'Duracab Office';
                            
                            $message = "Booking confirmation notification \n\n";
                            $message .= "congratulations Your Booking Is Confirmed\n\n";
                            $message .= "Your Booking ID : " . $order->id . "\n\n";
                            $message .= "Name:" . $customerName . "\n\n";
                            $message .= "Mobile Number : " . $customerMobile . "\n\n";
                            $message .= "Booking Type : Self Drive ($plan)\n\n";
                            $message .= "Booking For : " . $bookingFor . "\n\n";
                            $message .= "Date : " . $dateStr . "\n\n";
                            $message .= "Time : " . $timeStr . "\n\n";
                            $message .= "Taxi Type : " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "Pick up : " . $pickup . "\n\n";
                            $message .= "Drop: " . $drop . "\n\n";
                            $message .= "Booking Amount : Rs." . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "Advance : Rs." . number_format($advance, 2) . "/-\n\n";
                            $message .= "Balance : Rs." . number_format($balance, 2) . "/-\n\n";
                            $message .= "Please Note: Toll And Parking Extra If Applicable  ( as per Actual)\n\n";
                            $message .= "Invoice: " . $invoiceLink . "\n\n";
                            $message .= "If Need Any Help Please Call : " . $contactNumber;
                            break;
                            
                        case 'local':
                        default:
                            $plan = $order->plan ?? '';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            $dateStr = '';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' To ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "Booking confirmation notification \n\n";
                            $message .= "congratulations Your Booking Is Confirmed\n\n";
                            $message .= "Your Booking ID : " . $order->id . "\n\n";
                            $message .= "Name:" . $customerName . "\n\n";
                            $message .= "Mobile Number : " . $customerMobile . "\n\n";
                            $message .= "Booking Type : Local Trip ($plan)\n\n";
                            $message .= "Booking For : " . $bookingFor . "\n\n";
                            $message .= "Date : " . $dateStr . "\n\n";
                            $message .= "Time : " . $timeStr . "\n\n";
                            $message .= "Taxi Type : " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "Pick up : " . $pickup . "\n\n";
                            $message .= "Drop: " . $drop . "\n\n";
                            $message .= "Booking Amount : Rs." . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "Advance : Rs." . number_format($advance, 2) . "/-\n\n";
                            $message .= "Balance : Rs." . number_format($balance, 2) . "/-\n\n";
                            $message .= "Please Note: Toll And Parking Extra If Applicable  ( as per Actual)\n\n";
                            $message .= "Invoice: " . $invoiceLink . "\n\n";
                            $message .= "If Need Any Help Please Call : " . $contactNumber;
                            break;
                    }
                    
                    if ($message) {
                        try {
                            WhatsAppService::send($transporter->mobile, $message);
                        } catch (\Exception $e) {
                            \Log::error('WhatsApp transporter confirmation failed: ' . $e->getMessage());
                        }
                    }
                }
            }

            // Send WhatsApp cancellation notification to transporter when status changes to 'cancelled'
            if ($order->wasChanged('status') && $order->status === 'cancelled' && $order->transporter_id) {
                $transporter = User::where('id', $order->transporter_id)->first();
                
                if ($transporter && $transporter->mobile) {
                    // Format booking type
                    $bookingTypeLabel = '';
                    switch($order->ride_type) {
                        case 'local':
                            $bookingTypeLabel = 'Local';
                            break;
                        case 'one_way':
                            $bookingTypeLabel = 'One Way';
                            break;
                        case 'return':
                            $bookingTypeLabel = 'Round Trip';
                            break;
                        case 'self_drive':
                            $bookingTypeLabel = 'Self Drive';
                            break;
                        default:
                            $bookingTypeLabel = ucfirst($order->ride_type ?? 'Booking');
                    }
                    
                    $message = "Booking Cancel\n\n";
                    $message .= "Dear Partner!  We are very sorry to inform you that the booking of Ticket ID : " . $order->id . " for  " . $bookingTypeLabel . " has been canceled by the customer.Hence you are requested not to harass the customer by calling.";
                    
                    try {
                        WhatsAppService::send($transporter->mobile, $message);
                    } catch (\Exception $e) {
                        \Log::error('WhatsApp transporter cancellation failed: ' . $e->getMessage());
                    }
                }
            }
        });
    }
}
