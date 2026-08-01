<div class="min-h-screen bg-slate-50 px-4 py-8 sm:py-12">
    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
            <header class="bg-gradient-to-br from-slate-950 via-blue-950 to-cyan-800 px-6 py-6 text-white sm:px-8">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-300">
                            Secure Reservation Payment
                        </p>

                        <h1 class="mt-2 truncate text-2xl font-extrabold">
                            Booking #<?php echo e($order->booking_number); ?>

                        </h1>

                        <p class="mt-2 text-sm leading-6 text-slate-200">
                            <?php echo e($isSelfDrive && $paymentOption === 'token'
                                ? 'Pay the reservation token to secure your selected vehicle.'
                                : 'Complete the secure payment for your reservation.'); ?>

                        </p>
                    </div>

                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border border-white/15 bg-white/10 text-xl">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    </div>
                </div>
            </header>

            <main
                class="space-y-6 p-6 sm:p-8"
                x-data="{
                    processing: false,

                    openRazorpay() {
                        if (this.processing) {
                            return;
                        }

                        if (typeof Razorpay === 'undefined') {
                            alert('The secure payment gateway could not be loaded. Please refresh the page and try again.');
                            return;
                        }

                        this.processing = true;

                        const options = {
                            key: <?php echo \Illuminate\Support\Js::from(config('services.razorpay.key', env('RAZORPAY_API_KEY')))->toHtml() ?>,
                            amount: <?php echo e((int) round(((float) $paymentAmount) * 100)); ?>,
                            currency: 'INR',
                            name: <?php echo \Illuminate\Support\Js::from(config('app.name', 'Dura Cabs'))->toHtml() ?>,
                            description: <?php echo \Illuminate\Support\Js::from(
                                $isSelfDrive && $paymentOption === 'token'
                                    ? 'Self-Drive Reservation Token'
                                    : 'Dura Cabs Booking Payment'
                            )->toHtml() ?>,
                            image: <?php echo \Illuminate\Support\Js::from(asset('images/logo.png'))->toHtml() ?>,
                            prefill: {
                                name: <?php echo \Illuminate\Support\Js::from($customerName)->toHtml() ?>,
                                email: <?php echo \Illuminate\Support\Js::from($customerEmail)->toHtml() ?>,
                                contact: <?php echo \Illuminate\Support\Js::from($customerPhone)->toHtml() ?>,
                            },
                            notes: {
                                booking_id: <?php echo \Illuminate\Support\Js::from((string) $order->id)->toHtml() ?>,
                                payment_option: <?php echo \Illuminate\Support\Js::from($paymentOption)->toHtml() ?>,
                            },
                            theme: {
                                color: '#0284c7',
                            },
                            modal: {
                                ondismiss: () => {
                                    this.processing = false;
                                },
                            },
                            handler: (response) => {
                                const form = this.$refs.paymentForm;

                                this.$refs.razorpayPaymentId.value =
                                    response.razorpay_payment_id || '';

                                if (this.$refs.razorpayOrderId) {
                                    this.$refs.razorpayOrderId.value =
                                        response.razorpay_order_id || '';
                                }

                                if (this.$refs.razorpaySignature) {
                                    this.$refs.razorpaySignature.value =
                                        response.razorpay_signature || '';
                                }

                                form.submit();
                            },
                        };

                        const checkout = new Razorpay(options);

                        checkout.on('payment.failed', (response) => {
                            this.processing = false;

                            const message =
                                response?.error?.description
                                || 'The payment could not be completed. Please try again.';

                            alert(message);
                        });

                        checkout.open();
                    }
                }"
            >
                <!--[if BLOCK]><![endif]--><?php if(session('error')): ?>
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        <?php echo e(session('error')); ?>

                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <section class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Vehicle
                        </p>

                        <p class="mt-1 font-bold text-slate-900">
                            <?php echo e($order->productName ?: 'Dura Cabs Booking'); ?>

                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Payment Type
                        </p>

                        <p class="mt-1 font-bold text-slate-900">
                            <?php echo e($isSelfDrive && $paymentOption === 'token'
                                ? 'Reservation Token'
                                : 'Full Payment'); ?>

                        </p>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="space-y-3 p-5">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span class="text-slate-600">Total Reservation Value</span>

                            <strong class="text-slate-900">
                                ₹<?php echo e(number_format((float) $bookingTotal, 2)); ?>

                            </strong>
                        </div>

                        <!--[if BLOCK]><![endif]--><?php if($isSelfDrive && $paymentOption === 'token'): ?>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="text-slate-600">Remaining Balance</span>

                                <strong class="text-slate-900">
                                    ₹<?php echo e(number_format((float) $balanceAmount, 2)); ?>

                                </strong>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t border-slate-200 bg-blue-50 px-5 py-4">
                        <span class="text-sm font-bold text-blue-900">
                            Amount Payable Now
                        </span>

                        <strong class="text-2xl font-extrabold text-blue-700">
                            ₹<?php echo e(number_format((float) $paymentAmount, 2)); ?>

                        </strong>
                    </div>
                </section>

                <section class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
                        <i class="fa-solid fa-lock text-blue-600" aria-hidden="true"></i>
                        <p class="mt-2 text-[11px] font-bold text-slate-700">Encrypted</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
                        <i class="fa-solid fa-shield-halved text-blue-600" aria-hidden="true"></i>
                        <p class="mt-2 text-[11px] font-bold text-slate-700">Razorpay Secure</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
                        <i class="fa-solid fa-credit-card text-blue-600" aria-hidden="true"></i>
                        <p class="mt-2 text-[11px] font-bold text-slate-700">Cards & UPI</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
                        <i class="fa-solid fa-building-columns text-blue-600" aria-hidden="true"></i>
                        <p class="mt-2 text-[11px] font-bold text-slate-700">Net Banking</p>
                    </div>
                </section>

                <form
                    x-ref="paymentForm"
                    action="<?php echo e(route('razorpay.payment.store')); ?>"
                    method="POST"
                    class="hidden"
                >
                    <?php echo csrf_field(); ?>

                    <input type="hidden" name="booking_id" value="<?php echo e($order->id); ?>">
                    <input x-ref="razorpayPaymentId" type="hidden" name="razorpay_payment_id">
                    <input x-ref="razorpayOrderId" type="hidden" name="razorpay_order_id">
                    <input x-ref="razorpaySignature" type="hidden" name="razorpay_signature">
                </form>

                <button
                    type="button"
                    x-on:click="openRazorpay()"
                    x-bind:disabled="processing"
                    class="group flex min-h-16 w-full items-center justify-between rounded-2xl bg-gradient-to-r from-blue-700 to-sky-500 px-5 py-4 text-left text-white shadow-lg shadow-blue-200 transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-65"
                >
                    <span>
                        <span class="block text-[11px] font-bold uppercase tracking-[0.16em] text-blue-100">
                            Secure Payment
                        </span>

                        <span class="mt-1 block text-lg font-extrabold">
                            <span x-show="!processing">
                                Pay ₹<?php echo e(number_format((float) $paymentAmount, 2)); ?> Securely
                            </span>

                            <span x-show="processing" x-cloak>
                                Opening Secure Checkout...
                            </span>
                        </span>
                    </span>

                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/15 text-lg transition group-hover:bg-white/25">
                        <i
                            x-show="!processing"
                            class="fa-solid fa-arrow-right"
                            aria-hidden="true"
                        ></i>

                        <i
                            x-show="processing"
                            x-cloak
                            class="fa-solid fa-spinner fa-spin"
                            aria-hidden="true"
                        ></i>
                    </span>
                </button>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-lock mt-0.5 text-emerald-600" aria-hidden="true"></i>

                        <p class="text-xs leading-5 text-emerald-800">
                            Your payment is processed securely by Razorpay. Dura Cabs does not store your card, UPI, or banking credentials.
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</div><?php /**PATH C:\xampp\htdocs\duracabs\resources\views/livewire/razore-pay.blade.php ENDPATH**/ ?>