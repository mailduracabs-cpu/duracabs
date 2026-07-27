@php
    $address = $order->address;
    $rideType = (string) ($order->ride_type ?? '');
    $isSelfDriveBooking = (bool) ($isSelfDrive ?? ($rideType === 'self_drive'));

    $money = static fn ($value) => Number::currency((float) ($value ?? 0), 'INR');
    $formatDate = static function ($value, string $format = 'd/m/Y') {
        if (blank($value)) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return '—';
        }
    };

    $baseAmount = (float) ($pricingBreakdown['base_amount'] ?? $storedBaseFare ?? 0);
    $rentalDiscount = (float) ($pricingBreakdown['discount_amount'] ?? 0);
    $rentAmount = (float) ($pricingBreakdown['rent'] ?? max(0, $baseAmount - $rentalDiscount));
    $extrasAmount = (float) ($pricingBreakdown['extras_total'] ?? 0);
    $couponDiscount = (float) ($pricingBreakdown['coupon_discount'] ?? $storedDiscountAmount ?? 0);
    $taxableAmount = (float) ($pricingBreakdown['taxable_amount'] ?? 0);
    $gstAmount = (float) ($pricingBreakdown['gst_amount'] ?? $storedTaxAmount ?? $order->tax ?? 0);
    $securityDeposit = (float) ($pricingBreakdown['security_deposit'] ?? $storedSecurityDeposit ?? 0);
    $payableAmount = (float) ($pricingBreakdown['payable_amount'] ?? $order->grand_total ?? 0);
    $pricingMode = (string) ($pricingBreakdown['mode'] ?? '');
    $selectedHours = (int) ($pricingBreakdown['selected_hours'] ?? 0);
    $chargeableHours = (int) ($pricingBreakdown['chargeable_hours'] ?? 0);
    $totalDays = (int) ($pricingBreakdown['total_days'] ?? 0);
    $totalWeeks = (int) ($pricingBreakdown['total_weeks'] ?? 0);
    $totalMonths = (int) ($pricingBreakdown['total_months'] ?? 0);

    $durationLabel = match ($pricingMode) {
        'hourly' => ($selectedHours ?: $chargeableHours) . ' Hour(s)',
        'daily' => $totalDays . ' Day(s)',
        'weekly' => $totalWeeks . ' Week(s)',
        'monthly' => $totalMonths . ' Month(s)',
        default => $isSelfDriveBooking && $days > 0 ? ($days . ' Day(s)') : null,
    };
@endphp

<div class="w-full max-w-[85rem] py-6 md:py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <section class="dark:bg-gray-800">
        <div class="justify-center flex-1 max-w-6xl mx-auto bg-white border rounded-md dark:border-gray-900 dark:bg-gray-900">
            <hr class="h-7 mb-5 bg-sky-500 border-0 dark:bg-gray-700" />

            <div class="py-4 px-4 md:py-0 md:px-10">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="w-full md:w-1/2">
                        <img class="w-1/2 max-w-[200px]" src="{{ url('storage') }}/images/Duracab-Logo-425x115.png" alt="Duracabs" />
                    </div>

                    <div class="w-full md:w-auto text-left md:text-right">
                        <div class="mb-2"><span class="text-sm md:text-base">Booking No:</span> <a href="tel:+917088873331" class="font-bold text-sm md:text-base">+91-7088873331</a></div>
                        <div class="mb-2"><span class="text-sm md:text-base">WhatsApp No:</span> <a href="tel:+917088873332" class="font-bold text-sm md:text-base">+91-7088873332</a></div>
                        <p class="text-sm md:text-base">24 × 7 Customer Support</p>
                    </div>
                </div>

                <div class="my-5 rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
                    <h1 class="text-xl md:text-2xl font-semibold tracking-wide text-green-800 dark:text-green-300">
                        Thank you. Your booking has been received.
                    </h1>
                    <p class="mt-1 text-sm text-green-700 dark:text-green-400">Please keep your booking ID for future reference.</p>
                </div>

                <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 w-full px-4 mb-8 md:flex-row gap-6">
                    <div class="w-full md:w-1/2 pb-6">
                        <p class="text-lg md:text-xl pb-4 font-semibold text-gray-800 dark:text-gray-300">Traveller Details</p>
                        <div class="space-y-2 text-sm dark:text-gray-400">
                            <p><span class="text-base font-semibold text-gray-800 dark:text-gray-300">Name:</span> {{ $address?->full_name ?? '—' }}</p>
                            <p><span class="text-base font-semibold text-gray-800 dark:text-gray-300">Mobile:</span> {{ $address?->phone ?? '—' }}</p>
                            <p><span class="text-base font-semibold text-gray-800 dark:text-gray-300">Email:</span> {{ $address?->email ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="w-full md:w-1/2 pb-6 md:text-right">
                        <h2 class="text-xl md:text-3xl text-sky-600 font-bold uppercase">Booking ID: {{ $order->id }}</h2>
                        <p class="mt-2 text-base text-gray-800 dark:text-gray-400">
                            <span class="font-semibold">Generated:</span>
                            {{ $order->created_at?->setTimezone('Asia/Kolkata')->format('d-m-Y h:i a') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pb-4 mb-10 border-b border-gray-200 dark:border-gray-700">
                    <div class="px-4 mb-4">
                        <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Order Number</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-300">{{ $order->id }}</p>
                    </div>
                    <div class="px-4 mb-4">
                        <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Start Date & Time</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-300">{{ $formatDate($order->date) }} {{ $formatDate($order->time, 'h:i a') }}</p>
                    </div>
                    <div class="px-4 mb-4">
                        <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Total Payable</p>
                        <p class="font-semibold text-blue-600 dark:text-blue-400">{{ $money($order->grand_total) }}</p>
                    </div>
                    <div class="px-4 mb-4">
                        <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Payment Method</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-300">{{ $order->payment_method === 'cash' ? 'Cash' : 'Online' }}</p>
                    </div>
                </div>

                <div class="px-2 mb-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <h2 class="mb-3 text-xl font-semibold text-gray-700 dark:text-gray-300">Trip Details</h2>

                            @if (!$isSelfDriveBooking && in_array($rideType, ['one_way', 'return'], true))
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Trip Route</span><span class="text-right">{{ $rideType === 'return' ? (($order->cityFrom ?? '') . ' → ' . ($order->cityTo ?? '')) : ($product->name ?? '—') }}</span></div>
                            @endif

                            <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Trip Type</span><span class="text-right capitalize">{{ str_replace('_', ' ', $rideType) }}</span></div>
                            <div class="flex justify-between gap-4 py-2"><span class="font-semibold">{{ $isSelfDriveBooking ? 'Vehicle' : 'Taxi Type' }}</span><span class="text-right">{{ $order->productName ?? '—' }}</span></div>
                            <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Pickup Address</span><span class="text-right break-words">{{ $address?->pickup_address ?? '—' }}</span></div>

                            @if ($address?->drop_address)
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Drop Address</span><span class="text-right break-words">{{ $address->drop_address }}</span></div>
                            @endif

                            @if ($rideType === 'one_way')
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Total KM</span><span>{{ $product->km_limit ?? 0 }}</span></div>
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Total Hours</span><span>{{ $product->hr_limit ?? 0 }}</span></div>
                            @elseif ($rideType === 'return')
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Total KM</span><span>{{ $order->total_km ?? 0 }}</span></div>
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Total Days</span><span>{{ $days + 1 }}</span></div>
                            @endif

                            @if (in_array($rideType, ['return', 'self_drive'], true))
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">End Date</span><span>{{ $formatDate($order->dateTo) }}</span></div>
                            @endif

                            @if ($isSelfDriveBooking)
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">End Time</span><span>{{ $formatDate($order->endTime, 'h:i a') }}</span></div>
                                @if ($durationLabel)
                                    <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Rental Duration</span><span>{{ $durationLabel }}</span></div>
                                @endif
                                @if ($pricingMode)
                                    <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Pricing Plan</span><span class="capitalize">{{ $pricingMode }}</span></div>
                                @endif
                            @endif

                            @if ($address?->comments)
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Comments</span><span class="text-right break-words">{{ $address->comments }}</span></div>
                            @endif

                            @if (!$isSelfDriveBooking && $address?->number_travellers)
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Travellers</span><span>{{ $address->number_travellers }}</span></div>
                            @endif

                            @if (!$isSelfDriveBooking && $address?->number_luggage)
                                <div class="flex justify-between gap-4 py-2"><span class="font-semibold">Luggage</span><span>{{ $address->number_luggage }}</span></div>
                            @endif
                        </div>

                        <div class="space-y-6">
                            <div>
                                <h2 class="mb-3 text-xl font-semibold text-gray-700 dark:text-gray-300">Fare Summary</h2>
                                <div class="space-y-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                                    @if ($isSelfDriveBooking)
                                        <div class="flex justify-between"><span>Base Rental Amount</span><span class="font-semibold">{{ $money($baseAmount) }}</span></div>

                                        @if ($rentalDiscount > 0)
                                            <div class="flex justify-between text-green-700 dark:text-green-400"><span>Plan Discount</span><span>-{{ $money($rentalDiscount) }}</span></div>
                                        @endif

                                        <div class="flex justify-between"><span>Rental Amount</span><span>{{ $money($rentAmount) }}</span></div>

                                        @if ($extrasAmount > 0)
                                            <div class="flex justify-between"><span>Extra Charges</span><span>{{ $money($extrasAmount) }}</span></div>
                                        @endif

                                        @if ($couponDiscount > 0)
                                            <div class="flex justify-between text-green-700 dark:text-green-400"><span>Coupon Discount</span><span>-{{ $money($couponDiscount) }}</span></div>
                                        @endif

                                        @if ($taxableAmount > 0)
                                            <div class="flex justify-between"><span>Taxable Amount</span><span>{{ $money($taxableAmount) }}</span></div>
                                        @endif

                                        <div class="flex justify-between"><span>GST</span><span>{{ $money($gstAmount) }}</span></div>

                                        @if ($securityDeposit > 0)
                                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30">
                                                <div class="flex justify-between font-semibold"><span>Refundable Security Deposit</span><span>{{ $money($securityDeposit) }}</span></div>
                                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">Refundable after successful vehicle return, subject to booking terms.</p>
                                            </div>
                                        @endif
                                    @else
                                        <div class="flex justify-between"><span>Base Fare</span><span class="font-semibold">{{ $money($storedBaseFare) }}</span></div>

                                        @if (($storedTollTax ?? 0) > 0)
                                            <div class="flex justify-between"><span>Toll</span><span>{{ $money($storedTollTax) }}</span></div>
                                        @endif

                                        @if ($rideType === 'return' && $driverCharge > 0)
                                            <div class="flex justify-between"><span>Driver Allowance</span><span>{{ $money($driverCharge * $days) }}</span></div>
                                        @endif

                                        @foreach ($selectedOptions as $option)
                                            @if (($option['is_checked'] ?? false) === true)
                                                <div class="flex justify-between"><span>{{ $option['title'] ?? 'Extra Option' }}</span><span>{{ $money($option['price'] ?? 0) }}</span></div>
                                            @endif
                                        @endforeach

                                        @if (($storedDiscountAmount ?? 0) > 0)
                                            <div class="flex justify-between text-green-700 dark:text-green-400"><span>Coupon Discount</span><span>-{{ $money($storedDiscountAmount) }}</span></div>
                                        @endif

                                        <div class="flex justify-between"><span>Tax</span><span>{{ $money($storedTaxAmount) }}</span></div>
                                    @endif
                                </div>

                                <div class="mt-4 flex items-center justify-between text-lg font-bold">
                                    <span>Total Payable</span>
                                    <span>{{ $money($payableAmount) }}</span>
                                </div>
                            </div>

                            <div>
                                <h2 class="mb-3 text-xl font-semibold text-gray-700 dark:text-gray-300">Payment Details</h2>
                                <div class="space-y-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                                    @forelse ($invoices as $item)
                                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                            <div class="flex justify-between gap-3"><span class="font-semibold uppercase">Invoice #{{ $item->id }}</span><span class="uppercase">{{ $item->status }}</span></div>
                                            <div class="mt-2 flex justify-between gap-3 text-sm"><span>{{ $item->date }}</span><span>{{ $money($item->ammount) }}</span></div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No payment entries are available yet.</p>
                                    @endforelse
                                </div>

                                <div class="mt-4 space-y-3">
                                    <div class="flex justify-between font-semibold"><span>Total Paid</span><span>{{ $money($InvoiceSum) }}</span></div>
                                    <div class="flex justify-between font-semibold"><span>Total Due</span><span>{{ $money(max(0, (float) $order->grand_total - (float) $InvoiceSum)) }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-2">
                    <hr class="h-1 mb-5 bg-sky-500 border-0 dark:bg-gray-700" />
                    <h2 class="font-semibold text-base md:text-lg mb-3">Booking Terms and Conditions</h2>
                    <ul class="space-y-2 my-4 text-xs md:text-sm list-disc pl-5">
                        <li>Total booking amount: {{ $money($order->grand_total) }}.</li>

                        @if ($isSelfDriveBooking)
                            <li>Fuel, toll, parking, inter-state tax and traffic penalties are not included unless explicitly mentioned in the booking.</li>
                            <li>Pickup and drop charges may apply based on distance, time and rental office location.</li>
                            <li>Extra kilometre charges and usage limits will apply according to the confirmed vehicle plan.</li>
                            <li>The refundable security deposit is subject to vehicle inspection, fuel level, damage and pending penalties.</li>
                        @else
                            @if ($rideType === 'one_way')
                                <li>Extra hours will be charged at {{ $money($product->extra_hr_charge ?? 0) }} per hour.</li>
                                <li>Extra kilometres will be charged at {{ $money($product->extra_km_charge ?? 0) }} per kilometre.</li>
                            @elseif ($rideType === 'return')
                                <li>Extra kilometres will be charged at {{ $money($perKm) }} per kilometre.</li>
                            @endif
                            <li>Inter-state tax, toll and parking are excluded unless explicitly included in the selected fare.</li>
                            <li>Night driving between 10:00 PM and 6:00 AM may attract a driver allowance.</li>
                        @endif
                    </ul>

                    <h2 class="font-semibold text-base md:text-lg mb-3 mt-6">Cancellation & Refund Policy</h2>
                    <ul class="space-y-2 my-4 text-xs md:text-sm list-disc pl-5">
                        <li>Cancellation within the applicable free-cancellation period has no charge.</li>
                        <li>Cancellation more than four hours before pickup may attract a 25% cancellation fee.</li>
                        <li>Cancellation within four hours of pickup or a no-show may attract a 100% cancellation fee.</li>
                        <li>If Dura Cabs cancels the booking, the applicable paid amount will be refunded.</li>
                        <li>Approved refunds are processed after applicable deductions and may take up to 21 days.</li>
                    </ul>
                </div>
            </div>

            <hr class="h-7 mt-5 bg-sky-500 border-0 dark:bg-gray-700" />
        </div>

        <div class="justify-center flex-1 max-w-6xl mx-auto mt-5 px-4">
            <div class="flex flex-col sm:flex-row gap-4 sm:justify-center">
                <a href="/products" class="w-full sm:w-auto text-center px-6 py-3 text-blue-500 border border-blue-500 rounded-md hover:text-white hover:bg-blue-600 transition-colors">Go back shopping</a>
                <a href="/my-orders" class="w-full sm:w-auto text-center px-6 py-3 bg-blue-500 rounded-md text-white hover:bg-blue-600 transition-colors">View My Orders</a>
                <button type="button" wire:click="createInvoice" wire:loading.attr="disabled" class="w-full sm:w-auto px-6 py-3 bg-slate-700 rounded-md text-white hover:bg-slate-800 disabled:opacity-60 transition-colors">
                    <span wire:loading.remove wire:target="createInvoice">Download Invoice</span>
                    <span wire:loading wire:target="createInvoice">Preparing Invoice...</span>
                </button>
            </div>
        </div>
    </section>
</div>