<div class="premium-motion-page rides-premium-page <?php echo e(($tab ?? null) === 'self_drive' ? 'self-drive-mode' : ''); ?> w-full max-w-[86rem] px-3 sm:px-5 lg:px-6 mx-auto" id="rides-page" x-data="{ otpSeconds: 0 }" x-on:rides-otp-sent.window="otpSeconds = $event.detail.seconds; const timer = setInterval(() => { otpSeconds--; if (otpSeconds <= 0) clearInterval(timer); }, 1000)">
    <?php $__env->startSection('title', $pageTitle); ?>
    <?php $__env->startSection('description', $pageDescription); ?>
    <?php $__env->startSection('image', $pageImage); ?>

    <?php
        /*
         * Self-drive duration is calculated from the actual pickup/drop
         * date and time. The old $days URL value is used only as a safe
         * fallback when any date/time value is missing or invalid.
         */
        $selfDriveHours = 1;
        $selfDriveRentalDays = 1;

        if (($tab ?? null) === 'self_drive') {
            try {
                $pickupDateTime = \Carbon\Carbon::createFromFormat(
                    '!Y-m-d H:i',
                    trim((string) $date . ' ' . (string) $time)
                );

                $dropDateTime = \Carbon\Carbon::createFromFormat(
                    '!Y-m-d H:i',
                    trim((string) $dateto . ' ' . (string) $endTime)
                );

                if ($dropDateTime->greaterThan($pickupDateTime)) {
                    $durationMinutes = $pickupDateTime->diffInMinutes(
                        $dropDateTime,
                        true
                    );

                    // Any partial hour is billed as one complete hour.
                    $selfDriveHours = max(
                        1,
                        (int) ceil($durationMinutes / 60)
                    );
                }
            } catch (\Throwable $e) {
                $selfDriveHours = max(1, (int) ($days ?: 1));
            }

            $selfDriveRentalDays = max(
                1,
                (int) ceil($selfDriveHours / 24)
            );
        }
    ?>
    <section class="ride-shell rides-premium-shell font-poppins rounded-3xl py-3 sm:py-5">
        <div class="mx-auto max-w-7xl px-1 sm:px-3">
            <!--[if BLOCK]><![endif]--><?php if($nameTo): ?>
                <section class="ride-summary-card premium-hero-animate mb-5">
                    <div class="ride-summary-route">
                        <div class="ride-route-markers" aria-hidden="true">
                            <span class="ride-route-dot ride-route-dot--start"></span>
                            <span class="ride-route-line"></span>
                            <span class="ride-route-dot ride-route-dot--end"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="ride-summary-city"><?php echo e($nameTo); ?></p>
                            <!--[if BLOCK]><![endif]--><?php if(in_array($tab, ['one_way', 'return'], true)): ?>
                                <span class="ride-summary-to">TO</span>
                                <p class="ride-summary-city"><?php echo e($tab === 'return' ? $cityFrom : $nameFrom); ?></p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>

                    <div class="ride-summary-meta">
                        <div class="ride-summary-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><small>Trip Type</small><strong><?php echo e(str($tab ?: 'one_way')->replace('_', ' ')->title()); ?></strong></span>
                        </div>
                        <!--[if BLOCK]><![endif]--><?php if($date): ?>
                            <div class="ride-summary-item">
                                <i class="fa-regular fa-calendar"></i>
                                <span><small>Date</small><strong><?php echo e(\Carbon\Carbon::parse($date)->format('d M Y')); ?></strong></span>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        <!--[if BLOCK]><![endif]--><?php if($newTime): ?>
                            <div class="ride-summary-item">
                                <i class="fa-regular fa-clock"></i>
                                <span><small>Time</small><strong><?php echo e($newTime->format('h:i A')); ?></strong></span>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        <div class="ride-summary-item">
                            <i class="fa-regular fa-user"></i>
                            <span><small>Passengers</small><strong><?php echo e(max(1, (int) ($cars ?: 1))); ?> Passenger</strong></span>
                        </div>
                    </div>

                    <div class="ride-summary-actions">
                        <button type="button" wire:click="showEditQueryModal" class="ride-summary-edit">
                            <i class="fa-solid fa-pen-to-square"></i><span>Edit Trip</span>
                        </button>
                        <!--[if BLOCK]><![endif]--><?php if (! ($fareUnlocked)): ?>
                            <button type="button" wire:click="openFareGate" class="ride-summary-unlock">Unlock fare</button>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                </section>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            <div wire:loading.flex wire:target="sort,selected_categories,selected_brands,price_range" class="surface mb-4 items-center gap-3 px-4 py-3 text-sm font-semibold text-blue-700">
                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                Updating available rides…
            </div>
            <div class="lg:flex flex-wrap mb-24 -mx-3">
                <div class="rides-layout-sidebar w-full pr-2 lg:w-1/4 lg:block hidden">

                    <!--[if BLOCK]><![endif]--><?php if($nameTo): ?>
                        <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900 ">
                            <h2 class="text-xl font-medium text-sky-500 dark:text-gray-400">Trip Details</h2>
                            <div class="flex mt-3 justify-evenly">
                                <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                                &nbsp; <p class="text-sm">PickUp City: </p> &nbsp; &nbsp; <span
                                    class="text-sm"><?php echo e($nameTo); ?></span>

                            </div>



                            <!--[if BLOCK]><![endif]--><?php if($tab === 'one_way'): ?>
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Drop City: </p> &nbsp; &nbsp; <span class="text-sm">

                                        <!--[if BLOCK]><![endif]--><?php if($tab == 'one_way'): ?>
                                            <?php echo e($nameFrom); ?>

                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->



                                        <!--[if BLOCK]><![endif]--><?php if($tab == 'return'): ?>
                                            <?php echo e($cityFrom); ?>

                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    </span>

                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <div class="flex mt-3">
                                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                &nbsp; <p class="text-sm">PickUp Date: </p> &nbsp; &nbsp; <span
                                    class="text-sm"><?php echo e($date); ?></span>

                            </div>

                            <!--[if BLOCK]><![endif]--><?php if($tab === 'self_drive' || $tab === 'return'): ?>
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Drop Date: </p> &nbsp; &nbsp; <span
                                        class="text-sm"><?php echo e($dateto); ?></span>

                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <!--[if BLOCK]><![endif]--><?php if($newTime): ?>
                                <div class="flex mt-3">


                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Pickup TIme: </p> &nbsp; &nbsp;
                                    <span class="text-sm"><?php echo e($newTime->format('h:i A')); ?></span>

                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <!--[if BLOCK]><![endif]--><?php if($endTime): ?>
                                <div class="flex mt-3">


                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Pickup TIme: </p> &nbsp; &nbsp;
                                    <span class="text-sm"><?php echo e($timeEnd->format('h:i A')); ?></span>

                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->




                            <!--[if BLOCK]><![endif]--><?php if($tab === 'return'): ?>
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Days: </p> &nbsp; &nbsp;
                                    <span class="text-sm"><?php echo e($days === 0 ? 1 : $days + 1); ?></span>

                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <!--[if BLOCK]><![endif]--><?php if($tab === 'self_drive'): ?>
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Hours: </p> &nbsp; &nbsp;
                                    <span class="text-sm"><?php echo e($selfDriveHours); ?></span>

                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <!--[if BLOCK]><![endif]--><?php if($tab === 'local'): ?>
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Plan: </p> &nbsp; &nbsp; <span
                                        class="text-sm"><?php echo e($plan); ?></span>

                                </div>

                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Cars: </p> &nbsp; &nbsp; <span
                                        class="text-sm"><?php echo e($cars); ?></span>

                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                        <h2 class="text-xl font-medium text-sky-500 dark:text-gray-400"> Need Help Booking?</h2>
                        <div class="flex mt-3">
                            <p>Call Our Customer Care Executive. We Are Available 24×7 Just Dial.</p>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-sky-600 p-1 text-sky-300 rounded-lg">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            </div>
                            &nbsp; <a href="tel:+91-7088873331"
                                class="text-sky-700 font-bold text-xl">+91-7088873331</a>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-green-500 p-1 text-sky-300 rounded-full">
                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                            </div>
                            &nbsp; &nbsp; <a href="tel:+91-7088873331"
                                class="text-green-500 font-bold text-xl">+91-7088873331</a>

                        </div>
                    </div>

                    <!--[if BLOCK]><![endif]--><?php if(!$tab): ?>

                        
                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Destination</h2>
                            <input type="text" wire:model.live='query2' placeholder="Search City.."
                                id="simple-search-1"
                                class="lg:mt-3 bg-gray-50 border border-gray-300 text-black font-extrabold  text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>
                            <ul>
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="mb-4" wire:key='<?php echo e($brand->id); ?>'>
                                        <label for="<?php echo e($brand->slug); ?>"
                                            class="flex items-center dark:text-gray-300">
                                            <input type="checkbox" wire:model.live='selected_brands'
                                                id='<?php echo e($brand->slug); ?>' value="<?php echo e($brand->id); ?>"
                                                class="w-4 h-4 mr-2">
                                            <span class="text-lg dark:text-gray-400"><?php echo e($brand->name); ?></span>
                                        </label>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                            </ul>

                        </div>


                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Price</h2>
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>
                            <div>
                                <div class="font-semibold"><?php echo e(Number::currency($price_range, 'INR')); ?></div>
                                <input type="range" wire:model.live='price_range'
                                    class="w-full h-1 mb-4 bg-blue-100 rounded appearance-none cursor-pointer"
                                    max="50000" value="100" step="10">
                                <div class="flex justify-between ">
                                    <span
                                        class="inline-block text-lg font-bold text-blue-400 "><?php echo e(Number::currency(1000, 'INR')); ?></span>
                                    <span
                                        class="inline-block text-lg font-bold text-blue-400 "><?php echo e(Number::currency(50000, 'INR')); ?></span>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                </div>
                <!--[if BLOCK]><![endif]--><?php if($tab === 'return'): ?>

                    <div class="w-full px-3 lg:w-3/4">
                        <div class="surface rides-toolbar mb-4 flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-800"><?php echo e($rides->total()); ?> <?php echo e(Str::plural('ride', $rides->total())); ?> available</p>
                                <p class="text-xs text-slate-500">Select a vehicle to continue your booking.</p>
                            </div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                                <span>Sort</span>
                                <select wire:model.live="sort" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="price">Price: low to high</option>
                                    <option value="latest">Latest first</option>
                                </select>
                            </label>
                        </div>
                        <div class="rides-results rides-premium-results grid items-center relative">
                            <!--[if BLOCK]><![endif]--><?php if (! ($fareUnlocked)): ?>
                                <div class="absolute inset-0 z-30 flex min-h-[420px] items-start justify-center rounded-2xl bg-white/90 px-4 pt-10 backdrop-blur-sm">
                                    <div class="surface max-w-md p-6 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-2xl">₹</div>
                                        <h3 class="mt-4 text-xl font-extrabold text-slate-900">Unlock exact cab fares</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">Verify your mobile with a 4 digit OTP. We will also save this trip as an inquiry so our team can help if your booking remains incomplete.</p>
                                        <button type="button" wire:click="openFareGate" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 font-extrabold text-white hover:bg-blue-700">View exact fares</button>
                                        <p class="mt-3 text-xs text-slate-500">Fast verification • No password required</p>
                                    </div>
                                </div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $categories2; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $returnFare = ($kmValue / 1000) * ($days === 0 ? 2 : 2) > ($days === 0 ? 1 : $days + 1) * $category->range
                                        ? ($kmValue / 1000) * ($days === 0 ? 2 : 2) * $category->km_charge + $category->driver_charge * ($days === 0 ? 0 : $days)
                                        : ($days === 0 ? 1 : $days + 1) * $category->range * $category->km_charge + $category->driver_charge * ($days === 1 ? 1 : $days);
                                    $returnKm = round(
                                        ($kmValue / 1000) * ($days === 0 ? 2 : $days + 1) > ($days === 0 ? 1 : $days + 1) * $category->range
                                            ? ($kmValue / 1000) * ($days === 0 ? 2 : $days + 1)
                                            : ($days === 0 ? 1 : $days + 1) * $category->range
                                    );
                                ?>
                                <article wire:key="return-category-<?php echo e($category->id); ?>" class="ride-package-card">
                                    <div class="ride-package-media">
                                        <span class="ride-package-badge ride-package-badge--green">Best price</span>
                                        <img src="<?php echo e(url('storage')); ?>/<?php echo e($category->image); ?>" alt="<?php echo e($category->name); ?>" loading="lazy">
                                    </div>
                                    <div class="ride-package-content">
                                        <div class="ride-package-title-row">
                                            <div>
                                                <h3><?php echo e($category->name); ?></h3>
                                                <div class="ride-package-rating" aria-label="5 star rated">
                                                    <!--[if BLOCK]><![endif]--><?php for($star = 0; $star < 5; $star++): ?>
                                                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                                                    <?php endfor; ?><!--[if ENDBLOCK]><![endif]-->
                                                    <span>5.0</span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="ride-package-model">Comfortable AC cab with a professional driver or similar vehicle.</p>
                                        <div class="ride-package-features">
                                            <span><i class="fa-solid fa-bottle-water"></i>Water Bottle</span>
                                            <span><i class="fa-solid fa-bolt"></i>Instant Booking</span>
                                            <span><i class="fa-solid fa-user-shield"></i>Trusted Driver</span>
                                            <span><i class="fa-solid fa-snowflake"></i>AC</span>
                                        </div>
                                    </div>
                                    <div class="ride-package-price">
                                        <p class="ride-package-price-label">Estimated trip fare</p>
                                        <strong><?php echo e(Number::currency($returnFare, 'INR')); ?></strong>
                                        <button type="button"
                                            onclick="showFareSummary(<?php echo e($category->id); ?>, '<?php echo e(addslashes($category->name)); ?>', <?php echo e($returnFare); ?>, <?php echo e($category->km_charge); ?>, <?php echo e($category->driver_charge); ?>, <?php echo e($category->range); ?>, <?php echo e($returnKm); ?>, <?php echo e($days === 0 ? 1 : $days); ?>)"
                                            class="ride-fare-icon-button" aria-label="View fare details" title="Fare details">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        </button>
                                        <a href="#"
                                            wire:click.prevent='addToCartReturn([<?php echo e($category->id); ?>,"<?php echo e($nameTo); ?>", "<?php echo e($cityFrom); ?>", "<?php echo e($returnFare); ?>","<?php echo e($date); ?>","<?php echo e($dateto); ?>","<?php echo e($time); ?>","<?php echo e($tab); ?>","<?php echo e($returnKm); ?>","<?php echo e($category->new_vehicle); ?>","<?php echo e($category->pet_friendly); ?>","<?php echo e($category->roof_career); ?>"])'
                                            class="ride-select-button">
                                            <span>Select Vehicle</span><i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                        </div>
                        <!-- pagination start -->
                        <div class="flex justify-end mt-6">
                            <?php echo e($rides->links()); ?>

                        </div>
                        <!-- pagination end -->
                    </div>
                <?php else: ?>
                    <div class="rides-layout-content w-full px-3 lg:w-3/4">
                        <div class="rides-filter-bar mb-4">
                            <label class="rides-filter-control">
                                <i class="fa-solid fa-arrow-down-wide-short"></i>
                                <span><small>Sort By</small>
                                    <select wire:model.live="sort">
                                        <option value="price">Price (Low to High)</option>
                                        <option value="latest">Latest First</option>
                                    </select>
                                </span>
                            </label>
                            <div class="rides-filter-control rides-filter-static">
                                <i class="fa-solid fa-car-side"></i>
                                <span><small>Vehicle Type</small><strong>All</strong></span>
                            </div>
                            <label class="rides-filter-control rides-price-filter">
                                <i class="fa-solid fa-tag"></i>
                                <span><small>Price Range</small><strong><?php echo e(Number::currency($price_range, 'INR')); ?></strong></span>
                                <input type="range" wire:model.live="price_range" min="1000" max="50000" step="500">
                            </label>
                            <button type="button" class="rides-reset-filter" wire:click="$set('price_range', 50000)">
                                <i class="fa-solid fa-rotate-right"></i><span>Reset Filters</span>
                            </button>
                            <strong class="rides-result-count"><?php echo e($rides->total()); ?> Packages Found</strong>
                        </div>
                        <div class="w-full">

                            <!--[if BLOCK]><![endif]--><?php if($tab === 'self_drive'): ?>
                                <div class="mb-3 flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                                    <div class="min-w-0">
                                        <h2 class="truncate text-sm font-extrabold text-slate-900 sm:text-base">Available Self Drive Cars</h2>
                                        <p class="text-xs text-slate-500"><?php echo e($rides->total()); ?> vehicle(s) found</p>
                                    </div>

                                </div>

                                <div class="sd-list">
                                    <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $rides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vehicle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <?php
                                            $hourlyPrice = max(
                                                0,
                                                (float) ($vehicle->hourly_price ?? 0)
                                            );

                                            $minimumBookingHours = max(
                                                1,
                                                (int) ($vehicle->minimum_booking_hours ?? 1)
                                            );

                                            $billableHours = max(
                                                $selfDriveHours,
                                                $minimumBookingHours
                                            );

                                            $securityDeposit = max(
                                                0,
                                                (float) ($vehicle->security_deposit ?? 0)
                                            );

                                            $rentalTotal = $hourlyPrice * $billableHours;
                                            $hasValidPrice = $hourlyPrice > 0;

                                            $vehicleImage = $vehicle->front_image_url
                                                ?: asset('cab_images/default-car.png');

                                            $vendorName = data_get($vehicle, 'transporter.business_name')
                                                ?: data_get($vehicle, 'transporter.company_name')
                                                ?: data_get($vehicle, 'transporter.name')
                                                ?: 'DuraCabs Partner';

                                            $vendorDistance = data_get($vehicle, 'vendor_distance')
                                                ?? data_get($vehicle, 'distance_km')
                                                ?? data_get($vehicle, 'distance');

                                            $distanceLabel = is_numeric($vendorDistance)
                                                ? number_format((float) $vendorDistance, 1) . ' km from pickup'
                                                : 'Pickup from vendor location';
                                        ?>

                                        <article wire:key="self-drive-vehicle-<?php echo e($vehicle->id); ?>" class="sd-premium-card">
                                            <div class="sd-premium-media">
                                                <img src="<?php echo e($vehicleImage); ?>"
                                                    alt="<?php echo e($vehicle->display_name); ?>"
                                                    title="<?php echo e($vehicle->display_name); ?>"
                                                    loading="lazy"
                                                    onerror="this.onerror=null;this.src='<?php echo e(asset('cab_images/default-car.png')); ?>';">
                                                <div class="sd-premium-badges">
                                                    <span class="sd-premium-badge sd-premium-badge-blue"><i class="fa-solid fa-car-side"></i> Self Drive</span>
                                                    <!--[if BLOCK]><![endif]--><?php if($vehicle->is_verified ?? false): ?>
                                                        <span class="sd-premium-badge sd-premium-badge-green"><i class="fa-solid fa-circle-check"></i> Verified</span>
                                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                                </div>
                                                <span class="sd-premium-year"><?php echo e((int) ($vehicle->manufacture_year ?? 0) > 0 ? $vehicle->manufacture_year : 'Premium'); ?></span>
                                            </div>

                                            <div class="sd-premium-body">
                                                <div class="sd-premium-heading">
                                                    <div>
                                                        <p class="sd-premium-eyebrow"><?php echo e($vendorName); ?></p>
                                                        <h3><?php echo e($vehicle->display_name); ?></h3>
                                                    </div>
                                                    <div class="sd-premium-rating"><i class="fa-solid fa-star"></i><strong>4.8</strong><span>Top rated</span></div>
                                                </div>

                                                <p class="sd-premium-location"><i class="fa-solid fa-location-dot"></i><?php echo e($distanceLabel); ?></p>

                                                <div class="sd-premium-specs">
                                                    <span><i class="fa-solid fa-gas-pump"></i><small>Fuel</small><strong><?php echo e(filled($vehicle->fuel_type) ? ucfirst($vehicle->fuel_type) : 'N/A'); ?></strong></span>
                                                    <span><i class="fa-solid fa-gears"></i><small>Transmission</small><strong><?php echo e(filled($vehicle->transmission) ? ucfirst($vehicle->transmission) : 'N/A'); ?></strong></span>
                                                    <span><i class="fa-solid fa-users"></i><small>Seats</small><strong><?php echo e($vehicle->seats ?: 'N/A'); ?></strong></span>
                                                    <span><i class="fa-regular fa-clock"></i><small>Duration</small><strong><?php echo e($selfDriveHours); ?> hrs</strong></span>
                                                </div>

                                                <div class="sd-premium-policy">
                                                    <span><i class="fa-solid fa-shield-halved"></i> Security <?php echo e(Number::currency($securityDeposit, 'INR')); ?></span>
                                                    <span><i class="fa-solid fa-bolt"></i> Instant confirmation</span>
                                                    <span><i class="fa-solid fa-headset"></i> 24×7 support</span>
                                                </div>
                                            </div>

                                            <div class="sd-premium-booking">
                                                <p class="sd-premium-price-label">Payable rental</p>
                                                <!--[if BLOCK]><![endif]--><?php if($hasValidPrice): ?>
                                                    <div class="sd-premium-price"><?php echo e(Number::currency($rentalTotal, 'INR')); ?></div>
                                                    <p class="sd-premium-rate"><?php echo e(Number::currency($hourlyPrice, 'INR')); ?> / hour</p>
                                                    <div class="sd-premium-billing">
                                                        <span>Selected <strong><?php echo e($selfDriveHours); ?> hrs</strong></span>
                                                        <span>Billable <strong><?php echo e($billableHours); ?> hrs</strong></span>
                                                    </div>
                                                    <!--[if BLOCK]><![endif]--><?php if($selfDriveHours < $minimumBookingHours): ?>
                                                        <p class="sd-premium-minimum"><i class="fa-solid fa-circle-info"></i> Minimum <?php echo e($minimumBookingHours); ?> hour billing applies</p>
                                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                                <?php else: ?>
                                                    <div class="sd-premium-unavailable">Price unavailable</div>
                                                    <p class="sd-premium-rate">Vendor price required</p>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                                <button type="button"
                                                    wire:click="addToCartSelfDrive(<?php echo e($vehicle->id); ?>)"
                                                    wire:loading.attr="disabled"
                                                    wire:target="addToCartSelfDrive(<?php echo e($vehicle->id); ?>)"
                                                    <?php if(!$hasValidPrice): echo 'disabled'; endif; ?>
                                                    class="sd-premium-cta">
                                                    <span wire:loading.remove wire:target="addToCartSelfDrive(<?php echo e($vehicle->id); ?>)">
                                                        <?php echo e($hasValidPrice ? 'Select This Car' : 'Unavailable'); ?>

                                                        <!--[if BLOCK]><![endif]--><?php if($hasValidPrice): ?><i class="fa-solid fa-arrow-right"></i><?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                                    </span>
                                                    <span wire:loading wire:target="addToCartSelfDrive(<?php echo e($vehicle->id); ?>)"><i class="fa-solid fa-spinner fa-spin"></i> Please wait</span>
                                                </button>
                                                <p class="sd-premium-safe"><i class="fa-solid fa-lock"></i> Secure booking</p>
                                            </div>
                                        </article>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <div class="w-full rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-2xl">🚗</div>
                                            <h3 class="mt-4 text-xl font-extrabold text-slate-900">No self-drive vehicle found</h3>
                                            <p class="mt-2 text-sm text-slate-600">Change the location, date, time or price filter and search again.</p>
                                            <button type="button" wire:click="showEditQueryModal"
                                                class="mt-5 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">
                                                Edit trip
                                            </button>
                                        </div>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            <?php else: ?>
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $rides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ride): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $ride->prices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $price): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $displayPrice = $tab === 'local' ? $price->price * max(1, (int) $cars) : $price->price;
                                        $displayMaxPrice = $tab === 'local' ? $price->max_price * max(1, (int) $cars) : $price->max_price;
                                        $badgeLabel = $loop->parent->first && $loop->first ? 'Best price' : ($loop->parent->iteration === 2 ? 'Popular' : 'Comfort');
                                        $badgeClass = $loop->parent->first && $loop->first ? 'ride-package-badge--green' : ($loop->parent->iteration === 2 ? 'ride-package-badge--blue' : 'ride-package-badge--purple');
                                    ?>
                                    <article wire:key="ride-<?php echo e($ride->id); ?>-price-<?php echo e($price->id ?? $loop->index); ?>" class="ride-package-card">
                                        <div class="ride-package-media">
                                            <span class="ride-package-badge <?php echo e($badgeClass); ?>"><?php echo e($badgeLabel); ?></span>
                                            <a href="/route/<?php echo e($ride->slug); ?>" aria-label="View <?php echo e($ride->name); ?> route details">
                                                <img src="<?php echo e(url('storage')); ?>/<?php echo e($price->category->image); ?>" alt="<?php echo e($price->category->name); ?>" loading="lazy">
                                            </a>
                                        </div>
                                        <div class="ride-package-content">
                                            <h3><?php echo e($price->category->name); ?></h3>
                                            <div class="ride-package-rating" aria-label="5 star rated">
                                                <!--[if BLOCK]><![endif]--><?php for($star = 0; $star < 5; $star++): ?>
                                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                                <?php endfor; ?><!--[if ENDBLOCK]><![endif]-->
                                                <span>5.0</span>
                                            </div>
                                            <p class="ride-package-model"><?php echo e($price->category->model ?: $ride->name); ?> or similar</p>
                                            <div class="ride-package-features">
                                                <span><i class="fa-solid fa-bottle-water"></i>Water Bottle</span>
                                                <span><i class="fa-solid fa-bolt"></i>Instant Booking</span>
                                                <span><i class="fa-solid fa-user-shield"></i>Trusted Driver</span>
                                                <span><i class="fa-solid fa-snowflake"></i>AC</span>
                                            </div>
                                        </div>
                                        <div class="ride-package-price">
                                            <!--[if BLOCK]><![endif]--><?php if($displayMaxPrice > $displayPrice): ?>
                                                <del><?php echo e(Number::currency($displayMaxPrice, 'INR')); ?></del>
                                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                            <strong><?php echo e(Number::currency($displayPrice, 'INR')); ?></strong>
                                            <!--[if BLOCK]><![endif]--><?php if($tab === 'local'): ?>
                                                <button type="button"
                                                    onclick="showFareSummaryLocal('<?php echo e(addslashes($ride->name)); ?>', '<?php echo e(addslashes($price->category->name)); ?>', <?php echo e($displayPrice); ?>, <?php echo e($displayMaxPrice); ?>, <?php echo e(max(1, (int) $cars)); ?>, '<?php echo e(addslashes((string) $plan)); ?>', <?php echo e($ride->extra_km_charge ?? 0); ?>, <?php echo e($ride->extra_hr_charge ?? 0); ?>, <?php echo e($ride->driver_allowances ?? 0); ?>)"
                                                    class="ride-fare-icon-button" aria-label="View fare details" title="Fare details">
                                                    <i class="fa-solid fa-circle-info"></i><span>Fare details</span>
                                                </button>
                                                <a href="#" wire:click.prevent='addToCartLocal([<?php echo e($ride->id); ?>,"<?php echo e($time); ?>","<?php echo e($tab); ?>","<?php echo e($date); ?>","<?php echo e($plan); ?>","<?php echo e($cars); ?>","<?php echo e($displayPrice); ?>","<?php echo e($ride->name); ?>", "<?php echo e($price->category->name); ?>","<?php echo e($ride->toll_tax); ?>","<?php echo e($ride->category->new_vehicle); ?>","<?php echo e($ride->category->pet_friendly); ?>","<?php echo e($ride->category->roof_career); ?>"])' class="ride-select-button">
                                                    <span>Select Vehicle</span><i class="fa-solid fa-arrow-right"></i>
                                                </a>
                                            <?php else: ?>
                                                <button type="button"
                                                    onclick="showFareSummaryOneWay('<?php echo e(addslashes($ride->name)); ?>', '<?php echo e(addslashes($price->category->name)); ?>', <?php echo e($displayPrice); ?>, <?php echo e($displayMaxPrice); ?>, <?php echo e($ride->toll_tax ?? 0); ?>, <?php echo e($ride->km_limit ?? 0); ?>, <?php echo e($ride->hr_limit ?? 0); ?>, <?php echo e($ride->extra_km_charge ?? 0); ?>, <?php echo e($ride->extra_hr_charge ?? 0); ?>)"
                                                    class="ride-fare-icon-button" aria-label="View fare details" title="Fare details">
                                                    <i class="fa-solid fa-circle-info"></i><span>Fare details</span>
                                                </button>
                                                <a href="#" wire:click.prevent='addToCartOneWay([<?php echo e($ride->id); ?>,"<?php echo e($time); ?>","<?php echo e($tab); ?>","<?php echo e($date); ?>","<?php echo e($displayPrice); ?>","<?php echo e($ride->name); ?>", "<?php echo e($price->category->name); ?>","<?php echo e($ride->toll_tax); ?>","<?php echo e($ride->category->new_vehicle); ?>","<?php echo e($ride->category->pet_friendly); ?>","<?php echo e($ride->category->roof_career); ?>"])' class="ride-select-button">
                                                    <span>Select Vehicle</span><i class="fa-solid fa-arrow-right"></i>
                                                </a>
                                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        </div>
                                    </article>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->





                        </div>
                        <!-- pagination start -->
                        <div class="flex justify-end mt-6">
                            <?php echo e($rides->links()); ?>

                        </div>
                        <!-- pagination end -->
                    </div>

                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->



                <div class="w-full pr-2 lg:w-1/4 lg:hidden block">



                    <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                        <h2 class="text-2xl font-medium text-sky-500 dark:text-gray-400"> Need Help Booking?</h2>
                        <div class="flex mt-3">
                            <p>Call Our Customer Care Executive. We Are Available 24×7 Just Dial.</p>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-sky-600 p-1 text-sky-300 rounded-lg">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            </div>
                            &nbsp; <a href="tel:+91-7088873331"
                                class="text-sky-700 font-bold text-xl">+91-7088873331</a>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-green-500 p-1 text-sky-300 rounded-full">
                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                            </div>
                            &nbsp; &nbsp; <a href="tel:+91-7088873331"
                                class="text-green-500 font-bold text-xl">+91-7088873331</a>

                        </div>
                    </div>


                    <!--[if BLOCK]><![endif]--><?php if(!$tab): ?>

                        <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Cab Categories</h2>
                            
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>

                            <ul>
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="mb-4" wire:key='<?php echo e($category->id); ?>'>
                                        <label for="<?php echo e($category->slug); ?>"
                                            class="flex items-center dark:text-gray-400 ">
                                            <input type="checkbox" wire:model.live='selected_categories'
                                                id="<?php echo e($category->slug); ?>" value="<?php echo e($category->id); ?>"
                                                class="w-4 h-4 mr-2">
                                            <span class="text-lg"><?php echo e($category->name); ?> </span>
                                        </label>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                            </ul>

                        </div>
                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Destination</h2>
                            <input type="text" wire:model.live='query2' placeholder="Search City.."
                                id="simple-search-1"
                                class="lg:mt-3 bg-gray-50 border border-gray-300 text-black font-extrabold  text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>
                            <ul>
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="mb-4" wire:key='<?php echo e($brand->id); ?>'>
                                        <label for="<?php echo e($brand->slug); ?>"
                                            class="flex items-center dark:text-gray-300">
                                            <input type="checkbox" wire:model.live='selected_brands'
                                                id='<?php echo e($brand->slug); ?>' value="<?php echo e($brand->id); ?>"
                                                class="w-4 h-4 mr-2">
                                            <span class="text-lg dark:text-gray-400"><?php echo e($brand->name); ?></span>
                                        </label>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                            </ul>

                        </div>


                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Price</h2>
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>
                            <div>
                                <div class="font-semibold"><?php echo e(Number::currency($price_range, 'INR')); ?></div>
                                <input type="range" wire:model.live='price_range'
                                    class="w-full h-1 mb-4 bg-blue-100 rounded appearance-none cursor-pointer"
                                    max="50000" value="100" step="10">
                                <div class="flex justify-between ">
                                    <span
                                        class="inline-block text-lg font-bold text-blue-400 "><?php echo e(Number::currency(1000, 'INR')); ?></span>
                                    <span
                                        class="inline-block text-lg font-bold text-blue-400 "><?php echo e(Number::currency(50000, 'INR')); ?></span>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->


                </div>

            </div>
        </div>
        <section class="max-w-4xl mx-auto px-4 py-10 bg-white">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">
                Explore Flexible Ride Options with Duracabs – From Local Cabs to Intercity One Way Taxi Services
            </h1>

            <p class="text-base md:text-lg text-gray-700 mb-4">
                Duracabs brings you an extensive selection of ride categories to suit your travel needs—whether you're
                looking for a convenient
                <strong class="font-semibold">taxi service</strong> nearby or planning a long-distance journey like a
                <strong class="font-semibold">Delhi to Agra taxi</strong> or
                <strong class="font-semibold">Agra to Delhi taxi</strong>. Our platform makes
                <strong class="font-semibold">online cab booking</strong> fast, easy, and reliable.
            </p>
        </section>

    </section>
    <section class="max-w-5xl mx-auto px-4 py-10 bg-white">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
            Flexible Travel Options: From Car Rentals to Airport Taxis & Tempo Travellers
        </h2>


        <p class="text-base md:text-lg text-gray-700 mb-4">
            Need more freedom? Try our affordable
            <strong class="font-semibold">car rental</strong> options or choose a
            <strong class="font-semibold">one way cab</strong> to skip return trip costs. For airport transfers, our
            <strong class="font-semibold">Delhi airport taxi booking</strong> ensures timely pickup and drop. Traveling
            in a group?
            Book a spacious <strong class="font-semibold">tempo traveller for rent</strong> for a hassle-free
            experience.
        </p>

        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
            Clean, GPS-Enabled Fleet for Reliable One Way Taxi Services and Local Travel
        </h2>

        <p class="text-base md:text-lg text-gray-700">
            Every vehicle in our fleet is clean, GPS-enabled, and ready for your journey. From solo city rides to
            cross-state travel,
            Duracabs is your smart choice for
            <strong class="font-semibold">one way taxi services</strong> and reliable
            <strong class="font-semibold">taxi service near me</strong>.
        </p>
    </section>

    <!-- Enhanced Fare details Popup -->
    <div id="fareSummaryModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 scale-95">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fa-solid fa-calculator" aria-hidden="true"></i>
                        <h3 class="text-xl font-bold">Fare Breakdown</h3>
                    </div>
                    <button onclick="closeFareSummary()"
                        class="text-white hover:text-gray-200 transition duration-200">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-4">
                <!-- Vehicle Information -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 font-semibold flex items-center">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            Vehicle Category:
                        </span>
                        <span id="carCategory" class="font-bold text-blue-700"></span>
                    </div>
                </div>

                <!-- Fare Breakdown -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 font-medium">Base Fare:</span>
                        <span id="baseFare" class="font-semibold text-gray-900"></span>
                    </div>

                    <div id="driverAllowanceSection"
                        class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 font-medium">Driver Allowance:</span>
                        <span id="driverAllowance" class="font-semibold text-gray-900">Included</span>
                    </div>

                    <div id="tollTaxSection" class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 font-medium">Toll Tax:</span>
                        <span id="tollTaxStatus" class="font-semibold text-red-600">Excluded</span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 font-medium">GST (5%):</span>
                        <span id="gstAmount" class="font-semibold text-gray-900"></span>
                    </div>
                </div>

                <!-- Total -->
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-800">Total Amount:</span>
                        <span id="totalPrice" class="text-2xl font-bold text-green-600"></span>
                    </div>
                </div>

                <!-- Important Notes -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <div>
                            <h4 class="font-semibold text-yellow-800 mb-2">Important Information:</h4>
                            <div id="fareNotes" class="text-sm text-yellow-700">
                                Extra Charge After: <span id="extraKmLimit"></span> KMS. will be ₹<span
                                    id="extraKmRate"></span>/KM.<br>
                                There will be a night Allowance of ₹0 for the driver. after 8PM<br>
                                <strong>Toll-Tax:</strong> Excluded |
                                <strong>Parking:</strong> Extra (if applicable)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="text-center text-sm text-gray-500 bg-gray-50 p-3 rounded-lg">
                    <div class="flex items-center justify-center">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        Extra KM charges would be directly paid to the driver.
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="px-6 pb-6">
                <div class="flex space-x-3">
                    <button onclick="closeFareSummary()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Query Modal -->
    <!--[if BLOCK]><![endif]--><?php if($showOtpModal): ?>
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 px-4" wire:click.self="closeOtpModal">
            <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="bg-gradient-to-r from-blue-700 to-sky-500 px-6 py-5 text-white">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-100">DuraCabs secure verification</p>
                            <h3 class="mt-1 text-2xl font-extrabold"><?php echo e($otpStage === 'mobile' ? 'Unlock exact fare' : 'Enter 4 digit OTP'); ?></h3>
                        </div>
                        <button type="button" wire:click="closeOtpModal" class="rounded-full bg-white/15 p-2 text-white hover:bg-white/25" aria-label="Close">✕</button>
                    </div>
                </div>

                <div class="p-6">
                    <!--[if BLOCK]><![endif]--><?php if($otpStage === 'mobile'): ?>
                        <p class="mb-5 text-sm leading-6 text-slate-600">Enter your mobile number to view live cab prices and availability.</p>
                        <label class="mb-2 block text-sm font-bold text-slate-800">Mobile number</label>
                        <div class="flex overflow-hidden rounded-xl border border-slate-300 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-100">
                            <span class="flex items-center bg-slate-50 px-4 font-bold text-slate-600">+91</span>
                            <input type="tel" maxlength="10" inputmode="numeric" wire:model.live="mobileNumber" wire:keydown.enter="sendFareOtp" class="min-h-12 w-full border-0 px-4 text-lg font-bold tracking-wide outline-none focus:ring-0" placeholder="9876543210" autofocus>
                        </div>
                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['mobileNumber'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

                        <button type="button" wire:click="sendFareOtp" wire:loading.attr="disabled" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 font-extrabold text-white hover:bg-blue-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="sendFareOtp">Send 4 digit OTP</span>
                            <span wire:loading wire:target="sendFareOtp">Sending…</span>
                        </button>
                    <?php else: ?>
                        <p class="text-sm text-slate-600">OTP sent to <strong>+91 <?php echo e($mobileNumber); ?></strong></p>
                        <button type="button" wire:click="$set('otpStage', 'mobile')" class="mt-1 text-sm font-bold text-blue-600">Change number</button>

                        <label class="mb-2 mt-5 block text-sm font-bold text-slate-800">4 digit OTP</label>
                        <input type="text" maxlength="4" inputmode="numeric" autocomplete="one-time-code" wire:model.live="otpCode" wire:keydown.enter="verifyFareOtp" class="min-h-14 w-full rounded-xl border border-slate-300 px-4 text-center text-3xl font-extrabold tracking-[.65em] outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="••••" autofocus>
                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['otpCode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

                        <!--[if BLOCK]><![endif]--><?php if($otpError): ?><div class="mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?php echo e($otpError); ?></div><?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        <button type="button" wire:click="verifyFareOtp" wire:loading.attr="disabled" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 font-extrabold text-white hover:bg-blue-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="verifyFareOtp">Verify & view fares</span>
                            <span wire:loading wire:target="verifyFareOtp">Verifying…</span>
                        </button>

                        <div class="mt-4 text-center text-sm text-slate-500">
                            <template x-if="otpSeconds > 0"><span>Resend OTP in <strong x-text="otpSeconds"></strong>s</span></template>
                            <button x-show="otpSeconds <= 0" type="button" wire:click="resendFareOtp" class="font-bold text-blue-600">Resend OTP</button>
                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    <p class="mt-5 text-center text-xs leading-5 text-slate-500">By continuing, you agree to receive booking assistance related to this trip.</p>
                </div>
            </div>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!--[if BLOCK]><![endif]--><?php if($showEditModal): ?>
        <div class="fixed inset-0 z-50 flex min-h-full items-start justify-center overflow-y-auto bg-slate-950/55 p-3 pt-8 backdrop-blur-sm sm:p-6 sm:pt-14"
            wire:click.self="$set('showEditModal', false)">
            <div class="relative w-full max-w-5xl rounded-3xl border border-white/70 bg-white p-4 shadow-2xl sm:p-6">
                <!-- Modal Header -->
                <div class="mb-5 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div><p class="text-xs font-bold uppercase tracking-wider text-sky-600">Trip search</p><h3 class="mt-1 text-xl font-extrabold text-slate-900">Edit Trip</h3><p class="mt-1 text-sm text-slate-500">Update only the details for your current service.</p></div>
                    <button wire:click="$set('showEditModal', false)" type="button" aria-label="Close edit trip" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-800">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                
                <?php echo $__env->make('livewire.service-search-panel', [
                    'searchPanelMode' => 'ride_edit',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
</div>
<?php endif; ?><!--[if ENDBLOCK]><![endif]-->


</div>
<?php /**PATH C:\xampp\htdocs\duracabs\resources\views/livewire/rides-page.blade.php ENDPATH**/ ?>