<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternationalCustomerOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    private function customer(string $mobile, bool $active = true): User
    {
        return User::withoutEvents(fn () => User::query()->create([
            'name' => 'OTP test customer', 'mobile' => $mobile,
            'email' => str_replace('+', '', $mobile) . '-' . uniqid() . '@example.test',
            'password' => Hash::make('test-password-only'), 'is_active' => $active,
        ]));
    }

    private function service(): OtpService
    {
        return new class extends OtpService {
            public array $calls = ['sms' => 0, 'whatsapp' => 0, 'email' => 0];
            public string $lastOtp = '';
            public bool $whatsappSucceeds = true;
            public bool $authenticationTemplateRequested = false;

            protected function sendSmsOtp(string $mobile, string $otp): array
            {
                $this->calls['sms']++;
                return ['status' => true];
            }

            protected function sendWhatsAppOtp(string $mobile, string $otp, bool $authenticationOnly = false): array
            {
                $this->calls['whatsapp']++;
                $this->lastOtp = $otp;
                $this->authenticationTemplateRequested = $authenticationOnly;
                return ['status' => $this->whatsappSucceeds];
            }

            protected function sendEmailOtp(string $email, string $otp): array
            {
                $this->calls['email']++;
                return ['status' => false];
            }
        };
    }

    public function test_international_login_works_via_whatsapp_without_sms_and_code_is_consumed(): void
    {
        $customer = $this->customer('+447911123456');
        $service = $this->service();
        $sent = $service->sendLoginOtp('+447911123456');
        self::assertTrue($sent['status']);
        self::assertSame(['whatsapp'], $sent['delivered_on']);
        self::assertSame(['sms' => 0, 'whatsapp' => 1, 'email' => 0], $service->calls);
        self::assertTrue($service->authenticationTemplateRequested);
        $verified = $service->verifyLoginOtp('+447911123456', $service->lastOtp);
        self::assertTrue($verified['status']);
        self::assertSame($customer->id, $verified['user']->id);
        self::assertNotEmpty($verified['token']);
        self::assertFalse($service->verifyLoginOtp('+447911123456', $service->lastOtp)['status']);
    }

    public function test_indian_local_and_plus91_reuse_one_customer(): void
    {
        $customer = $this->customer('9876543210');
        $service = $this->service();
        self::assertTrue($service->sendLoginOtp('+919876543210')['status']);
        self::assertSame(['sms' => 1, 'whatsapp' => 1, 'email' => 1], $service->calls);
        $verified = $service->verifyLoginOtp('9876543210', $service->lastOtp);
        self::assertTrue($verified['status']);
        self::assertSame($customer->id, $verified['user']->id);
        self::assertSame(1, User::query()->count());
    }

    public function test_failed_whatsapp_does_not_report_success_or_leave_a_valid_code(): void
    {
        $customer = $this->customer('+447911123456');
        $service = $this->service();
        $service->whatsappSucceeds = false;
        self::assertFalse($service->sendLoginOtp('+447911123456')['status']);
        self::assertFalse(Cache::has('otp_+447911123456'));
        self::assertNull($customer->fresh()->otp);
        self::assertFalse($service->verifyLoginOtp('+447911123456', $service->lastOtp)['status']);
    }

    public function test_inactive_accounts_are_not_reactivated_by_requesting_a_code(): void
    {
        $customer = $this->customer('+447911123456', false);
        $service = $this->service();
        self::assertFalse($service->sendLoginOtp('+447911123456')['status']);
        self::assertFalse($customer->fresh()->is_active);
        self::assertSame(['sms' => 0, 'whatsapp' => 0, 'email' => 0], $service->calls);
    }

    public function test_foreign_full_number_with_ten_digits_cannot_claim_an_indian_local_account(): void
    {
        $indian = $this->customer('4512345678');
        $foreign = $this->customer('+4512345678');
        $service = $this->service();
        self::assertTrue($service->sendLoginOtp('+4512345678')['status']);
        $verified = $service->verifyLoginOtp('+4512345678', $service->lastOtp);
        self::assertSame($foreign->id, $verified['user']->id);
        self::assertNotSame($indian->id, $verified['user']->id);
    }

    public function test_database_otp_fallback_respects_expiry(): void
    {
        $customer = $this->customer('+447911123456');
        $customer->forceFill(['otp' => '1234', 'otp_expire_at' => now()->subMinute()])->save();
        self::assertFalse($this->service()->verifyLoginOtp('+447911123456', '1234')['status']);
    }

    public function test_a_successful_new_code_does_not_inherit_an_old_codes_attempt_limit(): void
    {
        $this->customer('+447911123456');
        Cache::put('otp_attempt_+447911123456', 5, now()->addMinutes(5));
        $service = $this->service();
        self::assertTrue($service->sendLoginOtp('+447911123456')['status']);
        self::assertTrue($service->verifyLoginOtp('+447911123456', $service->lastOtp)['status']);
    }
}
