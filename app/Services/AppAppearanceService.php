<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AppAppearanceService
{
    public static function presets(): array
    {
        return [
            'normal' => ['primary_color' => '#0059D6', 'accent_color' => '#E53A8B'],
            'diwali' => ['primary_color' => '#9A3E00', 'accent_color' => '#FFC857'],
            'holi' => ['primary_color' => '#9B167B', 'accent_color' => '#39C8C2'],
            'custom' => ['primary_color' => '#0059D6', 'accent_color' => '#E53A8B'],
        ];
    }

    public function defaults(): array
    {
        return [
            'theme_enabled' => false, 'festival' => 'normal',
            'primary_color' => '#0059D6', 'accent_color' => '#E53A8B',
            'theme_image' => null, 'theme_starts_at' => null, 'theme_ends_at' => null,
            'offer_enabled' => false, 'offer_title' => 'DuraCabs offers',
            'offer_image' => null, 'offer_starts_at' => null, 'offer_ends_at' => null,
        ];
    }

    private function path(): string
    {
        return storage_path('app/duracabs/app-appearance.json');
    }

    public function settings(): array
    {
        if (! File::exists($this->path())) {
            return $this->defaults();
        }
        try {
            $stored = json_decode(File::get($this->path()), true, 512, JSON_THROW_ON_ERROR);
            return array_replace($this->defaults(), is_array($stored) ? $stored : []);
        } catch (\Throwable $exception) {
            report($exception);
            return $this->defaults();
        }
    }

    public function save(array $data): array
    {
        $valid = Validator::make($data, [
            'theme_enabled' => ['required', 'boolean'],
            'festival' => ['required', 'in:normal,diwali,holi,custom'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_image' => ['nullable', 'string', 'max:255'],
            'theme_starts_at' => ['nullable', 'date'],
            'theme_ends_at' => array_merge(['nullable', 'date'], empty($data['theme_starts_at']) ? [] : ['after:theme_starts_at']),
            'offer_enabled' => ['required', 'boolean'],
            'offer_title' => ['nullable', 'string', 'max:100'],
            'offer_image' => ['nullable', 'required_if:offer_enabled,true', 'string', 'max:255'],
            'offer_starts_at' => ['nullable', 'date'],
            'offer_ends_at' => array_merge(['nullable', 'date'], empty($data['offer_starts_at']) ? [] : ['after:offer_starts_at']),
        ])->validate();
        foreach (['theme_image', 'offer_image'] as $key) {
            if (! empty($valid[$key])) {
                $path = $valid[$key];
                abort_unless(str_starts_with($path, 'app/appearance/') && ! str_contains($path, '..') &&
                    in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp']) &&
                    Storage::disk('public')->exists($path), 422, 'Invalid appearance image.');
            }
        }
        $valid = array_replace($this->defaults(), $valid);
        $valid['updated_at'] = now()->utc()->toIso8601String();
        File::ensureDirectoryExists(dirname($this->path()));
        // Atomic rename prevents app readers from seeing half-written JSON.
        File::replace($this->path(), json_encode($valid, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        return $valid;
    }

    private function date(?string $value): ?string
    {
        return $value ? CarbonImmutable::parse($value, config('app.timezone'))->utc()->toIso8601String() : null;
    }

    private function active(bool $enabled, ?string $start, ?string $end): bool
    {
        if (! $enabled) return false;
        $now = CarbonImmutable::now();
        return (! $start || $now->greaterThanOrEqualTo(CarbonImmutable::parse($start, config('app.timezone')))) &&
            (! $end || $now->lessThan(CarbonImmutable::parse($end, config('app.timezone'))));
    }

    private function image(?string $path): ?string
    {
        if (! $path || ! str_starts_with($path, 'app/appearance/') || str_contains($path, '..') ||
            ! Storage::disk('public')->exists($path)) return null;
        return url(Storage::disk('public')->url($path));
    }

    public function publicConfig(): array
    {
        $s = $this->settings();
        try {
            $themeActive = $this->active((bool) $s['theme_enabled'], $s['theme_starts_at'], $s['theme_ends_at']);
            $offerActive = $this->active((bool) $s['offer_enabled'], $s['offer_starts_at'], $s['offer_ends_at']);
            return [
                'schema_version' => 1, 'revision' => $s['updated_at'] ?? null,
                'server_time' => now()->utc()->toIso8601String(),
                'theme' => [
                    'enabled' => (bool) $s['theme_enabled'], 'active' => $themeActive,
                    'festival' => $s['festival'],
                    'primary_color' => $s['primary_color'], 'accent_color' => $s['accent_color'],
                    'image_url' => $this->image($s['theme_image']),
                    'starts_at' => $this->date($s['theme_starts_at']), 'ends_at' => $this->date($s['theme_ends_at']),
                ],
                'offer' => [
                    'enabled' => (bool) $s['offer_enabled'], 'active' => $offerActive,
                    'title' => $s['offer_title'], 'image_url' => $this->image($s['offer_image']),
                    'starts_at' => $this->date($s['offer_starts_at']), 'ends_at' => $this->date($s['offer_ends_at']),
                ],
            ];
        } catch (\Throwable $exception) {
            report($exception);
            return ['schema_version' => 1, 'theme' => ['enabled' => false], 'offer' => ['enabled' => false]];
        }
    }
}
