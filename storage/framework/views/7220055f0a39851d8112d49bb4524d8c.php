<div class="w-full max-w-[85rem] py-6 md:py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <section class=" dark:bg-gray-800 ">
        <div
            class="justify-center flex-1 max-w-6xl  mx-auto bg-white border rounded-md dark:border-gray-900 dark:bg-gray-900 ">
            <hr class="h-7 mb-5 bg-sky-500 border-0 dark:bg-gray-700" />
            <div class="py-4 px-4 md:py-0 md:px-10">

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                    <div class="w-full md:w-1/2">
                        <img class="w-1/2 md:w-1/2 max-w-[200px]" src="<?php echo e(url('storage')); ?>/images/Duracab-Logo-425x115.png" alt="Duracabs Success" />
                    </div>
                    <div class="w-full md:w-auto text-left md:text-right">
                        <div class="mb-2"> <span class="text-sm md:text-base">Booking No :</span> <a href="tel:+917088873331"
                                class="font-bold text-sm md:text-base">+91-7088873331 </a></div>
                        <div class="mb-2"> <span class="text-sm md:text-base">Whatsapp No :</span> <a href="tel:+917088873332"
                                class="font-bold text-sm md:text-base">+91-7088873332 </a></div>
                        <p class="text-sm md:text-base"> 24 x 7 Customer Support </p>
                    </div>
                </div>
                

                <h1 class="px-4 mb-6 md:mb-8 text-xl md:text-2xl my-4 md:my-5 font-semibold tracking-wide text-gray-700 dark:text-gray-300">
                    Thank you. Your order has been received. </h1>
                <hr class="h-1 mb-5 bg-sky-500 border-0 dark:bg-gray-700" />
                <div
                    class="flex flex-col border-b border-gray-200 dark:border-gray-700 items-stretch w-full h-full px-4 mb-8 md:flex-row xl:flex-auto md:space-x-6 lg:space-x-6 xl:space-x-0 space-y-6 md:space-y-0">

                    <div class="w-full md:w-1/2">
                        <p class="text-lg md:text-xl text-left pb-4 font-semibold leading-6 text-gray-800 dark:text-gray-400">
                            Traveller Detail : <br />
                        </p>
                        <div class="flex items-center justify-start w-full pb-6 space-x-4">
                            <div class="flex flex-col items-start justify-start space-y-2">
                                <div class="flex">
                                    <p class="text-base md:text-lg font-semibold leading-4 text-left text-gray-800 dark:text-gray-400">
                                        Name : <?php echo e($order->address->full_name); ?></p>
                                </div>
                                <p class="text-sm leading-4 cursor-pointer dark:text-gray-400">
                                    <span class="text-base md:text-lg font-semibold leading-4 text-gray-800 dark:text-gray-400">
                                        Mobile : </span> <?php echo e($order->address->phone); ?>

                                </p>
                                <p class="text-sm leading-4 cursor-pointer dark:text-gray-400">
                                    <span class="text-base md:text-lg font-semibold leading-4 text-gray-800 dark:text-gray-400">
                                        Email : </span> <?php echo e($order->address->email); ?>

                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="w-full md:w-1/2 px-0 md:px-8">
                        <div class="">
                            <div class="flex justify-start md:justify-end">
                                <div class="text-left md:text-right">
                                    <h1 class="text-xl md:text-3xl text-sky-600 font-bold uppercase">Booking ID : <span
                                            class="text-xl md:text-3xl text-sky-600 font-bold"><?php echo e($order->id); ?></span></h1>
                                    <p class="text-base md:text-xl leading-6 text-gray-800 dark:text-gray-400">
                                        <span class="font-semibold"> Generated On : </span>
                                        <span class="text-sm md:text-lg">Date
                                            <?php echo e($order->created_at->setTimezone('Asia/Kolkata')->format('d-m-y')); ?> Time
                                            <?php echo e($order->created_at->setTimezone('Asia/Kolkata')->format('h:i a')); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pb-4 mb-10 border-b border-gray-200 dark:border-gray-700">
                    <div class="px-4 mb-4">
                        <p class="mb-2 text-sm leading-5 text-gray-600 dark:text-gray-400">
                            Order Number: </p>
                        <p class="text-base font-semibold leading-4 text-gray-800 dark:text-gray-400">
                            <?php echo e($order->id); ?></p>
                    </div>
                    <div class="px-4 mb-4">
                        <p class="mb-2 text-sm leading-5 text-gray-600 dark:text-gray-400">
                            Start Date & Time: </p>
                        <p class="text-base font-semibold leading-4 text-gray-800 dark:text-gray-400">
                            <?php echo e(\Carbon\Carbon::parse($order->date)->format('d/m/Y')); ?>

                            <?php echo e(\Carbon\Carbon::parse($order->time)->format('h:i:a')); ?></p>
                    </div>
                    <div class="px-4 mb-4">
                        <p class="mb-2 text-sm font-medium leading-5 text-gray-800 dark:text-gray-400">
                            Total: </p>
                        <p class="text-base font-semibold leading-4 text-blue-600 dark:text-gray-400">
                            <?php echo e(Number::currency($order->grand_total, 'INR')); ?> </p>
                    </div>
                    <div class="px-4 mb-4">
                        <p class="mb-2 text-sm leading-5 text-gray-600 dark:text-gray-400">
                            Payment Method: </p>
                        <p class="text-base font-semibold leading-4 text-gray-800 dark:text-gray-400">
                            <?php echo e($order->payment_method == 'cash' ? 'Cash' : 'Online'); ?> </p>
                    </div>
                </div>
                <div class="px-2 mb-6 md:mb-10">
                    <div
                        class="flex flex-col items-stretch justify-center w-full space-y-8 md:flex-row md:space-y-0 md:space-x-8">
                        <div class="flex flex-col w-full md:w-1/2 px-2 space-y-2 capitalize">
                            <h2 class="mb-2 text-xl font-semibold text-gray-700 dark:text-gray-400">Trip Details</h2>

                            <?php if($order->ride_type === 'return'): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Trip Route
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400 break-words">
                                            <?php echo e($order->cityFrom); ?> -> <?php echo e($order->cityTo); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($product->ride_type === 'one_way'): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Trip Route
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400 break-words">
                                            <?php echo e($product->name); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                <div class="flex-shrink-0 sm:w-40">
                                    <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                        Trip Type
                                    </p>
                                </div>
                                <div class="flex-1 sm:text-right">
                                    <p class="text-sm md:text-base text-gray-800 dark:text-gray-400 capitalize">
                                        <?php echo e($order->ride_type); ?>

                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                <div class="flex-shrink-0 sm:w-40">
                                    <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                        Taxi Type
                                    </p>
                                </div>
                                <div class="flex-1 sm:text-right">
                                    <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                        <?php echo e($order->productName); ?>

                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                <div class="flex-shrink-0 sm:w-40">
                                    <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                        Pickup Address
                                    </p>
                                </div>
                                <div class="flex-1 sm:text-right">
                                    <p class="text-sm md:text-base text-gray-800 dark:text-gray-400 break-words leading-relaxed">
                                        <?php echo e($order->address->pickup_address); ?>

                                    </p>
                                </div>
                            </div>
                            <?php if($order->address->drop_address): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Drop Address
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400 break-words leading-relaxed">
                                            <?php echo e($order->address->drop_address); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($order->ride_type === 'one_way'): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Total KM
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                            <?php echo e($product->km_limit); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($order->ride_type === 'return'): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Total KM
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                            <?php echo e($order->total_km); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if($order->ride_type === 'return' || $order->ride_type === 'self_drive'): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            End Date
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                            <?php echo e(\Carbon\Carbon::parse($order->dateTo)->format('d/m/Y')); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if($order->ride_type === 'self_drive'): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            End Time
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                            <?php echo e(\Carbon\Carbon::parse($order->endTime)->format('h:i a')); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>


                            <?php if($order->ride_type === 'one_way'): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Total Hour
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                            <?php echo e($product->hr_limit); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($order->ride_type === 'return'): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Total Days
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                            <?php echo e($days + 1); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($order->address->comments): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Comments
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400 break-words leading-relaxed">
                                            <?php echo e($order->address->comments); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if($order->address->number_travellers): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Number Of Travellers
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                            <?php echo e($order->address->number_travellers); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if($order->address->number_luggage): ?>
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between w-full gap-2 sm:gap-4 py-2">
                                    <div class="flex-shrink-0 sm:w-40">
                                        <p class="text-sm md:text-base font-semibold text-gray-800 dark:text-gray-400">
                                            Number of Luggage
                                        </p>
                                    </div>
                                    <div class="flex-1 sm:text-right">
                                        <p class="text-sm md:text-base text-gray-800 dark:text-gray-400">
                                            <?php echo e($order->address->number_luggage); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>


                        </div>
                        <hr class="my-5 "/>
                        <div class="flex flex-col w-full md:w-1/2 space-y-6">
                            <h2 class="mb-2 text-xl font-semibold text-gray-700 dark:text-gray-400">Fare Summary</h2>
                            <div
                                class="flex flex-col items-center justify-center w-full pb-4 space-y-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between w-full">
                                    <p class="text-base leading-4 text-gray-800 dark:text-gray-400">Subtotal</p>
                                    <p class="text-base font-semibold leading-4 text-gray-600 dark:text-gray-400">
                                        <?php echo e(Number::currency($order->grand_total - $order->tax - collect($order->extraOptions)
        ->where('is_checked', operator: true)
        ->sum('price') + $order->coupon_value, 'INR')); ?>

                                    </p>
                                </div>
                                <div class="flex items-center justify-between w-full">
                                    <p class="text-base leading-4 text-gray-800 dark:text-gray-400">Discount
                                    </p>
                                    <p class="text-base leading-4 text-gray-600 dark:text-gray-400">
                                        <?php echo e(Number::currency($order->coupon_value, 'INR')); ?></p>
                                </div>
                                <?php if($order->ride_type === 'return'): ?>
                                    <div class="flex items-center justify-between w-full">
                                        <p class="text-base leading-4 text-gray-800 dark:text-gray-400">Driver
                                            Allowance
                                        </p>
                                        <p class="text-base leading-4 text-gray-600 dark:text-gray-400">
                                            <?php echo e(Number::currency($driverCharge * $days, 'INR')); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if(count($order->extraOptions) > 1): ?>
                               
                                    <?php $__currentLoopData = $order->extraOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                      <?php if($item['is_checked'] == true): ?> 
                                      <div class="flex items-center justify-between w-full">
                                            <p class="text-base leading-4 text-gray-800 dark:text-gray-400">
                                               <?php echo e($item['title']); ?></p> 
                                            <p class="text-base leading-4 text-gray-600 dark:text-gray-400">
                                                <?php echo e(Number::currency($item['price'], 'INR')); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>  

                                <div class="flex items-center justify-between w-full">
                                    <p class="text-base leading-4 text-gray-800 dark:text-gray-400">Tax</p>
                                    <p class="text-base leading-4 text-gray-600 dark:text-gray-400">
                                        <?php echo e(Number::currency($order->tax, 'INR')); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between w-full">
                                <p class="text-base font-semibold leading-4 text-gray-800 dark:text-gray-400">Total</p>
                                <p class="text-base font-semibold leading-4 text-gray-600 dark:text-gray-400">
                                    <?php echo e(Number::currency($order->grand_total, 'INR')); ?></p>
                            </div>
                            <h2 class="mb-2 text-xl font-semibold text-gray-700 dark:text-gray-400">Payment Details
                            </h2>
                            <div
                                class="flex flex-col items-center justify-center w-full pb-4 space-y-4 border-b border-gray-200 dark:border-gray-700">

                                <?php if($invoices): ?>
                                    <?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex justify-between w-full">
                                            <p class="text-base leading-4 text-gray-800 dark:text-gray-400 uppercase">
                                                Invoice (#<?php echo e($item->id); ?>)</p>
                                            <p
                                                class="text-base font-semibold leading-4 uppercase text-gray-600 dark:text-gray-400">
                                                <?php echo e($item->status); ?>

                                            </p>

                                            <p
                                                class="text-base font-semibold leading-4 text-gray-600 dark:text-gray-400">
                                                <?php echo e($item->date); ?>

                                            </p>

                                            <p
                                                class="text-base font-semibold leading-4 text-gray-600 dark:text-gray-400">
                                                <?php echo e($item->ammount); ?>

                                            </p>

                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                <?php endif; ?>





                            </div>
                            <div class="flex items-center justify-between w-full">
                                <p class="text-base font-semibold leading-4 text-gray-800 dark:text-gray-400">Total
                                    Paid</p>
                                <p class="text-base font-semibold leading-4 text-gray-600 dark:text-gray-400">
                                    <?php echo e(Number::currency($InvoiceSum, 'INR')); ?></p>
                            </div>
                            <div class="flex items-center justify-between w-full">
                                <p class="text-base font-semibold leading-4 text-gray-800 dark:text-gray-400">Total Due
                                </p>
                                <p class="text-base font-semibold leading-4 text-gray-600 dark:text-gray-400">
                                    <?php echo e(Number::currency($order->grand_total - $InvoiceSum, 'INR')); ?></p>


                            </div>
                            <?php if(count($order->extraOptions) == 1): ?>
                             
                                    <?php $__currentLoopData = $order->extraOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-center justify-between w-full">
                                            <p class="text-base leading-4 text-gray-800 dark:text-gray-400">
                                               <?php echo e($item['title']); ?></p> 
                                            <p class="text-base leading-4 text-gray-600 dark:text-gray-400">
                                                <?php echo e(Number::currency($item['price'], 'INR')); ?></p>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>  
                        </div>

                    </div>
                </div>

                <div class="px-2">
                    <hr class="h-1 mb-5 bg-sky-500 border-0 dark:bg-gray-700" />
                    
                        
                    <h1 class="font-semibold text-base md:text-lg mb-3">Booking Terms and Conditions</h1>

                    <p class="text-xs md:text-sm leading-relaxed mb-4">The Services are provided by Dura Cabs Services (hereinafter referred to as
                        "Dura" or "Duracabs"), located at Uttar Pradesh Agra 282001. Please read these terms carefully.
                        By using our services, you are agreeing to all terms & conditions set forth. In this agreement,
                        the words "including" and "include" mean "including, but not limited to."</p>
                    <ul class="space-y-2 my-4 text-xs md:text-sm">
                        <li>> Collect Rs.<?php echo e($order->grand_total); ?>/- collect the below extra(add 5%GST on all
                            extras)</li>
                        <?php if($order->ride_type === 'one_way'): ?>
                            <li>> Extra Hours(Beyond hours)will be Charged :Rs.<?php echo e($product->extra_hr_charge); ?>/-hr.
                            </li>
                            <li>> Extra KM (Beyond hours)will be Charged :Rs<?php echo e($product->extra_km_charge); ?>/- km</li>
                        <?php endif; ?>

                        <?php if($order->ride_type === 'return'): ?>
                            <li>> Extra KM (Beyond hours)will be Charged :Rs<?php echo e($perKm); ?>/- km</li>
                        <?php endif; ?>


                        <?php if($order->ride_type === 'self_drive'): ?>
                            <li>> This fare dose not include inter-state tax , toll , parking & fule please pay directly
                                to the
                                authorities.</li>

                            <li>
                                > Pickup and drop charges are additional and will depend on the distance from the
                                pickup/drop location to the designated rental office.
                                The charge will also vary depending on the time of day. Premium rates may apply for
                                late-night or early-morning services (between [Specify Time Range, e.g., 10 PM to 6
                                AM]).
                            </li>
                            <li>> If the renter drives the car more than 300 kilometres per day, an additional charge of Rs 7 per additional kilometre will be applicable.</li>

                            <li>
                                > The charge will also vary depending on the time of day. Premium rates may apply for
                                late-night or early-morning services (between [Specify Time Range, e.g., 10 PM to 6
                                AM]).
                            </li>

                            <li>
                                > Customers can avoid extra pickup and drop charges by collecting or returning the
                                vehicle directly at the Dura Cabs Services office during official business hours.
                            </li>
                        <?php else: ?>
                            <li>> This fare dose not include inter-state tax , toll , parking please pay directly to the
                                authorities.</li>
                            <li>> For night time driving (between 10:00 Pm to 6:00 Am)on any of the night , there will
                                be
                                a night allowance Rs.250 for the driver.</li>
                        <?php endif; ?>

                    </ul>

                    <h1 class="font-semibold text-base md:text-lg mb-3 mt-6">Cancel & Refund Policy</h1>
                    <ul class="space-y-2 my-4 text-xs md:text-sm">
                    
                    
                    
        <li>
                                > Free Cancellation: Cancel within the free period mentioned in your booking for no charges.</li>
        
        <li>
                                > After Free Period:
         
                <li>
                                > Cancel more than 4 hrs before pickup → 25% cancellation fee.</li>
                <li>
                                > Cancel within 4 hrs of pickup / No-Show → 100% of booking amount charged.</li>
            
        </li>
        
        <li>
                                > If DURA Cancels: You get a full refund + DURA Coins as credits for future bookings.</li>
        <li>
                                > Driver Delay: If the driver is delayed, cancellation charges are waived.</li>
        <li>
                                > Refunds: Processed within 21 days, after deducting applicable cancellation fees.</li>
    </ul>

                    
                    
                    </p>







                </div>

            </div>
            <hr class="h-7 mt-5 bg-sky-500 border-0 dark:bg-gray-700" />

        </div>

        <div class="justify-center flex-1 max-w-6xl mx-auto mt-5 px-4">
            <div class="flex flex-col sm:flex-row gap-4 sm:justify-center">
                <a href="/products"
                    class="w-full sm:w-auto text-center px-6 py-3 text-blue-500 border border-blue-500 rounded-md hover:text-white hover:bg-blue-600 dark:border-gray-700 dark:hover:bg-gray-700 dark:text-gray-300 transition-colors">
                    Go back shopping
                </a>
                <a href="/my-orders"
                    class="w-full sm:w-auto text-center px-6 py-3 bg-blue-500 rounded-md text-gray-50 dark:text-gray-300 hover:bg-blue-600 dark:hover:bg-gray-700 dark:bg-gray-800 transition-colors">
                    View My Orders
                </a>
            </div>
        </div>



    </section>

</div>
<?php /**PATH /var/www/duracabs/duracabs/resources/views/livewire/success-page.blade.php ENDPATH**/ ?>