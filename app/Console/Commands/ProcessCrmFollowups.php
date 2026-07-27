<?php

namespace App\Console\Commands;

use App\Services\LeadAssignmentService;
use App\Services\LeadScoringService;
use App\Services\NotificationManagerService;
use App\Services\WhatsAppFollowupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCrmFollowups extends Command
{
    protected $signature = 'crm:followups
        {--limit=200 : Maximum records per category}
        {--skip-scoring}
        {--skip-assignment}
        {--skip-whatsapp}
        {--skip-notification-retry}
        {--strategy=}';

    protected $description = 'Process CRM scoring, assignment, WhatsApp follow-ups and notification retries.';

    public function __construct(
        private readonly LeadScoringService $leadScoringService,
        private readonly LeadAssignmentService $leadAssignmentService,
        private readonly WhatsAppFollowupService $whatsAppFollowupService,
        private readonly NotificationManagerService $notificationManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = max(1, min((int) $this->option('limit'), 2000));
        $summary = [];

        try {
            if (!$this->option('skip-scoring')) {
                $summary['scoring'] = $this->normalize(
                    $this->leadScoringService->recalculateOpenLeads($limit)
                );
            }

            if (!$this->option('skip-assignment')) {
                $summary['assignment'] = $this->leadAssignmentService->assignPendingLeads(
                    limit: $limit,
                    strategy: $this->option('strategy') ?: null
                );
            }

            if (!$this->option('skip-whatsapp')) {
                $summary['whatsapp'] = $this->whatsAppFollowupService->processAll($limit);
            }

            if (!$this->option('skip-notification-retry')) {
                $summary['notification_retry'] = $this->notificationManager->retryFailed($limit);
            }

            $this->info('CRM automation completed.');
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            Log::info('CRM automation completed.', $summary);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('CRM automation failed.', [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function normalize(mixed $result): int
    {
        if (is_int($result)) {
            return $result;
        }

        if (is_array($result)) {
            return (int) ($result['processed'] ?? $result['updated'] ?? $result['count'] ?? 0);
        }

        return $result instanceof \Countable ? count($result) : 0;
    }
}
