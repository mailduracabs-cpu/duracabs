<?php

namespace App\Console\Commands;

use App\Enums\MediaType;
use App\Services\Media\ImageOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ConvertOldImagesCommand extends Command
{
    protected $signature = 'media:convert-old-images
        {folder=brands : Folder inside storage/app/public}
        {--type=destination : Media type used for resize and quality settings}
        {--quality= : Optional WebP quality between 40 and 95}
        {--overwrite : Replace an existing WebP file}
        {--dry-run : Show files without converting them}';

    protected $description =
        'Convert legacy JPG, JPEG, PNG and GIF images to optimized WebP files.';

    public function handle(
        ImageOptimizationService $optimizer,
    ): int {
        $disk = Storage::disk('public');

        $folder = trim(
            str_replace('\\', '/', (string) $this->argument('folder')),
            '/',
        );

        if ($folder === '') {
            $this->error('A valid public storage folder is required.');

            return self::FAILURE;
        }

        $mediaType = $this->resolveMediaType(
            (string) $this->option('type'),
        );

        if ($mediaType === null) {
            $this->error(
                'Unsupported media type. Use one of: '
                . 'banner, vehicle, tour, destination, offer, service, '
                . 'profile, review, icon or other.'
            );

            return self::FAILURE;
        }

        $quality = $this->resolveQuality();

        if ($quality === false) {
            return self::FAILURE;
        }

        if (!$disk->exists($folder)) {
            $this->error(
                "Folder not found: storage/app/public/{$folder}"
            );

            return self::FAILURE;
        }

        $sourceFiles = collect($disk->allFiles($folder))
            ->filter(
                static fn (string $path): bool => in_array(
                    strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                    ['jpg', 'jpeg', 'png', 'gif'],
                    true,
                )
            )
            ->values();

        if ($sourceFiles->isEmpty()) {
            $this->warn(
                "No legacy images found in storage/app/public/{$folder}."
            );

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');

        $converted = 0;
        $skipped = 0;
        $failed = 0;

        $this->info(
            sprintf(
                '%s %d image(s) in storage/app/public/%s...',
                $dryRun ? 'Checking' : 'Converting',
                $sourceFiles->count(),
                $folder,
            )
        );

        $this->newLine();

        foreach ($sourceFiles as $sourceRelativePath) {
            $destinationRelativePath = preg_replace(
                '/\.(jpe?g|png|gif)$/i',
                '.webp',
                $sourceRelativePath,
            );

            if (!is_string($destinationRelativePath)) {
                $failed++;
                $this->error("Unable to resolve destination: {$sourceRelativePath}");
                continue;
            }

            if ($disk->exists($destinationRelativePath) && !$overwrite) {
                $skipped++;
                $this->line("SKIP  {$destinationRelativePath} already exists");
                continue;
            }

            if ($dryRun) {
                $converted++;
                $this->line(
                    "DRY   {$sourceRelativePath} -> {$destinationRelativePath}"
                );
                continue;
            }

            try {
                $options = [
                    'disk' => 'public',
                    'is_public' => true,
                ];

                if (is_int($quality)) {
                    $options['quality'] = $quality;
                }

                $result = $optimizer->convertExistingImage(
                    sourcePath: $disk->path($sourceRelativePath),
                    destinationPath: $destinationRelativePath,
                    mediaType: $mediaType,
                    options: $options,
                );

                $converted++;

                $this->info(
                    sprintf(
                        'OK    %s -> %s (%dx%d, %s)',
                        $sourceRelativePath,
                        $destinationRelativePath,
                        (int) $result['final_width'],
                        (int) $result['final_height'],
                        $this->formatBytes((int) $result['optimized_size']),
                    )
                );
            } catch (Throwable $exception) {
                $failed++;

                $this->error(
                    "FAIL  {$sourceRelativePath}: {$exception->getMessage()}"
                );
            }
        }

        $this->newLine();

        $this->table(
            ['Result', 'Count'],
            [
                ['Converted', $converted],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ],
        );

        if ($dryRun) {
            $this->warn('Dry run complete. No files were changed.');
        } elseif ($failed === 0) {
            $this->info('Legacy image conversion completed successfully.');
        } else {
            $this->warn('Conversion completed with one or more failures.');
        }

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function resolveMediaType(string $value): ?MediaType
    {
        return match (strtolower(trim($value))) {
            'banner', 'banners' => MediaType::Banner,
            'vehicle', 'vehicles' => MediaType::Vehicle,
            'tour', 'tours' => MediaType::Tour,
            'destination', 'destinations', 'brand', 'brands' => MediaType::Destination,
            'offer', 'offers' => MediaType::Offer,
            'service', 'services' => MediaType::Service,
            'profile', 'profiles' => MediaType::Profile,
            'review', 'reviews' => MediaType::Review,
            'icon', 'icons' => MediaType::Icon,
            'other' => MediaType::Other,
            default => null,
        };
    }

    private function resolveQuality(): int|false|null
    {
        $value = $this->option('quality');

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (!is_numeric($value)) {
            $this->error('The --quality option must be a number between 40 and 95.');

            return false;
        }

        $quality = (int) $value;

        if ($quality < 40 || $quality > 95) {
            $this->error('The --quality option must be between 40 and 95.');

            return false;
        }

        return $quality;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min(
            (int) floor(log($bytes, 1024)),
            count($units) - 1,
        );

        return number_format(
            $bytes / (1024 ** $power),
            $power === 0 ? 0 : 2,
        ) . ' ' . $units[$power];
    }
}