<?php

namespace App\Providers\Filament;

use App\Filament\Resources\TransporterProfileResource;
use App\Filament\Resources\VehicleResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\SelfDriveBookingResource;


class TransporterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('transporter')
            ->path('transporter')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            
			
			->resources([
				TransporterProfileResource::class,
				VehicleResource::class,
				SelfDriveBookingResource::class,
			])
            ->discoverWidgets(
                in: app_path('Filament/Transporter/Widgets'),
                for: 'App\\Filament\\Transporter\\Widgets'
            )
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}