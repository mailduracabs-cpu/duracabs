<?php

namespace App\Filament\Resources\AppMediaResource\Widgets;

use App\Models\AppMedia;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MediaStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval =
        null;

    protected function getStats(): array
    {
        $total = AppMedia::withTrashed()
            ->count();

        $active = AppMedia::query()
            ->where('is_active', true)
            ->count();

        $unused = AppMedia::query()
            ->where('reference_count', 0)
            ->whereDoesntHave('usages')
            ->count();

        $originalBytes = (int) AppMedia::withTrashed()
            ->sum('original_size');

        $optimizedBytes = (int) AppMedia::withTrashed()
            ->sum('optimized_size');

        $savedBytes = max(
            0,
            $originalBytes -
                $optimizedBytes
        );

        $savedPercentage = $originalBytes > 0
            ? round(
                ($savedBytes /
                    $originalBytes) * 100,
                2
            )
            : 0;

        return [
            Stat::make(
                'Total Media',
                number_format($total)
            )
                ->description(
                    number_format($active)
                    . ' active files'
                )
                ->descriptionIcon(
                    'heroicon-m-photo'
                )
                ->color('primary'),

            Stat::make(
                'Storage Used',
                $this->formatBytes(
                    $originalBytes
                )
            )
                ->description(
                    'Original uploaded files'
                )
                ->descriptionIcon(
                    'heroicon-m-circle-stack'
                )
                ->color('info'),

            Stat::make(
                'Storage Saved',
                $this->formatBytes(
                    $savedBytes
                )
            )
                ->description(
                    number_format(
                        $savedPercentage,
                        2
                    )
                    . '% reduction'
                )
                ->descriptionIcon(
                    'heroicon-m-arrow-trending-down'
                )
                ->color('success'),

            Stat::make(
                'Unused Media',
                number_format($unused)
            )
                ->description(
                    'Files without references'
                )
                ->descriptionIcon(
                    'heroicon-m-trash'
                )
                ->color(
                    $unused > 0
                        ? 'warning'
                        : 'success'
                ),
        ];
    }

    private function formatBytes(
        int $bytes,
    ): string {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $power = min(
            (int) floor(
                log($bytes, 1024)
            ),
            count($units) - 1
        );

        return number_format(
            $bytes / (1024 ** $power),
            $power === 0 ? 0 : 2
        ) . ' ' . $units[$power];
    }
}