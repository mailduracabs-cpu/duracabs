<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CustomerLeadResource;
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

        /*
        |--------------------------------------------------------------------------
        | TOTAL LEADS
        |--------------------------------------------------------------------------
        */
        $totalLeads = CustomerSearchActivity::query()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | TODAY LEADS
        |--------------------------------------------------------------------------
        */
        $todayLeads = CustomerSearchActivity::query()
            ->whereBetween(
                'searched_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | HOT LEADS
        |--------------------------------------------------------------------------
        */
        $hotLeads = CustomerSearchActivity::query()
            ->whereIn('priority', [
                CustomerSearchActivity::PRIORITY_HIGH,
                CustomerSearchActivity::PRIORITY_URGENT,
            ])
            ->openLeads()
            ->notConverted()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | FOLLOW-UP DUE
        |--------------------------------------------------------------------------
        */
        $followUpsDue = CustomerSearchActivity::query()
            ->needsFollowUp()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | CONVERTED LEADS
        |--------------------------------------------------------------------------
        */
        $convertedLeads = CustomerSearchActivity::query()
            ->converted()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | TODAY CONVERTED REVENUE
        |--------------------------------------------------------------------------
        */
        $todayRevenue = (float) CustomerSearchActivity::query()
            ->converted()
            ->whereBetween(
                'converted_at',
                [$todayStart, $todayEnd]
            )
            ->sum('grand_total');

        /*
        |--------------------------------------------------------------------------
        | CONVERSION RATE
        |--------------------------------------------------------------------------
        */
        $conversionRate = $totalLeads > 0
            ? round(
                ($convertedLeads / $totalLeads) * 100,
                1
            )
            : 0.0;

        /*
        |--------------------------------------------------------------------------
        | TODAY LEADS URL
        |--------------------------------------------------------------------------
        |
        | Click card:
        | Customer Leads -> Today filter
        |
        */
        $todayLeadsUrl = CustomerLeadResource::getUrl(
            'index',
            [
                'tableFilters' => [
                    'today' => [
                        'isActive' => true,
                    ],
                ],
            ]
        );

        return [

            /*
            |--------------------------------------------------------------------------
            | TODAY LEADS - CLICKABLE
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Today Leads',
                number_format($todayLeads)
            )
                ->description('Click to view today leads')
                ->descriptionIcon('heroicon-m-arrow-right')
                ->icon('heroicon-o-user-plus')
                ->color(
                    $todayLeads > 0
                        ? 'primary'
                        : 'gray'
                )
                ->chart(
                    $this->getLastSevenDaysLeadChart()
                )
                ->url($todayLeadsUrl),

            /*
            |--------------------------------------------------------------------------
            | HOT LEADS
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Hot Leads',
                number_format($hotLeads)
            )
                ->description('High and urgent intent')
                ->descriptionIcon('heroicon-m-fire')
                ->icon('heroicon-o-fire')
                ->color(
                    $hotLeads > 0
                        ? 'danger'
                        : 'gray'
                ),

            /*
            |--------------------------------------------------------------------------
            | FOLLOW-UP DUE
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Follow-up Due',
                number_format($followUpsDue)
            )
                ->description('Needs action now')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-calendar-days')
                ->color(
                    $followUpsDue > 0
                        ? 'warning'
                        : 'success'
                ),

            /*
            |--------------------------------------------------------------------------
            | CONVERTED
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Converted',
                number_format($convertedLeads)
            )
                ->description(
                    $conversionRate . '% conversion rate'
                )
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            /*
            |--------------------------------------------------------------------------
            | TODAY REVENUE
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Today Revenue',
                '₹' . number_format(
                    $todayRevenue,
                    0
                )
            )
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
        $start = now()
            ->subDays(6)
            ->startOfDay();

        $counts = CustomerSearchActivity::query()
            ->where(
                'searched_at',
                '>=',
                $start
            )
            ->selectRaw(
                'DATE(searched_at) as lead_date, COUNT(*) as total'
            )
            ->groupBy('lead_date')
            ->pluck('total', 'lead_date');

        $chart = [];

        for ($day = 6; $day >= 0; $day--) {
            $date = now()
                ->subDays($day)
                ->toDateString();

            $chart[] = (int) (
                $counts[$date] ?? 0
            );
        }

        return $chart;
    }
}