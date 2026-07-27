<?php

namespace App\Filament\Widgets;

use App\Models\CustomerSearchActivity;
use Filament\Widgets\ChartWidget;

class CustomerLeadConversionFunnelWidget extends ChartWidget
{
    protected static ?string $heading = 'Lead Conversion Funnel';

    protected static ?string $description =
        'Search to checkout, payment and successful conversion.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected static ?string $maxHeight = '320px';

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $searches = CustomerSearchActivity::query()->count();

        $checkoutStarted = CustomerSearchActivity::query()
            ->whereNotNull('checkout_started_at')
            ->count();

        $paymentStarted = CustomerSearchActivity::query()
            ->whereNotNull('payment_started_at')
            ->count();

        $converted = CustomerSearchActivity::query()
            ->converted()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Customers',
                    'data' => [
                        $searches,
                        $checkoutStarted,
                        $paymentStarted,
                        $converted,
                    ],
                ],
            ],
            'labels' => [
                'Searches',
                'Checkout Started',
                'Payment Started',
                'Converted',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
