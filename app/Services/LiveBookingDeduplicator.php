<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class LiveBookingDeduplicator
{
    private string $text;
    private Carbon $receipt;

    public static function forMessage(string $text, ?Carbon $receipt = null): self
    {
        $instance = new self;
        $instance->text = $text;
        $instance->receipt = ($receipt ?? Carbon::now('Asia/Kolkata'))->copy()->timezone('Asia/Kolkata');
        return $instance;
    }

    private static function normal(string $value): string
    {
        if (class_exists(\Normalizer::class)) $value = \Normalizer::normalize($value, \Normalizer::FORM_KC) ?: $value;
        $value = mb_strtolower($value, 'UTF-8');
        return trim((string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', $value));
    }

    private static function details(array $values): string
    {
        $car = self::normal((string) ($values['car_type'] ?? ''));
        $car = ['swift dzire' => 'dzire', 'desire' => 'dzire', 'dezire' => 'dzire',
            'crysta' => 'innova crysta', 'artiga' => 'ertiga', 'sadan' => 'sedan'][$car] ?? $car;
        $phone = (string) preg_replace('/\D/', '', (string) ($values['requester_phone'] ?? ''));
        if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
        $data = [
            self::normal((string) ($values['pickup_address'] ?? $values['from_city'] ?? '')),
            self::normal((string) ($values['drop_address'] ?? $values['to_city'] ?? '')),
            self::normal((string) ($values['trip_type'] ?? '')),
            $car, substr((string) ($values['pickup_at'] ?? ''), 0, 16),
            substr((string) ($values['return_at'] ?? ''), 0, 16), $phone,
        ];
        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private static function textKey(string $text, string $pickupAt): string
    {
        // The same undated clock can mean today before it passes and tomorrow after it.
        // Target pickup day, not receipt day, identifies this logical occurrence.
        return hash('sha256','text-v2|'.substr($pickupAt,0,10).'|'.self::normal($text));
    }

    public function insertOrIgnore(array $values): int
    {
        $pickupDay=substr((string)$values['pickup_at'],0,10);
        $legacyKey=hash('sha256','text-v1|'.$this->receipt->format('Y-m-d').'|'.self::normal($this->text));
        $keys = [self::textKey($this->text,(string)$values['pickup_at']),
            hash('sha256', 'details-v1|'.self::details($values))];
        $keys = array_values(array_unique($keys)); sort($keys, SORT_STRING);

        return DB::transaction(function () use ($keys, $values, $legacyKey, $pickupDay) {
            // Atomic unique keys and row locks serialize simultaneous messages from
            // different groups, processes and HTTP requests.
            $existingId = null;
            foreach ($keys as $key) {
                DB::table('live_booking_dedup_keys')->insertOrIgnore([
                    'fingerprint' => $key, 'booking_id' => null, 'created_at' => now(),
                ]);
                $row = DB::table('live_booking_dedup_keys')->where('fingerprint', $key)->lockForUpdate()->first();
                if ($row && $row->booking_id && DB::table('live_bookings')->where('id', $row->booking_id)->exists()) {
                    $existingId = (int) $row->booking_id;
                }
            }
            if($existingId===null){
                // Migrate old receipt-day text matches only when their target pickup day
                // is the same. An old today's job must not suppress tomorrow's new job.
                $legacy=DB::table('live_booking_dedup_keys')->where('fingerprint',$legacyKey)->lockForUpdate()->first();
                if($legacy&&$legacy->booking_id){
                    $booking=DB::table('live_bookings')->where('id',$legacy->booking_id)->first(['id','pickup_at']);
                    if($booking&&substr((string)$booking->pickup_at,0,10)===$pickupDay)$existingId=(int)$booking->id;
                }
            }
            if ($existingId === null) {
                // Respect matching bookings already imported before installing this patch.
                $candidates = DB::table('live_bookings')->where('requester_phone', $values['requester_phone'])
                    ->where('pickup_at', '>=', substr((string) $values['pickup_at'], 0, 10).' 00:00:00')
                    ->where('pickup_at', '<=', substr((string) $values['pickup_at'], 0, 10).' 23:59:59')
                    ->orderBy('id')->get();
                foreach ($candidates as $candidate) {
                    if (self::details((array) $candidate) === self::details($values)) {
                        $existingId = (int) $candidate->id; break;
                    }
                }
            }
            if ($existingId !== null) {
                DB::table('live_booking_dedup_keys')->whereIn('fingerprint', $keys)->update(['booking_id' => $existingId]);
                return 0;
            }
            $created = DB::table('live_bookings')->insertOrIgnore($values);
            $bookingId = DB::table('live_bookings')->where('source_message_id', $values['source_message_id'])->value('id');
            if (!$bookingId) throw new \RuntimeException('Booking insertion could not be confirmed; deduplication rolled back.');
            DB::table('live_booking_dedup_keys')->whereIn('fingerprint', $keys)->update(['booking_id' => $bookingId]);
            return (int) $created;
        }, 5);
    }
}
