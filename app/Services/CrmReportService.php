<?php

namespace App\Services;

use App\Models\CustomerSearchActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CrmReportService
{
    public function summary(?string $from = null, ?string $to = null): array
    {
        [$fromDate, $toDate] = $this->resolveDates($from, $to);

        $base = $this->queryBetween($fromDate, $toDate);

        $totalLeads = (clone $base)->count();
        $converted = $this->countConverted(clone $base);
        $lost = $this->countByStatuses(clone $base, [
            'lost', 'closed', 'not_interested', 'cancelled',
        ]);
        $open = max(0, $totalLeads - $converted - $lost);
        $revenue = $this->sumRevenue(clone $base);

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'total_leads' => $totalLeads,
            'converted_leads' => $converted,
            'open_leads' => $open,
            'lost_leads' => $lost,
            'conversion_rate' => $totalLeads > 0
                ? round(($converted / $totalLeads) * 100, 2)
                : 0.0,
            'revenue' => round($revenue, 2),
            'average_lead_value' => $converted > 0
                ? round($revenue / $converted, 2)
                : 0.0,
        ];
    }

    public function serviceBreakdown(?string $from = null, ?string $to = null): array
    {
        [$fromDate, $toDate] = $this->resolveDates($from, $to);
        $column = $this->firstExistingColumn(['service_type', 'module']);

        if ($column === null) {
            return [];
        }

        return $this->queryBetween($fromDate, $toDate)
            ->selectRaw("COALESCE({$column}, 'unknown') as label, COUNT(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    public function sourceBreakdown(?string $from = null, ?string $to = null): array
    {
        [$fromDate, $toDate] = $this->resolveDates($from, $to);

        if (!$this->hasColumn('source')) {
            return [];
        }

        return $this->queryBetween($fromDate, $toDate)
            ->selectRaw("COALESCE(source, 'unknown') as label, COUNT(*) as total")
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    public function dailyTrend(?string $from = null, ?string $to = null): array
    {
        [$fromDate, $toDate] = $this->resolveDates($from, $to);
        $dateColumn = $this->dateColumn();

        return $this->queryBetween($fromDate, $toDate)
            ->selectRaw("DATE({$dateColumn}) as day, COUNT(*) as leads")
            ->groupByRaw("DATE({$dateColumn})")
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->day,
                'leads' => (int) $row->leads,
            ])
            ->values()
            ->all();
    }

    public function fullReport(?string $from = null, ?string $to = null): array
    {
        return [
            'summary' => $this->summary($from, $to),
            'services' => $this->serviceBreakdown($from, $to),
            'sources' => $this->sourceBreakdown($from, $to),
            'trend' => $this->dailyTrend($from, $to),
        ];
    }

    private function queryBetween(Carbon $from, Carbon $to): Builder
    {
        return CustomerSearchActivity::query()
            ->whereBetween($this->dateColumn(), [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ]);
    }

    private function countConverted(Builder $query): int
    {
        if ($this->hasColumn('is_converted')) {
            return $query->where('is_converted', true)->count();
        }

        if ($this->hasColumn('lead_status')) {
            return $query->whereIn('lead_status', ['converted', 'completed'])->count();
        }

        if ($this->hasColumn('stage')) {
            return $query->whereIn('stage', ['booking_completed', 'completed', 'converted'])->count();
        }

        return 0;
    }

    private function countByStatuses(Builder $query, array $statuses): int
    {
        if ($this->hasColumn('lead_status')) {
            return $query->whereIn('lead_status', $statuses)->count();
        }

        return 0;
    }

    private function sumRevenue(Builder $query): float
    {
        $column = $this->firstExistingColumn([
            'grand_total', 'total_amount', 'estimated_amount',
        ]);

        return $column ? (float) $query->sum($column) : 0.0;
    }

    private function resolveDates(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from) : now()->subDays(29);
        $toDate = $to ? Carbon::parse($to) : now();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }

    private function dateColumn(): string
    {
        return $this->firstExistingColumn(['created_at', 'last_activity_at', 'updated_at']) ?? 'id';
    }

    private function firstExistingColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($column)) {
                return $column;
            }
        }

        return null;
    }

    private function hasColumn(string $column): bool
    {
        return Schema::hasColumn((new CustomerSearchActivity())->getTable(), $column);
    }
}
