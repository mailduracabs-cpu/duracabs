<?php

namespace Database\Seeders;

use App\Models\WhatsAppNotificationRule;
use Illuminate\Database\Seeder;

class WhatsAppNotificationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'New Customer Registration',
                'event_key' => 'customer.registered',
                'template_key' => 'admin_new_customer',
                'send_admin' => true,
            ],
            [
                'name' => 'New Vendor Registration',
                'event_key' => 'vendor.registered',
                'template_key' => 'admin_new_vendor_registration',
                'send_admin' => true,
            ],
            [
                'name' => 'Customer Search',
                'event_key' => 'lead.searched',
                'template_key' => 'admin_customer_enquiry',
                'send_admin' => true,
                'send_sales' => true,
            ],
            [
                'name' => 'Fare Checked',
                'event_key' => 'lead.fare_checked',
                'template_key' => 'admin_customer_enquiry',
                'send_admin' => true,
                'send_sales' => true,
            ],
            [
                'name' => 'Vehicle Selected',
                'event_key' => 'lead.vehicle_selected',
                'template_key' => 'admin_customer_enquiry',
                'send_admin' => true,
                'send_sales' => true,
            ],
            [
                'name' => 'Checkout Started',
                'event_key' => 'lead.checkout_started',
                'template_key' => 'admin_customer_enquiry',
                'send_admin' => true,
                'send_sales' => true,
            ],
            [
                'name' => 'Payment Started',
                'event_key' => 'lead.payment_started',
                'template_key' => 'admin_customer_enquiry',
                'send_admin' => true,
                'send_sales' => true,
            ],
            [
                'name' => 'Payment Failed Lead',
                'event_key' => 'lead.payment_failed',
                'template_key' => 'admin_customer_enquiry',
                'send_admin' => true,
                'send_sales' => true,
                'send_accounts' => true,
            ],
            [
                'name' => 'Lead Abandoned',
                'event_key' => 'lead.abandoned',
                'template_key' => 'admin_customer_enquiry',
                'send_admin' => true,
                'send_sales' => true,
            ],
            [
                'name' => 'Booking Received',
                'event_key' => 'booking.received',
                'template_key' => 'booking_received',
                'send_customer' => true,
                'send_admin' => true,
                'send_operations' => true,
            ],
            [
                'name' => 'Booking Confirmed',
                'event_key' => 'booking.confirmed',
                'template_key' => 'booking_confirmed',
                'send_customer' => true,
                'send_vendor' => true,
                'send_admin' => true,
                'send_operations' => true,
            ],
            [
                'name' => 'Booking Cancelled',
                'event_key' => 'booking.cancelled',
                'template_key' => 'booking_cancelled',
                'send_customer' => true,
                'send_vendor' => true,
                'send_admin' => true,
                'send_operations' => true,
            ],
            [
                'name' => 'Payment Received',
                'event_key' => 'payment.received',
                'template_key' => 'payment_received',
                'send_customer' => true,
                'send_admin' => true,
                'send_accounts' => true,
            ],
            [
                'name' => 'Payment Reminder',
                'event_key' => 'payment.reminder',
                'template_key' => 'payment_reminder',
                'send_customer' => true,
                'send_accounts' => true,
            ],
            [
                'name' => 'Driver Assigned',
                'event_key' => 'driver.assigned',
                'template_key' => 'driver_assigned',
                'send_customer' => true,
                'send_driver' => true,
                'send_vendor' => true,
                'send_admin' => true,
                'send_operations' => true,
            ],
            [
                'name' => 'Trip Started',
                'event_key' => 'trip.started',
                'template_key' => 'trip_started',
                'send_customer' => true,
                'send_admin' => true,
                'send_operations' => true,
            ],
            [
                'name' => 'Trip Completed',
                'event_key' => 'trip.completed',
                'template_key' => 'trip_completed',
                'send_customer' => true,
                'send_admin' => true,
                'send_operations' => true,
            ],
            [
                'name' => 'Invoice Ready',
                'event_key' => 'invoice.ready',
                'template_key' => 'invoice_ready',
                'send_customer' => true,
                'send_accounts' => true,
            ],
            [
                'name' => 'Review Request',
                'event_key' => 'review.request',
                'template_key' => 'review_request',
                'send_customer' => true,
            ],
            [
                'name' => 'Refund Processed',
                'event_key' => 'refund.processed',
                'template_key' => 'refund_processed',
                'send_customer' => true,
                'send_admin' => true,
                'send_accounts' => true,
            ],
            [
                'name' => 'Security Refunded',
                'event_key' => 'security.refunded',
                'template_key' => 'security_refunded',
                'send_customer' => true,
                'send_admin' => true,
                'send_accounts' => true,
            ],
        ];

        foreach ($rules as $rule) {
            WhatsAppNotificationRule::updateOrCreate(
                ['event_key' => $rule['event_key']],
                array_merge(
                    [
                        'name' => $rule['name'],
                        'template_key' => $rule['template_key'],
                        'send_customer' => false,
                        'send_vendor' => false,
                        'send_driver' => false,
                        'send_admin' => false,
                        'send_sales' => false,
                        'send_operations' => false,
                        'send_accounts' => false,
                        'send_support' => false,
                        'is_active' => true,
                        'description' => null,
                    ],
                    $rule
                )
            );
        }

        $this->command?->info(
            count($rules)
            . ' WhatsApp notification rules seeded.'
        );
    }
}