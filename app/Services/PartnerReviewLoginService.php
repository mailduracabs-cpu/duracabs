<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PartnerReviewLoginService
{
    public const MOBILE = '0000000002';
    public const EMAIL = 'partner-play-review@duracabs.local';

    public function authorized(User $user): bool
    {
        if (config('partner_play_review.enabled') !== true
            || (int) config('partner_play_review.user_id') !== (int) $user->getKey()
            || (int) $user->getKey() < 1
            || $user->mobile !== self::MOBILE || $user->email !== self::EMAIL
            || !$user->canUseTransporterLogin()
            || !is_string(config('partner_play_review.code_hash'))
            || config('partner_play_review.code_hash') === '') {
            return false;
        }
        $roles = $user->roles()->get();
        return $roles->count() === 1
            && $roles->first()->name === User::ROLE_TRANSPORTER
            && $roles->first()->guard_name === 'web'
            && !$user->permissions()->exists();
    }

    public function send(User $user): array
    {
        if (!$this->authorized($user)) {
            return ['status' => false, 'message' => 'Partner review login is unavailable.'];
        }
        $id = (string) Str::uuid();
        Cache::put($this->key($id), (int) $user->getKey(), now()->addMinutes(5));
        return [
            'status' => true,
            'message' => 'Enter the review code provided in the app access instructions.',
            'verification_id' => $id,
            'expires_in' => 300,
            'resend_after' => 30,
        ];
    }

    public function verify(User $user, string $code, string $id): array
    {
        if (!$this->authorized($user)
            || !Str::isUuid($id)
            || Cache::get($this->key($id)) !== (int) $user->getKey()
            || !preg_match('/^\d{4}$/', $code)
            || !Hash::check($code, config('partner_play_review.code_hash'))) {
            return ['status' => false, 'message' => 'Invalid review code or expired request. Request a new code.'];
        }
        // Controller holds the account lock; consume each challenge only once.
        Cache::forget($this->key($id));
        return ['status' => true, 'data' => ['payload' => ['user_id' => $user->getKey()]]];
    }

    private function key(string $id): string
    {
        return 'partner-review-challenge:' . $id;
    }
}
