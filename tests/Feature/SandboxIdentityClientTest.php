<?php
namespace Tests\Feature;
use App\Services\Identity\AadhaarIdentityParser;
use App\Services\Identity\IdentityProviderException;
use App\Services\Identity\SandboxIdentityClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SandboxIdentityClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default'=>'array','sandbox_identity.enabled'=>true,'sandbox_identity.environment'=>'test',
            'sandbox_identity.api_key'=>'key_test_fixture','sandbox_identity.api_secret'=>'secret_test_fixture',
            'sandbox_identity.api_version'=>'1.0.0','sandbox_identity.redirect_url'=>'https://example.com/return',
            'sandbox_identity.purpose'=>'Partner identity verification','sandbox_identity.requestor_name'=>'Dura Cabs',
            'sandbox_identity.document_hosts'=>['in-co-sandbox-kyc-digilocker-dev.s3.ap-south-1.amazonaws.com']]);
        Cache::flush();
        Http::preventStrayRequests();
    }
    public function test_creates_aadhaar_only_session_and_caches_encrypted_authentication(): void
    {
        Http::fake([
            'https://test-api.sandbox.co.in/authenticate'=>Http::response(['code'=>200,'data'=>['access_token'=>'fixture-token']]),
            'https://test-api.sandbox.co.in/kyc/digilocker/sessions/init'=>Http::response(['code'=>200,'data'=>['session_id'=>'fixture','authorization_url'=>'https://digilocker.meripehchaan.gov.in/verify']]),
        ]);
        $client=new SandboxIdentityClient();$client->initiate();$client->initiate();
        Http::assertSentCount(3);
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/sessions/init') && $r['doc_types']===['aadhaar'] && $r->hasHeader('Authorization','fixture-token') && !$r->hasHeader('x-api-secret'));
    }
    public function test_refuses_live_credentials_in_test_environment_without_network_call(): void
    {
        Http::fake();config(['sandbox_identity.api_key'=>'key_live_fixture']);
        try {(new SandboxIdentityClient())->initiate();$this->fail('Credential mismatch was allowed.');}
        catch(IdentityProviderException $e){$this->assertSame(503,$e->httpStatus);}
        Http::assertNothingSent();
    }
    public function test_refuses_unapproved_document_download_host(): void
    {
        Http::fake([
            'https://test-api.sandbox.co.in/authenticate'=>Http::response(['code'=>200,'data'=>['access_token'=>'fixture-token']]),
            'https://test-api.sandbox.co.in/kyc/digilocker/sessions/fixture/documents/aadhaar'=>Http::response(['code'=>200,'data'=>['files'=>[
                ['url'=>'https://127.0.0.1/private','metadata'=>['issuer_id'=>'in.gov.uidai','ContentType'=>'application/xml']]
            ]]]),
        ]);
        try {(new SandboxIdentityClient())->identity('fixture',new AadhaarIdentityParser());$this->fail('Unsafe download allowed.');}
        catch(IdentityProviderException $e){$this->assertStringContainsString('host',$e->getMessage());}
        Http::assertSentCount(2);
    }
    public function test_does_not_expose_provider_secrets_in_errors(): void
    {
        Http::fake(['*'=>Http::response(['message'=>'secret_test_PRIVATE_PROVIDER_RESPONSE'],403)]);
        try {(new SandboxIdentityClient())->initiate();$this->fail('Provider failure was accepted.');}
        catch(IdentityProviderException $e){$this->assertStringNotContainsString('PRIVATE_PROVIDER_RESPONSE',$e->getMessage());}
    }
}
