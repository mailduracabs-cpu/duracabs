<?php

namespace App\Services;

use InvalidArgumentException;

final class CustomerPhoneNumber
{
    /** Legacy Indian keys stay 10 digits; other numbers retain +country code. */
    public static function normalize(string $input, ?string $countryCode = null): string
    {
        $input = trim($input);
        if (! preg_match('/^\+?[0-9 ().\-]+$/D', $input)) {
            throw new InvalidArgumentException('Enter a valid phone number with its country code.');
        }
        $explicit = str_starts_with($input, '+') || str_starts_with($input, '00');
        $digits = preg_replace('/\D/', '', $input);
        if (str_starts_with($input, '00')) {
            $digits = substr($digits, 2);
        }
        $code = $countryCode === null || trim($countryCode) === ''
            ? null : ltrim(trim($countryCode), '+');
        if ($code !== null && ! preg_match('/^[1-9][0-9]{0,2}$/D', $code)) {
            throw new InvalidArgumentException('Enter a valid country calling code.');
        }
        if ($explicit) {
            if ($code !== null && ! str_starts_with($digits, $code)) {
                throw new InvalidArgumentException('The phone number does not match the selected country code.');
            }
        } else {
            // Preserve existing API/web clients sending an Indian local number.
            if ($code === null && strlen($digits) === 12 && str_starts_with($digits, '91')) {
                $explicit = true;
            } else {
                $code ??= '91';
                // Familiar national dialling prefixes; Italy's significant zero is preserved.
                if (in_array($code, ['44', '33', '49', '61', '64', '81', '971'], true)
                    && str_starts_with($digits, '0')) {
                    $digits = substr($digits, 1);
                }
                if ($code === '91' && ! preg_match('/^[0-9]{10}$/D', $digits)) {
                    throw new InvalidArgumentException('Enter a 10-digit Indian mobile number.');
                }
                if ($code === '1' && strlen($digits) !== 10) {
                    throw new InvalidArgumentException('Enter a 10-digit number for country code +1.');
                }
                $digits = $code . $digits;
            }
        }
        if (! preg_match('/^[1-9][0-9]{6,14}$/D', $digits)) {
            throw new InvalidArgumentException('The full international number must contain 7 to 15 digits.');
        }
        if (str_starts_with($digits, '1') && strlen($digits) !== 11) {
            throw new InvalidArgumentException('Enter a 10-digit number for country code +1.');
        }
        if (str_starts_with($digits, '91')) {
            if (strlen($digits) !== 12) {
                throw new InvalidArgumentException('Enter a 10-digit Indian mobile number.');
            }
            return substr($digits, 2);
        }
        return '+' . $digits;
    }

    public static function isIndian(string $canonical): bool
    {
        return (bool) preg_match('/^[0-9]{10}$/D', $canonical);
    }

    public static function aliases(string $canonical): array
    {
        if (self::isIndian($canonical)) {
            return [$canonical, '91' . $canonical, '+91' . $canonical, '0091' . $canonical];
        }
        $digits = substr($canonical, 1);
        // A ten-digit legacy value means India, not a foreign full number.
        // Never link two different countries' accounts through that ambiguity.
        return strlen($digits) === 10
            ? [$canonical, '00' . $digits]
            : [$canonical, $digits, '00' . $digits];
    }
}
