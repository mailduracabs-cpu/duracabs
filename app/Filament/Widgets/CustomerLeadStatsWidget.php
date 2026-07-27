<?php

namespace App\Filament\Widgets;

use App\Models\CustomerSearchActivity;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class CustomerLeadStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        $totalLeads = CustomerSearchActivity::query()->count();

        $todayLeads = CustomerSearchActivity::query()
            ->whereBetween('searched_at', [$todayStart, $todayEnd])
            ->count();

        $hotLeads = CustomerSearchActivity::query()
            ->whereIn('priority', [
                CustomerSearchActivity::PRIORITY_HIGH,
                CustomerSearchActivity::PRIORITY_URGENT,
            ])
            ->openLeads()
            ->notConverted()
            ->count();

        $followUpsDue = CustomerSearchActivity::query()
            ->needsFollowUp()
            ->count();

        $convertedLeads = CustomerSearchActivity::query()
            ->converted()
            ->count();

        $todayRevenue = (float) CustomerSearchActivity::query()
            ->converted()
            ->whereBetween('converted_at', [$todayStart, $todayEnd])
            ->sum('grand_total');

        $conversionRate = $totalLeads > 0
            ? round(($convertedLeads / $totalLeads) * 100, 1)
            : 0.0;

        return [
            Stat::make('Total Leads', number_format($totalLeads))
                ->description($todayLeads . ' new today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-user-group')
                ->color('primary')
                ->chart($this->getLastSevenDaysLeadChart()),

            Stat::make('Hot Leads', number_format($hotLeads))
                ->description('High and urgent intent')
                ->descriptionIcon('heroicon-m-fire')
                ->icon('heroicon-o-fire')
                ->color($hotLeads > 0 ? 'danger' : 'gray'),

            Stat::make('Follow-up Due', number_format($followUpsDue))
                ->description('Needs action now')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-calendar-days')
                ->color($followUpsDue > 0 ? 'warning' : 'success'),

            Stat::make('Converted', number_format($convertedLeads))
                ->description($conversionRate . '% conversion rate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Today Revenue', '₹' . number_format($todayRevenue, 0))
                ->description('Converted lead value')
                ->descriptionIcon('heroicon-m-banknotes')
                ->icon('heroicon-o-currency-rupee')
                ->color('success'),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function getLastSevenDaysLeadChart(): array
    {
        $start = now()->subDays(6)->startOfDay();

        $counts = CustomerSearchActivity::query()
            ->where('searched_at', '>=', $start)
            ->selectRaw('DATE(searched_at) as lead_date, COUNT(*) as total')
            ->groupBy('lead_date')
            ->pluck('total', 'lead_date');

        $chart = [];

        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day)->toDateString();
            $chart[] = (int) ($counts[$date] ?? 0);
        }

        return $chart;
    }
}
