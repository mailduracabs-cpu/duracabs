<?php

namespace App\Services;

use App\Models\CustomerSearchActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ExecutivePerformanceService
{
    public function report(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(29)->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            return [];
        }

        $leadTable = (new CustomerSearchActivity())->getTable();
        $userTable = (new User())->getTable();
        $dateColumn = $this->dateColumn();

        $rows = CustomerSearchActivity::query()
            ->leftJoin($userTable, "{$userTable}.id", '=', "{$leadTable}.{$assignmentColumn}")
            ->whereBetween("{$leadTable}.{$dateColumn}", [$fromDate, $toDate])
            ->selectRaw("
                {$leadTable}.{$assignmentColumn} as executive_id,
                COALESCE({$userTable}.name, 'Unassigned') as executive_name,
                COUNT({$leadTable}.id) as assigned_leads
            ")
            ->groupBy("{$leadTable}.{$assignmentColumn}", "{$userTable}.name")
            ->orderByDesc('assigned_leads')
            ->get();

        return $rows->map(function ($row) use ($fromDate, $toDate, $assignmentColumn) {
            $query = CustomerSearchActivity::query()
                ->where($assignmentColumn, $row->executive_id)
                ->whereBetween($this->dateColumn(), [$fromDate, $toDate]);

            $assigned = (int) $row->assigned_leads;
            $converted = $this->convertedCount(clone $query);
            $revenue = $this->revenue(clone $query);

            return [
                'executive_id' => $row->executive_id ? (int) $row->executive_id : null,
                'executive_name' => (string) $row->executive_name,
                'assigned_leads' => $assigned,
                'converted_leads' => $converted,
                'pending_leads' => max(0, $assigned - $converted),
                'conversion_rate' => $assigned > 0
                    ? round(($converted / $assigned) * 100, 2)
                    : 0.0,
                'revenue' => round($revenue, 2),
            ];
        })->values()->all();
    }

    private function convertedCount($query): int
    {
        if ($this->hasColumn('is_converted')) {
            return $query->where('is_converted', true)->count();
        }

        if ($this->hasColumn('lead_status')) {
            return $query->whereIn('lead_status', ['converted', 'completed'])->count();
        }

        return 0;
    }

    private function revenue($query): float
    {
        foreach (['grand_total', 'total_amount', 'estimated_amount'] as $column) {
            if ($this->hasColumn($column)) {
                return (float) $query->sum($column);
            }
        }

        return 0.0;
    }

    private function assignmentColumn(): ?string
    {
        foreach (['assigned_user_id', 'assigned_to', 'assigned_to_id', 'executive_id'] as $column) {
            if ($this->hasColumn($column)) {
                return $column;
            }
        }

        return null;
    }

    private function dateColumn(): string
    {
        foreach (['created_at', 'last_activity_at', 'updated_at'] as $column) {
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
}
