<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class WhatsAppLiveImport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'WhatsApp Live Import';
    protected static ?string $navigationGroup = 'Partner Marketplace';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'whatsapp-live-import';
    protected static string $view = 'filament.pages.whatsapp-live-import';
    protected static ?string $title = 'WhatsApp Live Booking Import';

    public static function canAccess(): bool
    {
        $user = Auth::guard('admin')->user();

        return $user instanceof User && $user->canUseAdminLogin();
    }
}
