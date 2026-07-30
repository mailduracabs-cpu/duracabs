<?php

namespace App\Jobs;

use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Services\WhatsAppCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppCampaign implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Job ko maximum 3 baar try kiya jayega.
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds.
     */
    public int $timeout = 3600;

    /**
     * Retry ke beech ka delay.
     *
     * @var array<int>
     */
    public array $backoff = [
        60,
        180,
        600,
    ];

    public function __construct(
        public int $campaignId
    ) {
        $this->onQueue('whatsapp');
    }

    /**
     * Ek campaign ko ek samay par sirf ek worker process karega.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                'whatsapp-campaign-' . $this->campaignId
            ))
                ->expireAfter(3700)
                ->dontRelease(),
        ];
    }

    public function handle(
        WhatsAppCampaignService $campaignService
    ): void {
        $campaign = WhatsAppCampaign::query()
            ->withCount('recipients')
            ->find($this->campaignId);

        if (! $campaign) {
            Log::warning('WhatsApp campaign job stopped: campaign not found.', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        if ($campaign->isCancelled()) {
            Log::info('WhatsApp campaign job stopped: campaign cancelled.', [
                'campaign_id' => $campaign->id,
            ]);

            return;
        }

        if ($campaign->isCompleted()) {
            Log::info('WhatsApp campaign job skipped: already completed.', [
                'campaign_id' => $campaign->id,
            ]);

            return;
        }

        if (
            $campaign->isScheduled()
            && $campaign->scheduled_at
            && $campaign->scheduled_at->isFuture()
        ) {
            $delayInSeconds = now()->diffInSeconds(
                $campaign->scheduled_at
            );

            self::dispatch($campaign->id)
                ->delay($campaign->scheduled_at)
                ->onQueue('whatsapp');

            Log::info('WhatsApp campaign job rescheduled.', [
                'campaign_id' => $campaign->id,
                'scheduled_at' => $campaign->scheduled_at->toDateTimeString(),
                'delay_seconds' => $delayInSeconds,
            ]);

            return;
        }

        $pendingRecipientsExist = $campaign->recipients()
            ->whereIn('status', [
                WhatsAppCampaignRecipient::STATUS_PENDING,
                WhatsAppCampaignRecipient::STATUS_PROCESSING,
                WhatsAppCampaignRecipient::STATUS_FAILED,
            ])
            ->exists();

        if (! $pendingRecipientsExist) {
            $campaign->refreshCounters();

            $campaign->forceFill([
                'status' => WhatsAppCampaign::STATUS_COMPLETED,
                'completed_at' => $campaign->completed_at ?? now(),
            ])->saveQuietly();

            Log::info(
                'WhatsApp campaign completed because no pending recipients exist.',
                [
                    'campaign_id' => $campaign->id,
                ]
            );

            return;
        }

        $campaign->forceFill([
            'status' => WhatsAppCampaign::STATUS_PROCESSING,
            'started_at' => $campaign->started_at ?? now(),
            'completed_at' => null,
            'cancelled_at' => null,
            'failure_reason' => null,
        ])->saveQuietly();

        Log::info('WhatsApp campaign processing started.', [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->campaign_name,
            'recipients_count' => $campaign->recipients_count,
        ]);

        try {
            $campaignService->process($campaign);

            $campaign->refresh();

            if ($campaign->isCancelled()) {
                return;
            }

            $campaign->refreshCounters();

            $remainingRecipients = $campaign->recipients()
                ->whereIn('status', [
                    WhatsAppCampaignRecipient::STATUS_PENDING,
                    WhatsAppCampaignRecipient::STATUS_PROCESSING,
                ])
                ->exists();

            if (! $remainingRecipients) {
                $campaign->forceFill([
                    'status' => WhatsAppCampaign::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'failure_reason' => null,
                ])->saveQuietly();

                Log::info('WhatsApp campaign processing completed.', [
                    'campaign_id' => $campaign->id,
                    'sent_count' => $campaign->sent_count,
                    'delivered_count' => $campaign->delivered_count,
                    'read_count' => $campaign->read_count,
                    'failed_count' => $campaign->failed_count,
                ]);
            }
        } catch (Throwable $exception) {
            $campaign->forceFill([
                'status' => WhatsAppCampaign::STATUS_FAILED,
                'failure_reason' => $exception->getMessage(),
            ])->saveQuietly();

            Log::error('WhatsApp campaign processing failed.', [
                'campaign_id' => $campaign->id,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw $exception;
        }
    }

    /**
     * Sabhi retries fail hone par campaign ko failed mark karega.
     */
    public function failed(?Throwable $exception): void
    {
        $campaign = WhatsAppCampaign::query()
            ->find($this->campaignId);

        if (! $campaign || $campaign->isCancelled()) {
            return;
        }

        $campaign->forceFill([
            'status' => WhatsAppCampaign::STATUS_FAILED,
            'failure_reason' => $exception?->getMessage()
                ?? 'Campaign queue job failed after all retries.',
        ])->saveQuietly();

        Log::critical(
            'WhatsApp campaign job failed after all retries.',
            [
                'campaign_id' => $this->campaignId,
                'message' => $exception?->getMessage(),
            ]
        );
    }
}