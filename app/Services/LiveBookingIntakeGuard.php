<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class LiveBookingIntakeGuard
{
    private static string $reason = 'missing_required_fields';
    public static function lastReason(): string { return self::$reason; }

    public static function bookingSegment(string $text): string
    {
        $plain = LiveBookingEvidenceValidator::plain($text);
        // Split only after a clear request route and a pickup clock. This avoids
        // treating "if any car is free" inside a requirement as a separate ad.
        $boundary = '/\b(?:new\s+)?(?:ertiga|innova(?:\s+crysta)?|crysta|dzire|swift|etios)\s+(?:cab\s*)?[^\p{L}\p{N}]*free\s+(?:in\s+)?[\p{L}]/iu';
        if (preg_match($boundary, $plain, $advert, PREG_OFFSET_CAPTURE)) {
            $before = trim(substr($plain, 0, $advert[0][1]));
            if (preg_match('/\b(?:req|required|need|drop)\b/iu', $before)
                && preg_match('/[\p{L}\p{M}]\s+(?:to|se|से)\s+[\p{L}\p{M}]/iu', $before)
                && preg_match('/\b\d{1,2}(?:[.:]\d{2})?\s*(?:am|pm)\b/iu', $before)) {
                // Contact may appear only in the shared footer. Only use it when
                // the entire message contains one distinct valid contact number.
                preg_match_all('/(?<!\d)(?:\+?91[\s-]*)?[6-9](?:[\s-]*\d){9}(?!\d)/u', $plain, $matches);
                $phones = array_values(array_unique(array_map(fn ($v) => substr((string) preg_replace('/\D/', '', $v), -10), $matches[0])));
                if (!preg_match('/[6-9]\d{9}/', $before) && count($phones) !== 1) return $plain;
                $plain = $before.(count($phones) === 1 ? ' Contact '.$phones[0] : '');
            }
        }
        // Repeated "Drop" is common requirement shorthand. Admit it only when
        // a route follows; the existing minimum-field validator still runs.
        if (preg_match('/^drop(?:\s+drop)*\s+/iu', $plain)
            && preg_match('/\s+(?:to|se|से)\s+/iu', $plain)
            && !preg_match('/\b(?:free|khali|empty|available)\b/iu', $plain)) {
            $plain = 'Required '.preg_replace('/^(?:drop\s+)+/iu', '', $plain);
        }
        return $plain;
    }

    public static function parse(string $text, ?Carbon $receivedAt = null): ?array
    {
        $receipt = ($receivedAt ?? Carbon::now('Asia/Kolkata'))->copy()->timezone('Asia/Kolkata');
        self::$reason = 'rule_parser_no_result';
        $source = self::bookingSegment($text);
        $candidate = LiveBookingMessageParser::parse($source, $receipt);
        if ($candidate === null) return null;
        $checked = LiveBookingEvidenceValidator::check($candidate, $source, $receipt);
        self::$reason = $checked['reason'];
        if ($checked['data'] === null) Log::info('Live booking rule result rejected', ['reason'=>self::$reason, 'message_hash'=>substr(hash('sha256', $text), 0, 16)]);
        return $checked['data'];
    }

    public static function hasMinimumExplicitFields(string $text): bool
    {
        // Compatibility method. Final acceptance is performed on extracted fields, not this filter.
        return LiveBookingEvidenceValidator::eligible(self::bookingSegment($text));
    }

    public static function parseWithAi(string $text, ?Carbon $receivedAt = null): ?array
    {
        $receipt = ($receivedAt ?? Carbon::now('Asia/Kolkata'))->copy()->timezone('Asia/Kolkata');
        $result = LiveBookingAiParser::parse(self::bookingSegment($text), $receipt);
        self::$reason = LiveBookingAiParser::lastReason();
        return $result;
    }
}
