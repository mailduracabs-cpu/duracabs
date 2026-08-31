<?php

namespace App\Filament\Widgets;

use App\Models\CustomerSearchActivity;
use Filament\Widgets\ChartWidget;

class CustomerLeadServiceBreakdownWidget extends ChartWidget
{
	protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Lead Service Breakdown';

    protected static ?string $description =
        'Customer demand across Taxi, Self Drive and Bike Rental.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected static ?string $maxHeight = '320px';

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $counts = CustomerSearchActivity::query()
            ->selectRaw('module, COUNT(*) as total')
            ->groupBy('module')
            ->pluck('total', 'module');

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => [
                        (int) ($counts['taxi'] ?? 0),
                        (int) ($counts['self_drive'] ?? 0),
                        (int) ($counts['bike_rental'] ?? 0),
                        (int) ($counts['website'] ?? 0),
                    ],
                ],
            ],
            'labels' => [
                'Taxi',
                'Self Drive',
                'Bike Rental',
                'Website',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
