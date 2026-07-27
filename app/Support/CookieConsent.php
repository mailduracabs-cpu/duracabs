<?php

namespace App\Support;

use Illuminate\Http\Request;

final class CookieConsent
{
    /**
     * Return normalized consent values from the current request.
     * Supports both the v2 base64url format and the older URL-encoded JSON format.
     *
     * @return array{version:string,necessary:bool,preferences:bool,analytics:bool,marketing:bool,updated_at:?string}|null
     */
    public static function fromRequest(Request $request): ?array
    {
        $name = (string) config('cookie-consent.cookie_name', 'duracabs_cookie_consent');
        $raw = $request->cookie($name);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = self::decode($raw);
        if (! is_array($decoded)) {
            return null;
        }

        $version = (string) ($decoded['version'] ?? '');
        if ($version !== (string) config('cookie-consent.version', '2')) {
            return null;
        }

        return [
            'version' => $version,
            'necessary' => true,
            'preferences' => (bool) ($decoded['preferences'] ?? false),
            'analytics' => (bool) ($decoded['analytics'] ?? false),
            'marketing' => (bool) ($decoded['marketing'] ?? false),
            'updated_at' => isset($decoded['updated_at']) ? (string) $decoded['updated_at'] : null,
        ];
    }

    public static function allows(Request $request, string $category): bool
    {
        if ($category === 'necessary') {
            return true;
        }

        $consent = self::fromRequest($request);

        return (bool) ($consent[$category] ?? false);
    }

    private static function decode(string $value): ?array
    {
        $candidates = [$value, rawurldecode($value)];

        foreach ($candidates as $candidate) {
            $json = json_decode($candidate, true);
            if (is_array($json)) {
                return $json;
            }

            $base64 = strtr($candidate, '-_', '+/');
            $padding = strlen($base64) % 4;
            if ($padding !== 0) {
                $base64 .= str_repeat('=', 4 - $padding);
            }

            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                continue;
            }

            $json = json_decode($decoded, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return null;
    }
}
