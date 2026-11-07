<?php
declare(strict_types=1);
namespace App\Services\Identity;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

final class SandboxIdentityClient
{
    public function environment(): string
    {
        $env = (string) config('sandbox_identity.environment', 'test');
        if (!in_array($env, ['test','live'], true)) throw new IdentityProviderException('Invalid verification environment.');
        return $env;
    }

    public function assertConfigured(): void
    {
        $env = $this->environment();
        $key = (string) config('sandbox_identity.api_key');
        $secret = (string) config('sandbox_identity.api_secret');
        if (!config('sandbox_identity.enabled')) {
            throw new IdentityProviderException('Aadhaar verification is disabled. Set SANDBOX_KYC_ENABLED=true in Laravel .env, then run php artisan optimize:clear.');
        }
        if ($key === '' || $secret === '') {
            throw new IdentityProviderException('SANDBOX_KYC_API_KEY or SANDBOX_KYC_API_SECRET is missing from Laravel .env. Set both locally, then run php artisan optimize:clear.');
        }
        if (!str_starts_with($key, 'key_'.$env.'_') || !str_starts_with($secret, 'secret_'.$env.'_')) {
            throw new IdentityProviderException('API credentials do not match SANDBOX_KYC_ENVIRONMENT='.$env.'. Use the matching '.$env.' key and secret.');
        }
        $redirect = (string) config('sandbox_identity.redirect_url');
        if (!filter_var($redirect, FILTER_VALIDATE_URL) || parse_url($redirect, PHP_URL_SCHEME) !== 'https' || parse_url($redirect, PHP_URL_USER) !== null) {
            throw new IdentityProviderException('A public HTTPS verification return URL must be configured.');
        }
    }

    private function base(): string
    {
        return $this->environment() === 'test' ? 'https://test-api.sandbox.co.in' : 'https://api.sandbox.co.in';
    }

    private function tokenKey(): string
    {
        return 'sandbox-identity-token:'.hash('sha256', $this->environment().'|'.config('sandbox_identity.api_key').'|'.config('sandbox_identity.api_secret'));
    }

    private function token(): string
    {
        $this->assertConfigured();
        $encrypted = Cache::get($this->tokenKey());
        if (is_string($encrypted)) return Crypt::decryptString($encrypted);
        try {
            $response = Http::acceptJson()->asJson()->connectTimeout(5)->timeout(20)
                ->withOptions(['allow_redirects' => false])->withHeaders([
                    'x-api-key' => config('sandbox_identity.api_key'),
                    'x-api-secret' => config('sandbox_identity.api_secret'),
                    'x-api-version' => config('sandbox_identity.api_version'),
                ])->post($this->base().'/authenticate');
        } catch (ConnectionException) {
            throw new IdentityProviderException('Verification service is temporarily unreachable. Please retry.');
        }
        $token = $response->json('data.access_token');
        if (!$response->successful() || !is_string($token) || $token === '') {
            throw new IdentityProviderException('Verification service access is unavailable. Check your API credentials and service activation.');
        }
        Cache::put($this->tokenKey(), Crypt::encryptString($token), now()->addHours(23));
        return $token;
    }

    public function call(string $method, string $path, array $body = []): array
    {
        // Paths originate only from these server methods, never from clients.
        try {
            $request = Http::acceptJson()->asJson()->connectTimeout(5)->timeout(25)
                ->withOptions(['allow_redirects' => false])->withHeaders([
                    'x-api-key' => config('sandbox_identity.api_key'),
                    'x-api-version' => config('sandbox_identity.api_version'),
                    'Authorization' => $this->token(), // No Bearer prefix.
                ]);
            $response = $method === 'POST' ? $request->post($this->base().$path, $body) : $request->get($this->base().$path);
        } catch (ConnectionException) {
            throw new IdentityProviderException('Verification service is temporarily unreachable. Please retry.');
        }
        if ($response->status() === 401) Cache::forget($this->tokenKey());
        if (!$response->successful() || (int) $response->json('code') !== 200 || !is_array($response->json('data'))) {
            $status = $response->status();
            $message = match ($status) {
                401, 403 => 'Verification service access is unavailable. Ask admin to check credentials and DigiLocker activation.',
                402, 422 => 'Verification could not start. Ask admin to check the API plan and wallet.',
                429 => 'Verification service is busy. Wait a moment and retry.',
                default => 'Verification could not complete. Please retry or contact support.',
            };
            // Never expose the provider response or signed URLs in an exception.
            throw new IdentityProviderException($message, $status === 429 ? 429 : 503);
        }
        return $response->json('data');
    }

    public function checkAccess(): void
    {
        $this->token();
    }

    public function initiate(): array
    {
        return $this->call('POST', '/kyc/digilocker/sessions/init', [
            '@entity' => 'in.co.sandbox.kyc.digilocker.session.request',
            'flow' => 'signin',
            'redirect_url' => config('sandbox_identity.redirect_url'),
            'doc_types' => ['aadhaar'],
            'purpose' => config('sandbox_identity.purpose'),
            'requestor_name' => config('sandbox_identity.requestor_name'),
            'options' => ['pinless' => true, 'force_login' => true],
            'consent_expiry' => now()->addHours(2)->getTimestampMs(),
        ]);
    }

    public function status(string $id): array
    {
        return $this->call('GET', '/kyc/digilocker/sessions/'.rawurlencode($id).'/status');
    }

    public function identity(string $id, AadhaarIdentityParser $parser): array
    {
        $data = $this->call('GET', '/kyc/digilocker/sessions/'.rawurlencode($id).'/documents/aadhaar');
        foreach ($data['files'] ?? [] as $file) {
            if (($file['metadata']['issuer_id'] ?? '') !== 'in.gov.uidai' || !in_array(strtolower($file['metadata']['ContentType'] ?? ''), ['application/xml','text/xml'], true)) continue;
            $url = (string) ($file['url'] ?? '');
            $parts = parse_url($url);
            $hosts = array_map('strtolower', (array) config('sandbox_identity.document_hosts', []));
            if (!$parts || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443) || !in_array(strtolower($parts['host'] ?? ''), $hosts, true)) {
                throw new IdentityProviderException('The provider document host is not configured. Please contact support.');
            }
            try {
                $response = Http::connectTimeout(5)->timeout(25)->withOptions(['allow_redirects' => false, 'stream' => true])->get($url);
                if (!$response->successful()) throw new IdentityProviderException('The Aadhaar document could not be retrieved. Please retry.');
                $stream = $response->toPsrResponse()->getBody();
                $xml = '';
                try {
                    while (!$stream->eof() && strlen($xml) <= 2 * 1024 * 1024) $xml .= $stream->read(8192);
                } finally { $stream->close(); }
                return $parser->parse($xml);
            } catch (ConnectionException) {
                throw new IdentityProviderException('The Aadhaar document could not be retrieved. Please retry.');
            }
        }
        throw new IdentityProviderException('A UIDAI-issued Aadhaar document was not shared. Please give Aadhaar consent and retry.', 422);
    }
}
