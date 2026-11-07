<?php
declare(strict_types=1);
namespace App\Console\Commands;
use App\Services\Identity\IdentityProviderException;
use App\Services\Identity\SandboxIdentityClient;
use Illuminate\Console\Command;

final class CheckSandboxIdentity extends Command
{
    protected $signature = 'sandbox-kyc:check';
    protected $description = 'Check Sandbox API authentication without sending any Aadhaar or OTP';

    public function handle(SandboxIdentityClient $client): int
    {
        try {
            $client->checkAccess();
            $this->info('Sandbox authentication OK. Environment: '.$client->environment());
            $this->line('No Aadhaar or OTP was submitted. Complete DigiLocker consent in the app to test document access.');
            return self::SUCCESS;
        } catch (IdentityProviderException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
