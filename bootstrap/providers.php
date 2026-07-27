<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\TransporterPanelProvider::class,
    App\Providers\VoltServiceProvider::class,
    App\Providers\SeoServiceProvider::class,
	App\SEO\Providers\SeoAiServiceProvider::class,
];