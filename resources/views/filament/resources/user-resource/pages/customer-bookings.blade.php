<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Customer Card --}}
        <x-filament::section>
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2
                        class="text-xl font-bold text-gray-950 dark:text-white"
                    >
                        {{ $this->record->name ?: 'No Name' }}
                    </h2>

                    <div
                        class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500"
                    >
                        <span>
                            Mobile:
                            {{ $this->record->mobile ?: 'Not available' }}
                        </span>

                        <span>
                            Email:
                            {{ $this->record->email ?: 'Not available' }}
                        </span>

                        <span>
                            Customer ID:
                            {{ $this->record->id }}
                        </span>
                    </div>
                </div>

                <a
                    href="{{ \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $this->record]) }}"
                    class="fi-btn fi-btn-size-md fi-color-primary inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold"
                >
                    Edit Customer
                </a>
            </div>
        </x-filament::section>

        {{-- Statistics --}}
        <div
    style="
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
    "
>
    <x-filament::section>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Total Bookings
        </div>

        <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
            {{ $this->stats['total'] }}
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Taxi Bookings
        </div>

        <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
            {{ $this->stats['taxi'] }}
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Self Drive
        </div>

        <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
            {{ $this->stats['self_drive'] }}
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Running
        </div>

        <div class="mt-1 text-2xl font-semibold text-warning-600">
            {{ $this->stats['running'] }}
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Completed
        </div>

        <div class="mt-1 text-2xl font-semibold text-success-600">
            {{ $this->stats['completed'] }}
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Cancelled
        </div>

        <div class="mt-1 text-2xl font-semibold text-danger-600">
            {{ $this->stats['cancelled'] }}
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Total Amount
        </div>

        <div class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">
            ₹{{ number_format($this->stats['revenue'], 2) }}
        </div>
    </x-filament::section>
</div>
        {{-- Filters --}}
        <x-filament::section>
            <div
                class="grid grid-cols-1 gap-4 md:grid-cols-3"
            >
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Search
                    </label>

                    <input
                        type="text"
                        wire:model.live.debounce.500ms="search"
                        placeholder="Booking no, vehicle, pickup, drop..."
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-white/5"
                    >
                </div>

                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Booking Type
                    </label>

                    <select
                        wire:model.live="bookingType"
                        class="fi-select-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-white/5"
                    >
                        <option value="all">All Types</option>
                        <option value="one_way">One Way</option>
                        <option value="return">Round Trip</option>
                        <option value="local">Local</option>
                        <option value="airport">Airport</option>
                        <option value="tour">Tour</option>
                        <option value="self_drive">Self Drive</option>
                    </select>
                </div>

                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Booking Status
                    </label>

                    <select
                        wire:model.live="bookingStatus"
                        class="fi-select-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-white/5"
                    >
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="running">Running</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </x-filament::section>

        {{-- Booking Table --}}
        <x-filament::section>
            <div class="overflow-x-auto">
                <table
                    class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10"
                >
                    <thead>
                        <tr
                            class="bg-gray-50 dark:bg-white/5"
                        >
                            <th class="px-4 py-3 font-semibold">
                                Booking
                            </th>

                            <th class="px-4 py-3 font-semibold">
                                Type
                            </th>

                            <th class="px-4 py-3 font-semibold">
                                Route / Pickup
                            </th>

                            <th class="px-4 py-3 font-semibold">
                                Vehicle
                            </th>

                            <th class="px-4 py-3 font-semibold">
                                Date
                            </th>

                            <th class="px-4 py-3 font-semibold">
                                Amount
                            </th>

                            <th class="px-4 py-3 font-semibold">
                                Payment
                            </th>

                            <th class="px-4 py-3 font-semibold">
                                Status
                            </th>

                            <th class="px-4 py-3 font-semibold">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-200 dark:divide-white/10"
                    >
                        @forelse ($this->bookings as $booking)
                            <tr
                                wire:key="{{ $booking['key'] }}"
                                class="hover:bg-gray-50 dark:hover:bg-white/5"
                            >
                                <td class="px-4 py-4">
                                    <div class="font-semibold">
                                        {{ $booking['booking_no'] }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        {{ $booking['source'] === 'self_drive' ? 'Self Drive Booking' : 'Taxi Booking' }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-400"
                                    >
                                        {{ $booking['type_label'] }}
                                    </span>
                                </td>

                                <td class="max-w-xs px-4 py-4">
                                    <div
                                        class="truncate font-medium"
                                        title="{{ $booking['pickup'] }}"
                                    >
                                        {{ $booking['pickup'] }}
                                    </div>

                                    @if (
                                        filled($booking['drop']) &&
                                        $booking['drop'] !== $booking['pickup']
                                    )
                                        <div
                                            class="mt-1 truncate text-xs text-gray-500"
                                            title="{{ $booking['drop'] }}"
                                        >
                                            To: {{ $booking['drop'] }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    {{ $booking['vehicle'] }}
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div>
                                        {{ $booking['booking_date'] ?: 'Not available' }}
                                    </div>

                                    @if (filled($booking['booking_time']))
                                        <div
                                            class="mt-1 text-xs text-gray-500"
                                        >
                                            {{ $booking['booking_time'] }}
                                        </div>
                                    @endif
                                </td>

                                <td
                                    class="px-4 py-4 whitespace-nowrap font-semibold"
                                >
                                    ₹{{ number_format($booking['amount'], 2) }}
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300"
                                    >
                                        {{ ucwords(str_replace('_', ' ', $booking['payment_status'])) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    @php
                                        $dangerStatuses = [
                                            'cancelled',
                                            'rejected',
                                            'failed',
                                        ];

                                        $successStatuses = [
                                            'completed',
                                            'closed',
                                            'confirm',
                                            'confirmed',
                                        ];

                                        $statusClass = in_array(
                                            $booking['status_key'],
                                            $dangerStatuses,
                                            true
                                        )
                                            ? 'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400'
                                            : (
                                                in_array(
                                                    $booking['status_key'],
                                                    $successStatuses,
                                                    true
                                                )
                                                    ? 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400'
                                                    : 'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-400'
                                            );
                                    @endphp

                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $statusClass }}"
                                    >
                                        {{ $booking['status_label'] }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <a
                                            href="{{ $booking['view_url'] }}"
                                            class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200"
                                        >
                                            View
                                        </a>

                                        <a
                                            href="{{ $booking['edit_url'] }}"
                                            class="rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500"
                                        >
                                            Edit
                                        </a>

                                        @if (auth()->user()?->hasRole('Admin'))
                                            <button
                                                type="button"
                                                wire:click="deleteBooking('{{ $booking['source'] }}', {{ $booking['id'] }})"
                                                wire:confirm="Kya aap booking {{ $booking['booking_no'] }} delete karna chahte hain? Ye action undo nahi hoga."
                                                class="rounded-lg bg-danger-600 px-3 py-2 text-xs font-semibold text-white hover:bg-danger-500"
                                            >
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="9"
                                    class="px-4 py-12 text-center"
                                >
                                    <div
                                        class="font-semibold text-gray-950 dark:text-white"
                                    >
                                        No bookings found
                                    </div>

                                    <div
                                        class="mt-1 text-sm text-gray-500"
                                    >
                                        Is customer ki matching booking nahi mili.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->bookings->hasPages())
                <div class="mt-6">
                    {{ $this->bookings->links() }}
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>