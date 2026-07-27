<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class ImageHashService
{
    public function generate(UploadedFile|string $file): string
    {
        $path = $file instanceof UploadedFile
            ? $file->getRealPath()
            : $file;

        if (
            !is_string($path) ||
            $path === '' ||
            !is_file($path)
        ) {
            throw new InvalidArgumentException(
                'A valid file is required to generate its hash.'
            );
        }

        $hash = hash_file('sha256', $path);

        if ($hash === false) {
            throw new InvalidArgumentException(
                'Unable to generate the file hash.'
            );
        }

        return $hash;
    }

    public function matches(
        string $firstHash,
        string $secondHash
    ): bool {
        if ($firstHash === '' || $secondHash === '') {
            return false;
        }

        return hash_equals(
            $firstHash,
            $secondHash
        );
    }
}