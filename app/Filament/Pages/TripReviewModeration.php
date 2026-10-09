<?php
namespace App\Filament\Pages;

use App\Models\TripAccountRestriction;
use App\Services\TripReviewService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

class TripReviewModeration extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationLabel = 'Trip Reviews & Restrictions';
    protected static ?string $navigationGroup = 'App Settings';
    protected static string $view = 'filament.pages.trip-review-moderation';
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }
    public function table(Table $table): Table
    {
        return $table->query(TripAccountRestriction::query()->with(['user', 'review.reviewer'])->orderByDesc('id'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Restricted account')->searchable(),
                Tables\Columns\TextColumn::make('user.mobile')->label('Mobile')->searchable(),
                Tables\Columns\TextColumn::make('review.reviewer.name')->label('Reviewed by'),
                Tables\Columns\TextColumn::make('review.rating')->label('Stars'),
                Tables\Columns\TextColumn::make('review.trip_type')->label('Service'),
                Tables\Columns\TextColumn::make('review.trip_id')->label('Booking ID'),
                Tables\Columns\TextColumn::make('review.comment')->label('Reason')->wrap()->limit(150)->tooltip(fn ($record) => $record->review?->comment),
                Tables\Columns\TextColumn::make('created_at')->label('Blocked since')->dateTime(),
                Tables\Columns\TextColumn::make('resolved_at')->label('Reactivated')->dateTime()->placeholder('Blocked'),
                Tables\Columns\TextColumn::make('resolution_note')->label('Admin resolution')->wrap()->limit(100),
            ])->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Currently blocked')->default(true)
                    ->queries(true: fn ($query) => $query->whereNull('resolved_at'), false: fn ($query) => $query->whereNotNull('resolved_at'), blank: fn ($query) => $query),
            ])->actions([
                Tables\Actions\Action::make('reactivate')->label('Reactivate account')->icon('heroicon-o-check-circle')
                    ->visible(fn ($record): bool => static::canAccess() && $record->resolved_at === null)
                    ->requiresConfirmation()->modalDescription('This resolves ALL currently active 1-star restrictions on this account. Review history and averages remain. A new 1-star review will block it again.')
                    ->form([Textarea::make('note')->label('Resolution reason')->required()->minLength(5)->maxLength(2000)])
                    ->action(function (TripAccountRestriction $record, array $data): void {
                        abort_unless(static::canAccess(), 403);
                        app(TripReviewService::class)->reactivate(Filament::auth()->user(), (int) $record->user_id, $data['note']);
                        Notification::make()->title('Account reactivated')->success()->send();
                    }),
            ])->defaultPaginationPageOption(25);
    }
}
