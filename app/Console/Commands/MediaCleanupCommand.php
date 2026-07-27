<?php

namespace App\Console\Commands;

use App\Services\Media\MediaCleanupService;
use Illuminate\Console\Command;
use Throwable;

class MediaCleanupCommand extends Command
{
    protected $signature = 'media:cleanup
        {--unused-days=30 : Delete unused media older than this many days}
        {--trashed-days=7 : Permanently delete soft-deleted media after this many days}
        {--delete-missing : Delete database records whose original files are missing}
        {--dry-run : Show what would be deleted without making changes}
        {--stats : Display media statistics only}';

    protected $description =
        'Clean unused, orphaned, missing and soft-deleted Dura Media files.';

    public function handle(
        MediaCleanupService $cleanupService,
    ): int {
        try {
            if ((bool) $this->option('stats')) {
                $this->displayStatistics(
                    $cleanupService->statistics()
                );

                return self::SUCCESS;
            }

            $unusedDays = max(
                0,
                (int) $this->option('unused-days')
            );

            $trashedDays = max(
                0,
                (int) $this->option('trashed-days')
            );

            $dryRun = (bool) $this->option(
                'dry-run'
            );

            $deleteMissing = (bool) $this->option(
                'delete-missing'
            );

            if ($dryRun) {
                $this->warn(
                    'DRY RUN: No files or database records will be deleted.'
                );
            }

            $this->info(
                'Starting Dura Media cleanup...'
            );

            $report = $cleanupService->cleanup(
                unusedDays: $unusedDays,
                trashedDays: $trashedDays,
                deleteMissingRecords: $deleteMissing,
                dryRun: $dryRun,
            );

            $this->newLine();

            $this->table(
                [
                    'Cleanup Item',
                    'Result',
                ],
                [
                    [
                        'Reference counts fixed',
                        $report[
                            'reference_counts_fixed'
                        ],
                    ],
                    [
                        'Orphan usages removed',
                        $report[
                            'orphan_usage_records_deleted'
                        ],
                    ],
                    [
                        'Unused media found',
                        $report[
                            'unused_media_found'
                        ],
                    ],
                    [
                        'Unused media deleted',
                        $report[
                            'unused_media_deleted'
                        ],
                    ],
                    [
                        'Soft-deleted media found',
                        $report[
                            'trashed_media_found'
                        ],
                    ],
                    [
                        'Soft-deleted media purged',
                        $report[
                            'trashed_media_deleted'
                        ],
                    ],
                    [
                        'Missing originals found',
                        $report[
                            'missing_originals_found'
                        ],
                    ],
                    [
                        'Missing records deleted',
                        $report[
                            'missing_records_deleted'
                        ],
                    ],
                    [
                        'Empty folders deleted',
                        $report[
                            'empty_directories_deleted'
                        ],
                    ],
                ]
            );

            if (
                !empty($report['errors'])
            ) {
                $this->error(
                    'Cleanup completed with errors.'
                );

                foreach ($report['errors'] as $error) {
                    $this->line(
                        '- ' . $error
                    );
                }

                return self::FAILURE;
            }

            $this->newLine();

            $this->info(
                $dryRun
                    ? 'Dry run completed successfully.'
                    : 'Media cleanup completed successfully.'
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                'Media cleanup failed: '
                . $exception->getMessage()
            );

            return self::FAILURE;
        }
    }

    /**
     * @param array<string, int|float> $statistics
     */
    private function displayStatistics(
        array $statistics,
    ): void {
        $this->info(
            'Dura Media Statistics'
        );

        $this->newLine();

        $this->table(
            [
                'Metric',
                'Value',
            ],
            [
                [
                    'Total media',
                    $statistics['total_media'],
                ],
                [
                    'Active media',
                    $statistics['active_media'],
                ],
                [
                    'Unused media',
                    $statistics['unused_media'],
                ],
                [
                    'Soft-deleted media',
                    $statistics['trashed_media'],
                ],
                [
                    'Original storage',
                    $this->formatBytes(
                        (int) $statistics[
                            'original_bytes'
                        ]
                    ),
                ],
                [
                    'Generated variants',
                    $this->formatBytes(
                        (int) $statistics[
                            'optimized_bytes'
                        ]
                    ),
                ],
                [
                    'Storage saved',
                    $this->formatBytes(
                        (int) $statistics[
                            'saved_bytes'
                        ]
                    ),
                ],
                [
                    'Saved percentage',
                    number_format(
                        (float) $statistics[
                            'saved_percentage'
                        ],
                        2
                    ) . '%',
                ],
            ]
        );
    }

    private function formatBytes(
        int $bytes,
    ): string {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $power = min(
            (int) floor(
                log($bytes, 1024)
            ),
            count($units) - 1
        );

        return number_format(
            $bytes / (1024 ** $power),
            $power === 0 ? 0 : 2
        ) . ' ' . $units[$power];
    }
}