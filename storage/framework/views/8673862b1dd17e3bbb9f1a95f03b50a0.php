<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">

    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">
        Checkout
    </h1>

    <form wire:submit.prevent='placeOrder' >
        <div class="grid grid-cols-12 gap-4">
            <div class="md:col-span-12 lg:col-span-8 col-span-12">
                <!-- Card -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900">
                    <!-- Shipping Address -->
                    <div class="mb-6">
                        <h2 class="text-xl font-bold underline text-gray-700 dark:text-white mb-2">
                            Trip Details
                        </h2>
                        <div class="grid lg:grid-cols-2 grid-rows-1  gap-4">
                            <div>
                                <label class="block text-gray-700 dark:text-white mb-1" for="full_name">
                                    Full Name
                                </label>
                                <input
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    wire:model='full_name' id="full_name" type="text">
                                </input>
                                <?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-white mb-1" for="email">
                                    Email
                                </label>
                                <input wire:model='email'
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="email" type="email">
                                </input>
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                            </div>
                        </div>
                        <div class="grid lg:grid-cols-2 grid-rows-1 gap-4">
                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="phone">
                                    Phone 1
                                </label>
                                <input wire:model='phone'
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="phone" type="text">
                                </input>
                                <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="phone2">
                                    Phone 2
                                </label>
                                <input wire:model='phone2'
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['phone2'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="phone2" type="text">
                                </input>
                                <?php $__errorArgs = ['phone2'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <?php if($cart_items[0]['type'] ?? "" != 'self_drive'): ?>
                            <div class="grid lg:grid-cols-2 grid-rows-1 gap-4">
                                <div class="mt-4">
                                    <label class="block text-gray-700 dark:text-white mb-1" for="number_travellers">
                                        Number Of Travellers
                                    </label>
                                    <input wire:model='number_travellers'
                                        class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['number_travellers'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="number_travellers" type="text">
                                    </input>
                                    <?php $__errorArgs = ['number_travellers'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-gray-700 dark:text-white mb-1" for="number_luggage">
                                        Number Of Luggage
                                    </label>
                                    <input wire:model='number_luggage'
                                        class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['number_luggage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="number_luggage" type="text">
                                    </input>
                                    <?php $__errorArgs = ['number_luggage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>
                        <?php endif; ?>


                        <div class="mt-4">
                            <label class="block text-gray-700 dark:text-white mb-1" for="pickup_address">
                                Pickup Address
                            </label>
                            <textarea wire:model='pickup_address'
                                class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['pickup_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="pickup_address" type="text">
                            </textarea>
                            <?php $__errorArgs = ['pickup_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="mt-4">
                            <label class="block text-gray-700 dark:text-white mb-1" for="drop_address">
                                Drop Address
                            </label>
                            <textarea wire:model='drop_address'
                                class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['drop_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="drop_address" type="text">
                            </textarea>
                            <?php $__errorArgs = ['drop_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div>
                            <label class="block text-gray-700 dark:text-white mb-1" for="zip">
                                Comments
                            </label>
                            <textarea wire:model='comments'
                                class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php $__errorArgs = ['comments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="zip" type="text">
                                </textarea>
                            <?php $__errorArgs = ['comments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                </div>
                <!-- End Card -->
            </div>
            <div class="md:col-span-12 lg:col-span-4 col-span-12">
                <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900">
                    <div class="text-xl font-bold underline text-gray-700 dark:text-white mb-2">
                        ORDER SUMMARY
                    </div>
                    <div class="flex justify-between mb-2 font-bold">
                        <span>
                            Subtotal
                        </span>
                        <span>
                            <?php echo e(Number::currency($grand_total, 'INR')); ?>

                        </span>
                    </div>
                  

                   
                        <div class="flex justify-between mb-2 font-bold">
                            <span>
                                Toll 
                            </span>
                            <span>
                               <?php echo e($tollTax == 0 || $tollTax == "" ? 
                                    $cart_items[0]['type']?? "" == 'one_way' ? "Included" : 'Excluded'
                               
                                : $tollTax); ?>

                            </span>
                        </div>
                  

                  
                 
                    
                    
                    <div class="flex justify-between mb-2 font-bold">
                        <span>
                            Taxes (5%)
                        </span>
                        <span>

                            <?php echo e(Number::currency(
                                
                                    ((5 / 100) * ($grand_total +  (int)$this->tollTax + $this->extraTotal)),
                                'INR',
                            )); ?>

                        </span>
                    </div>

                   


                    <hr class="bg-slate-400 my-4 h-1 rounded">

                     
                     <?php if(!empty($cart_items) && isset($cart_items[0]['type']) && $cart_items[0]['type'] !== 'self_drive'): ?>
                         <div class="text-xm font-bold underline text-gray-700 dark:text-white mb-2">
                            SPECIAL REQUESTS
                        </div>
                        
    
                          <?php $__currentLoopData = $this->extraAmountArr; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          
                            <div class="flex justify-between mb-2 font-bold">
                               
                                <span class="flex items-center space-x-2 gap-2 align-top">
                                 <input type="checkbox" wire:click='newWehicalValueFun(<?php echo e($key); ?>)' <?php echo e($item['is_checked'] ? 'checked' : ''); ?>   class="form-checkbox h-5 w-5 text-green-600">
                                    <div>
                                    <?php echo e($item['title']); ?>

                                    <p class="font-normal text-gray-500 dark:text-gray-400 text-xs"><?php echo e($item['description']); ?></p>
                                    </div>
                                </span>
                                <span>
                                   <?php echo e($item['price']); ?>

                                </span>
                            </div>
                   
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    
                         
                     <?php endif; ?>
                     
                     
                    <div class="flex justify-between mb-2 font-bold">
                        <span>
                            Grand Total. 
                        </span>
                        <span>
                             <?php echo e(Number::currency(
                                
                                    ( $grand_total +  (int)$this->tollTax + (5 / 100) * ($grand_total +  (int)$this->tollTax )+ $this->extraTotal),
                                'INR',
                            )); ?>

                        </span>
                    </div>

                      <?php if($couponData): ?>
                        <div class="flex justify-between mb-2 font-bold">
                            <span>
                                Coupon (<?php echo e(round($couponData)); ?>%)
                            </span>
                            <span>
                                <?php echo e(Number::currency(($this->couponData / 100) * ($grand_total +  (int)$this->tollTax + (5 / 100) * ($grand_total +  (int)$this->tollTax )+ $this->extraTotal), 'INR')); ?>

                            </span>
                        </div>
                    <?php endif; ?>
                   
                    </hr>
                    
                    <!-- Available Coupons Section -->
                    <?php if($availableCoupons->count() > 0): ?>
                    <div class="mb-4">
                        <div class="text-lg font-bold text-gray-700 dark:text-white mb-3">
                            Available Discount Coupons
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php $__currentLoopData = $availableCoupons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $availableCoupon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-blue-50 dark:from-gray-700 dark:to-gray-600 border border-green-200 dark:border-gray-600 rounded-lg hover:shadow-md transition duration-200">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <div class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                            <?php echo e($availableCoupon->value); ?>% OFF
                                        </div>
                                        <span class="font-semibold text-gray-800 dark:text-white"><?php echo e($availableCoupon->name); ?></span>
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                        Valid till: <?php echo e(\Carbon\Carbon::parse($availableCoupon->to_date)->format('M d, Y')); ?>

                                    </div>
                                </div>
                                <button wire:click="applyCoupon('<?php echo e($availableCoupon->name); ?>')" 
                                        class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-md transition duration-200 <?php echo e($coupon === $availableCoupon->name ? 'bg-green-500 hover:bg-green-600' : ''); ?>">
                                    <?php echo e($coupon === $availableCoupon->name ? 'Applied' : 'Apply'); ?>

                                </button>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Coupon Input Field -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-2">
                            Have a coupon code?
                        </label>
                        <div class="flex space-x-2">
                            <input wire:model.live.debounce='coupon' placeholder="Enter coupon code"
                                class="flex-1 rounded-lg border py-3 px-3 dark:bg-gray-700 dark:text-white dark:border-none <?php echo e($couponData ? 'border-green-500 border-2 text-green-700' : 'border-gray-300'); ?>"
                                id="coupon" type="text">
                            <?php if($couponData): ?>
                            <button wire:click="$set('coupon', '')" 
                                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-lg transition duration-200">
                                Remove
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php if($couponData): ?>
                        <div class="mt-2 text-sm text-green-600 font-medium">
                            ✓ Coupon "<?php echo e($couponName); ?>" applied successfully! You saved <?php echo e($couponData); ?>%
                        </div>
                        <?php elseif(!empty($coupon) && !$couponData): ?>
                        <div class="mt-2 text-sm text-red-600">
                            Invalid or expired coupon code
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="text-lg font-semibold mb-4">
                        Select Payment Method
                    </div>
                    <ul class="grid w-full gap-6 md:grid-cols-2">
                        <li>
                            <input wire:model='payment_method' class="hidden peer" id="hosting-small" required=""
                                type="radio" value="cash" />
                            <label
                                class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700"
                                for="hosting-small">
                                <div class="block">
                                    <div class="w-full text-lg font-semibold">
                                        Cash
                                    </div>
                                </div>
                                <svg aria-hidden="true" class="w-5 h-5 ms-3 rtl:rotate-180" fill="none"
                                    viewbox="0 0 14 10" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2">
                                    </path>
                                </svg>
                            </label>
                        </li>
                        <li>
                            <input wire:model='payment_method' class="hidden peer" id="hosting-big" type="radio"
                                value="RazorPay">
                            <label
                                class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700"
                                for="hosting-big">
                                <div class="block">
                                    <div class="w-full text-lg font-semibold">
                                        RazorPay
                                    </div>
                                </div>
                                <svg aria-hidden="true" class="w-5 h-5 ms-3 rtl:rotate-180" fill="none"
                                    viewbox="0 0 14 10" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2">
                                    </path>
                                </svg>
                            </label>
                            </input>

                        </li>
                    </ul>
                    <?php $__errorArgs = ['payment_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-red-500 text-sm"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>




                </div>

                 
                 
                 
                <button type="submit" 
                    class="bg-green-500 mt-4 w-full p-3 rounded-lg text-lg text-white hover:bg-green-600">


                    <span wire:loading.remove>Make Payment   <span>
                 <?php if($this->couponData): ?> 

                    <?php echo e(Number::currency(
                                
                                      ($grand_total +  (int)$this->tollTax + (5 / 100) * ($grand_total +  (int)$this->tollTax )+ $this->extraTotal) - ($this->couponData / 100) * ($grand_total +  (int)$this->tollTax + (5 / 100) * ($grand_total +  (int)$this->tollTax )+ $this->extraTotal) ,
                                'INR',
                            )); ?>


                 
                     
                 <?php else: ?>

                 <?php echo e(Number::currency(
                                
                                     $grand_total +  (int)$this->tollTax + (5 / 100) * ($grand_total +  (int)$this->tollTax )+ $this->extraTotal,
                                'INR',
                            )); ?>

                     
                 <?php endif; ?>
                 
                        </span> </span>
                    <span wire:loading>Processing...</span>
                </button>
              
                <div class="bg-white mt-4 rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900">
                    <div class="text-xl font-bold underline text-gray-700 dark:text-white mb-2">
                        Booking Summary
                    </div>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700" role="list">

                        <?php $__currentLoopData = $cart_items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ci): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="py-3 sm:py-4" wire:key='<?php echo e($ci['product_id']); ?>'>
                                <div class="flex items-center">
                                    <div class="flex-1 min-w-0 ms-0 mb-2">
                                        <p><b>PickUp City</b> :</p>
                                    </div>
                                    <div class="">
                                        <?php echo e($ci['cityFrom']); ?>

                                    </div>

                                </div>

                                <div class="flex items-center">
                                    <div class="flex-1 min-w-0 ms-0 mb-2">
                                        <p><b>Drop City</b> :</p>
                                    </div>
                                    <div class="">
                                        <?php echo e($ci['cityTo']); ?>

                                    </div>

                                </div>

                                <div class="flex items-center">
                                    <div class="flex-1 min-w-0 ms-0 mb-2">
                                        <p><b>Cab Model</b> :</p>
                                    </div>
                                    <div class="">
                                        <?php echo e($ci['cabModel']); ?>

                                    </div>

                                </div>

                                <div class="flex items-center">

                                    <div class="flex-shrink-0">
                                        <img alt="<?php echo e($ci['name']); ?>" class="w-12 h-12 rounded-full"
                                            src="<?php echo e(url('storage')); ?>/<?php echo e($ci['image']); ?>" />

                                    </div>
                                    <div class="flex-1 min-w-0 ms-4">
                                        <p class="text-sm font-medium text-gray-900 truncate dark:text-white">
                                            <?php echo e($ci['name']); ?>

                                        </p>
                                        <p class="text-sm text-gray-500 truncate dark:text-gray-400">
                                            Quantity: <?php echo e($ci['quantity']); ?>

                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">
                                        <?php echo e(Number::currency($ci['total_ammount'], 'INR')); ?>

                                    </div>
                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


                    </ul>
                </div>
            </div>

        </div>
    </form>

  

</div>
<?php /**PATH /var/www/duracabs/duracabs/resources/views/livewire/checkout-page.blade.php ENDPATH**/ ?>