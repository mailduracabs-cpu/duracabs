<?php

namespace App\Console\Commands;

use App\Enums\MediaType;
use App\Models\AppMedia;
use App\Services\Media\ImageOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class DuraMediaCheck extends Command
{
    protected $signature = 'dura-media:check {--apply : Convert existing non-browser images; preserve old files} {--optimize-all : Also recompress other non-WebP images} {--id= : Inspect one media record}';
    protected $description = 'Check media storage and optionally convert existing photos to WebP without deleting originals';

    public function handle(ImageOptimizationService $optimizer): int
    {
        $this->info('Imagick: ' . (extension_loaded('imagick') ? 'yes' : 'no'));
        if (extension_loaded('imagick')) {
            foreach (['HEIC', 'AVIF', 'TIFF', 'WEBP'] as $format) {
                $this->line($format . ': ' . (\Imagick::queryFormats($format) ? 'available' : 'missing'));
            }
        }
        $this->line('ZIP: ' . (class_exists(\ZipArchive::class) ? 'available' : 'missing'));
        $failures = 0;
        $query = AppMedia::query();
        if ($this->option('id')) { $query->whereKey($this->option('id')); }
        $query->chunkById(100, function ($rows) use ($optimizer, &$failures): void {
            foreach ($rows as $media) {
                $destination = null;
                $disk = Storage::disk($media->disk ?: 'public');
                try {
                    $source = $media->storedPath();
                    if (!$source || str_contains($source, '..') || str_contains($source, '://') || !$disk->exists($source)) {
                        $this->warn("#{$media->id}: missing file; restore from backup or upload replacement");
                        $failures++;
                        continue;
                    }
                    $mime = $disk->mimeType($source) ?: $media->mime_type;
                    $image = str_starts_with((string) $mime, 'image/') || in_array(strtolower(pathinfo($source, PATHINFO_EXTENSION)), ['heic', 'heif', 'avif', 'tif', 'tiff', 'bmp'], true);
                    $convert = $image && $mime !== 'image/webp' && ($this->option('optimize-all') || !\App\Support\MediaFormats::browserImage($mime));
                    $this->line("#{$media->id}: exists" . ($convert ? ' / conversion candidate' : ''));
                    if (!$this->option('apply') || !$convert) { continue; }
                    $destination = dirname($source) . '/repaired-' . Str::uuid() . '.webp';
                    $result = $optimizer->convertExistingImage($disk->path($source), $destination,
                        $media->media_type ?? MediaType::Other,
                        ['disk' => $media->disk ?: 'public', 'is_public' => (bool) $media->is_public]);
                    $backup = $media->only(['original_path', 'large_path', 'medium_path', 'thumbnail_path', 'mime_type', 'original_extension', 'optimized_size', 'width', 'height']);
                    $media->update([
                        'original_path' => $destination, 'mime_type' => 'image/webp', 'original_extension' => 'webp',
                        'optimized_size' => $result['optimized_size'], 'width' => $result['final_width'], 'height' => $result['final_height'],
                        'metadata' => array_merge((array) $media->metadata, ['before_media_repair' => $backup, 'media_repaired_at' => now()->toIso8601String()]),
                    ]);
                    $this->info("#{$media->id}: converted; original retained");
                } catch (Throwable $e) {
                    if ($destination && $media->getRawOriginal('original_path') !== $destination) { $disk->delete($destination); }
                    $failures++;
                    $this->error("#{$media->id}: " . $e->getMessage());
                }
            }
        });
        if (!$this->option('apply')) { $this->info('Read-only check completed. Use --apply after reviewing output and taking a backup.'); }
        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
