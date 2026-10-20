<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PartnerReviewLoginService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class PartnerReviewLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'partner_play_review' => [
            'enabled' => true, 'user_id' => 42, 'code_hash' => Hash::make('2846'),
        ]]);
        Cache::flush();
    }

    private function user(string $role = User::ROLE_TRANSPORTER): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => 42, 'mobile' => PartnerReviewLoginService::MOBILE,
            'email' => PartnerReviewLoginService::EMAIL, 'is_active' => true]);
        $user->shouldReceive('canUseTransporterLogin')->andReturn(true);
        $roles = Mockery::mock(BelongsToMany::class);
        $roles->shouldReceive('get')->andReturn(collect([(object) ['name' => $role, 'guard_name' => 'web']]));
        $user->shouldReceive('roles')->andReturn($roles);
        $permissions = Mockery::mock(BelongsToMany::class);
        $permissions->shouldReceive('exists')->andReturn(false);
        $user->shouldReceive('permissions')->andReturn($permissions);
        return $user;
    }

    public function test_reserved_identity_and_enabled_config_are_required(): void
    {
        $service = new PartnerReviewLoginService();
        $user = $this->user();
        $this->assertTrue($service->authorized($user));
        $user->mobile = '9876543210';
        $this->assertFalse($service->authorized($user));
        $user->mobile = PartnerReviewLoginService::MOBILE;
        config(['partner_play_review.enabled' => false]);
        $this->assertFalse($service->send($user)['status']);
    }

    public function test_admin_role_cannot_use_review_login(): void
    {
        $this->assertFalse((new PartnerReviewLoginService())->authorized($this->user(User::ROLE_ADMIN)));
    }

    public function test_wrong_code_does_not_consume_challenge_and_success_cannot_replay(): void
    {
        $service = new PartnerReviewLoginService();
        $user = $this->user();
        $id = $service->send($user)['verification_id'];
        $this->assertFalse($service->verify($user, '9999', $id)['status']);
        $this->assertTrue($service->verify($user, '2846', $id)['status']);
        $this->assertFalse($service->verify($user, '2846', $id)['status']);
    }

    public function test_expired_challenge_requires_a_new_request(): void
    {
        $service = new PartnerReviewLoginService();
        $user = $this->user();
        $id = $service->send($user)['verification_id'];
        $this->travel(6)->minutes();
        $this->assertFalse($service->verify($user, '2846', $id)['status']);
        $this->travelBack();
    }

    public function test_challenge_cannot_be_used_by_another_account(): void
    {
        $service = new PartnerReviewLoginService();
        $user = $this->user();
        $id = $service->send($user)['verification_id'];
        $user->id = 43;
        config(['partner_play_review.user_id' => 43]);
        $this->assertFalse($service->verify($user, '2846', $id)['status']);
    }
}
