<?php

namespace App\Jobs;

use App\Services\Media\MediaCleanupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanupMediaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public readonly ?int $unusedDays = null,
        public readonly ?int $trashedDays = null,
        public readonly ?bool $deleteMissingRecords = null,
        public readonly bool $dryRun = false,
    ) {
        $connection = config(
            'dura-media.queue.connection'
        );

        $queue = config(
            'dura-media.queue.name',
            'media'
        );

        if (filled($connection)) {
            $this->onConnection($connection);
        }

        if (filled($queue)) {
            $this->onQueue($queue);
        }
    }

    public function handle(
        MediaCleanupService $cleanupService,
    ): void {
        $report = $cleanupService->cleanup(
            unusedDays:
                $this->unusedDays
                ?? (int) config(
                    'dura-media.cleanup.unused_after_days',
                    30
                ),

            trashedDays:
                $this->trashedDays
                ?? (int) config(
                    'dura-media.cleanup.purge_trashed_after_days',
                    7
                ),

            deleteMissingRecords:
                $this->deleteMissingRecords
                ?? (bool) config(
                    'dura-media.cleanup.delete_missing_records',
                    false
                ),

            dryRun: $this->dryRun,
        );

        Log::info(
            'CleanupMediaJob completed.',
            $report
        );
    }

    public function failed(
        ?Throwable $exception,
    ): void {
        Log::error(
            'CleanupMediaJob failed.',
            [
                'exception' =>
                    $exception?->getMessage(),
            ]
        );
    }
}