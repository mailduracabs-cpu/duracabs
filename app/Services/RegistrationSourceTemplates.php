<?php

namespace App\Services;

final class RegistrationSourceTemplates
{
    public static function definitions(): array
    {
        $types = [
            'customer.registered' => ['Customer', 'admin_new_customer_source', [
                ['customer_id', '1001'], ['customer_name', 'Sanjay Singh'],
                ['customer_mobile', '9876543210'], ['customer_email', 'customer@example.com'],
                ['registration_time', '26 October 2026, 06:30 PM'], ['registration_source', 'App'],
            ], "New Customer Registration\n\nCustomer ID: {{1}}\nName: {{2}}\nMobile: {{3}}\nEmail: {{4}}\nRegistration Time: {{5}}\nRegistration Source: {{6}}\n\nPlease review the customer profile if required."],
            'vendor.registered' => ['Vendor', 'admin_new_vendor_source', [
                ['vendor_id', 'V1001'], ['vendor_name', 'Dura Vendor'],
                ['vendor_mobile', '9876543210'], ['city', 'Agra'],
                ['business_name', 'Dura Travel Services'], ['registration_source', 'Website'],
            ], "New Vendor Registration\n\nVendor ID: {{1}}\nName: {{2}}\nMobile: {{3}}\nCity: {{4}}\nBusiness Name: {{5}}\nRegistration Source: {{6}}\n\nPlease review the submitted profile and documents."],
            'driver.registered' => ['Driver', 'admin_new_driver_source', [
                ['driver_id', '2001'], ['driver_name', 'Raj Kumar'],
                ['driver_mobile', '9876543210'], ['driver_email', 'driver@example.com'],
                ['registration_time', '26 October 2026, 06:30 PM'], ['registration_source', 'App'],
            ], "New Driver Registration\n\nDriver ID: {{1}}\nName: {{2}}\nMobile: {{3}}\nEmail: {{4}}\nRegistration Time: {{5}}\nRegistration Source: {{6}}\n\nPlease review the driver profile and documents."],
        ];
        $definitions = [];
        foreach ($types as $event => [$type, $key, $variables, $body]) {
            $definitions[$event] = [
                'name' => 'Internal New ' . $type . ' — Registration Source',
                'template_name' => $key . '_v1', 'template_key' => $key,
                'event_key' => $event, 'category' => \App\Models\WhatsAppTemplate::CATEGORY_UTILITY, 'language' => 'en',
                'header_type' => 'none', 'header_text' => null, 'header_media' => null,
                'body' => $body, 'footer' => 'Dura Cabs Operations', 'buttons' => [],
                'variables' => array_map(fn ($variable, $position) => [
                    'position' => $position + 1, 'key' => $variable[0], 'sample' => $variable[1],
                ], $variables, array_keys($variables)),
            ];
        }
        return $definitions;
    }
}
