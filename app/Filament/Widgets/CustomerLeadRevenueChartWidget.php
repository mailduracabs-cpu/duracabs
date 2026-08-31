<?php

namespace App\Filament\Widgets;

use App\Models\CustomerSearchActivity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CustomerLeadRevenueChartWidget extends ChartWidget
{
	protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Converted Revenue';

    protected static ?string $description =
        'Revenue from converted customer leads during the last 30 days.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected static ?string $maxHeight = '320px';

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $startDate = now()->subDays(29)->startOfDay();

        $revenueByDate = CustomerSearchActivity::query()
            ->converted()
            ->whereNotNull('converted_at')
            ->where('converted_at', '>=', $startDate)
            ->selectRaw('DATE(converted_at) as conversion_date')
            ->selectRaw('SUM(COALESCE(grand_total, 0)) as total_revenue')
            ->groupBy('conversion_date')
            ->pluck('total_revenue', 'conversion_date');

        $labels = [];
        $data = [];

        for ($day = 29; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $dateKey = $date->toDateString();

            $labels[] = $date->format('d M');
            $data[] = (float) ($revenueByDate[$dateKey] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data,
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return "₹" + Number(context.raw).toLocaleString("en-IN");
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return "₹" + Number(value).toLocaleString("en-IN");
                        }',
                    ],
                ],
            ],
        ];
    }
}
