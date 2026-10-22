<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoogleAuthService
{
    public function loginWithGoogle(array $data): array
    {
        try {
            $googleUser = null;

            if (!empty($data['id_token'])) {
                $verified = $this->verifyGoogleIdToken($data['id_token']);

                if (!$verified['status']) {
                    return $verified;
                }

                $googleUser = $verified['data'];
            } else {
                return ['status' => false, 'message' => 'Google ID token is required. Please sign in again.'];
            }

            if (empty($googleUser['email']) && empty($googleUser['google_id'])) {
                return [
                    'status' => false,
                    'message' => 'Google user email or google_id is required.',
                ];
            }

            $email = $googleUser['email'] ?? 'google_' . $googleUser['google_id'] . '@duracabs.local';

            $user = User::firstOrNew(['email' => $email]);
            $isNewUser = !$user->exists;
            if (!$isNewUser && Schema::hasColumn('users', 'is_active') && !$user->is_active) {
                return ['status' => false, 'message' => 'Your account is inactive. Please contact support.'];
            }
            if (!$isNewUser && !empty($user->google_id) && (string) $user->google_id !== (string) $googleUser['google_id']) {
                return ['status' => false, 'message' => 'This email is linked to another Google account. Please contact support.'];
            }
            $attributes = [
                'google_id' => $googleUser['google_id'],
                'photo' => $googleUser['photo'],
                'device_token' => $data['device_token'] ?? null,
                'login_type' => 'google',
                'email_verified_at' => now(),
            ];
            if ($isNewUser) {
                $attributes += ['name' => $googleUser['name'], 'password' => Hash::make(Str::random(32)), 'is_active' => true];
            }
            $user->forceFill($this->onlyUserColumns($attributes));
            $user->save();
            if (!method_exists($user, 'createToken')) {
                return ['status' => false, 'message' => 'Account token configuration is incomplete. Please contact support.'];
            }
            $token = $user->createToken('dura_app_token')->plainTextToken;

            return [
                'status' => true,
                'message' => 'Google login successful',
                'data' => [
                    'token' => $token,
                    'user' => $user,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Google Login Error', [
                'exception' => get_class($e),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to login with Google.',
            ];
        }
    }

    public function loginWithFirebase(array $data, FirebaseService $firebaseService): array
    {
        if (empty($data['firebase_token'])) {
            return [
                'status' => false,
                'message' => 'Firebase token is required.',
            ];
        }

        $verified = $firebaseService->verifyIdToken($data['firebase_token']);

        if (!$verified['status']) {
            return $verified;
        }

        $firebaseUser = $verified['data'];

        $email = $firebaseUser['email']
            ?? ('firebase_' . ($firebaseUser['firebase_uid'] ?? Str::uuid()) . '@duracabs.local');

        $user = User::updateOrCreate(
            ['email' => $email],
            $this->onlyUserColumns([
                'name' => $firebaseUser['name'] ?? 'Dura User',
                'email' => $email,
                'mobile' => $firebaseUser['mobile'] ?? null,
                'google_id' => $firebaseUser['firebase_uid'] ?? null,
                'photo' => $firebaseUser['photo'] ?? null,
                'device_token' => $data['device_token'] ?? null,
                'login_type' => $firebaseUser['provider'] ?? 'firebase',
                'email_verified_at' => !empty($firebaseUser['email_verified']) ? now() : null,
                'password' => Hash::make(Str::random(32)),
                'is_active' => true,
            ])
        );

        $token = method_exists($user, 'createToken')
            ? $user->createToken('dura_app_token')->plainTextToken
            : 'duracabs_firebase_token_' . $user->id;

        return [
            'status' => true,
            'message' => 'Firebase login successful',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ];
    }

    private function verifyGoogleIdToken(string $idToken): array
    {
        try {
            $response = Http::connectTimeout(5)->timeout(20)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

            if (!$response->successful()) {
                return [
                    'status' => false,
                    'message' => 'Invalid Google ID token.',
                ];
            }

            $payload = $response->json();

            $clients = config('customer_login.google_client_ids', []);
            if (!is_array($payload) || empty($payload['sub']) || empty($payload['email'])
                || !in_array($payload['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)
                || !in_array($payload['aud'] ?? '', $clients, true)
                || (int) ($payload['exp'] ?? 0) <= time()
                || !in_array($payload['email_verified'] ?? false, [true, 'true', 1, '1'], true)) {
                return ['status' => false, 'message' => 'Google token does not match this app or has expired. Please sign in again.'];
            }

            return [
                'status' => true,
                'data' => [
                    'google_id' => $payload['sub'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'name' => $payload['name'] ?? 'Google User',
                    'photo' => $payload['picture'] ?? null,
                    'email_verified' => in_array($payload['email_verified'] ?? false, [true, 'true', 1, '1'], true),
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Google Token Verify Error', [
                'exception' => get_class($e),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to verify Google token.',
            ];
        }
    }

    private function onlyUserColumns(array $data): array
    {
        return collect($data)
            ->filter(fn($value) => !is_null($value))
            ->filter(fn($value, $key) => Schema::hasColumn('users', $key))
            ->toArray();
    }
}