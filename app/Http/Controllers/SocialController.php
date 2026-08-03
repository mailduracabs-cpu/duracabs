<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialController extends Controller
{
    private const SEARCH_CONSOLE_TOKEN_CACHE_KEY =
        'google.search_console.oauth_tokens';

    private const SEARCH_CONSOLE_STATE_SESSION_KEY =
        'google.search_console.oauth_state';

    /*
    |--------------------------------------------------------------------------
    | Existing Customer Google Login
    |--------------------------------------------------------------------------
    */

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::query()
                ->where('google_id', $googleUser->id)
                ->first();

            if (! $user && filled($googleUser->email)) {
                $user = User::query()
                    ->where('email', $googleUser->email)
                    ->first();
            }

            if (! $user) {
                $user = User::query()->create([
                    'name' => $googleUser->name
                        ?: $googleUser->nickname
                        ?: 'Google User',
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => bcrypt(Str::random(48)),
                ]);
            } elseif (blank($user->google_id)) {
                $user->forceFill([
                    'google_id' => $googleUser->id,
                ])->save();
            }

            Auth::login($user, true);

            request()->session()->regenerate();

            return redirect()->intended('/');
        } catch (Throwable $exception) {
            report($exception);

            Log::error('Google customer login failed.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Google login could not be completed. Please try again.'
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Google Search Console OAuth
    |--------------------------------------------------------------------------
    |
    | This OAuth flow is separate from customer Google Login. It requests only
    | Search Console read-only access and stores encrypted OAuth tokens in the
    | configured Laravel cache store.
    |
    */

    public function redirectToSearchConsole(
        Request $request
    ): RedirectResponse {
        abort_unless(
            Auth::check(),
            403,
            'Please sign in before connecting Google Search Console.'
        );

        $clientId = trim((string) config(
            'services.search_console.client_id'
        ));

        $redirectUri = trim((string) config(
            'services.search_console.redirect'
        ));

        if ($clientId === '' || $redirectUri === '') {
            return $this->searchConsoleRedirect()
                ->with(
                    'error',
                    'Google Search Console credentials are not configured.'
                );
        }

        $state = Str::random(64);

        $request->session()->put(
            self::SEARCH_CONSOLE_STATE_SESSION_KEY,
            $state
        );

        $scopes = config(
            'services.search_console.scopes',
            ['https://www.googleapis.com/auth/webmasters.readonly']
        );

        $authorizationUrl = (string) config(
            'services.search_console.authorization_url',
            'https://accounts.google.com/o/oauth2/v2/auth'
        );

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', (array) $scopes),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent select_account',
            'state' => $state,
        ]);

        return redirect()->away($authorizationUrl . '?' . $query);
    }

    public function handleSearchConsoleCallback(
        Request $request
    ): RedirectResponse {
        abort_unless(
            Auth::check(),
            403,
            'Please sign in before connecting Google Search Console.'
        );

        if ($request->filled('error')) {
            return $this->searchConsoleRedirect()
                ->with(
                    'error',
                    'Google authorization was cancelled or denied: '
                    . (string) $request->string('error')
                );
        }

        $expectedState = (string) $request->session()->pull(
            self::SEARCH_CONSOLE_STATE_SESSION_KEY,
            ''
        );

        $receivedState = (string) $request->string('state');

        if (
            $expectedState === ''
            || $receivedState === ''
            || ! hash_equals($expectedState, $receivedState)
        ) {
            return $this->searchConsoleRedirect()
                ->with(
                    'error',
                    'Google authorization state validation failed. Please reconnect.'
                );
        }

        $authorizationCode = trim(
            (string) $request->string('code')
        );

        if ($authorizationCode === '') {
            return $this->searchConsoleRedirect()
                ->with(
                    'error',
                    'Google did not return an authorization code.'
                );
        }

        try {
            $tokenResponse = Http::asForm()
                ->acceptJson()
                ->timeout($this->searchConsoleTimeout())
                ->post(
                    (string) config(
                        'services.search_console.token_url',
                        'https://oauth2.googleapis.com/token'
                    ),
                    [
                        'code' => $authorizationCode,
                        'client_id' => config(
                            'services.search_console.client_id'
                        ),
                        'client_secret' => config(
                            'services.search_console.client_secret'
                        ),
                        'redirect_uri' => config(
                            'services.search_console.redirect'
                        ),
                        'grant_type' => 'authorization_code',
                    ]
                );

            if ($tokenResponse->failed()) {
                Log::error(
                    'Google Search Console token exchange failed.',
                    [
                        'status' => $tokenResponse->status(),
                        'response' => $tokenResponse->json(),
                    ]
                );

                return $this->searchConsoleRedirect()
                    ->with(
                        'error',
                        $this->googleErrorMessage(
                            $tokenResponse->json(),
                            'Google token exchange failed.'
                        )
                    );
            }

            $tokens = $tokenResponse->json();

            if (
                ! is_array($tokens)
                || blank($tokens['access_token'] ?? null)
            ) {
                return $this->searchConsoleRedirect()
                    ->with(
                        'error',
                        'Google did not return a valid access token.'
                    );
            }

            $existing = $this->storedSearchConsoleTokens();

            if (
                blank($tokens['refresh_token'] ?? null)
                && filled($existing['refresh_token'] ?? null)
            ) {
                $tokens['refresh_token'] =
                    $existing['refresh_token'];
            }

            $tokens['created_at'] = now()->timestamp;
            $tokens['expires_at'] = now()
                ->addSeconds(
                    max(60, (int) ($tokens['expires_in'] ?? 3600))
                )
                ->timestamp;

            $tokens['property'] = (string) config(
                'services.search_console.property'
            );

            $tokens['connected_by_user_id'] = Auth::id();
            $tokens['connected_at'] = now()->toIso8601String();

            $this->storeSearchConsoleTokens($tokens);

            $verification = $this->verifySearchConsoleProperty(
                (string) $tokens['access_token']
            );

            if (! $verification['success']) {
                return $this->searchConsoleRedirect()
                    ->with(
                        'warning',
                        'Google connected, but the configured Search Console property could not be verified: '
                        . $verification['message']
                    );
            }

            return $this->searchConsoleRedirect()
                ->with(
                    'success',
                    'Google Search Console connected successfully.'
                );
        } catch (Throwable $exception) {
            report($exception);

            Log::error('Google Search Console OAuth failed.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->searchConsoleRedirect()
                ->with(
                    'error',
                    'Google Search Console connection failed. Please check the Laravel log.'
                );
        }
    }

    public function disconnectSearchConsole(): RedirectResponse
    {
        abort_unless(
            Auth::check(),
            403,
            'Please sign in before disconnecting Google Search Console.'
        );

        $tokens = $this->storedSearchConsoleTokens();
        $accessToken = (string) ($tokens['access_token'] ?? '');

        if ($accessToken !== '') {
            try {
                Http::asForm()
                    ->timeout($this->searchConsoleTimeout())
                    ->post(
                        'https://oauth2.googleapis.com/revoke',
                        ['token' => $accessToken]
                    );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        Cache::forget(self::SEARCH_CONSOLE_TOKEN_CACHE_KEY);

        return $this->searchConsoleRedirect()
            ->with(
                'success',
                'Google Search Console has been disconnected.'
            );
    }

    /**
     * @return array{
     *     success: bool,
     *     message: string,
     *     permission_level?: string
     * }
     */
    private function verifySearchConsoleProperty(
        string $accessToken
    ): array {
        $property = trim((string) config(
            'services.search_console.property'
        ));

        if ($property === '') {
            return [
                'success' => false,
                'message' => 'Search Console property is not configured.',
            ];
        }

        $baseUrl = rtrim(
            (string) config(
                'services.search_console.api_base_url',
                'https://searchconsole.googleapis.com/webmasters/v3'
            ),
            '/'
        );

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout($this->searchConsoleTimeout())
            ->get(
                $baseUrl . '/sites/' . rawurlencode($property)
            );

        if ($response->failed()) {
            return [
                'success' => false,
                'message' => $this->googleErrorMessage(
                    $response->json(),
                    'Property verification failed.'
                ),
            ];
        }

        return [
            'success' => true,
            'message' => 'Search Console property verified.',
            'permission_level' => (string) (
                $response->json('permissionLevel')
                ?? 'unknown'
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storedSearchConsoleTokens(): array
    {
        $encrypted = Cache::get(
            self::SEARCH_CONSOLE_TOKEN_CACHE_KEY
        );

        if (! is_string($encrypted) || $encrypted === '') {
            return [];
        }

        try {
            $tokens = Crypt::decryptString($encrypted);
            $decoded = json_decode($tokens, true);

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * @param array<string, mixed> $tokens
     */
    private function storeSearchConsoleTokens(
        array $tokens
    ): void {
        Cache::forever(
            self::SEARCH_CONSOLE_TOKEN_CACHE_KEY,
            Crypt::encryptString(
                json_encode(
                    $tokens,
                    JSON_THROW_ON_ERROR
                )
            )
        );
    }

    private function searchConsoleTimeout(): int
    {
        return max(
            5,
            (int) config(
                'services.search_console.timeout',
                30
            )
        );
    }

    private function searchConsoleRedirect(): RedirectResponse
    {
        return redirect('/admin');
    }

    private function googleErrorMessage(
        mixed $response,
        string $fallback
    ): string {
        if (! is_array($response)) {
            return $fallback;
        }

        return (string) (
            data_get($response, 'error.message')
            ?? data_get($response, 'error_description')
            ?? data_get($response, 'error')
            ?? $fallback
        );
    }
}