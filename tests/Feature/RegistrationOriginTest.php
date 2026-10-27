<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FleetManagement\TransporterProfile;
use App\Services\AdminLeadNotificationService;
use App\Services\OtpService;
use App\Services\RegistrationOrigin;
use App\Services\RegistrationSourceTemplates;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationOriginTest extends TestCase
{
    private string $originalConnection;
    private string $testConnection = 'registration_origin_test';
    private AdminLeadNotificationService $spy;
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.' . $this->testConnection, [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection($this->testConnection);
        Schema::clearResolvedInstance('db.schema');
        config()->set('cache.default', 'array');
        Cache::flush();
        Http::preventStrayRequests();
        $this->spy = new class extends AdminLeadNotificationService {
    public array $events = [];
    public bool $throwOnSend = false;
    public function sendNewCustomerRegistration(User $user): void
    {
        $this->events[] = ['kind' => 'customer', 'source' => $user->registration_source];
        if ($this->throwOnSend) throw new \RuntimeException('Simulated notification failure');
    }
    public function sendNewDriverRegistration(User $user): void
    {
        $this->events[] = ['kind' => 'driver', 'source' => $user->registration_source];
    }
    public function sendNewVendorRegistration(TransporterProfile $profile): void
    {
        $this->events[] = ['kind' => 'vendor', 'source' => $profile->registration_source];
    }

        };
        app()->instance(AdminLeadNotificationService::class, $this->spy);
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name')->nullable(); $table->string('email')->nullable();
            $table->string('mobile')->nullable(); $table->string('password')->nullable();
            $table->string('login_type')->nullable(); $table->boolean('is_active')->default(true);
            $table->string('otp')->nullable(); $table->timestamp('otp_expire_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable(); $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken(); $table->timestamps();
        });
        Schema::create('fleet_transporter_profiles', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id(); $table->morphs('tokenable'); $table->string('name');
            $table->string('token', 64)->unique(); $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        $migration = require base_path('database/migrations/2026_10_26_140000_add_registration_origin.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        while (DB::connection($this->testConnection)->transactionLevel() > 0) DB::connection($this->testConnection)->rollBack();
        DB::purge($this->testConnection);
        DB::setDefaultConnection($this->originalConnection);
        Schema::clearResolvedInstance('db.schema');
        parent::tearDown();
    }

    private function pending(?string $source = RegistrationOrigin::WEBSITE): User
    {
        $this->sequence++;
        return User::withoutEvents(function () use ($source): User {
            $user = new User();
            $user->forceFill(['name' => 'Test User', 'email' => 'test' . $this->sequence . '@example.test',
                'mobile' => '98765000' . str_pad((string) $this->sequence, 2, '0', STR_PAD_LEFT),
                'is_active' => true, 'login_type' => 'otp', 'registration_source' => $source]);
            $user->save();
            return $user;
        });
    }

    public function test_website_entry_point_is_website(): void
    {
        $this->assertSame('website', RegistrationOrigin::detect(Request::create('/register', 'POST')));
    }

    public function test_livewire_website_entry_point_is_website(): void
    {
        $this->assertSame('website', RegistrationOrigin::detect(Request::create('/livewire/update', 'POST')));
    }

    public function test_api_entry_point_is_app(): void
    {
        $this->assertSame('app', RegistrationOrigin::detect(Request::create('/api/v1/verify-otp', 'POST')));
    }

    public function test_client_cannot_claim_a_different_source(): void
    {
        $request = Request::create('/api/v1/google-login', 'POST', ['registration_source' => 'website', 'login_type' => 'website']);
        $request->headers->set('Referer', 'https://www.duracabs.com/register');
        $this->assertSame('app', RegistrationOrigin::detect($request));
    }

    public function test_pending_otp_account_does_not_notify(): void
    {
        $user = User::create(['name' => 'Pending OTP', 'mobile' => '9876500099',
            'email' => 'pending@example.test', 'login_type' => 'otp', 'is_active' => true]);
        $this->assertNull($user->registration_completed_at);
        $this->assertSame([], $this->spy->events);
    }

    public function test_successful_otp_verification_completes_registration_once(): void
    {
        $user = $this->pending('app');
        Cache::put('otp_' . $user->mobile, '1234', now()->addMinutes(5));
        Auth::shouldReceive('login')->once()->withArgs(fn ($record, $remember) => $record->id === $user->id && $remember === true);
        $result = app(OtpService::class)->verifyLoginOtp($user->mobile, '1234');
        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['token']);
        $this->assertSame(['customer'], array_column($this->spy->events, 'kind'));
        $this->assertNotNull($user->fresh()->registration_completed_at);
        $this->assertSame('app', $user->fresh()->registration_source);
    }

    public function test_invalid_otp_does_not_complete_or_notify(): void
    {
        $user = $this->pending();
        Cache::put('otp_' . $user->mobile, '1234', now()->addMinutes(5));
        Auth::shouldReceive('login')->never();
        $result = app(OtpService::class)->verifyLoginOtp($user->mobile, '9999');
        $this->assertFalse($result['status']);
        $this->assertNull($user->fresh()->registration_completed_at);
        $this->assertSame([], $this->spy->events);
    }

    public function test_later_login_does_not_change_source_or_repeat_registration_notice(): void
    {
        $user = $this->pending('website');
        RegistrationOrigin::completeUser($user, 'customer', 'website');
        RegistrationOrigin::completeUser($user->fresh(), 'customer', 'app');
        $this->assertSame('website', $user->fresh()->registration_source);
        $this->assertCount(1, $this->spy->events);
    }

    public function test_completed_source_cannot_be_edited_through_model_save(): void
    {
        $user = $this->pending('website');
        RegistrationOrigin::completeUser($user, 'customer', 'website');
        $user->refresh()->forceFill(['registration_source' => 'app'])->save();
        $this->assertSame('website', $user->fresh()->registration_source);
    }

    public function test_historical_unknown_user_is_not_backfilled_by_login(): void
    {
        $user = $this->pending(null);
        RegistrationOrigin::completeUser($user, 'customer', 'app');
        $this->assertNull($user->fresh()->registration_source);
        $this->assertSame('Unknown', $user->fresh()->registration_source_label);
        $this->assertSame([], $this->spy->events);
    }

    public function test_driver_registration_uses_driver_notice_only(): void
    {
        $driver = $this->pending('app');
        RegistrationOrigin::completeUser($driver, 'driver', 'app');
        RegistrationOrigin::completeUser($driver, 'driver', 'app');
        $this->assertSame('driver', $driver->fresh()->registration_account_type);
        $this->assertSame(['driver'], array_column($this->spy->events, 'kind'));
    }

    public function test_vendor_profile_source_is_independent_of_original_customer_source(): void
    {
        $user = $this->pending('website');
        RegistrationOrigin::completeUser($user, 'customer', 'website');
        $profile = TransporterProfile::withoutEvents(function () use ($user): TransporterProfile {
            $profile = new TransporterProfile();
            $profile->forceFill(['user_id' => $user->id, 'registration_source' => 'app']);
            // Avoid unrelated default columns in this isolated fixture.
            $profile->setRawAttributes(['user_id' => $user->id, 'registration_source' => 'app']);
            $profile->save();
            return $profile;
        });
        RegistrationOrigin::completeVendor($profile);
        RegistrationOrigin::completeVendor($profile);
        $this->assertSame('website', $user->fresh()->registration_source);
        $this->assertSame(['customer', 'vendor'], array_column($this->spy->events, 'kind'));
        $this->assertSame('app', $this->spy->events[1]['source']);
    }

    public function test_rolled_back_completion_sends_nothing(): void
    {
        $user = $this->pending();
        DB::beginTransaction();
        RegistrationOrigin::completeUser($user, 'customer', 'website');
        $this->assertSame([], $this->spy->events);
        DB::rollBack();
        $this->assertNull($user->fresh()->registration_completed_at);
        $this->assertSame([], $this->spy->events);
    }

    public function test_source_is_not_mass_assignable_from_request_fields(): void
    {
        $user = new User();
        $this->assertFalse($user->isFillable('registration_source'));
        $this->assertFalse($user->isFillable('registration_admin_attempted_at'));
    }

    public function test_notification_failure_does_not_break_or_repeat_registration(): void
    {
        $user = $this->pending();
        $this->spy->throwOnSend = true;
        RegistrationOrigin::completeUser($user, 'customer', 'website');
        RegistrationOrigin::completeUser($user, 'customer', 'website');
        $this->assertNotNull($user->fresh()->registration_completed_at);
        $this->assertNotNull($user->fresh()->registration_admin_attempted_at);
        $this->assertCount(1, $this->spy->events);
    }

    public function test_new_template_variables_include_source_without_changing_legacy_keys(): void
    {
        $definitions = RegistrationSourceTemplates::definitions();
        $this->assertCount(3, $definitions);
        foreach ($definitions as $definition) {
            $this->assertContains('registration_source', array_column($definition['variables'], 'key'));
            $this->assertStringContainsString('Registration Source: {{6}}', $definition['body']);
            $this->assertStringContainsString('_source_v1', $definition['template_name']);
        }
    }
}
