<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\SelfDriveBookingResource;
use App\Filament\Resources\UserResource;
use App\Models\Order;
use App\Models\SelfDriveBooking;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\WithPagination;
use Throwable;

class CustomerBookings extends Page
{
    use WithPagination;

    protected static string $resource = UserResource::class;

    protected static string $view =
        'filament.resources.user-resource.pages.customer-bookings';

    public User $record;

    public string $search = '';

    public string $bookingType = 'all';

    public string $bookingStatus = 'all';

    public int $perPage = 15;

    protected $queryString = [
        'search' => [
            'except' => '',
        ],
        'bookingType' => [
            'except' => 'all',
        ],
        'bookingStatus' => [
            'except' => 'all',
        ],
    ];

 public function mount(): void
{
    $authenticatedUser = Auth::user();

    abort_unless($authenticatedUser, 403);

    $routeUser = request()->route('user')
        ?? request()->route('record');

    abort_unless($routeUser, 404);

    if ($routeUser instanceof User) {
        $this->record = $routeUser->loadMissing('roles');
    } else {
        $this->record = User::query()
            ->with('roles')
            ->findOrFail($routeUser);
    }

    $this->authorizePageAccess();
}

    public function getTitle(): string
    {
        $customerName = $this->record->name
            ?: $this->record->mobile
            ?: 'Customer';

        return 'All Bookings - ' . $customerName;
    }

    public function getSubheading(): ?string
    {
        return implode(' | ', [
            $this->record->mobile ?: 'No mobile number',
            $this->record->email ?: 'No email address',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit_customer')
                ->label('Edit Customer')
                ->icon('heroicon-o-pencil-square')
                ->url(
                    UserResource::getUrl(
                        'edit',
                        [
                            'record' => $this->record->id,
                        ]
                    )
                ),

            Actions\Action::make('back_to_users')
                ->label('Back to Users')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(
                    UserResource::getUrl('index')
                ),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBookingType(): void
    {
        $this->resetPage();
    }

    public function updatedBookingStatus(): void
    {
        $this->resetPage();
    }

    public function getBookingsProperty(): LengthAwarePaginator
    {
        $bookings = $this->loadCombinedBookings();

        $filteredBookings = $this->applyFilters($bookings)
            ->sortByDesc('sort_date')
            ->values();

        $currentPage = $this->getPage();

        return new LengthAwarePaginator(
            $filteredBookings
                ->forPage($currentPage, $this->perPage)
                ->values(),
            $filteredBookings->count(),
            $this->perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function getStatsProperty(): array
    {
        $bookings = $this->loadCombinedBookings();

        return [
            'total' => $bookings->count(),

            'taxi' => $bookings
                ->where('source', 'order')
                ->count(),

            'self_drive' => $bookings
                ->where('source', 'self_drive')
                ->count(),

            'running' => $bookings
                ->filter(
                    fn (array $booking): bool => in_array(
                        $booking['status_key'],
                        [
                            'start',
                            'running',
                            'return_pending',
                        ],
                        true
                    )
                )
                ->count(),

            'completed' => $bookings
                ->filter(
                    fn (array $booking): bool => in_array(
                        $booking['status_key'],
                        [
                            'closed',
                            'completed',
                        ],
                        true
                    )
                )
                ->count(),

            'cancelled' => $bookings
                ->filter(
                    fn (array $booking): bool => in_array(
                        $booking['status_key'],
                        [
                            'cancelled',
                            'rejected',
                            'failed',
                        ],
                        true
                    )
                )
                ->count(),

            'revenue' => (float) $bookings->sum('amount'),
        ];
    }

    public function deleteBooking(
        string $source,
        int $bookingId
    ): void {
        $this->authorizePageAccess();

        if (! Auth::user()?->hasRole('Admin')) {
            Notification::make()
                ->title('Delete permission denied')
                ->body('Only administrators can delete bookings.')
                ->danger()
                ->send();

            return;
        }

        try {
            DB::transaction(
                function () use ($source, $bookingId): void {
                    match ($source) {
                        'order' => $this->deleteOrder($bookingId),
                        'self_drive' =>
                            $this->deleteSelfDriveBooking($bookingId),
                        default => abort(404),
                    };
                }
            );

            $this->resetPage();

            Notification::make()
                ->title('Booking deleted')
                ->body('The booking was deleted successfully.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Unable to delete booking')
                ->body(
                    'The booking may be linked to payment, invoice, or operational records.'
                )
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private function loadCombinedBookings(): Collection
    {
        $orders = Order::query()
            ->with([
                'vehicle',
                'address',
            ])
            ->where(
                'user_id',
                $this->record->id
            )
            ->get()
            ->map(
                fn (Order $order): array =>
                    $this->normaliseOrder($order)
            );

        $selfDriveBookings = SelfDriveBooking::query()
            ->with([
                'vehicle',
                'transporter',
            ])
            ->where(
                'customer_id',
                $this->record->id
            )
            ->get()
            ->map(
                fn (SelfDriveBooking $booking): array =>
                    $this->normaliseSelfDriveBooking($booking)
            );

        return $orders
            ->concat($selfDriveBookings)
            ->values();
    }

    private function normaliseOrder(Order $order): array
    {
        $typeKey = strtolower(
            trim(
                (string) ($order->ride_type ?: 'local')
            )
        );

        $pickup = $order->address?->pickup_address
            ?: $order->booking_from
            ?: $order->cityFrom
            ?: 'Not available';

        $drop = $order->address?->drop_address
            ?: $order->booking_to
            ?: $order->cityTo
            ?: 'Not available';

        $vehicleName = $this->resolveVehicleName(
            companyName: $order->vehicle?->car_company_name,
            modelName: $order->vehicle?->model_name,
            registrationNumber: $order->vehicle?->vehicle_number,
            fallbackName: $order->productName
        );

        return [
            'key' => 'order-' . $order->id,
            'source' => 'order',
            'id' => (int) $order->id,
            'booking_no' => 'DC-' . $order->id,
            'type_key' => $typeKey,
            'type_label' => $this->bookingTypeLabel($typeKey),
            'vehicle' => $vehicleName,
            'pickup' => $pickup,
            'drop' => $drop,
            'booking_date' => $order->date
                ?: $order->created_at?->format('Y-m-d'),
            'booking_time' => $order->time,
            'amount' => (float) ($order->grand_total ?? 0),
            'payment_status' =>
                strtolower(
                    (string) ($order->payment_status ?: 'pending')
                ),
            'status_key' =>
                strtolower(
                    (string) ($order->status ?: 'new')
                ),
            'status_label' =>
                $this->normalOrderStatusLabel($order->status),
            'created_at' => $order->created_at,
            'sort_date' =>
                $order->created_at?->timestamp ?? 0,
            'view_url' =>
                $this->normalOrderViewUrl($order),
            'edit_url' =>
                OrderResource::getUrl(
                    'edit',
                    [
                        'record' => $order->id,
                    ]
                ),
        ];
    }

    private function normaliseSelfDriveBooking(
        SelfDriveBooking $booking
    ): array {
        $vehicleName = $this->resolveVehicleName(
            companyName: $booking->vehicle?->car_company_name,
            modelName: $booking->vehicle?->model_name,
            registrationNumber:
                $booking->vehicle?->vehicle_number
        );

        return [
            'key' => 'self-drive-' . $booking->id,
            'source' => 'self_drive',
            'id' => (int) $booking->id,
            'booking_no' =>
                $booking->booking_no ?: 'SD-' . $booking->id,
            'type_key' => 'self_drive',
            'type_label' => 'Self Drive',
            'vehicle' => $vehicleName,
            'pickup' =>
                $booking->pickup_location ?: 'Not available',
            'drop' =>
                $booking->pickup_location ?: 'Same location',
            'booking_date' =>
                $booking->start_datetime?->format('Y-m-d'),
            'booking_time' =>
                $booking->start_datetime?->format('h:i A'),
            'amount' => (float) (
                $booking->final_amount
                ?: $booking->total_amount
                ?: 0
            ),
            'payment_status' =>
                strtolower(
                    (string) (
                        $booking->payment_status ?: 'pending'
                    )
                ),
            'status_key' =>
                strtolower(
                    (string) ($booking->status ?: 'pending')
                ),
            'status_label' =>
                $this->selfDriveStatusLabel($booking->status),
            'created_at' => $booking->created_at,
            'sort_date' =>
                $booking->created_at?->timestamp ?? 0,
            'view_url' =>
                SelfDriveBookingResource::getUrl(
                    'edit',
                    [
                        'record' => $booking->id,
                    ]
                ),
            'edit_url' =>
                SelfDriveBookingResource::getUrl(
                    'edit',
                    [
                        'record' => $booking->id,
                    ]
                ),
        ];
    }

    private function applyFilters(
        Collection $bookings
    ): Collection {
        if ($this->bookingType !== 'all') {
            $bookings = $bookings->where(
                'type_key',
                $this->bookingType
            );
        }

        if ($this->bookingStatus !== 'all') {
            $selectedStatus = $this->bookingStatus;

            $bookings = $bookings->filter(
                fn (array $booking): bool =>
                    $this->matchesStatusFilter(
                        $booking['status_key'],
                        $selectedStatus
                    )
            );
        }

        $searchTerm = strtolower(
            trim($this->search)
        );

        if ($searchTerm !== '') {
            $bookings = $bookings->filter(
                function (array $booking) use (
                    $searchTerm
                ): bool {
                    $searchableContent = strtolower(
                        implode(
                            ' ',
                            [
                                $booking['booking_no'],
                                $booking['type_label'],
                                $booking['vehicle'],
                                $booking['pickup'],
                                $booking['drop'],
                                $booking['payment_status'],
                                $booking['status_label'],
                            ]
                        )
                    );

                    return str_contains(
                        $searchableContent,
                        $searchTerm
                    );
                }
            );
        }

        return $bookings;
    }

    private function matchesStatusFilter(
        string $status,
        string $selectedStatus
    ): bool {
        return match ($selectedStatus) {
            'running' => in_array(
                $status,
                [
                    'start',
                    'running',
                    'return_pending',
                ],
                true
            ),

            'completed' => in_array(
                $status,
                [
                    'closed',
                    'completed',
                ],
                true
            ),

            'cancelled' => in_array(
                $status,
                [
                    'cancelled',
                    'rejected',
                    'failed',
                ],
                true
            ),

            'confirmed' => in_array(
                $status,
                [
                    'confirm',
                    'confirmed',
                    'pickup_pending',
                ],
                true
            ),

            'pending' => in_array(
                $status,
                [
                    'new',
                    'pending',
                    'payment_pending',
                    'reconfirmation',
                ],
                true
            ),

            default => $status === $selectedStatus,
        };
    }

    private function deleteOrder(
        int $bookingId
    ): void {
        $order = Order::query()
            ->where(
                'user_id',
                $this->record->id
            )
            ->findOrFail($bookingId);

        $order->delete();
    }

    private function deleteSelfDriveBooking(
        int $bookingId
    ): void {
        $booking = SelfDriveBooking::query()
            ->where(
                'customer_id',
                $this->record->id
            )
            ->findOrFail($bookingId);

        $booking->delete();
    }

    private function normalOrderViewUrl(
        Order $order
    ): string {
        if (Route::has('my-orders.show')) {
            return route(
                'my-orders.show',
                [
                    'order_id' => $order->id,
                ]
            );
        }

        if (Route::has('success')) {
            return route(
                'success',
                [
                    'id' => $order->id,
                ]
            );
        }

        return OrderResource::getUrl(
            'edit',
            [
                'record' => $order->id,
            ]
        );
    }

    private function resolveVehicleName(
        ?string $companyName,
        ?string $modelName,
        ?string $registrationNumber,
        ?string $fallbackName = null
    ): string {
        $vehicleName = trim(
            implode(
                ' ',
                array_filter([
                    $companyName,
                    $modelName,
                ])
            )
        );

        if ($vehicleName !== '') {
            return $vehicleName;
        }

        if (filled($fallbackName)) {
            return (string) $fallbackName;
        }

        if (filled($registrationNumber)) {
            return (string) $registrationNumber;
        }

        return 'Not assigned';
    }

    private function bookingTypeLabel(
        string $type
    ): string {
        return match ($type) {
            'one_way' => 'One Way',
            'return', 'round_trip' => 'Round Trip',
            'local' => 'Local',
            'airport' => 'Airport',
            'tour' => 'Tour',
            'self_drive' => 'Self Drive',
            default => ucwords(
                str_replace(
                    '_',
                    ' ',
                    $type
                )
            ),
        };
    }

    private function normalOrderStatusLabel(
        mixed $status
    ): string {
        return match (
            strtolower(
                (string) $status
            )
        ) {
            'new' => 'Waiting Response',
            'reconfirmation' => 'Reconfirmation',
            'confirm' => 'Confirmed',
            'modification' => 'Modification',
            'start' => 'Running',
            'cancelled' => 'Cancelled',
            'closed' => 'Completed',
            'refund' => 'Refund',
            default => ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $status
                )
            ),
        };
    }

    private function selfDriveStatusLabel(
        mixed $status
    ): string {
        return match (
            strtolower(
                (string) $status
            )
        ) {
            'pending' => 'Pending',
            'payment_pending' => 'Payment Pending',
            'confirmed' => 'Confirmed',
            'pickup_pending' => 'Pickup Pending',
            'running' => 'Running',
            'return_pending' => 'Return Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            'failed' => 'Failed',
            default => ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $status
                )
            ),
        };
    }

    private function authorizePageAccess(): void
    {
        $authenticatedUser = Auth::user();

        abort_unless(
            $authenticatedUser,
            403
        );

        if ($authenticatedUser->hasRole('Admin')) {
            return;
        }

        if (
            $authenticatedUser->hasRole('Transporter') &&
            (
                (int) $this->record->id ===
                    (int) $authenticatedUser->id ||
                (int) $this->record->created_by ===
                    (int) $authenticatedUser->id
            )
        ) {
            return;
        }

        abort(403);
    }
}