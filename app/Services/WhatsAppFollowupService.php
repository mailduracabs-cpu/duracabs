<?php

namespace App\Services;

use App\Models\CustomerActivity;
use App\Models\CustomerSearchActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class WhatsAppFollowupService
{
    public const TYPE_SEARCH_ABANDONED = 'search_abandoned';
    public const TYPE_CHECKOUT_ABANDONED = 'checkout_abandoned';
    public const TYPE_PAYMENT_FAILED = 'payment_failed';
    public const TYPE_HOT_LEAD = 'hot_lead';

    public function processAll(int $limit = 200): array
    {
        return [
            'abandoned' => $this->processAbandonedLeads($limit),
            'payment_failed' => $this->processPaymentFailedLeads($limit),
            'hot_leads' => $this->processHotLeads($limit),
        ];
    }

    public function processAbandonedLeads(int $limit = 200): array
    {
        $results = $this->emptyResult();

        $this->baseOpenQuery()
            ->where($this->dateColumn(), '<=', now()->subMinutes(
                (int) config('crm.followup.abandoned_after_minutes', 30)
            ))
            ->oldest('id')
            ->limit($limit)
            ->get()
            ->each(function (CustomerSearchActivity $lead) use (&$results): void {
                $results['processed']++;
                $stage = strtolower((string) ($lead->stage ?? ''));
                $type = str_contains($stage, 'checkout') || str_contains($stage, 'payment')
                    ? self::TYPE_CHECKOUT_ABANDONED
                    : self::TYPE_SEARCH_ABANDONED;

                $message = $type === self::TYPE_CHECKOUT_ABANDONED
                    ? $this->checkoutMessage($lead)
                    : $this->searchMessage($lead);

                $this->send($lead, $type, $message)
                    ? $results['sent']++
                    : $results['skipped_or_failed']++;
            });

        return $results;
    }

    public function processPaymentFailedLeads(int $limit = 200): array
    {
        $results = $this->emptyResult();

        if (!$this->hasColumn('stage')) {
            return $results;
        }

        $this->baseOpenQuery()
            ->whereIn('stage', ['payment_failed', 'payment_pending'])
            ->oldest('id')
            ->limit($limit)
            ->get()
            ->each(function (CustomerSearchActivity $lead) use (&$results): void {
                $results['processed']++;
                $this->send($lead, self::TYPE_PAYMENT_FAILED, $this->paymentMessage($lead))
                    ? $results['sent']++
                    : $results['skipped_or_failed']++;
            });

        return $results;
    }

    public function processHotLeads(int $limit = 200): array
    {
        $results = $this->emptyResult();
        $query = $this->baseOpenQuery();

        if ($this->hasColumn('intent_score')) {
            $query->where('intent_score', '>=', (int) config('crm.lead_scoring.hot_lead_score', 70));
        } elseif ($this->hasColumn('priority')) {
            $query->whereIn('priority', ['high', 'urgent']);
        } else {
            return $results;
        }

        $query->oldest('id')->limit($limit)->get()
            ->each(function (CustomerSearchActivity $lead) use (&$results): void {
                $results['processed']++;
                $this->send($lead, self::TYPE_HOT_LEAD, $this->hotLeadMessage($lead))
                    ? $results['sent']++
                    : $results['skipped_or_failed']++;
            });

        return $results;
    }

    private function send(CustomerSearchActivity $lead, string $type, string $message): bool
    {
        $mobile = trim((string) ($lead->mobile ?? $lead->phone ?? ''));

        if ($mobile === '' || !$this->canSend($lead, $type)) {
            return false;
        }

        try {
            $response = WhatsAppService::sendMessage($mobile, $message);
            $success = (bool) ($response['status'] ?? false);
            $this->storeResult($lead, $type, $success, $message, $response);

            return $success;
        } catch (Throwable $exception) {
            Log::error('WhatsApp CRM follow-up failed.', [
                'lead_id' => $lead->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function canSend(CustomerSearchActivity $lead, string $type): bool
    {
        $metadata = is_array($lead->metadata) ? $lead->metadata : [];
        $data = $metadata['whatsapp_followups'][$type] ?? [];
        $sentCount = (int) ($data['sent_count'] ?? 0);

        if ($sentCount >= (int) config('crm.followup.maximum_followups', 3)) {
            return false;
        }

        if (empty($data['last_sent_at'])) {
            return true;
        }

        $minutes = $sentCount <= 1 ? 180 : 1440;

        return Carbon::parse($data['last_sent_at'])->lte(now()->subMinutes($minutes));
    }

    private function storeResult(
        CustomerSearchActivity $lead,
        string $type,
        bool $success,
        string $message,
        array $response
    ): void {
        $metadata = is_array($lead->metadata) ? $lead->metadata : [];
        $current = $metadata['whatsapp_followups'][$type] ?? [];

        $metadata['whatsapp_followups'][$type] = [
            'attempt_count' => (int) ($current['attempt_count'] ?? 0) + 1,
            'sent_count' => (int) ($current['sent_count'] ?? 0) + ($success ? 1 : 0),
            'last_sent_at' => $success ? now()->toIso8601String() : ($current['last_sent_at'] ?? null),
            'last_status' => $success ? 'sent' : 'failed',
            'last_message' => $message,
            'last_response' => $response,
        ];

        $lead->forceFill(['metadata' => $metadata])->save();

        try {
            CustomerActivity::query()->create(array_filter([
                'user_id' => $lead->user_id ?? null,
                'mobile' => $lead->mobile ?? null,
                'event' => $success ? 'whatsapp_followup_sent' : 'whatsapp_followup_failed',
                'module' => $lead->module ?? null,
                'service_type' => $lead->service_type ?? null,
                'data' => [
                    'customer_search_activity_id' => $lead->id,
                    'followup_type' => $type,
                    'response' => $response,
                ],
                'occurred_at' => now(),
            ], fn ($value) => $value !== null));
        } catch (Throwable) {
            // Timeline logging must not stop delivery processing.
        }
    }

    private function baseOpenQuery(): Builder
    {
        $query = CustomerSearchActivity::query();

        if ($this->hasColumn('is_converted')) {
            $query->where('is_converted', false);
        }

        if ($this->hasColumn('lead_status')) {
            $query->whereNotIn('lead_status', [
                'converted', 'completed', 'closed', 'lost', 'cancelled',
            ]);
        }

        return $query;
    }

    private function searchMessage(CustomerSearchActivity $lead): string
    {
        return "Namaste {$this->name($lead)},\n\nAapne Dura Cabs par {$this->route($lead)} ke liye cab search ki thi. Aapki booking abhi complete nahi hui hai.\n\nBooking continue karein:\n{$this->link($lead)}";
    }

    private function checkoutMessage(CustomerSearchActivity $lead): string
    {
        return "Namaste {$this->name($lead)},\n\nAapki {$this->route($lead)} booking checkout tak pahunch gayi thi, lekin confirm nahi ho paayi.\n\nBooking confirm karein:\n{$this->link($lead)}";
    }

    private function paymentMessage(CustomerSearchActivity $lead): string
    {
        return "Namaste {$this->name($lead)},\n\nAapki Dura Cabs booking ka payment complete nahi ho paaya.\n\nPayment dobara complete karein:\n{$this->link($lead)}";
    }

    private function hotLeadMessage(CustomerSearchActivity $lead): string
    {
        return "Namaste {$this->name($lead)},\n\nAapki {$this->route($lead)} travel requirement ke liye Dura Cabs team taiyar hai.\n\nBooking continue karein:\n{$this->link($lead)}";
    }

    private function name(CustomerSearchActivity $lead): string
    {
        return trim((string) ($lead->customer_name ?? $lead->name ?? 'Customer')) ?: 'Customer';
    }

    private function route(CustomerSearchActivity $lead): string
    {
        $pickup = trim((string) ($lead->pickup_city ?? $lead->pickup_location ?? ''));
        $drop = trim((string) ($lead->drop_city ?? $lead->drop_location ?? ''));

        return $pickup && $drop ? "{$pickup} se {$drop}" : 'aapki trip';
    }

    private function link(CustomerSearchActivity $lead): string
    {
        $metadata = is_array($lead->metadata) ? $lead->metadata : [];

        return (string) ($metadata['booking_link'] ?? $metadata['checkout_link'] ?? config('app.frontend_url', 'https://www.duracabs.com'));
    }

    private function dateColumn(): string
    {
        foreach (['last_activity_at', 'updated_at', 'created_at'] as $column) {
            if ($this->hasColumn($column)) {
                return $column;
            }
        }

        return 'id';
    }

    private function hasColumn(string $column): bool
    {
        return Schema::hasColumn((new CustomerSearchActivity())->getTable(), $column);
    }

    private function emptyResult(): array
    {
        return ['processed' => 0, 'sent' => 0, 'skipped_or_failed' => 0];
    }
}
