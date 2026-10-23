<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\AuthController;
use App\Services\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class CustomerLoginOptionsTest extends TestCase
{
    private function googlePayload(array $changes = []): array
    {
        return array_replace([
            'iss' => 'https://accounts.google.com',
            'aud' => 'test-web-client',
            'sub' => 'verified-google-subject',
            'email' => 'customer@example.com',
            'email_verified' => 'true',
            'exp' => time() + 3600,
        ], $changes);
    }

    private function verifyGoogle(array $payload): array
    {
        config(['customer_login.google_client_ids' => ['test-web-client']]);
        Http::preventStrayRequests();
        Http::fake(['oauth2.googleapis.com/tokeninfo*' => Http::response($payload)]);
        $method = new ReflectionMethod(GoogleAuthService::class, 'verifyGoogleIdToken');
        $method->setAccessible(true);
        return $method->invoke(new GoogleAuthService(), 'test-token');
    }

    public function test_google_requires_server_verified_credentials(): void
    {
        Http::fake();
        $result = (new GoogleAuthService())->loginWithGoogle([
            'google_id' => 'untrusted-id', 'email' => 'customer@example.com',
        ]);
        $this->assertFalse($result['status']);
        Http::assertNothingSent();
    }

    public function test_google_rejects_wrong_app_expiry_issuer_and_unverified_email(): void
    {
        foreach ([
            ['aud' => 'another-app'], ['exp' => time() - 60],
            ['iss' => 'https://untrusted.example'], ['email_verified' => 'false'],
            ['sub' => null], ['email' => null],
        ] as $changes) {
            $result = $this->verifyGoogle($this->googlePayload($changes));
            $this->assertFalse($result['status'], json_encode($changes));
        }
    }

    public function test_google_accepts_verified_tokens_with_both_email_flag_types(): void
    {
        foreach (['true', true] as $verified) {
            $result = $this->verifyGoogle($this->googlePayload(['email_verified' => $verified]));
            $this->assertTrue($result['status']);
            $this->assertSame('verified-google-subject', $result['data']['google_id']);
        }
    }

    public function test_removed_login_options_cannot_issue_tokens(): void
    {
        $controller = new AuthController();
        foreach (['emailLogin', 'facebookLogin', 'appleLogin', 'whatsappLogin'] as $method) {
            $response = $controller->{$method}(Request::create('/login', 'POST', [
                'email' => 'someone@example.com', 'facebook_id' => 'untrusted', 'apple_id' => 'untrusted',
            ]));
            $this->assertSame(410, $response->getStatusCode());
            $this->assertFalse($response->getData(true)['status']);
            $this->assertArrayNotHasKey('data', $response->getData(true));
        }
    }
}
