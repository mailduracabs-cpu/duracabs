<?php
return [
    // Optional logical key of an existing active, approved WhatsApp template.
    // Blank uses the existing WhatsAppService text-message route.
    'driver_assignment_template_key' => env('PARTNER_DRIVER_ASSIGNMENT_TEMPLATE_KEY', ''),
];
