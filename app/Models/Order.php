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
                $message = "🙏 *Thank You for Choosing Dura Cabs!*\n\n";
                $message .= "We'll update your booking on our *Dura Cabs Services Portal* shortly.\n\n";
                $message .= "📞 *For more information:* Call us at : 7088873331\n\n";
                $message .= "💬 *Chat with us on WhatsApp:* https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                $message .= "Thanks again for choosing *Dura Cabs* — your trusted travel partner! 🚘";
                
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
                    $contactNumber = env('CONTACT_NUMBER', '+91 70888 73331');
                    
                    // Payment mode
                    $paymentMode = ucfirst($order->payment_method ?? 'Cash');

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
                            
                            $message = "🎉 *Congratulations! Your Booking is Confirmed* 🎉\n\n";
                            $message .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                            $message .= "👤 *Name:* " . $customerName . "\n\n";
                            $message .= "📞 *Mobile:* " . $mobile . "\n\n";
                            $message .= "🚖 *Booking Details*\n\n";
                            $message .= "*• Type:* One Way\n\n";
                            $message .= "*• Booking For:* " . $bookingFor . "\n\n";
                            $message .= "*• Date:* " . $dateStr . "\n\n";
                            $message .= "*• Time:* " . $timeStr . "\n\n";
                            $message .= "*• Taxi Type:* " . $taxiType . "\n\n";
                            $message .= "*• Pickup:* " . $pickup . "\n\n";
                            $message .= "*• Drop:* " . $drop . "\n\n";
                            $message .= "💰 *Payment Details*\n\n";
                            $message .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                            $message .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                            $message .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                            $message .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                            $message .= "🧾 *Invoice:* " . $invoiceLink . "\n\n";
                            $message .= "📞* Need Help?*\n\n";
                            $message .= "Call us at +91 70888 73331\n\n";
                            $message .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                            $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
                            break;
                            
                        case 'return':
                            // Return/Multi trip format
                            $cities = [];
                            if ($order->cityFrom) $cities[] = $order->cityFrom;
                            if ($order->cityTo) $cities[] = $order->cityTo;
                            $bookingFor = implode(' :', $cities);
                            
                            $dateStr = '';
                            $totalDays = 0;
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                    $start = strtotime($order->date);
                                    $end = strtotime($order->dateTo);
                                    $totalDays = ceil(($end - $start) / (60 * 60 * 24));
                                }
                            }
                            
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "🎉 *Congratulations! Your Booking is Confirmed* 🎉\n\n";
                            $message .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                            $message .= "👤 *Name:* " . $customerName . "\n\n";
                            $message .= "📞 *Mobile:* " . $mobile . "\n\n";
                            $message .= "🚖 *Booking Details*\n\n";
                            $message .= "*• Type:* Round Trip\n\n";
                            $message .= "*• Booking For:* " . $bookingFor . "\n\n";
                            $message .= "*• Date:* " . $dateStr . "\n\n";
                            if ($totalDays > 0) {
                                $message .= "*• Total Days:* " . $totalDays . "\n\n";
                            }
                            $message .= "*• Time:* " . $timeStr . "\n\n";
                            $message .= "*• Taxi Type:* " . $taxiType . "\n\n";
                            if ($pickup !== 'N/A') {
                                $message .= "*• Pickup:* " . $pickup . "\n\n";
                            }
                            if ($drop !== 'N/A') {
                                $message .= "*• Drop:* " . $drop . "\n\n";
                            }
                            $message .= "💰 *Payment Details*\n\n";
                            $message .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                            $message .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                            $message .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                            $message .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                            $message .= "🧾 *Invoice:* " . $invoiceLink . "\n\n";
                            $message .= "📞* Need Help?*\n\n";
                            $message .= "Call us at +91 70888 73331\n\n";
                            $message .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                            $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
                            break;
                            
                        case 'self_drive':
                            // Self Drive format
                            $plan = $order->plan ?? 'N/A';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            $dateStr = '';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = 'Duracab Office';
                            $drop = 'Duracab Office';
                            
                            $message = "🎉 *Congratulations! Your Booking is Confirmed* 🎉\n\n";
                            $message .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                            $message .= "👤 *Name:* " . $customerName . "\n\n";
                            $message .= "📞 *Mobile:* " . $mobile . "\n\n";
                            $message .= "🚖 *Booking Details*\n\n";
                            $message .= "*• Type:* Self Drive ($plan)\n\n";
                            $message .= "*• Booking For:* " . $bookingFor . "\n\n";
                            $message .= "*• Date:* " . $dateStr . "\n\n";
                            $message .= "*• Time:* " . $timeStr . "\n\n";
                            $message .= "*• Taxi Type:* " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "*• Pickup:* " . $pickup . "\n\n";
                            $message .= "*• Drop:* " . $drop . "\n\n";
                            $message .= "💰 *Payment Details*\n\n";
                            $message .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                            $message .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                            $message .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                            $message .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                            $message .= "🧾 *Invoice:* " . $invoiceLink . "\n\n";
                            $message .= "📞* Need Help?*\n\n";
                            $message .= "Call us at +91 70888 73331\n\n";
                            $message .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                            $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
                            break;
                            
                        case 'local':
                        default:
                            // Local trip format (default)
                            $plan = $order->plan ?? '';
                            $planDisplay = $plan ? "($plan)" : '';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            $dateStr = '';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "🎉 *Congratulations! Your Booking is Confirmed* 🎉\n\n";
                            $message .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                            $message .= "👤 *Name:* " . $customerName . "\n\n";
                            $message .= "📞 *Mobile:* " . $mobile . "\n\n";
                            $message .= "🚖 *Booking Details*\n\n";
                            $message .= "*• Type:* Local Trip " . $planDisplay . "\n\n";
                            $message .= "*• Booking For:* " . $bookingFor . "\n\n";
                            $message .= "*• Date:* " . $dateStr . "\n\n";
                            $message .= "*• Time:* " . $timeStr . "\n\n";
                            $message .= "*• Taxi Type:* " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "*• Pickup:* " . $pickup . "\n\n";
                            $message .= "*• Drop:* " . $drop . "\n\n";
                            $message .= "💰 *Payment Details*\n\n";
                            $message .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                            $message .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                            $message .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                            $message .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                            $message .= "🧾 *Invoice:* " . $invoiceLink . "\n\n";
                            $message .= "📞* Need Help?*\n\n";
                            $message .= "Call us at +91 70888 73331\n\n";
                            $message .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                            $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
                            break;
                    }

                    try {
                        WhatsAppService::send($mobile, $message);
                    } catch (\Exception $e) {
                        \Log::error('WhatsApp confirmation failed for order ' . $order->id . ': ' . $e->getMessage());
                    }
                    
                    // Send WhatsApp notification to admin when booking is confirmed
                    $adminMobile = env('ADMIN_MOBILE');
                    if ($adminMobile) {
                        // Format trip details for admin
                        $adminTripType = '';
                        $adminBookingFor = '';
                        $adminDateStr = '';
                        $adminTimeStr = $timeStr;
                        $adminPickup = 'N/A';
                        $adminDrop = 'N/A';
                        
                        switch($order->ride_type) {
                            case 'one_way':
                                $adminTripType = 'One Way';
                                $adminBookingFor = strtoupper(($order->cityFrom ?? '') . ' ➝ ' . ($order->cityTo ?? ''));
                                $adminDateStr = $order->date ? date('d/m/Y', strtotime($order->date)) : 'N/A';
                                $adminPickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                                $adminDrop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                                break;
                            case 'return':
                                $adminTripType = 'Round Trip';
                                $cities = [];
                                if ($order->cityFrom) $cities[] = $order->cityFrom;
                                if ($order->cityTo) $cities[] = $order->cityTo;
                                $adminBookingFor = implode(' :', $cities);
                                if ($order->date) {
                                    $adminDateStr = date('d/m/Y', strtotime($order->date));
                                    if ($order->dateTo) {
                                        $adminDateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                    }
                                }
                                $adminPickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                                $adminDrop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                                break;
                            case 'self_drive':
                                $plan = $order->plan ?? 'N/A';
                                $adminTripType = 'Self Drive (' . $plan . ')';
                                $adminBookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                                if ($order->date) {
                                    $adminDateStr = date('d/m/Y', strtotime($order->date));
                                    if ($order->dateTo) {
                                        $adminDateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                    }
                                }
                                $adminPickup = 'Duracab Office';
                                $adminDrop = 'Duracab Office';
                                break;
                            case 'local':
                            default:
                                $plan = $order->plan ?? '';
                                $planDisplay = $plan ? "($plan)" : '';
                                $adminTripType = 'Local Trip ' . $planDisplay;
                                $adminBookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                                if ($order->date) {
                                    $adminDateStr = date('d/m/Y', strtotime($order->date));
                                    if ($order->dateTo) {
                                        $adminDateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                    }
                                }
                                $adminPickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                                $adminDrop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                                break;
                        }
                        
                        $adminMessage = "🎉 📢 *New Booking Alert*\n\n";
                        $adminMessage .= "Dear Admin,\n\n";
                        $adminMessage .= "A *new booking* has been confirmed.\n\n";
                        $adminMessage .= "🎉 *Booking Details* 🎉\n\n";
                        $adminMessage .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                        $adminMessage .= "👤 *Name:* " . $customerName . "\n\n";
                        $adminMessage .= "📞 *Mobile:* " . $mobile . "\n\n";
                        $adminMessage .= "🚖 *Booking Details*\n\n";
                        $adminMessage .= "*• Type:* " . $adminTripType . "\n\n";
                        $adminMessage .= "*• Booking For:* " . $adminBookingFor . "\n\n";
                        $adminMessage .= "*• Date:* " . $adminDateStr . "\n\n";
                        $adminMessage .= "*• Time:* " . $adminTimeStr . "\n\n";
                        $adminMessage .= "*• Taxi Type:* " . $taxiType . " (" . $taxiCount . ")\n\n";
                        $adminMessage .= "*• Pickup:* " . $adminPickup . "\n\n";
                        $adminMessage .= "*• Drop:* " . $adminDrop . "\n\n";
                        $adminMessage .= "💰 *Payment Details*\n\n";
                        $adminMessage .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                        $adminMessage .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                        $adminMessage .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                        $adminMessage .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                        $adminMessage .= "📞* Need Help?*\n\n";
                        $adminMessage .= "Call us at +91 70888 73331\n\n";
                        $adminMessage .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                        $adminMessage .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
                        
                        try {
                            WhatsAppService::send($adminMobile, $adminMessage);
                        } catch (\Exception $e) {
                            \Log::error('WhatsApp admin booking confirmation failed: ' . $e->getMessage());
                        }
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
                                $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                            }
                        }
                        
                        // Format booking type
                        $bookingType = '';
                        $bookingFor = '';
                        switch($order->ride_type) {
                            case 'local':
                                $bookingType = "Local Trip";
                                $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                                break;
                            case 'one_way':
                                $bookingType = "One Way Trip";
                                $bookingFor = strtoupper(($order->cityFrom ?? '') . ' TO ' . ($order->cityTo ?? ''));
                                break;
                            case 'return':
                                $bookingType = "Round Trip";
                                $cities = [];
                                if ($order->cityFrom) $cities[] = $order->cityFrom;
                                if ($order->cityTo) $cities[] = $order->cityTo;
                                $bookingFor = implode(' :', $cities);
                                break;
                            case 'self_drive':
                                $bookingType = "Self Drive Trip";
                                $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                                break;
                            default:
                                $bookingType = ucfirst($order->ride_type ?? 'Trip');
                                $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                        }
                        
                        $message = "❌ *Booking Cancellation Confirmation*\n\n";
                        $message .= "Dear Sir,\n\n";
                        $message .= "Your booking has been *successfully canceled.*\n\n";
                        $message .= "🆔 *Ticket ID:* " . $order->id . "\n\n";
                        $message .= "📅 *Date:* " . $dateStr . "\n\n";
                        $message .= "🚘 *Trip Type:* " . $bookingType . "\n\n";
                        $message .= "💸 *Refund Process:*\n\n";
                        $message .= "Please kindly send your *UPI ID or QR Code* to initiate your refund.\n\n";
                        $message .= "For any questions or assistance,\n\n";
                        $message .= "📞 *Please call us at:* +91 70888 73331\n\n";
                        $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides.*";
                        
                        WhatsAppService::send($mobile, $message);
                        
                        // Send cancellation notice to driver if driver was assigned
                        if ($order->driver_id) {
                            $driver = User::where('id', $order->driver_id)->first();
                            if ($driver && $driver->mobile) {
                                $driverCancellationMessage = "*Booking Cancellation Notice*\n\n";
                                $driverCancellationMessage .= "Dear Partner,\n\n";
                                $driverCancellationMessage .= "We regret to inform you that the *booking (Ticket ID: " . $order->id . ")* for *" . $bookingFor . "* has been *canceled by the customer.*\n\n";
                                $driverCancellationMessage .= "You are kindly requested *not to contact or call the customer* regarding this booking.\n\n";
                                $driverCancellationMessage .= "For any questions or clarification,\n\n";
                                $driverCancellationMessage .= "📞 *Please call us at:* 7088873331";
                                
                                try {
                                    WhatsAppService::send($driver->mobile, $driverCancellationMessage);
                                } catch (\Exception $e) {
                                    \Log::error('WhatsApp driver cancellation notice failed: ' . $e->getMessage());
                                }
                            }
                        }
                    } else {
                        // Booking canceled by Duracabs/Admin
                        $message = "🙏 *Booking Update*\n\n";
                        $message .= "Dear Sir,\n\n";
                        $message .= "We sincerely regret to inform you that we are *unable to accept your booking* at the moment due to *high booking volume.*\n\n";
                        $message .= "We truly apologize for the inconvenience caused and request you to *please try again after some time.*\n\n";
                        $message .= "For any questions or assistance:\n\n";
                        $message .= "📞 *Call us:* +91 70888 73331\n\n";
                        $message .= "📧 *Email:* info@duracabs.com\n\n";
                        $message .= "🚗 *Dura Cabs — Safe • Reliable • Affordable Rides.*";
                        
                        WhatsAppService::send($mobile, $message);
                    }
                }
            }

            // Send WhatsApp notification when driver is assigned to order
            if ($order->wasChanged('driver_id') && $order->driver_id && $order->vehicle_id) {
                // Load relationships
                $order->load(['user', 'address', 'vehicle', 'items']);
                
                // Get driver (driver_id points to User model)
                $driver = User::where('id', $order->driver_id)->first();
                
                $customerMobile = null;
                
                // Get mobile from address first, then user (for customer notification)
                if ($order->address) {
                    $customerMobile = $order->address->phone ?? null;
                }
                
                if (!$customerMobile && $order->user) {
                    $customerMobile = $order->user->mobile ?? null;
                }
                
                // Send notification to customer about driver details
                if ($customerMobile && $driver && $order->vehicle) {
                    $contactNumber = env('CONTACT_NUMBER', '+91 70888 73331');
                    
                    $driverName = $driver->name ?? 'N/A';
                    $driverMobile = $driver->mobile ?? 'N/A';
                    $taxiType = $order->productName ?? $order->vehicle->car_classification ?? 'N/A';
                    $taxiNumber = $order->vehicle->vehicle_number ?? 'N/A';
                    
                    $message = "👨‍✈️ *Driver Details*\n\n";
                    $message .= "Dear Sir,\n\n";
                    $message .= "Please kindly note the driver details for your booking.\n\n";
                    $message .= "🆔 *Ticket ID:* " . $order->id . "\n\n";
                    $message .= "👤 *Driver Name:* " . $driverName . "\n\n";
                    $message .= "📞 *Mobile Number:* " . $driverMobile . "\n\n";
                    $message .= "🚘 *Taxi Type:* " . $taxiType . "\n\n";
                    $message .= "🔢 *Taxi Number:* " . $taxiNumber . "\n\n";
                    $message .= "For any questions or assistance,\n\n";
                    $message .= "📞 *Please call us:* +91 70888 73331\n\n";
                    $message .= "🚗 *Dura Cabs — Safe • Reliable • Affordable Rides.*";
                    
                    WhatsAppService::send($customerMobile, $message);
                }
                
                // Send WhatsApp notification to admin when driver is assigned
                $adminMobile = env('ADMIN_MOBILE');
                if ($adminMobile && $driver && $order->vehicle) {
                    // Get transporter/vendor name if available
                    $vendorName = 'N/A';
                    if ($order->transporter_id) {
                        $transporter = User::where('id', $order->transporter_id)->first();
                        if ($transporter) {
                            $vendorName = $transporter->name ?? $transporter->company_name ?? 'N/A';
                        }
                    }
                    
                    $driverName = $driver->name ?? 'N/A';
                    $driverMobile = $driver->mobile ?? 'N/A';
                    $taxiType = $order->productName ?? $order->vehicle->car_classification ?? 'N/A';
                    $taxiNumber = $order->vehicle->vehicle_number ?? 'N/A';
                    
                    $adminMessage = "📢 Driver Details Received from Vendor\n\n";
                    $adminMessage .= "Vendor Name: " . $vendorName . "\n\n";
                    $adminMessage .= "Ticket ID: " . $order->id . "\n\n";
                    $adminMessage .= "Driver Name: " . $driverName . "\n\n";
                    $adminMessage .= "Driver Mobile: " . $driverMobile . "\n\n";
                    $adminMessage .= "Taxi Type: " . $taxiType . "\n\n";
                    $adminMessage .= "Taxi Number: " . $taxiNumber . "\n\n";
                    $adminMessage .= "📞 For any assistance, please contact: +91 70888 73331\n\n";
                    $adminMessage .= "🚗 Dura Cabs — Safe • Reliable • Affordable Rides.";
                    
                    try {
                        WhatsAppService::send($adminMobile, $adminMessage);
                    } catch (\Exception $e) {
                        \Log::error('WhatsApp admin driver details notification failed: ' . $e->getMessage());
                    }
                }
                
                // Send notification to DRIVER about new booking assignment
                if ($driver && $driver->mobile) {
                    $order->refresh();
                    $address = $order->address;
                    if (!$address) {
                        $address = \App\Models\address::where('order_id', $order->id)->first();
                    }
                    
                    $customerName = $address->full_name ?? $order->user->name ?? 'N/A';
                    $customerMobileNumber = $address->phone ?? $order->user->mobile ?? 'N/A';
                    $bookingAmount = $order->grand_total ?? 0;
                    
                    $advance = 0;
                    if ($order->payment_status === 'paid') {
                        $advance = $bookingAmount;
                    } elseif ($order->payment_status === 'partial') {
                        $advance = $bookingAmount * 0.2;
                    }
                    $balance = $bookingAmount - $advance;
                    
                    $invoiceLink = route('my-orders.show', $order->id);
                    $timeStr = $order->time ? date('h:i A', strtotime($order->time)) : 'N/A';
                    $taxiType = $order->productName ?? 'N/A';
                    $taxiCount = $order->items->count() ?? 1;
                    $paymentMode = ucfirst($order->payment_method ?? 'Cash');
                    
                    // Format trip details based on ride type
                    $tripType = '';
                    $bookingFor = '';
                    $dateStr = '';
                    $pickup = 'N/A';
                    $drop = 'N/A';
                    
                    switch($order->ride_type) {
                        case 'one_way':
                            $tripType = 'One Way';
                            $bookingFor = strtoupper(($order->cityFrom ?? '') . ' TO ' . ($order->cityTo ?? ''));
                            $dateStr = $order->date ? date('d/m/Y', strtotime($order->date)) : 'N/A';
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            break;
                        case 'return':
                            $tripType = 'Round Trip';
                            $cities = [];
                            if ($order->cityFrom) $cities[] = $order->cityFrom;
                            if ($order->cityTo) $cities[] = $order->cityTo;
                            $bookingFor = implode(' :', $cities);
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            break;
                        case 'self_drive':
                            $plan = $order->plan ?? 'N/A';
                            $tripType = 'Self Drive (' . $plan . ')';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = 'Duracab Office';
                            $drop = 'Duracab Office';
                            break;
                        case 'local':
                        default:
                            $plan = $order->plan ?? '';
                            $planDisplay = $plan ? "($plan)" : '';
                            $tripType = 'Local Trip ' . $planDisplay;
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            break;
                    }
                    
                    $driverMessage = "🎉 📢 *New Booking Alert*\n\n";
                    $driverMessage .= "Dear Driver Partner *" . ($driver->name ?? 'Driver') . ",*\n\n";
                    $driverMessage .= "You have received a *new booking* from *Dura Cabs.* 🚘\n\n";
                    $driverMessage .= "Please check your driver app or contact the support team for full trip details.\n\n";
                    $driverMessage .= "🎉 *Booking Details* 🎉\n\n";
                    $driverMessage .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                    $driverMessage .= "👤 *Name:* " . $customerName . "\n\n";
                    $driverMessage .= "📞 *Mobile:* " . $customerMobileNumber . "\n\n";
                    $driverMessage .= "🚖 *Booking Details*\n\n\n";
                    $driverMessage .= "*• Type:* " . $tripType . "\n\n";
                    $driverMessage .= "*• Booking For:* " . $bookingFor . "\n\n";
                    $driverMessage .= "*• Date:* " . $dateStr . "\n\n";
                    $driverMessage .= "*• Time:* " . $timeStr . "\n\n";
                    $driverMessage .= "*• Taxi Type:* " . $taxiType . " (" . $taxiCount . ")\n\n";
                    $driverMessage .= "*• Pickup:* " . $pickup . "\n\n";
                    $driverMessage .= "*• Drop:* " . $drop . "\n\n";
                    $driverMessage .= "💰 *Payment Details*\n\n";
                    $driverMessage .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                    $driverMessage .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                    $driverMessage .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                    $driverMessage .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                    $driverMessage .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                    $driverMessage .= "🧾 *Invoice:* " . $invoiceLink . "\n\n\n";
                    $driverMessage .= "📞* Need Help?*\n\n\n";
                    $driverMessage .= "Call us at +91 70888 73331\n\n";
                    $driverMessage .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                    $driverMessage .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
                    
                    try {
                        WhatsAppService::send($driver->mobile, $driverMessage);
                    } catch (\Exception $e) {
                        \Log::error('WhatsApp driver booking alert failed: ' . $e->getMessage());
                    }
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
                    $message = "⭐ *We Value Your Feedback!*\n\n";
                    $message .= "Your feedback means a lot to us 🙏\n\n";
                    $message .= "If you have a moment, we'd truly appreciate it if you could share your experience by leaving a review on our *Google Business Profile.*\n\n";
                    $message .= "📝 Click below to leave your review:\n\n";
                    $message .= "👉 https://web.scanstandy.com/gr/review/smartyd_511_E8LEjDZ\n\n";
                    $message .= "Thank you for choosing *Dura Cabs — Safe • Reliable • Affordable Rides.* 🚗";
                    
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
                    
                    // Parse trip details from order
                    $tripRoute = 'N/A';
                    $timeStr = $order->time ? date('h:i A', strtotime($order->time)) : 'N/A';
                    
                    if ($order->ride_type === 'one_way') {
                        $tripRoute = ($order->cityFrom ?? '') . ' ➝ ' . ($order->cityTo ?? '');
                    } elseif ($order->ride_type === 'return') {
                        $cities = [];
                        if ($order->cityFrom) $cities[] = $order->cityFrom;
                        if ($order->cityTo) $cities[] = $order->cityTo;
                        $tripRoute = implode(' ➝ ', $cities);
                    } else {
                        $tripRoute = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                    }
                    
                    $message = "⚠️ *Attention! New Booking Enquiry Received*\n\n";
                    $message .= "Dura Cabs has received a new *booking enquiry.*\n\n";
                    $message .= "📋 *Booking Details:*\n\n";
                    $message .= "*• Booking Type:* " . ucfirst($bookingTypeLabel) . "\n\n";
                    if ($tripRoute !== 'N/A') {
                        $message .= "*• Trip Route:* " . $tripRoute . "\n\n";
                    }
                    $message .= "*• Date:* " . $dateStr . "\n\n";
                    $message .= "*• Time:* " . $timeStr . "\n\n";
                    $message .= "Please review and take necessary action.\n\n";
                    $message .= "📞 *For any assistance:* +91 70888 73331";
                    
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
                    
                    $paymentMode = ucfirst($order->payment_method ?? 'Cash');
                    
                    $message = '';
                    
                    switch($order->ride_type) {
                        case 'one_way':
                            $bookingFor = strtoupper(($order->cityFrom ?? '') . ' TO ' . ($order->cityTo ?? ''));
                            $dateStr = $order->date ? date('d/m/Y', strtotime($order->date)) : 'N/A';
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "🎉 📢 *New Booking Alert*\n\n";
                            $message .= "Dear Vendor Partner,\n\n";
                            $message .= "You have received a *new booking* from *Dura Cabs.* 🚘\n\n";
                            $message .= "Please check your vendor panel or contact the support team for full trip details.\n\n";
                            $message .= "🎉 *Booking Details* 🎉\n\n";
                            $message .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                            $message .= "👤 *Name:* " . $customerName . "\n\n";
                            $message .= "📞 *Mobile:* " . $customerMobile . "\n\n";
                            $message .= "🚖 *Booking Details*\n\n";
                            $message .= "*• Type:* One Way\n\n";
                            $message .= "*• Booking For:* " . $bookingFor . "\n\n";
                            $message .= "*• Date:* " . $dateStr . "\n\n";
                            $message .= "*• Time:* " . $timeStr . "\n\n";
                            $message .= "*• Taxi Type:* " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "*• Pickup:* " . $pickup . "\n\n";
                            $message .= "*• Drop:* " . $drop . "\n\n";
                            $message .= "💰 *Payment Details*\n\n";
                            $message .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                            $message .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                            $message .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                            $message .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                            $message .= "🧾 *Invoice:* " . $invoiceLink . "\n\n";
                            $message .= "📞* Need Help?*\n\n";
                            $message .= "Call us at +91 70888 73331\n\n";
                            $message .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                            $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
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
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                    $start = strtotime($order->date);
                                    $end = strtotime($order->dateTo);
                                    $totalDays = ceil(($end - $start) / (60 * 60 * 24));
                                }
                            }
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "🎉 📢 *New Booking Alert*\n\n";
                            $message .= "Dear Vendor Partner,\n\n";
                            $message .= "You have received a *new booking* from *Dura Cabs.* 🚘\n\n";
                            $message .= "Please check your vendor panel or contact the support team for full trip details.\n\n";
                            $message .= "🎉 *Booking Details* 🎉\n\n";
                            $message .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                            $message .= "👤 *Name:* " . $customerName . "\n\n";
                            $message .= "📞 *Mobile:* " . $customerMobile . "\n\n";
                            $message .= "🚖 *Booking Details*\n\n";
                            $message .= "*• Type:* Round Trip\n\n";
                            $message .= "*• Booking For:* " . $bookingFor . "\n\n";
                            $message .= "*• Date:* " . $dateStr . "\n\n";
                            if ($totalDays > 0) {
                                $message .= "*• Total Days:* " . $totalDays . "\n\n";
                            }
                            $message .= "*• Time:* " . $timeStr . "\n\n";
                            $message .= "*• Taxi Type:* " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "*• Pickup:* " . $pickup . "\n\n";
                            $message .= "*• Drop:* " . $drop . "\n\n";
                            $message .= "💰 *Payment Details*\n\n";
                            $message .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                            $message .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                            $message .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                            $message .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                            $message .= "🧾 *Invoice:* " . $invoiceLink . "\n\n";
                            $message .= "📞* Need Help?*\n\n";
                            $message .= "Call us at +91 70888 73331\n\n";
                            $message .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                            $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
                            break;
                            
                        case 'self_drive':
                            $plan = $order->plan ?? 'N/A';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            $dateStr = '';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = 'Duracab Office';
                            $drop = 'Duracab Office';
                            
                            $message = "🎉 📢 *New Booking Alert*\n\n";
                            $message .= "Dear Vendor Partner,\n\n";
                            $message .= "You have received a *new booking* from *Dura Cabs.* 🚘\n\n";
                            $message .= "Please check your vendor panel or contact the support team for full trip details.\n\n";
                            $message .= "🎉 *Booking Details* 🎉\n\n";
                            $message .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                            $message .= "👤 *Name:* " . $customerName . "\n\n";
                            $message .= "📞 *Mobile:* " . $customerMobile . "\n\n";
                            $message .= "🚖 *Booking Details*\n\n";
                            $message .= "*• Type:* Self Drive (" . $plan . ")\n\n";
                            $message .= "*• Booking For:* " . $bookingFor . "\n\n";
                            $message .= "*• Date:* " . $dateStr . "\n\n";
                            $message .= "*• Time:* " . $timeStr . "\n\n";
                            $message .= "*• Taxi Type:* " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "*• Pickup:* " . $pickup . "\n\n";
                            $message .= "*• Drop:* " . $drop . "\n\n";
                            $message .= "💰 *Payment Details*\n\n";
                            $message .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                            $message .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                            $message .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                            $message .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                            $message .= "🧾 *Invoice:* " . $invoiceLink . "\n\n";
                            $message .= "📞* Need Help?*\n\n";
                            $message .= "Call us at +91 70888 73331\n\n";
                            $message .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                            $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
                            break;
                            
                        case 'local':
                        default:
                            $plan = $order->plan ?? '';
                            $planDisplay = $plan ? "($plan)" : '';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            $dateStr = '';
                            if ($order->date) {
                                $dateStr = date('d/m/Y', strtotime($order->date));
                                if ($order->dateTo) {
                                    $dateStr .= ' to ' . date('d/m/Y', strtotime($order->dateTo));
                                }
                            }
                            $pickup = ($address ? $address->pickup_address : null) ?? $order->booking_from ?? 'N/A';
                            $drop = ($address ? $address->drop_address : null) ?? $order->booking_to ?? 'N/A';
                            
                            $message = "🎉 📢 *New Booking Alert*\n\n";
                            $message .= "Dear Vendor Partner,\n\n";
                            $message .= "You have received a *new booking* from *Dura Cabs.* 🚘\n\n";
                            $message .= "Please check your vendor panel or contact the support team for full trip details.\n\n";
                            $message .= "🎉 *Booking Details* 🎉\n\n";
                            $message .= "🆔 *Booking ID:* " . $order->id . "\n\n";
                            $message .= "👤 *Name:* " . $customerName . "\n\n";
                            $message .= "📞 *Mobile:* " . $customerMobile . "\n\n";
                            $message .= "🚖 *Booking Details*\n\n";
                            $message .= "*• Type:* Local Trip " . $planDisplay . "\n\n";
                            $message .= "*• Booking For:* " . $bookingFor . "\n\n";
                            $message .= "*• Date:* " . $dateStr . "\n\n";
                            $message .= "*• Time:* " . $timeStr . "\n\n";
                            $message .= "*• Taxi Type:* " . $taxiType . " (" . $taxiCount . ")\n\n";
                            $message .= "*• Pickup:* " . $pickup . "\n\n";
                            $message .= "*• Drop:* " . $drop . "\n\n";
                            $message .= "💰 *Payment Details*\n\n";
                            $message .= "*• Payment Mode:* " . $paymentMode . "\n\n";
                            $message .= "*• Total Amount:* ₹" . number_format($bookingAmount, 2) . "/-\n\n";
                            $message .= "*• Advance Paid:* ₹" . number_format($advance, 2) . "/-\n\n";
                            $message .= "*• Balance:* ₹" . number_format($balance, 2) . "/-\n\n";
                            $message .= "📌 Note: Check Your Invoice And Read All Terms And Conditions.\n\n";
                            $message .= "🧾 *Invoice:* " . $invoiceLink . "\n\n";
                            $message .= "📞* Need Help?*\n\n";
                            $message .= "Call us at +91 70888 73331\n\n";
                            $message .= "or Chat with us on WhatsApp 👉 https://wa.me/message/2D2MKVLRGWDOJ1\n\n";
                            $message .= "🚗 *Thank you for choosing Dura Cabs — Safe • Reliable • Affordable Rides*";
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
                    // Format booking type and location
                    $bookingTypeLabel = '';
                    $bookingFor = '';
                    switch($order->ride_type) {
                        case 'local':
                            $bookingTypeLabel = 'Local';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            break;
                        case 'one_way':
                            $bookingTypeLabel = 'One Way';
                            $bookingFor = strtoupper(($order->cityFrom ?? '') . ' ➝ ' . ($order->cityTo ?? ''));
                            break;
                        case 'return':
                            $bookingTypeLabel = 'Round Trip';
                            $cities = [];
                            if ($order->cityFrom) $cities[] = $order->cityFrom;
                            if ($order->cityTo) $cities[] = $order->cityTo;
                            $bookingFor = implode(' :', $cities);
                            break;
                        case 'self_drive':
                            $bookingTypeLabel = 'Self Drive';
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                            break;
                        default:
                            $bookingTypeLabel = ucfirst($order->ride_type ?? 'Booking');
                            $bookingFor = $order->cityTo ?? $order->cityFrom ?? 'N/A';
                    }
                    
                    $message = "⚠️ *Booking Cancellation Notice*\n\n";
                    $message .= "Dear Partner,\n\n";
                    $message .= "We regret to inform you that the *booking (Ticket ID: " . $order->id . ")* for *" . $bookingFor . "* has been *canceled by the customer.*\n\n";
                    $message .= "You are kindly requested *not to contact or call the customer* regarding this booking.\n\n";
                    $message .= "For any questions or clarification,\n\n";
                    $message .= "📞 *Please call us at:* 7088873331";
                    
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
