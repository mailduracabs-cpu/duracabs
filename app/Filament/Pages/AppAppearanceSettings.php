<?php

namespace App\Filament\Pages;

use App\Services\AppAppearanceService;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AppAppearanceSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'App Appearance & Offers';
    protected static ?string $navigationGroup = 'App Settings';
    protected static ?string $title = 'App Appearance & Offers';
    protected static string $view = 'filament.pages.app-appearance-settings';
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    public function mount(AppAppearanceService $appearance): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill($appearance->settings());
    }

    private function imageField(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)->label($label)->disk('public')
            ->directory('app/appearance')->visibility('public')->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(4096)
            ->helperText('JPG, PNG or WebP, up to 4 MB. Offer: portrait image; festival artwork: landscape image.');
    }

    public function form(Form $form): Form
    {
        $timezone = config('app.timezone');
        return $form->statePath('data')->schema([
            Section::make('Festival theme')->description('User Light/Dark choice stays respected. Dates use '.$timezone.'. Empty dates mean active until disabled.')
                ->schema([
                    Toggle::make('theme_enabled')->label('Enable festival theme')->default(false),
                    Select::make('festival')->options(['normal' => 'Normal', 'diwali' => 'Diwali', 'holi' => 'Holi', 'custom' => 'Custom'])
                        ->required()->live()->afterStateUpdated(function (string $state, Set $set): void {
                            foreach (AppAppearanceService::presets()[$state] ?? [] as $key => $value) $set($key, $value);
                        }),
                    ColorPicker::make('primary_color')->label('Primary colour')->required()->regex('/^#[0-9a-fA-F]{6}$/'),
                    ColorPicker::make('accent_color')->label('Accent colour')->required()->regex('/^#[0-9a-fA-F]{6}$/'),
                    $this->imageField('theme_image', 'Festival artwork'),
                    DateTimePicker::make('theme_starts_at')->label('Theme starts')->seconds(false),
                    DateTimePicker::make('theme_ends_at')->label('Theme ends')->seconds(false),
                ])->columns(2),
            Section::make('Startup offer')->description('Shown on every fresh app launch; Skip and Continue stay available. No image means no offer screen.')
                ->schema([
                    Toggle::make('offer_enabled')->label('Enable startup offer')->default(false),
                    TextInput::make('offer_title')->label('Offer heading')->maxLength(100),
                    $this->imageField('offer_image', 'Offer image')->required(fn (\Filament\Forms\Get $get): bool => (bool) $get('offer_enabled')),
                    DateTimePicker::make('offer_starts_at')->label('Offer starts')->seconds(false),
                    DateTimePicker::make('offer_ends_at')->label('Offer ends')->seconds(false),
                ])->columns(2),
            Section::make('Home offer slider')
                ->description('These banners appear in the top slider of the customer app. Drag to change their order.')
                ->schema([
                    Repeater::make('home_banners')
                        ->label('Slider banners')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New banner')
                        ->addActionLabel('Add offer banner')
                        ->schema([
                            Toggle::make('enabled')->label('Active')->default(true),
                            TextInput::make('tag')->label('Small label')->default('Offer')->maxLength(30),
                            TextInput::make('title')->label('Heading')->required()->maxLength(100),
                            TextInput::make('subtitle')->label('Banner text')->maxLength(180),
                            $this->imageField('image', 'Banner image')->required(),
                            TextInput::make('button_label')->label('Button text')->default('Book now')->maxLength(40),
                            Select::make('redirect_type')->label('Open on click')->required()->default('none')->live()->options([
                                'none' => 'No action',
                                'one_way' => 'One Way booking',
                                'round_trip' => 'Round Trip booking',
                                'local_taxi' => 'Local Taxi booking',
                                'self_drive' => 'Self Drive',
                                'bike_rental' => 'Bike Rental',
                                'tours' => 'Tours',
                                'services' => 'All Services',
                                'bookings' => 'My Bookings',
                                'profile' => 'Profile',
                                'external_url' => 'Website URL',
                            ]),
                            TextInput::make('redirect_value')->label('Website URL')->url()->maxLength(500)
                                ->visible(fn (\Filament\Forms\Get $get): bool => $get('redirect_type') === 'external_url'),
                        ])->columns(2)->columnSpanFull(),
                ]),
        ]);
    }

    public function save(AppAppearanceService $appearance): void
    {
        abort_unless(static::canAccess(), 403);
        $appearance->save($this->form->getState());
        Notification::make()->title('App appearance saved')->body('App refreshes settings on launch, resume and every minute while open.')->success()->send();
    }
}
