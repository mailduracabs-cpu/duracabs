<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\SelfDriveBooking;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OperationsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $now = now();
        $today = Carbon::today();

        /*
         * WITH DRIVER - TODAY
         *
         * Trip date is used, not created_at.
         */
        $todayOrders = Order::query()
            ->whereDate('date', $today)
            ->whereNotIn('status', [
                'cancelled',
                'closed',
                'refund',
            ]);

        $todayBookings = (clone $todayOrders)->count();

        $todayBookingValue = (float) (clone $todayOrders)
            ->sum('grand_total');

        /*
         * PENDING COLLECTION
         *
         * Use the Order model's paid_amount / remaining_amount
         * accessors so manual corrected amounts in extraOptions
         * are respected.
         */
        $pendingCollection = (clone $todayOrders)
            ->get()
            ->sum(
                fn (Order $order): float =>
                    (float) $order->remaining_amount
            );

        $pendingPaymentBookings = (clone $todayOrders)
            ->get()
            ->filter(
                fn (Order $order): bool =>
                    (float) $order->remaining_amount > 0
            )
            ->count();

        /*
         * UNASSIGNED TODAY
         *
         * A booking needs attention when driver or vehicle
         * has not been assigned.
         */
        $unassignedToday = (clone $todayOrders)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('driver_id')
                    ->orWhereNull('vehicle_id');
            })
            ->count();

        /*
         * ACTIVE WITH DRIVER TRIPS
         */
        $activeWithDriverTrips = Order::query()
            ->where('status', 'start')
            ->count();

        /*
         * ACTIVE SELF DRIVE TRIPS
         *
         * Booking window covers the current date/time.
         */
        $activeSelfDriveTrips = SelfDriveBooking::query()
            ->activeBooking()
            ->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>', $now)
            ->count();

        $activeTrips = $activeWithDriverTrips
            + $activeSelfDriveTrips;

        /*
         * AVAILABLE SELF DRIVE CARS RIGHT NOW
         */
        $availableSelfDriveCars = Vehicle::query()
            ->where(
                'service_type',
                Vehicle::SERVICE_SELF_DRIVE
            )
            ->where(
                'vehicle_type',
                Vehicle::TYPE_CAR
            )
            ->where('is_active', true)
            ->where('is_live', true)
            ->where('is_verified', true)
            ->whereDoesntHave(
                'selfDriveBookings',
                function (Builder $query) use ($now): void {
                    $query
                        ->activeBooking()
                        ->where(
                            'start_datetime',
                            '<=',
                            $now
                        )
                        ->where(
                            'end_datetime',
                            '>',
                            $now
                        );
                }
            )
            ->count();

        /*
         * UPCOMING SELF DRIVE BOOKINGS
         */
        $upcomingSelfDriveBookings =
            SelfDriveBooking::query()
                ->activeBooking()
                ->where('start_datetime', '>', $now)
                ->count();

        return [
            Stat::make(
                'Today Bookings',
                number_format($todayBookings)
            )
                ->description(
                    'With Driver trips scheduled today'
                )
                ->descriptionIcon(
                    'heroicon-m-calendar-days'
                )
                ->icon('heroicon-o-calendar-days')
                ->color(
                    $todayBookings > 0
                        ? 'primary'
                        : 'gray'
                ),

            Stat::make(
                'Today Booking Value',
                '₹' . number_format(
                    $todayBookingValue,
                    0
                )
            )
                ->description(
                    'Scheduled booking value today'
                )
                ->descriptionIcon(
                    'heroicon-m-banknotes'
                )
                ->icon(
                    'heroicon-o-currency-rupee'
                )
                ->color('success'),

            Stat::make(
                'Pending Collection',
                '₹' . number_format(
                    $pendingCollection,
                    0
                )
            )
                ->description(
                    number_format(
                        $pendingPaymentBookings
                    )
                    . ' booking(s) with balance'
                )
                ->descriptionIcon(
                    'heroicon-m-credit-card'
                )
                ->icon('heroicon-o-credit-card')
                ->color(
                    $pendingCollection > 0
                        ? 'warning'
                        : 'success'
                ),

            Stat::make(
                'Available Self Drive',
                number_format(
                    $availableSelfDriveCars
                )
            )
                ->description(
                    $upcomingSelfDriveBookings
                    . ' upcoming booking(s)'
                )
                ->descriptionIcon(
                    'heroicon-m-check-circle'
                )
                ->icon('heroicon-o-truck')
                ->color(
                    $availableSelfDriveCars > 0
                        ? 'success'
                        : 'danger'
                ),

            Stat::make(
                'Active Trips',
                number_format($activeTrips)
            )
                ->description(
                    $activeWithDriverTrips
                    . ' driver + '
                    . $activeSelfDriveTrips
                    . ' self drive'
                )
                ->descriptionIcon(
                    'heroicon-m-arrow-trending-up'
                )
                ->icon('heroicon-o-map')
                ->color(
                    $activeTrips > 0
                        ? 'info'
                        : 'gray'
                ),

            Stat::make(
                'Unassigned Today',
                number_format($unassignedToday)
            )
                ->description(
                    $unassignedToday > 0
                        ? 'Driver / vehicle required'
                        : 'All assigned'
                )
                ->descriptionIcon(
                    $unassignedToday > 0
                        ? 'heroicon-m-exclamation-triangle'
                        : 'heroicon-m-check-circle'
                )
                ->icon(
                    'heroicon-o-exclamation-triangle'
                )
                ->color(
                    $unassignedToday > 0
                        ? 'danger'
                        : 'success'
                ),
        ];
    }
}