<?php

namespace Tests\Feature;

use App\Services\AppAppearanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppAppearanceServiceTest extends TestCase
{
    private string $originalStorage;
    private string $testStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalStorage = storage_path();
        $this->testStorage = sys_get_temp_dir().'/duracabs-appearance-'.bin2hex(random_bytes(8));
        $this->app->useStoragePath($this->testStorage);
        Storage::fake('public');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-09T10:00:00Z'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        $this->app->useStoragePath($this->originalStorage);
        File::deleteDirectory($this->testStorage);
        parent::tearDown();
    }

    public function test_defaults_are_disabled_and_do_not_need_a_database(): void
    {
        $config = app(AppAppearanceService::class)->publicConfig();
        $this->assertSame(1, $config['schema_version']);
        $this->assertFalse($config['theme']['active']);
        $this->assertFalse($config['offer']['active']);
    }

    public function test_end_only_schedule_is_valid_and_expiry_is_exclusive(): void
    {
        $service = app(AppAppearanceService::class);
        $data = array_replace($service->defaults(), ['theme_enabled' => true,
            'festival' => 'diwali', 'theme_ends_at' => '2026-10-09T11:00:00Z']);
        $service->save($data);
        $this->assertTrue($service->publicConfig()['theme']['active']);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-09T11:00:00Z'));
        $this->assertFalse($service->publicConfig()['theme']['active']);
    }

    public function test_end_before_start_is_rejected(): void
    {
        $service = app(AppAppearanceService::class);
        $this->expectException(ValidationException::class);
        $service->save(array_replace($service->defaults(), [
            'theme_starts_at' => '2026-10-10', 'theme_ends_at' => '2026-10-09',
        ]));
    }

    public function test_enabled_offer_requires_an_image(): void
    {
        $service = app(AppAppearanceService::class);
        $this->expectException(ValidationException::class);
        $service->save(array_replace($service->defaults(), ['offer_enabled' => true]));
    }

    public function test_offer_url_uses_real_public_upload_and_keeps_private_settings_private(): void
    {
        Storage::disk('public')->put('app/appearance/offer.jpg', 'test image bytes');
        $service = app(AppAppearanceService::class);
        $service->save(array_replace($service->defaults(), [
            'offer_enabled' => true, 'offer_image' => 'app/appearance/offer.jpg',
        ]));
        $config = $service->publicConfig();
        $this->assertTrue($config['offer']['active']);
        $this->assertStringContainsString('/storage/app/appearance/offer.jpg', $config['offer']['image_url']);
        $this->assertFileExists(storage_path('app/duracabs/app-appearance.json'));
        $this->assertFalse(Storage::disk('public')->exists('duracabs/app-appearance.json'));
    }
}
