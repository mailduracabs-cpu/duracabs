<?php

namespace App\Support;

class DuraImage
{
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    public static function first($images): ?string
    {
        if (empty($images)) {
            return null;
        }

        if (is_string($images)) {
            $decoded = json_decode($images, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return self::url($decoded[0] ?? null);
            }

            return self::url($images);
        }

        if (is_array($images)) {
            return self::url($images[0] ?? null);
        }

        return null;
    }
}
