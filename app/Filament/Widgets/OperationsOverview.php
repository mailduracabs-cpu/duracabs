<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SelfDriveBookingResource;
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
        |--------------------------------------------------------------------------
        | TODAY WITH DRIVER BOOKINGS
        |--------------------------------------------------------------------------
        */
        $todayOrders = Order::query()
            ->whereDate('date', $today->toDateString())
            ->whereNotIn('status', [
                'cancelled',
                'closed',
                'refund',
            ]);

        $todayBookings = (clone $todayOrders)->count();

        $todayBookingValue = (float) (clone $todayOrders)
            ->sum('grand_total');

        /*
        |--------------------------------------------------------------------------
        | PENDING COLLECTION
        |--------------------------------------------------------------------------
        */
        $todayOrdersForPayment = (clone $todayOrders)->get();

        $pendingCollection = $todayOrdersForPayment
            ->sum(function (Order $order): float {
                return (float) $order->remaining_amount;
            });

        $pendingPaymentBookings = $todayOrdersForPayment
            ->filter(function (Order $order): bool {
                return (float) $order->remaining_amount > 0;
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | UNASSIGNED WITH DRIVER BOOKINGS TODAY
        |--------------------------------------------------------------------------
        */
        $unassignedToday = (clone $todayOrders)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('driver_id')
                    ->orWhereNull('vehicle_id');
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | ACTIVE WITH DRIVER TRIPS
        |--------------------------------------------------------------------------
        */
        $activeWithDriverTrips = Order::query()
            ->where('status', 'start')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | ACTIVE SELF DRIVE TRIPS
        |
        | Running status is used here so clicking the card and opening
        | the "Running" filter shows the exact same bookings.
        |--------------------------------------------------------------------------
        */
        $activeSelfDriveTrips = SelfDriveBooking::query()
            ->where('status', 'running')
            ->count();

        $activeTrips = $activeWithDriverTrips + $activeSelfDriveTrips;

        /*
        |--------------------------------------------------------------------------
        | AVAILABLE SELF DRIVE CARS RIGHT NOW
        |--------------------------------------------------------------------------
        */
        $availableSelfDriveCars = Vehicle::query()
            ->where('service_type', Vehicle::SERVICE_SELF_DRIVE)
            ->where('vehicle_type', Vehicle::TYPE_CAR)
            ->where('is_active', true)
            ->where('is_live', true)
            ->where('is_verified', true)
            ->whereDoesntHave(
                'selfDriveBookings',
                function (Builder $query) use ($now): void {
                    $query
                        ->activeBooking()
                        ->where('start_datetime', '<=', $now)
                        ->where('end_datetime', '>', $now);
                }
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | UPCOMING SELF DRIVE BOOKINGS
        |--------------------------------------------------------------------------
        */
        $upcomingSelfDriveBookings = SelfDriveBooking::query()
            ->activeBooking()
            ->where('start_datetime', '>', $now)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | ACTIVE SELF DRIVE BOOKINGS URL
        |--------------------------------------------------------------------------
        |
        | Opens:
        | Self Drive Bookings -> Status = Running
        |
        */
        $activeSelfDriveUrl = SelfDriveBookingResource::getUrl(
            'index',
            [
                'tableFilters' => [
                    'status' => [
                        'value' => 'running',
                    ],
                ],
            ]
        );

        return [

            /*
            |--------------------------------------------------------------------------
            | TODAY BOOKINGS
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Today Bookings',
                number_format($todayBookings)
            )
                ->description('With Driver trips scheduled today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->icon('heroicon-o-calendar-days')
                ->color(
                    $todayBookings > 0
                        ? 'primary'
                        : 'gray'
                ),

            /*
            |--------------------------------------------------------------------------
            | TODAY BOOKING VALUE
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Today Booking Value',
                '₹' . number_format($todayBookingValue, 0)
            )
                ->description('Scheduled booking value today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->icon('heroicon-o-currency-rupee')
                ->color('success'),

            /*
            |--------------------------------------------------------------------------
            | PENDING COLLECTION
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Pending Collection',
                '₹' . number_format($pendingCollection, 0)
            )
                ->description(
                    number_format($pendingPaymentBookings)
                    . ' booking(s) with balance'
                )
                ->descriptionIcon('heroicon-m-credit-card')
                ->icon('heroicon-o-credit-card')
                ->color(
                    $pendingCollection > 0
                        ? 'warning'
                        : 'success'
                ),

            /*
            |--------------------------------------------------------------------------
            | AVAILABLE SELF DRIVE
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Available Self Drive',
                number_format($availableSelfDriveCars)
            )
                ->description(
                    number_format($upcomingSelfDriveBookings)
                    . ' upcoming booking(s)'
                )
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-truck')
                ->color(
                    $availableSelfDriveCars > 0
                        ? 'success'
                        : 'danger'
                ),

            /*
            |--------------------------------------------------------------------------
            | ACTIVE TRIPS - CLICKABLE
            |--------------------------------------------------------------------------
            */
            Stat::make(
                'Active Trips',
                number_format($activeTrips)
            )
                ->description(
                    number_format($activeWithDriverTrips)
                    . ' driver + '
                    . number_format($activeSelfDriveTrips)
                    . ' self drive'
                )
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-map')
                ->color(
                    $activeTrips > 0
                        ? 'info'
                        : 'gray'
                )
                ->url(
                    $activeSelfDriveTrips > 0
                        ? $activeSelfDriveUrl
                        : null
                ),

            /*
            |--------------------------------------------------------------------------
            | UNASSIGNED TODAY
            |--------------------------------------------------------------------------
            */
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
                ->icon('heroicon-o-exclamation-triangle')
                ->color(
                    $unassignedToday > 0
                        ? 'danger'
                        : 'success'
                ),
        ];
    }
}