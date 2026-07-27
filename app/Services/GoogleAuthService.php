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
                $googleUser = [
                    'google_id' => $data['google_id'] ?? null,
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? 'Google User',
                    'photo' => $data['photo'] ?? null,
                    'email_verified' => true,
                ];
            }

            if (empty($googleUser['email']) && empty($googleUser['google_id'])) {
                return [
                    'status' => false,
                    'message' => 'Google user email or google_id is required.',
                ];
            }

            $email = $googleUser['email'] ?? 'google_' . $googleUser['google_id'] . '@duracabs.local';

            $user = User::updateOrCreate(
                ['email' => $email],
                $this->onlyUserColumns([
                    'name' => $googleUser['name'] ?? 'Google User',
                    'email' => $email,
                    'google_id' => $googleUser['google_id'] ?? null,
                    'photo' => $googleUser['photo'] ?? null,
                    'device_token' => $data['device_token'] ?? null,
                    'login_type' => 'google',
                    'email_verified_at' => !empty($googleUser['email_verified']) ? now() : null,
                    'password' => Hash::make(Str::random(32)),
                    'is_active' => true,
                ])
            );

            $token = method_exists($user, 'createToken')
                ? $user->createToken('dura_app_token')->plainTextToken
                : 'duracabs_google_token_' . $user->id;

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
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to login with Google.',
                'error' => config('app.debug') ? $e->getMessage() : null,
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
            $response = Http::timeout(20)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

            if (!$response->successful()) {
                return [
                    'status' => false,
                    'message' => 'Invalid Google ID token.',
                ];
            }

            $payload = $response->json();

            return [
                'status' => true,
                'data' => [
                    'google_id' => $payload['sub'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'name' => $payload['name'] ?? 'Google User',
                    'photo' => $payload['picture'] ?? null,
                    'email_verified' => ($payload['email_verified'] ?? 'false') === 'true',
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Google Token Verify Error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to verify Google token.',
                'error' => config('app.debug') ? $e->getMessage() : null,
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