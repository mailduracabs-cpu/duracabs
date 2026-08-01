<div class="min-h-screen bg-slate-50 px-4 py-8 text-slate-900 sm:px-6 lg:px-8">
    <?php
        $bookingType = $cart_items[0]['type'] ?? null;
        $isSelfDriveCheckout = $isSelfDrive ?? ($bookingType === 'self_drive');
        $primaryItem = $cart_items[0] ?? [];
        $vehicleName = $primaryItem['name'] ?? 'DuraCabs Vehicle';
        $vehicleImage = $primaryItem['image_url'] ?? null;
        $rentalCharge = (float) ($grand_total ?? 0);
        $securityDeposit = (float) ($security ?? 0);
        $selectedHours = $primaryItem['selected_hours'] ?? null;
        $chargeableHours = $primaryItem['chargeable_hours'] ?? null;
        $pickupDate = $primaryItem['date'] ?? null;
        $returnDate = $primaryItem['dateTo'] ?? null;
        $pickupTime = $primaryItem['time'] ?? null;
        $returnTime = $primaryItem['endTime'] ?? null;
    ?>

    <div class="mx-auto w-full max-w-7xl">
        <header class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-blue-950 to-sky-800 px-5 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.16em] text-sky-100 backdrop-blur">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        Secure Checkout
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">
                        <?php echo e($isSelfDriveCheckout ? 'Quick Vehicle Reservation' : 'Complete Your Booking'); ?>

                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        <?php echo e($isSelfDriveCheckout
                            ? 'Reserve your selected vehicle with minimal details. Delivery preferences, documents and remaining information can be completed from your customer profile.'
                            : 'Review your trip, provide the required details and choose a payment method to confirm your booking.'); ?>

                    </p>
                </div>

                <!--[if BLOCK]><![endif]--><?php if($isSelfDriveCheckout): ?>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs font-bold text-slate-200 sm:min-w-[300px]">
                        <div class="rounded-2xl border border-emerald-300/20 bg-emerald-400/10 px-3 py-3">
                            <span class="mx-auto grid h-7 w-7 place-items-center rounded-full bg-emerald-400 text-slate-950">1</span>
                            <span class="mt-2 block">Reserve</span>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-3">
                            <span class="mx-auto grid h-7 w-7 place-items-center rounded-full bg-white/15">2</span>
                            <span class="mt-2 block">Complete Details</span>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-3">
                            <span class="mx-auto grid h-7 w-7 place-items-center rounded-full bg-white/15">3</span>
                            <span class="mt-2 block">Confirmation</span>
                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        </header>

        <!--[if BLOCK]><![endif]--><?php if(session()->has('error')): ?>
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 text-red-600" aria-hidden="true"></i>
                <span><?php echo e(session('error')); ?></span>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        <!--[if BLOCK]><![endif]--><?php if($pricingError ?? false): ?>
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 shadow-sm">
                <i class="fa-solid fa-circle-info mt-0.5 text-amber-600" aria-hidden="true"></i>
                <span><?php echo e($pricingError); ?></span>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        <form
            wire:submit.prevent="placeOrder"
            wire:loading.class="pointer-events-none opacity-70"
            wire:target="placeOrder"
        >
            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_410px]">
                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <div class="mb-6 flex items-start gap-4">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-sky-100 text-xl text-sky-700">
                                <i class="fa-regular fa-user" aria-hidden="true"></i>
                            </span>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700">Step 1</p>
                                <h2 class="mt-1 text-xl font-black text-slate-900">Customer Information</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    <?php echo e($isSelfDriveCheckout
                                        ? 'Only essential contact details are required to secure this reservation.'
                                        : 'Enter the passenger and contact information required for this booking.'); ?>

                                </p>
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="full_name" class="mb-2 block text-sm font-bold text-slate-700">Full Name</label>
                                <div class="relative">
                                    <i class="fa-regular fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                                    <input
                                        wire:model="full_name"
                                        id="full_name"
                                        type="text"
                                        autocomplete="name"
                                        placeholder="Enter your full name"
                                        class="h-14 w-full rounded-2xl border bg-slate-50 pl-11 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4 focus:ring-sky-100 <?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 focus:border-red-500 <?php else: ?> border-slate-200 focus:border-sky-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    >
                                </div>
                                <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                            </div>

                            <div>
                                <label for="phone" class="mb-2 block text-sm font-bold text-slate-700">Mobile Number</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 border-r border-slate-200 pr-3 text-sm font-black text-slate-600">+91</span>
                                    <input
                                        wire:model="phone"
                                        id="phone"
                                        type="tel"
                                        inputmode="numeric"
                                        maxlength="10"
                                        autocomplete="tel"
                                        placeholder="10-digit mobile number"
                                        class="h-14 w-full rounded-2xl border bg-slate-50 pl-[4.6rem] pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4 focus:ring-sky-100 <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 focus:border-red-500 <?php else: ?> border-slate-200 focus:border-sky-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    >
                                </div>
                                <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                            </div>

                            <div class="sm:col-span-2">
                                <label for="email" class="mb-2 block text-sm font-bold text-slate-700">Email Address</label>
                                <div class="relative">
                                    <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                                    <input
                                        wire:model="email"
                                        id="email"
                                        type="email"
                                        autocomplete="email"
                                        placeholder="Enter your email address"
                                        class="h-14 w-full rounded-2xl border bg-slate-50 pl-11 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4 focus:ring-sky-100 <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 focus:border-red-500 <?php else: ?> border-slate-200 focus:border-sky-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    >
                                </div>
                                <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        </div>

                        <!--[if BLOCK]><![endif]--><?php if (! ($isSelfDriveCheckout)): ?>
                            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="phone2" class="mb-2 block text-sm font-bold text-slate-700">Alternative Mobile Number <span class="font-medium text-slate-400">(Optional)</span></label>
                                    <input wire:model="phone2" id="phone2" type="tel" maxlength="10" class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['phone2'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                                <div>
                                    <label for="number_travellers" class="mb-2 block text-sm font-bold text-slate-700">Number of Travellers</label>
                                    <input wire:model="number_travellers" id="number_travellers" type="number" min="1" class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                </div>
                                <div>
                                    <label for="number_luggage" class="mb-2 block text-sm font-bold text-slate-700">Number of Luggage Items</label>
                                    <input wire:model="number_luggage" id="number_luggage" type="number" min="0" class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                </div>
                            </div>

                            <div class="mt-5 grid gap-5">
                                <div>
                                    <label for="pickup_address" class="mb-2 block text-sm font-bold text-slate-700">Pick-up Address</label>
                                    <textarea wire:model="pickup_address" id="pickup_address" rows="3" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></textarea>
                                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['pickup_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                                <div>
                                    <label for="drop_address" class="mb-2 block text-sm font-bold text-slate-700">Drop-off Address <span class="font-medium text-slate-400">(Optional)</span></label>
                                    <textarea wire:model="drop_address" id="drop_address" rows="3" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></textarea>
                                </div>
                                <div>
                                    <label for="comments" class="mb-2 block text-sm font-bold text-slate-700">Additional Instructions <span class="font-medium text-slate-400">(Optional)</span></label>
                                    <textarea wire:model="comments" id="comments" rows="3" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></textarea>
                                </div>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </section>

                    <!--[if BLOCK]><![endif]--><?php if($isSelfDriveCheckout): ?>
                        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                            <div class="mb-6 flex items-start gap-4">
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-xl text-emerald-700">
                                    <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Step 2</p>
                                    <h2 class="mt-1 text-xl font-black text-slate-900">Payment Preference</h2>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Choose the amount you would like to pay now.</p>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="group relative cursor-pointer">
                                    <input wire:model.live="payment_option" type="radio" value="token" class="peer sr-only">
                                    <span class="block h-full rounded-2xl border-2 border-slate-200 bg-white p-5 transition group-hover:border-sky-300 group-hover:bg-sky-50/50 peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:ring-4 peer-checked:ring-sky-100">
                                        <span class="flex items-start justify-between gap-3">
                                            <span>
                                                <span class="block text-base font-black text-slate-900">Reservation Token</span>
                                                <span class="mt-1 block text-sm leading-5 text-slate-500">Secure the vehicle now and pay the remaining balance later.</span>
                                            </span>
                                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border-2 border-slate-300 peer-checked:border-sky-600">
                                                <span class="h-3 w-3 rounded-full bg-sky-600 opacity-0 transition peer-checked:opacity-100"></span>
                                            </span>
                                        </span>
                                        <span class="mt-5 flex items-end justify-between gap-3">
                                            <strong class="text-2xl font-black text-sky-700"><?php echo e(Number::currency($reservationTokenAmount, 'INR')); ?></strong>
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-emerald-800">Recommended</span>
                                        </span>
                                    </span>
                                </label>

                                <label class="group relative cursor-pointer">
                                    <input wire:model.live="payment_option" type="radio" value="full" class="peer sr-only">
                                    <span class="block h-full rounded-2xl border-2 border-slate-200 bg-white p-5 transition group-hover:border-sky-300 group-hover:bg-sky-50/50 peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:ring-4 peer-checked:ring-sky-100">
                                        <span class="flex items-start justify-between gap-3">
                                            <span>
                                                <span class="block text-base font-black text-slate-900">Full Payment</span>
                                                <span class="mt-1 block text-sm leading-5 text-slate-500">Pay the complete estimated booking amount now.</span>
                                            </span>
                                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border-2 border-slate-300"></span>
                                        </span>
                                        <strong class="mt-5 block text-2xl font-black text-slate-900"><?php echo e(Number::currency($grandTotal, 'INR')); ?></strong>
                                    </span>
                                </label>
                            </div>

                            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['payment_option'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-3 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

                            <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-circle-info mt-1 text-blue-600" aria-hidden="true"></i>
                                    <p>Delivery preferences, collection preferences, addresses and verification documents will be completed securely from your customer profile after reservation.</p>
                                </div>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                            <h2 class="text-xl font-black text-slate-900">Optional Booking Preferences</h2>
                            <div class="mt-5 space-y-3">
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $this->extraAmountArr; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <label class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl border border-slate-200 p-4 transition hover:border-sky-300 hover:bg-sky-50/40">
                                        <span class="flex items-start gap-3">
                                            <input type="checkbox" wire:click="newWehicalValueFun(<?php echo e($key); ?>)" <?php echo e($item['is_checked'] ? 'checked' : ''); ?> class="mt-1 h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                            <span>
                                                <strong class="block text-sm text-slate-900"><?php echo e($item['title']); ?></strong>
                                                <small class="mt-1 block text-xs leading-5 text-slate-500"><?php echo e($item['description'] ?? ''); ?></small>
                                            </span>
                                        </span>
                                        <strong class="text-sm text-slate-900"><?php echo e(Number::currency($item['price'] ?? 0, 'INR')); ?></strong>
                                    </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        </section>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>

                <aside class="space-y-5 lg:sticky lg:top-5">
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5">
                        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 to-blue-950 px-5 py-5 text-white">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-200"><?php echo e($isSelfDriveCheckout ? 'Rental Summary' : 'Booking Summary'); ?></p>
                            <h2 class="mt-1 text-xl font-black">Review Your Selection</h2>
                        </div>

                        <div class="p-5">
                            <div class="flex gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <div class="h-20 w-24 shrink-0 overflow-hidden rounded-xl bg-white">
                                    <!--[if BLOCK]><![endif]--><?php if(filled($vehicleImage)): ?>
                                        <img src="<?php echo e($vehicleImage); ?>" alt="<?php echo e($vehicleName); ?>" class="h-full w-full object-contain" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                                        <span class="hidden h-full w-full place-items-center text-2xl text-slate-400"><i class="fa-solid fa-car-side"></i></span>
                                    <?php else: ?>
                                        <span class="grid h-full w-full place-items-center text-2xl text-slate-400"><i class="fa-solid fa-car-side"></i></span>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase tracking-wide text-sky-700"><?php echo e($isSelfDriveCheckout ? 'Self-Drive Rental' : ucfirst(str_replace('_', ' ', (string) $bookingType))); ?></p>
                                    <h3 class="mt-1 line-clamp-2 text-base font-black text-slate-900"><?php echo e($vehicleName); ?></h3>
                                    <!--[if BLOCK]><![endif]--><?php if($pickupDate): ?>
                                        <p class="mt-2 text-xs font-semibold text-slate-500">
                                            <?php echo e(\Carbon\Carbon::parse($pickupDate)->format('d M Y')); ?>

                                            <!--[if BLOCK]><![endif]--><?php if($returnDate): ?> – <?php echo e(\Carbon\Carbon::parse($returnDate)->format('d M Y')); ?> <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        </p>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            </div>

                            <!--[if BLOCK]><![endif]--><?php if($isSelfDriveCheckout && ($selectedHours || $chargeableHours)): ?>
                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl bg-slate-50 p-3">
                                        <span class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Selected Duration</span>
                                        <strong class="mt-1 block text-sm text-slate-900"><?php echo e($selectedHours ?? '—'); ?> hours</strong>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 p-3">
                                        <span class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Chargeable Duration</span>
                                        <strong class="mt-1 block text-sm text-slate-900"><?php echo e($chargeableHours ?? '—'); ?> hours</strong>
                                    </div>
                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <div class="mt-5 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4 text-slate-600">
                                    <span><?php echo e($isSelfDriveCheckout ? 'Base Rental Charge' : 'Base Fare'); ?></span>
                                    <strong class="text-slate-900"><?php echo e(Number::currency($rentalCharge, 'INR')); ?></strong>
                                </div>

                                <!--[if BLOCK]><![endif]--><?php if($isSelfDriveCheckout && $securityDeposit > 0): ?>
                                    <div class="flex items-center justify-between gap-4 text-slate-600">
                                        <span>Refundable Security Deposit</span>
                                        <strong class="text-slate-900"><?php echo e(Number::currency($securityDeposit, 'INR')); ?></strong>
                                    </div>
                                <?php elseif(!$isSelfDriveCheckout): ?>
                                    <div class="flex items-center justify-between gap-4 text-slate-600">
                                        <span>Toll Charges</span>
                                        <strong class="text-slate-900">
                                            <?php echo e(($tollTax ?? 0) > 0 ? Number::currency($tollTax, 'INR') : ($bookingType === 'one_way' ? 'Included' : 'Excluded')); ?>

                                        </strong>
                                    </div>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                <div class="flex items-center justify-between gap-4 text-slate-600">
                                    <span>Taxes</span>
                                    <strong class="text-slate-900"><?php echo e(Number::currency($taxAmount, 'INR')); ?></strong>
                                </div>

                                <!--[if BLOCK]><![endif]--><?php if(($discountAmount ?? 0) > 0): ?>
                                    <div class="flex items-center justify-between gap-4 text-emerald-700">
                                        <span>Discount Applied</span>
                                        <strong>− <?php echo e(Number::currency($discountAmount, 'INR')); ?></strong>
                                    </div>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>

                            <div class="my-5 border-t border-dashed border-slate-300"></div>

                            <div class="rounded-2xl bg-slate-950 p-4 text-white">
                                <div class="flex items-end justify-between gap-4">
                                    <span>
                                        <small class="block text-xs font-bold uppercase tracking-wide text-slate-400">Estimated Total</small>
                                        <strong class="mt-1 block text-2xl font-black"><?php echo e(Number::currency($grandTotal, 'INR')); ?></strong>
                                    </span>
                                    <!--[if BLOCK]><![endif]--><?php if($isSelfDriveCheckout && $payment_option === 'token'): ?>
                                        <span class="rounded-full bg-emerald-400 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-emerald-950">Token Selected</span>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            </div>

                            <!--[if BLOCK]><![endif]--><?php if($isSelfDriveCheckout): ?>
                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-3">
                                        <small class="block text-[11px] font-bold uppercase tracking-wide text-sky-700">Pay Now</small>
                                        <strong class="mt-1 block text-base font-black text-sky-900"><?php echo e(Number::currency($amountPayableNow, 'INR')); ?></strong>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                        <small class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">Remaining Balance</small>
                                        <strong class="mt-1 block text-base font-black text-slate-900"><?php echo e(Number::currency($balanceAmount, 'INR')); ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Savings</p>
                                <h2 class="mt-1 text-lg font-black text-slate-900">Discount Code</h2>
                            </div>
                            <i class="fa-solid fa-ticket text-xl text-emerald-600" aria-hidden="true"></i>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <input wire:model.live.debounce.500ms="coupon" id="coupon" type="text" placeholder="Enter promo code" class="h-12 min-w-0 flex-1 rounded-xl border px-3 text-sm font-bold uppercase outline-none transition focus:ring-4 <?php echo e($couponData ? 'border-emerald-400 bg-emerald-50 text-emerald-800 focus:ring-emerald-100' : 'border-slate-200 bg-slate-50 text-slate-900 focus:border-sky-500 focus:bg-white focus:ring-sky-100'); ?>">
                            <!--[if BLOCK]><![endif]--><?php if($couponData): ?>
                                <button type="button" wire:click="$set('coupon', '')" class="h-12 rounded-xl border border-red-200 bg-red-50 px-3 text-xs font-black text-red-700 transition hover:bg-red-100">Remove</button>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>

                        <!--[if BLOCK]><![endif]--><?php if($couponData): ?>
                            <p class="mt-2 text-xs font-bold text-emerald-700">Coupon applied successfully. You saved <?php echo e(round($couponData)); ?>%.</p>
                        <?php elseif(!empty($coupon)): ?>
                            <p class="mt-2 text-xs font-bold text-red-600">The coupon code is invalid or has expired.</p>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        <!--[if BLOCK]><![endif]--><?php if($availableCoupons->count() > 0): ?>
                            <div class="mt-4 space-y-2">
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $availableCoupons->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $availableCoupon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <button type="button" wire:click="applyCoupon('<?php echo e($availableCoupon->name); ?>')" class="flex w-full items-center justify-between gap-3 rounded-xl border border-dashed border-emerald-300 bg-emerald-50/60 px-3 py-2.5 text-left transition hover:bg-emerald-50">
                                        <span class="min-w-0">
                                            <strong class="block truncate text-xs text-emerald-900"><?php echo e($availableCoupon->name); ?></strong>
                                            <small class="text-[11px] text-emerald-700"><?php echo e($availableCoupon->value); ?>% off</small>
                                        </span>
                                        <span class="text-xs font-black text-emerald-700">Apply</span>
                                    </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black text-slate-900">Payment Method</h2>
                        <p class="mt-1 text-sm text-slate-500">Choose a secure payment option.</p>

                        <div class="mt-4 grid gap-3 <?php echo e($isSelfDriveCheckout ? 'grid-cols-1' : 'sm:grid-cols-2 lg:grid-cols-1'); ?>">
                            <!--[if BLOCK]><![endif]--><?php if (! ($isSelfDriveCheckout)): ?>
                                <label class="cursor-pointer">
                                    <input wire:model="payment_method" type="radio" value="cash" class="peer sr-only">
                                    <span class="flex items-center gap-3 rounded-2xl border-2 border-slate-200 p-4 transition peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:ring-4 peer-checked:ring-sky-100">
                                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-100 text-amber-700"><i class="fa-solid fa-money-bill-wave"></i></span>
                                        <span><strong class="block text-sm text-slate-900">Pay at Pick-up</strong><small class="text-xs text-slate-500">Cash payment</small></span>
                                    </span>
                                </label>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <label class="cursor-pointer">
                                <input wire:model="payment_method" type="radio" value="RazorPay" class="peer sr-only" <?php echo e($isSelfDriveCheckout ? 'checked' : ''); ?>>
                                <span class="flex items-center gap-3 rounded-2xl border-2 border-slate-200 p-4 transition peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:ring-4 peer-checked:ring-sky-100">
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-100 text-sky-700"><i class="fa-solid fa-credit-card"></i></span>
                                    <span><strong class="block text-sm text-slate-900">Secure Online Payment</strong><small class="text-xs text-slate-500">UPI, cards and net banking</small></span>
                                    <i class="fa-solid fa-circle-check ml-auto text-sky-600 opacity-0 peer-checked:opacity-100"></i>
                                </span>
                            </label>
                        </div>
                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['payment_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                    </section>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="placeOrder"
                        class="group flex min-h-14 w-full items-center justify-between gap-4 rounded-2xl bg-gradient-to-r from-blue-700 to-sky-500 px-5 py-4 text-left text-white shadow-xl shadow-blue-700/20 transition hover:-translate-y-0.5 hover:shadow-2xl disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="placeOrder">
                            <small class="block text-[11px] font-bold uppercase tracking-[0.14em] text-sky-100">
                                <?php echo e($isSelfDriveCheckout ? 'Secure Your Reservation' : 'Confirm Your Booking'); ?>

                            </small>
                            <strong class="mt-0.5 block text-base font-black">
                                <?php echo e($isSelfDriveCheckout ? 'Reserve Vehicle' : 'Proceed to Payment'); ?>

                                • <?php echo e(Number::currency($isSelfDriveCheckout ? $amountPayableNow : $grandTotal, 'INR')); ?>

                            </strong>
                        </span>
                        <span wire:loading wire:target="placeOrder" class="font-black">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...
                        </span>
                        <i wire:loading.remove wire:target="placeOrder" class="fa-solid fa-arrow-right transition group-hover:translate-x-1" aria-hidden="true"></i>
                    </button>

                    <div class="flex items-center justify-center gap-2 text-center text-xs font-semibold text-slate-500">
                        <i class="fa-solid fa-lock text-emerald-600" aria-hidden="true"></i>
                        Payments are protected through a secure encrypted gateway.
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div><?php /**PATH C:\xampp\htdocs\duracabs\resources\views/livewire/checkout-page.blade.php ENDPATH**/ ?>