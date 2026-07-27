<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class OrderStates extends BaseWidget
{
    protected function getStats(): array
    {
        return [
           
            auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('New Bookings', Order::query()->count()) : '',
            auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings start', Order::query()->where('status',"start")->count()): '',
            auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings modification', Order::query()->where('status',"modification")->count()): '',
            auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings confirm', Order::query()->where('status',"confirm")->count()): '',
            auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings reconfirmation', Order::query()->where('status',"reconfirmation")->count()): '',
            auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings cancelled', Order::query()->where('status',"cancelled")->count()): '',
            auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings closed', Order::query()->where('status',"closed")->count()): '',
            auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings refund', Order::query()->where('status',"refund")->count()): '',

            // auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings Procesing', Order::query()->where('status',"start")->where('transporter_id', auth()->id())->count()): '',
            // auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings Confirm', Order::query()->where('status',"confirm")->where('transporter_id', auth()->id())->count()): '',
            // auth()->user()->roles->pluck('name')[0] === "Admin" ? Stat::make('Bookings Closed', Order::query()->where('status',"closed")->where('transporter_id', auth()->id())->count()): '',
            
            //Stat::make('Average Price', Number::currency(Order::query()->avg('grand_total'), 'INR'))
        ];
    }
}
