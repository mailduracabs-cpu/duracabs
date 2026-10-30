<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FleetManagement\TransporterProfile;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/** Saved fleet proposals, separate from customer order assignment. */
class PartnerMarketplaceFleet
{
    public static function ready(): void
    {
        abort_unless(Schema::hasColumn('users', 'partner_driver_profile_id') && Schema::hasColumn('users', 'partner_driver_removed_at')
            && Schema::hasColumn('vehicles', 'partner_removed_at'), 503, 'Run the existing partner driver/vehicle ownership migrations first.');
    }

    public static function drivers(TransporterProfile $vendor)
    {
        return User::query()->role(User::ROLE_DRIVER, 'web')->where('partner_driver_profile_id', $vendor->id)
            ->whereNull('partner_driver_removed_at')->where('is_active', true);
    }

    public static function vehicles(TransporterProfile $vendor)
    {
        return Vehicle::query()->where(function ($q) use ($vendor) {
            $q->where('transporter_profile_id', $vendor->id)->orWhere(function ($legacy) use ($vendor) {
                $legacy->whereNull('transporter_profile_id')->where('user_id', $vendor->user_id);
            });
        })->whereNull('partner_removed_at')->where('service_type', 'taxi')
            ->where('is_active', true)->where('is_verified', true)->where('verification_status', 'approved');
    }

    public static function matches(string $requested, string $classification, string $model): bool
    {
        $normal = static fn (string $v): string => preg_replace('/[^a-z0-9]/', '', strtolower($v));
        $requested = $normal($requested); $classification = $normal($classification); $model = $normal($model);
        if ($requested === '') return false;
        if ($requested === 'innovacrysta') return str_contains($model, 'crysta');
        if ($requested === 'innova') return str_contains($model, 'innova') || str_contains($model, 'crysta');
        if ($requested === 'ertiga') return str_contains($model, 'ertiga');
        if (in_array($requested, ['forcetraveller', 'traveller'], true)) return str_contains($model, 'traveller') || str_contains($classification, 'traveller');
        return $classification !== '' && str_contains($classification, $requested);
    }

    /** Only a front CAR photo: no RC/DL/documents, remote URLs or client paths. */
    public static function photo(Vehicle $vehicle): array
    {
        $normal = static function (string $path): ?string {
            $path = str_replace('\\', '/', trim($path));
            if (str_contains($path, '://')) {
                $local = (string) parse_url($path, PHP_URL_PATH);
                if (!str_starts_with($local, '/storage/')) return null;
                $path = substr($local, 9);
            } else {
                foreach (['storage/app/public/', 'public/storage/', '/storage/', 'storage/', 'public/'] as $prefix) {
                    if (str_starts_with($path, $prefix)) { $path = substr($path, strlen($prefix)); break; }
                }
            }
            return $path !== '' && !str_contains($path, '..') && !str_contains($path, ':') && !str_starts_with($path, '/') ? $path : null;
        };
        $sources = [];
        $media = $vehicle->frontMedia;
        if ($media) foreach ($media->allStoredPaths() as $path) $sources[] = [$media->disk ?: 'public', (string) $path];
        if ($vehicle->front_image) $sources[] = ['public', (string) $vehicle->front_image];
        foreach ($sources as [$disk, $original]) {
            $path = $normal($original);
            if (!$path || !Storage::disk($disk)->exists($path)) continue;
            $stream = Storage::disk($disk)->readStream($path);
            if (!is_resource($stream)) continue;
            try { $bytes = stream_get_contents($stream, 8 * 1024 * 1024 + 1); } finally { fclose($stream); }
            abort_if(!is_string($bytes) || strlen($bytes) > 8 * 1024 * 1024, 422, 'Save a car photo smaller than 8 MB in Manage Vehicles.');
            $info = @getimagesizefromstring($bytes);
            $mime = $info['mime'] ?? '';
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            abort_unless(isset($extensions[$mime]), 422, 'Save a valid JPG, PNG or WebP front car photo first.');
            return ['bytes' => $bytes, 'mime' => $mime, 'extension' => $extensions[$mime]];
        }
        abort(422, 'Upload the taxi front photo in Manage Vehicles before sending booking details.');
    }

    public static function text(string $driver, string $taxi, string $number): string
    {
        $oneLine = static fn (string $v): string => trim(preg_replace('/\s+/u', ' ', $v));
        return "Interested in this booking.\nCar Photo: attached\nDriver Name: " . $oneLine($driver)
            . "\nTaxi Name: " . $oneLine($taxi) . "\nTaxi Number: " . $oneLine($number);
    }

    public static function latest(int $post, int $vendor, int $revision): ?object
    {
        return DB::table('partner_market_fleet_submissions')->where('post_id', $post)->where('vendor_id', $vendor)
            ->where('post_revision', $revision)->orderByDesc('id')->first();
    }

    public static function requireFor(object $post, int $vendor): object
    {
        $row = self::latest((int) $post->id, $vendor, (int) $post->revision);
        abort_unless($row, 422, 'The receiving vendor must first send saved driver details and car photo in this booking chat for its current version.');
        return $row;
    }

    /** Issue only after authorizing the conversation or admin view. URL is a 5-minute photo-only capability. */
    public static function summary(object $row): array
    {
        return ['id' => $row->id, 'vendor_id' => $row->vendor_id, 'post_revision' => $row->post_revision,
            'driver_name' => $row->driver_name, 'taxi_name' => $row->taxi_name, 'taxi_number' => $row->taxi_number,
            'car_classification' => $row->car_classification, 'created_at' => $row->created_at,
            'photo_url' => Cache::remember('pm_fleet_photo:' . $row->id . ':' . hash('sha256', URL::to('/')), 240, fn () => URL::temporarySignedRoute('partner.market.fleet-photo', now()->addMinutes(5), ['submission' => $row->id]))];
    }

    public static function enrich($items): void
    {
        $ids = $items->pluck('fleet_submission_id')->filter()->unique();
        if ($ids->isEmpty()) return;
        $rows = DB::table('partner_market_fleet_submissions')->whereIn('id', $ids)->get()->keyBy('id');
        foreach ($items as $message) {
            $row = $rows->get($message->fleet_submission_id ?? null);
            if ($row && (int) $row->post_id === (int) $message->post_id && (int) $row->vendor_id === (int) $message->sender_vendor_id) $message->fleet_details = self::summary($row);
        }
    }
}
