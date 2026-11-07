<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PartnerMarketplaceWhatsAppGroups
{
    public static function queue(int $postId, string $eventKind = 'new'): void
    {
        if (!config('taxi_sathi_bridge.group_broadcast_enabled')) return;
        try {
            $post = DB::table('partner_market_posts')->find($postId);
            if (!$post || $post->kind !== 'booking' || $post->status !== 'open') return;

            $pickup = Carbon::parse($post->pickup_at)->timezone(config('app.timezone'));
            $inclusions = json_decode((string) ($post->inclusions ?? '[]'), true);
            // Only supported public flags are broadcast. Free-form notes can contain
            // phone numbers, fares or the poster's identity, so keep them inside the app.
            $allowed = ['Only Diesel', 'With Carrier', 'Inc Airport Parking', 'Airport Parking Extra', 'Inc AC in plains', 'All inclusive', 'All exclusive'];
            $inclusions = is_array($inclusions) ? array_values(array_intersect($allowed, $inclusions)) : [];
            $bookingBase = rtrim((string) config('taxi_sathi_bridge.booking_base_url'), '/');
            $downloadUrl = trim((string) config('taxi_sathi_bridge.app_download_url'));
            $websiteUrl = trim((string) config('taxi_sathi_bridge.website_url', 'https://www.duracabs.com'));
            foreach (array_filter([$bookingBase, $downloadUrl, $websiteUrl], fn ($value) => $value !== '') as $link) {
                if (!filter_var($link, FILTER_VALIDATE_URL) || parse_url($link, PHP_URL_SCHEME) !== 'https'
                    || parse_url($link, PHP_URL_USER) !== null || parse_url($link, PHP_URL_PASS) !== null) {
                    Log::warning('Marketplace group broadcast contains an invalid HTTPS link');
                    return;
                }
            }
            $bookingUrl = $bookingBase === '' ? '' : $bookingBase.'/'.rawurlencode((string) $post->id);
            $trip = $post->trip_type === 'round_trip' ? 'Round Trip' : 'One Way';

            $lines = [
                '🚖 *'.($eventKind === 'updated' ? 'BOOKING UPDATED' : 'NEW BOOKING').' | DuraCabs Taxi Sathi*',
                '',
                '🆔 *Booking ID:* '.$post->id,
                '📍 *Route:* '.$post->pickup_city.' → '.$post->drop_city,
                '🔄 *Trip Type:* '.$trip,
                '🚘 *Vehicle Type:* '.$post->vehicle_class,
                '📅 *Pickup Date:* '.$pickup->format('d M Y'),
                '⏰ *Pickup Time:* '.$pickup->format('h:i A'),
            ];
            if ($post->trip_type === 'round_trip' && (int) $post->total_days > 0) {
                $lines[] = '🗓️ *Total Days:* '.(int) $post->total_days;
            }
            if ($inclusions) $lines[] = '📝 *Extra Requirements:* '.implode(', ', array_slice($inclusions, 0, 15));
            if ($bookingUrl !== '') {
                $lines[] = '';
                $lines[] = '📲 *View Booking in DuraCabs Taxi Sathi*';
                $lines[] = $bookingUrl;
            }
            $lines[] = '';
            $lines[] = 'Booking dekhne aur interest bhejne ke liye app mein login karein.';
            if ($downloadUrl !== '') {
                $lines[] = '';
                $lines[] = '⬇️ *Download DuraCabs Taxi Sathi*';
                $lines[] = $downloadUrl;
            }
            if ($websiteUrl !== '') {
                $lines[] = '';
                $lines[] = '🌐 *DuraCabs Website*';
                $lines[] = $websiteUrl;
            }

            // Deliberately exclude fare, commission, poster identity and contact details.
            $payload = [
                'post_id' => (int) $post->id,
                'post_revision' => (int) $post->revision,
                'event_kind' => $eventKind,
                'message' => implode("\n", $lines),
            ];
            DB::table('partner_market_whatsapp_group_outbox')->insertOrIgnore([
                'post_id' => $post->id, 'post_revision' => $post->revision,
                'event_kind' => $eventKind, 'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'status' => 'pending', 'available_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Posting a booking must succeed even when WhatsApp is temporarily unavailable.
            Log::warning('Marketplace WhatsApp group broadcast queue failed', [
                'post_id' => $postId, 'error' => $e->getMessage(),
            ]);
        }
    }
}
