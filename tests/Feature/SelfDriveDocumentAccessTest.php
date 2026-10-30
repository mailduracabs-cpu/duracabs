<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SelfDriveDocumentAccessTest extends TestCase
{
    public function test_profile_kyc_requires_authentication(): void
    {
        $this->getJson('/api/v1/self-drive/customer-profile')->assertUnauthorized();
        $this->postJson('/api/v1/self-drive/customer-profile-update', [])->assertUnauthorized();
    }

    public function test_identity_files_require_authentication(): void
    {
        foreach (['aadhaar_front', 'aadhaar_back', 'driving_licence_front', 'driving_licence_back'] as $type) {
            $this->getJson('/api/v1/self-drive/customer-document/' . $type)->assertUnauthorized();
            $this->getJson('/api/v1/self-drive/booking/123/document/' . $type)->assertUnauthorized();
        }
    }

    public function test_unsigned_rental_documents_are_rejected(): void
    {
        foreach (['invoice', 'agreement'] as $type) {
            $this->get('/api/v1/self-drive/booking/123/rental-document/' . $type)->assertForbidden();
        }
    }

    public function test_expired_rental_document_link_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('self-drive.customer-rental-document', now()->subMinute(), [
            'bookingId' => 123, 'type' => 'invoice',
        ]);
        $this->get($url)->assertForbidden();
    }

    public function test_changing_the_signed_booking_id_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('self-drive.customer-rental-document', now()->addMinute(), [
            'bookingId' => 123, 'type' => 'agreement',
        ]);
        $tampered = str_replace('/booking/123/', '/booking/124/', $url);
        $this->assertNotSame($url, $tampered);
        $this->get($tampered)->assertForbidden();
    }
}
