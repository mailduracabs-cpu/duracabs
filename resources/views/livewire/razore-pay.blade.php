<div class="min-h-screen bg-slate-50 py-8 sm:py-12">
    <div class="mx-auto max-w-2xl px-4">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
            <div class="bg-slate-950 px-6 py-6 text-white sm:px-8">
                <p class="text-sm font-medium text-cyan-300">Secure Razorpay Checkout</p>
                <h1 class="mt-1 text-2xl font-bold">Booking #{{ $order->booking_number }}</h1>
                <p class="mt-2 text-sm text-slate-300">Payment amount is loaded securely from your booking.</p>
            </div>

            <div class="space-y-6 p-6 sm:p-8">
                @if (session('error'))
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vehicle</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $order->productName ?: 'Dura Cabs Booking' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total amount</p>
                        <p class="mt-1 text-xl font-bold text-slate-950">₹{{ number_format((float) $order->grand_total, 2) }}</p>
                    </div>
                </div>

                <form action="{{ route('razorpay.payment.store') }}" method="POST" class="text-center">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $order->id }}">

                    <script
                        src="https://checkout.razorpay.com/v1/checkout.js"
                        data-key="{{ config('services.razorpay.key', env('RAZORPAY_API_KEY')) }}"
                        data-amount="{{ (int) round(((float) $order->grand_total) * 100) }}"
                        data-currency="INR"
                        data-buttontext="Pay ₹{{ number_format((float) $order->grand_total, 2) }} securely"
                        data-name="{{ config('app.name', 'Dura Cabs') }}"
                        data-description="{{ $order->id }}"
                        data-prefill.name="{{ $customerName }}"
                        data-prefill.email="{{ $customerEmail }}"
                        data-prefill.contact="{{ $customerPhone }}"
                        data-notes.booking_id="{{ $order->id }}"
                        data-theme.color="#0f172a">
                    </script>
                </form>

                <p class="text-center text-xs leading-5 text-slate-500">
                    Never refresh or close the page while Razorpay is confirming the payment.
                </p>
            </div>
        </div>
    </div>
</div>
