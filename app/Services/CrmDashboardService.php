<?php

namespace App\Services;

use App\Models\CustomerSearchActivity;
use Illuminate\Support\Facades\Schema;

class CrmDashboardService
{
    public function __construct(
        private readonly CrmReportService $reports,
        private readonly ExecutivePerformanceService $executives
    ) {
    }

    public function dashboard(?string $from = null, ?string $to = null): array
    {
        return [
            'report' => $this->reports->fullReport($from, $to),
            'executives' => $this->executives->report($from, $to),
            'hot_leads' => $this->hotLeads(),
            'abandoned_leads' => $this->abandonedLeads(),
            'payment_failed_leads' => $this->paymentFailedLeads(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function hotLeads(int $limit = 20): array
    {
        $query = CustomerSearchActivity::query();

        if ($this->hasColumn('intent_score')) {
            $query->where('intent_score', '>=', (int) config('crm.lead_scoring.hot_lead_score', 70))
                ->orderByDesc('intent_score');
        } elseif ($this->hasColumn('priority')) {
            $query->whereIn('priority', ['high', 'urgent'])->latest('id');
        } else {
            return [];
        }

        return $query->limit($limit)->get()->toArray();
    }

    public function abandonedLeads(int $limit = 20): array
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

        return $query->where('updated_at', '<=', now()->subMinutes(
            (int) config('crm.followup.abandoned_after_minutes', 30)
        ))->latest('updated_at')->limit($limit)->get()->toArray();
    }

    public function paymentFailedLeads(int $limit = 20): array
    {
        if (!$this->hasColumn('stage')) {
            return [];
        }

        return CustomerSearchActivity::query()
            ->whereIn('stage', ['payment_failed', 'payment_pending'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function hasColumn(string $column): bool
    {
        return Schema::hasColumn((new CustomerSearchActivity())->getTable(), $column);
    }
}
