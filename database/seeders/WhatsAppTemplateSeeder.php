<?php

namespace Database\Seeders;

use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;

class WhatsAppTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            /*
            |--------------------------------------------------------------------------
            | Customer Templates
            |--------------------------------------------------------------------------
            */

            $this->template(
                name: 'OTP Login',
                templateName: 'otp_login_v1',
                category: WhatsAppTemplate::CATEGORY_AUTHENTICATION,
                body: <<<'BODY'
Your Dura Cabs verification code is {{1}}.

This code is valid for {{2}} minutes.
Do not share this code with anyone.
BODY,
                variables: [
                    $this->variable(1, 'otp', '123456'),
                    $this->variable(2, 'expiry_minutes', '5'),
                ]
            ),

            $this->template(
                name: 'Customer Account Created',
                templateName: 'account_created_v1',
                body: <<<'BODY'
Hello {{1}},

Welcome to Dura Cabs. Your customer account has been created successfully.

Customer ID: {{2}}
Registered Mobile: {{3}}

You can now book rides and manage your trips through Dura Cabs.

For assistance, please call +91 70888 73331.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'customer_id', '1001'),
                    $this->variable(3, 'customer_mobile', '7088873331'),
                ]
            ),

            $this->template(
                name: 'Customer Inquiry Received',
                templateName: 'customer_inquiry_received_v1',
                body: <<<'BODY'
Hello {{1}},

Thank you for contacting Dura Cabs. We have received your inquiry.

Inquiry ID: {{2}}
Service: {{3}}
Route: {{4}}
Travel Date: {{5}}

Our team will contact you shortly with the relevant details.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'inquiry_id', 'INQ1001'),
                    $this->variable(3, 'service_type', 'One Way Cab'),
                    $this->variable(4, 'route', 'Agra to Delhi'),
                    $this->variable(5, 'travel_date', '05 August 2026'),
                ]
            ),

            $this->template(
                name: 'Booking Received',
                templateName: 'booking_received_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs booking request has been received successfully.

Booking ID: {{2}}
Service: {{3}}
Route: {{4}}
Travel Date: {{5}}
Total Amount: INR {{6}}

Our team will review your booking and update you shortly.

For assistance, please call +91 70888 73331.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'service_type', 'One Way Cab'),
                    $this->variable(4, 'route', 'Agra to Delhi'),
                    $this->variable(5, 'travel_date', '05 August 2026'),
                    $this->variable(6, 'total_amount', '4500.00'),
                ]
            ),

            $this->template(
                name: 'Booking Confirmed',
                templateName: 'booking_confirmed_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs booking has been confirmed.

Booking ID: {{2}}
Service: {{3}}
Vehicle: {{4}}
Route: {{5}}
Travel Date: {{6}}
Travel Time: {{7}}
Total Amount: INR {{8}}

Thank you for choosing Dura Cabs.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'service_type', 'One Way Cab'),
                    $this->variable(4, 'vehicle_name', 'Swift Dzire'),
                    $this->variable(5, 'route', 'Agra to Delhi'),
                    $this->variable(6, 'travel_date', '05 August 2026'),
                    $this->variable(7, 'travel_time', '10:00 AM'),
                    $this->variable(8, 'total_amount', '4500.00'),
                ]
            ),

            $this->template(
                name: 'Booking Rejected',
                templateName: 'booking_rejected_v1',
                body: <<<'BODY'
Hello {{1}},

We regret to inform you that your Dura Cabs booking request could not be accepted.

Booking ID: {{2}}
Reason: {{3}}

Please contact our support team at +91 70888 73331 for alternative options.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'rejection_reason', 'Vehicle unavailable for the selected time'),
                ]
            ),

            $this->template(
                name: 'Booking Cancelled',
                templateName: 'booking_cancelled_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs booking has been cancelled.

Booking ID: {{2}}
Reason: {{3}}

For assistance, please call +91 70888 73331.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'cancellation_reason', 'Cancelled by customer'),
                ]
            ),

            $this->template(
                name: 'Driver Assigned',
                templateName: 'driver_assigned_v1',
                body: <<<'BODY'
Hello {{1}},

A driver has been assigned to your Dura Cabs booking.

Booking ID: {{2}}
Driver Name: {{3}}
Driver Mobile: {{4}}
Vehicle: {{5}}
Vehicle Number: {{6}}
Pickup Time: {{7}}

Please contact the driver only if required.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'driver_name', 'Raj Kumar'),
                    $this->variable(4, 'driver_mobile', '9876543210'),
                    $this->variable(5, 'vehicle_name', 'Swift Dzire'),
                    $this->variable(6, 'vehicle_number', 'UP80AB1234'),
                    $this->variable(7, 'travel_time', '10:00 AM'),
                ]
            ),

            $this->template(
                name: 'Trip Started',
                templateName: 'trip_started_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs trip has started.

Booking ID: {{2}}
Route: {{3}}
Driver: {{4}}
Vehicle: {{5}}

We wish you a safe and comfortable journey.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'route', 'Agra to Delhi'),
                    $this->variable(4, 'driver_name', 'Raj Kumar'),
                    $this->variable(5, 'vehicle_name', 'Swift Dzire'),
                ]
            ),

            $this->template(
                name: 'Trip Completed',
                templateName: 'trip_completed_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs trip has been completed successfully.

Booking ID: {{2}}
Route: {{3}}
Total Amount: INR {{4}}
Payment Status: {{5}}

Thank you for travelling with Dura Cabs.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'route', 'Agra to Delhi'),
                    $this->variable(4, 'total_amount', '4500.00'),
                    $this->variable(5, 'payment_status', 'Paid'),
                ]
            ),

            $this->template(
                name: 'Payment Received',
                templateName: 'payment_received_v1',
                body: <<<'BODY'
Hello {{1}},

We have received your payment successfully.

Booking ID: {{2}}
Paid Amount: INR {{3}}
Remaining Amount: INR {{4}}
Payment Status: {{5}}

Thank you for choosing Dura Cabs.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'paid_amount', '500.00'),
                    $this->variable(4, 'remaining_amount', '4000.00'),
                    $this->variable(5, 'payment_status', 'Partially Paid'),
                ]
            ),

            $this->template(
                name: 'Payment Reminder',
                templateName: 'payment_reminder_v1',
                body: <<<'BODY'
Hello {{1}},

A payment is pending for your Dura Cabs booking.

Booking ID: {{2}}
Pending Amount: INR {{3}}
Payment Link: {{4}}

Please complete the payment to avoid any interruption to your booking.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'remaining_amount', '4000.00'),
                    $this->variable(4, 'payment_link', 'https://www.duracabs.com/pay/DC1001'),
                ]
            ),

            $this->template(
                name: 'Invoice Ready',
                templateName: 'invoice_ready_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs invoice is ready.

Booking ID: {{2}}
Invoice Link: {{3}}

Thank you for choosing Dura Cabs.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'invoice_link', 'https://www.duracabs.com/invoice/DC1001'),
                ]
            ),

            $this->template(
                name: 'Review Request',
                templateName: 'review_request_v1',
                body: <<<'BODY'
Hello {{1}},

Thank you for travelling with Dura Cabs.

Booking ID: {{2}}

Please share your experience using the link below:
{{3}}

Your feedback helps us improve our services.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'review_link', 'https://www.duracabs.com/review/DC1001'),
                ]
            ),

            $this->template(
                name: 'Refund Processed',
                templateName: 'refund_processed_v1',
                body: <<<'BODY'
Hello {{1}},

Your refund has been processed.

Booking ID: {{2}}
Refund Amount: INR {{3}}
Refund Status: {{4}}

The amount will be credited according to your payment provider's processing timeline.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'refund_amount', '500.00'),
                    $this->variable(4, 'refund_status', 'Processed'),
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Self-Drive Customer Templates
            |--------------------------------------------------------------------------
            */

            $this->template(
                name: 'Self Drive Booking Received',
                templateName: 'selfdrive_booking_received_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs self-drive booking request has been received.

Booking ID: {{2}}
Vehicle: {{3}}
Pickup Date and Time: {{4}}
Return Date and Time: {{5}}
Rental Amount: INR {{6}}
Security Deposit: INR {{7}}

Our team will review your booking and update you shortly.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'SD1001'),
                    $this->variable(3, 'vehicle_name', 'Mahindra Thar'),
                    $this->variable(4, 'pickup_datetime', '05 August 2026, 10:00 AM'),
                    $this->variable(5, 'return_datetime', '07 August 2026, 10:00 AM'),
                    $this->variable(6, 'total_amount', '11040.00'),
                    $this->variable(7, 'security_deposit', '5000.00'),
                ]
            ),

            $this->template(
                name: 'Self Drive Booking Confirmed',
                templateName: 'selfdrive_booking_confirmed_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs self-drive booking has been confirmed.

Booking ID: {{2}}
Vehicle: {{3}}
Pickup Date and Time: {{4}}
Return Date and Time: {{5}}
Pickup Location: {{6}}
Security Deposit: INR {{7}}

Please carry all required original documents at the time of pickup.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'SD1001'),
                    $this->variable(3, 'vehicle_name', 'Mahindra Thar'),
                    $this->variable(4, 'pickup_datetime', '05 August 2026, 10:00 AM'),
                    $this->variable(5, 'return_datetime', '07 August 2026, 10:00 AM'),
                    $this->variable(6, 'pickup_location', 'Agra, Uttar Pradesh'),
                    $this->variable(7, 'security_deposit', '5000.00'),
                ]
            ),

            $this->template(
                name: 'Self Drive Pickup Reminder',
                templateName: 'selfdrive_pickup_reminder_v1',
                body: <<<'BODY'
Hello {{1}},

This is a reminder for your upcoming self-drive vehicle pickup.

Booking ID: {{2}}
Vehicle: {{3}}
Pickup Date and Time: {{4}}
Pickup Location: {{5}}

Please arrive on time and carry all required original documents.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'SD1001'),
                    $this->variable(3, 'vehicle_name', 'Mahindra Thar'),
                    $this->variable(4, 'pickup_datetime', '05 August 2026, 10:00 AM'),
                    $this->variable(5, 'pickup_location', 'Agra, Uttar Pradesh'),
                ]
            ),

            $this->template(
                name: 'Self Drive Return Reminder',
                templateName: 'selfdrive_return_reminder_v1',
                body: <<<'BODY'
Hello {{1}},

This is a reminder to return your self-drive vehicle on time.

Booking ID: {{2}}
Vehicle: {{3}}
Return Date and Time: {{4}}
Return Location: {{5}}

Late return charges may apply according to the booking terms.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'SD1001'),
                    $this->variable(3, 'vehicle_name', 'Mahindra Thar'),
                    $this->variable(4, 'return_datetime', '07 August 2026, 10:00 AM'),
                    $this->variable(5, 'return_location', 'Agra, Uttar Pradesh'),
                ]
            ),

            $this->template(
                name: 'Security Deposit Refunded',
                templateName: 'security_refunded_v1',
                body: <<<'BODY'
Hello {{1}},

Your self-drive security deposit refund has been processed.

Booking ID: {{2}}
Refund Amount: INR {{3}}
Refund Status: {{4}}

The amount will be credited according to your payment provider's processing timeline.
BODY,
                variables: [
                    $this->variable(1, 'customer_name', 'Sanjay Singh'),
                    $this->variable(2, 'booking_id', 'SD1001'),
                    $this->variable(3, 'refund_amount', '5000.00'),
                    $this->variable(4, 'refund_status', 'Processed'),
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Vendor Templates
            |--------------------------------------------------------------------------
            */

            $this->template(
                name: 'Vendor Registration Received',
                templateName: 'vendor_registration_received_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs vendor registration request has been received.

Registration ID: {{2}}
Business Name: {{3}}
City: {{4}}

Our team will review your information and documents and update you shortly.
BODY,
                variables: [
                    $this->variable(1, 'vendor_name', 'Dura Vendor'),
                    $this->variable(2, 'vendor_id', 'V1001'),
                    $this->variable(3, 'business_name', 'Dura Travel Services'),
                    $this->variable(4, 'city', 'Agra'),
                ]
            ),

            $this->template(
                name: 'Vendor Registration Approved',
                templateName: 'vendor_registration_approved_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs vendor registration has been approved.

Vendor ID: {{2}}
Business Name: {{3}}
Login Mobile: {{4}}

You may now access the vendor panel and manage your vehicles and bookings.
BODY,
                variables: [
                    $this->variable(1, 'vendor_name', 'Dura Vendor'),
                    $this->variable(2, 'vendor_id', 'V1001'),
                    $this->variable(3, 'business_name', 'Dura Travel Services'),
                    $this->variable(4, 'vendor_mobile', '9876543210'),
                ]
            ),

            $this->template(
                name: 'Vendor Registration Rejected',
                templateName: 'vendor_registration_rejected_v1',
                body: <<<'BODY'
Hello {{1}},

Your Dura Cabs vendor registration could not be approved.

Registration ID: {{2}}
Reason: {{3}}

Please review the required information and contact our support team if you need assistance.
BODY,
                variables: [
                    $this->variable(1, 'vendor_name', 'Dura Vendor'),
                    $this->variable(2, 'vendor_id', 'V1001'),
                    $this->variable(3, 'rejection_reason', 'Required documents are incomplete'),
                ]
            ),

            $this->template(
                name: 'Vendor New Booking',
                templateName: 'vendor_new_booking_v1',
                body: <<<'BODY'
Hello {{1}},

A new Dura Cabs booking has been assigned to you.

Booking ID: {{2}}
Customer: {{3}}
Customer Mobile: {{4}}
Vehicle: {{5}}
Route: {{6}}
Travel Date: {{7}}
Travel Time: {{8}}

Please review and respond to the booking from the vendor panel.
BODY,
                variables: [
                    $this->variable(1, 'vendor_name', 'Dura Vendor'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'customer_name', 'Sanjay Singh'),
                    $this->variable(4, 'customer_mobile', '7088873331'),
                    $this->variable(5, 'vehicle_name', 'Swift Dzire'),
                    $this->variable(6, 'route', 'Agra to Delhi'),
                    $this->variable(7, 'travel_date', '05 August 2026'),
                    $this->variable(8, 'travel_time', '10:00 AM'),
                ]
            ),

            $this->template(
                name: 'Vendor Booking Cancelled',
                templateName: 'vendor_booking_cancelled_v1',
                body: <<<'BODY'
Hello {{1}},

A booking assigned to you has been cancelled.

Booking ID: {{2}}
Customer: {{3}}
Route: {{4}}
Reason: {{5}}

Please check the vendor panel for complete details.
BODY,
                variables: [
                    $this->variable(1, 'vendor_name', 'Dura Vendor'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'customer_name', 'Sanjay Singh'),
                    $this->variable(4, 'route', 'Agra to Delhi'),
                    $this->variable(5, 'cancellation_reason', 'Cancelled by customer'),
                ]
            ),

            $this->template(
                name: 'Vendor Payment Released',
                templateName: 'vendor_payment_released_v1',
                body: <<<'BODY'
Hello {{1}},

A payment has been released for your completed Dura Cabs booking.

Booking ID: {{2}}
Released Amount: INR {{3}}
Payment Reference: {{4}}
Payment Status: {{5}}

Please check your account or vendor panel for further details.
BODY,
                variables: [
                    $this->variable(1, 'vendor_name', 'Dura Vendor'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'released_amount', '3500.00'),
                    $this->variable(4, 'payment_reference', 'PAY1001'),
                    $this->variable(5, 'payment_status', 'Released'),
                ]
            ),

            $this->template(
                name: 'Vendor Attach Taxi Status',
                templateName: 'vendor_attach_taxi_status_v1',
                body: <<<'BODY'
Hello {{1}},

Your Attach Taxi request has been updated.

Request ID: {{2}}
Vehicle: {{3}}
Status: {{4}}
Remarks: {{5}}

Please check the vendor panel for complete details.
BODY,
                variables: [
                    $this->variable(1, 'vendor_name', 'Dura Vendor'),
                    $this->variable(2, 'request_id', 'AT1001'),
                    $this->variable(3, 'vehicle_name', 'Swift Dzire'),
                    $this->variable(4, 'request_status', 'Approved'),
                    $this->variable(5, 'remarks', 'Your vehicle has been approved'),
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Driver Templates
            |--------------------------------------------------------------------------
            */

            $this->template(
                name: 'Driver New Trip Assigned',
                templateName: 'driver_new_trip_v1',
                body: <<<'BODY'
Hello {{1}},

A new Dura Cabs trip has been assigned to you.

Booking ID: {{2}}
Customer: {{3}}
Customer Mobile: {{4}}
Route: {{5}}
Travel Date: {{6}}
Travel Time: {{7}}
Vehicle: {{8}}

Please review the complete trip details before departure.
BODY,
                variables: [
                    $this->variable(1, 'driver_name', 'Raj Kumar'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'customer_name', 'Sanjay Singh'),
                    $this->variable(4, 'customer_mobile', '7088873331'),
                    $this->variable(5, 'route', 'Agra to Delhi'),
                    $this->variable(6, 'travel_date', '05 August 2026'),
                    $this->variable(7, 'travel_time', '10:00 AM'),
                    $this->variable(8, 'vehicle_name', 'Swift Dzire'),
                ]
            ),

            $this->template(
                name: 'Driver Trip Cancelled',
                templateName: 'driver_trip_cancelled_v1',
                body: <<<'BODY'
Hello {{1}},

The following Dura Cabs trip has been cancelled.

Booking ID: {{2}}
Customer: {{3}}
Route: {{4}}
Reason: {{5}}

No further action is required unless instructed by the operations team.
BODY,
                variables: [
                    $this->variable(1, 'driver_name', 'Raj Kumar'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'customer_name', 'Sanjay Singh'),
                    $this->variable(4, 'route', 'Agra to Delhi'),
                    $this->variable(5, 'cancellation_reason', 'Cancelled by customer'),
                ]
            ),

            $this->template(
                name: 'Driver Trip Reminder',
                templateName: 'driver_trip_reminder_v1',
                body: <<<'BODY'
Hello {{1}},

This is a reminder for your upcoming Dura Cabs trip.

Booking ID: {{2}}
Customer: {{3}}
Pickup Location: {{4}}
Travel Date: {{5}}
Travel Time: {{6}}

Please arrive at the pickup location on time.
BODY,
                variables: [
                    $this->variable(1, 'driver_name', 'Raj Kumar'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'customer_name', 'Sanjay Singh'),
                    $this->variable(4, 'pickup_location', 'Agra Railway Station'),
                    $this->variable(5, 'travel_date', '05 August 2026'),
                    $this->variable(6, 'travel_time', '10:00 AM'),
                ]
            ),

            $this->template(
                name: 'Driver Trip Completed',
                templateName: 'driver_trip_completed_v1',
                body: <<<'BODY'
Hello {{1}},

The Dura Cabs trip has been marked as completed.

Booking ID: {{2}}
Route: {{3}}
Completion Time: {{4}}

Thank you for completing the trip professionally.
BODY,
                variables: [
                    $this->variable(1, 'driver_name', 'Raj Kumar'),
                    $this->variable(2, 'booking_id', 'DC1001'),
                    $this->variable(3, 'route', 'Agra to Delhi'),
                    $this->variable(4, 'completion_time', '05 August 2026, 03:30 PM'),
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Internal Admin and Staff Templates
            |
            | These templates are intentionally shared by administrators and staff
            | to avoid maintaining duplicate Meta templates for the same event.
            |--------------------------------------------------------------------------
            */

            $this->template(
                name: 'Internal New Inquiry',
                templateName: 'admin_new_inquiry_v1',
                body: <<<'BODY'
A new customer inquiry has been received.

Inquiry ID: {{1}}
Customer: {{2}}
Mobile: {{3}}
Service: {{4}}
Route: {{5}}
Travel Date: {{6}}

Please review the inquiry and complete the required follow-up.
BODY,
                footer: 'Dura Cabs Operations',
                variables: [
                    $this->variable(1, 'inquiry_id', 'INQ1001'),
                    $this->variable(2, 'customer_name', 'Sanjay Singh'),
                    $this->variable(3, 'customer_mobile', '7088873331'),
                    $this->variable(4, 'service_type', 'One Way Cab'),
                    $this->variable(5, 'route', 'Agra to Delhi'),
                    $this->variable(6, 'travel_date', '05 August 2026'),
                ]
            ),

            $this->template(
                name: 'Internal New Customer',
                templateName: 'admin_new_customer_v1',
                body: <<<'BODY'
A new customer account has been created.

Customer ID: {{1}}
Name: {{2}}
Mobile: {{3}}
Email: {{4}}
Registration Time: {{5}}

Please review the customer profile if any follow-up is required.
BODY,
                footer: 'Dura Cabs Operations',
                variables: [
                    $this->variable(1, 'customer_id', '1001'),
                    $this->variable(2, 'customer_name', 'Sanjay Singh'),
                    $this->variable(3, 'customer_mobile', '7088873331'),
                    $this->variable(4, 'customer_email', 'customer@example.com'),
                    $this->variable(5, 'registration_time', '02 August 2026, 07:15 PM'),
                ]
            ),

            $this->template(
                name: 'Internal New Vendor Registration',
                templateName: 'admin_new_vendor_registration_v1',
                body: <<<'BODY'
A new vendor registration has been received.

Vendor ID: {{1}}
Vendor Name: {{2}}
Mobile: {{3}}
City: {{4}}
Business Name: {{5}}

Please review the submitted information and documents.
BODY,
                footer: 'Dura Cabs Operations',
                variables: [
                    $this->variable(1, 'vendor_id', 'V1001'),
                    $this->variable(2, 'vendor_name', 'Dura Vendor'),
                    $this->variable(3, 'vendor_mobile', '9876543210'),
                    $this->variable(4, 'city', 'Agra'),
                    $this->variable(5, 'business_name', 'Dura Travel Services'),
                ]
            ),

            $this->template(
                name: 'Internal New Booking',
                templateName: 'admin_new_booking_v1',
                body: <<<'BODY'
A new Dura Cabs booking has been received.

Booking ID: {{1}}
Customer: {{2}}
Mobile: {{3}}
Service: {{4}}
Route: {{5}}
Travel Date: {{6}}
Total Amount: INR {{7}}

Please review and process the booking.
BODY,
                footer: 'Dura Cabs Operations',
                variables: [
                    $this->variable(1, 'booking_id', 'DC1001'),
                    $this->variable(2, 'customer_name', 'Sanjay Singh'),
                    $this->variable(3, 'customer_mobile', '7088873331'),
                    $this->variable(4, 'service_type', 'One Way Cab'),
                    $this->variable(5, 'route', 'Agra to Delhi'),
                    $this->variable(6, 'travel_date', '05 August 2026'),
                    $this->variable(7, 'total_amount', '4500.00'),
                ]
            ),

            $this->template(
                name: 'Internal Attach Taxi Request',
                templateName: 'admin_attach_taxi_request_v1',
                body: <<<'BODY'
A new Attach Taxi request has been submitted.

Request ID: {{1}}
Vendor: {{2}}
Mobile: {{3}}
Vehicle: {{4}}
Vehicle Number: {{5}}
City: {{6}}

Please verify the vehicle and submitted documents.
BODY,
                footer: 'Dura Cabs Operations',
                variables: [
                    $this->variable(1, 'request_id', 'AT1001'),
                    $this->variable(2, 'vendor_name', 'Dura Vendor'),
                    $this->variable(3, 'vendor_mobile', '9876543210'),
                    $this->variable(4, 'vehicle_name', 'Swift Dzire'),
                    $this->variable(5, 'vehicle_number', 'UP80AB1234'),
                    $this->variable(6, 'city', 'Agra'),
                ]
            ),
        ];

        foreach ($templates as $template) {
            $record = WhatsAppTemplate::query()
                ->where('template_name', $template['template_name'])
                ->first();

            if (! $record) {
                WhatsAppTemplate::query()->create(array_merge($template, [
                    'buttons' => $template['buttons'] ?? [],
                    'status' => WhatsAppTemplate::STATUS_ACTIVE,
                    'meta_status' => WhatsAppTemplate::META_STATUS_NOT_SUBMITTED,
                    'meta_rejection_reason' => null,
                    'meta_template_id' => null,
                    'is_active' => true,
                    'submitted_at' => null,
                    'approved_at' => null,
                    'rejected_at' => null,
                    'last_synced_at' => null,
                ]));

                continue;
            }

            /*
             * Never overwrite the body/components of a template that is already
             * pending or approved on Meta. Local content must remain identical
             * to the version submitted to Meta.
             */
            if (in_array($record->meta_status, [
                WhatsAppTemplate::META_STATUS_PENDING,
                WhatsAppTemplate::META_STATUS_APPROVED,
            ], true)) {
                $record->forceFill([
                    'name' => $template['name'],
                    'status' => WhatsAppTemplate::STATUS_ACTIVE,
                    'is_active' => true,
                ])->save();

                continue;
            }

            $record->forceFill(array_merge($template, [
                'buttons' => $template['buttons'] ?? [],
                'status' => WhatsAppTemplate::STATUS_ACTIVE,
                'meta_status' => WhatsAppTemplate::META_STATUS_NOT_SUBMITTED,
                'meta_rejection_reason' => null,
                'meta_template_id' => null,
                'is_active' => true,
                'submitted_at' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'last_synced_at' => null,
            ]))->save();
        }

        /*
         * These staff templates duplicate the internal admin templates.
         * The same internal template should be sent to both admin and staff.
         */
        $duplicateNames = [
            'staff_new_inquiry_v1',
            'staff_new_customer_v1',
            'staff_new_vendor_registration_v1',
        ];

        WhatsAppTemplate::query()
            ->whereIn('template_name', $duplicateNames)
            ->whereNotIn('meta_status', [
                WhatsAppTemplate::META_STATUS_PENDING,
                WhatsAppTemplate::META_STATUS_APPROVED,
            ])
            ->delete();

        WhatsAppTemplate::query()
            ->whereIn('template_name', $duplicateNames)
            ->whereIn('meta_status', [
                WhatsAppTemplate::META_STATUS_PENDING,
                WhatsAppTemplate::META_STATUS_APPROVED,
            ])
            ->update([
                'is_active' => false,
                'status' => WhatsAppTemplate::STATUS_ARCHIVED,
            ]);
    }

    private function template(
        string $name,
        string $templateName,
        string $body,
        array $variables,
        string $category = WhatsAppTemplate::CATEGORY_UTILITY,
        string $language = 'en',
        string $footer = 'Dura Cabs',
        string $headerType = 'none',
        ?string $headerText = null,
        ?string $headerMedia = null,
        array $buttons = []
    ): array {
        return [
            'name' => $name,
            'template_name' => $templateName,
            'category' => $category,
            'language' => $language,
            'header_type' => $headerType,
            'header_text' => $headerText,
            'header_media' => $headerMedia,
            'body' => trim($body),
            'footer' => $footer,
            'variables' => $variables,
            'buttons' => $buttons,
        ];
    }

    private function variable(
        int $position,
        string $key,
        string $sample
    ): array {
        return [
            'position' => $position,
            'key' => $key,
            'sample' => $sample,
        ];
    }
}