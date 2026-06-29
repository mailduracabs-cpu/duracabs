<div class="background-options">
    <?php $__env->startSection('title', 'Edit Query - Duracabs'); ?>
    <?php $__env->startSection('description', 'Edit your ride search query - Duracabs'); ?>
    <?php $__env->startSection('image', 'https://www.duracabs.com/img/logo/duracabs_logo.jpeg'); ?>

    
    <div class="w-full from-blue-200 to-cyan-200 py-5 px-4 sm:px-6 lg:px-8 m-auto" 
         style='background: linear-gradient(180deg,rgb(30, 156, 253) 0%, rgb(255, 255, 255) 98%);'>
        <div class="max-w-[85rem] mx-auto px-0 sm:px-6 lg:px-8">
            <!-- Grid -->
            <div class="grid md:grid-cols-1 gap-0 md:gap-8 xl:gap-20 md:items-center">
                <div>
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <h1 class="lg:block text-center text-2xl mt-9 font-bold text-white sm:text-xl lg:text-4xl lg:leading-tight dark:text-white">
                            Edit Your Search Query - <span class="text-white">Duracabs</span>
                        </h1>
                        <p class="mt-4 text-white/80 text-lg hidden lg:block">Modify your travel preferences and find better options</p>
                        
                        <!-- Back Button -->
                        <div class="mt-4">
                            <a href="<?php echo e(route('rides')); ?>" 
                               class="inline-flex items-center px-6 py-2 text-sm font-medium text-blue-600 bg-white border border-white rounded-full hover:bg-gray-50 transition duration-200 shadow-md">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to Results
                            </a>
                        </div>
                    </div>

                    <!-- Sticky Ride Type Tabs -->
                    <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm py-4 -mx-4 px-4 mb-6 shadow-sm">
                        <ul class="flex form-margine-negative relative text-sm font-medium text-center text-gray-500 rounded-lg shadow dark:divide-gray-700 dark:text-gray-400 lg:mx-48">
                            <li class="w-1/4">
                                <a href="#" wire:click='changeTab("one_way")'
                                    class="lg:flex grid grid-rows-1 gap-0 place-items-center text-center items-center justify-center w-full lg:p-4 p-2 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-s-lg <?php echo e($ride_type === 'one_way' ? 'main-color text-white' : 'hover:bg-gray-50'); ?> transition duration-200">
                                    <div class="flex justify-center">
                                        <img src="/cab_images/one_way.webp" alt="One Way Taxi Service" 
                                             class="<?php echo e($ride_type === 'one_way' ? 'grayscale-0' : 'grayscale'); ?> mr-2 lg:w-12 w-8" />
                                    </div>
                                    <h2 class="font-bold lg:text-sm text-xs uppercase <?php echo e($ride_type === 'one_way' ? 'text-white font-extrabold' : ''); ?>">
                                        One way cab
                                    </h2>
                                </a>
                            </li>
                            <li class="w-1/4">
                                <a href="#" wire:click='changeTab("return")'
                                    class="lg:flex grid grid-rows-1 gap-0 h-full place-items-center text-center items-center justify-center w-full lg:p-4 p-2 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 <?php echo e($ride_type === 'return' ? 'main-color text-white' : 'hover:bg-gray-50'); ?> transition duration-200">
                                    <div class="flex justify-center">
                                        <img src="/cab_images/return.webp" alt="Return Taxi Service" 
                                             class="<?php echo e($ride_type === 'return' ? 'grayscale-0' : 'grayscale'); ?> mr-2 lg:w-12 w-8" />
                                    </div>
                                    <h2 class="font-bold lg:text-sm text-xs uppercase <?php echo e($ride_type === 'return' ? 'text-white font-extrabold' : ''); ?>">
                                        Round trip
                                    </h2>
                                </a>
                            </li>
                            <li class="w-1/4">
                                <a href="#" wire:click='changeTab("local")'
                                    class="lg:flex grid grid-rows-1 gap-0 place-items-center h-full text-center items-center justify-center w-full lg:p-4 p-2 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 <?php echo e($ride_type === 'local' ? 'main-color text-white' : 'hover:bg-gray-50'); ?> transition duration-200">
                                    <div class="flex justify-center">
                                        <img src="/cab_images/local.webp" alt="Local Taxi Service" 
                                             class="<?php echo e($ride_type === 'local' ? 'grayscale-0' : 'grayscale'); ?> mr-2 lg:w-12 w-8" />
                                    </div>
                                    <h2 class="font-bold lg:text-sm text-xs uppercase <?php echo e($ride_type === 'local' ? 'text-white font-extrabold' : ''); ?>">
                                        Local Taxi
                                    </h2>
                                </a>
                            </li>
                            <li class="w-1/4">
                                <a href="#" wire:click='changeTab("self_drive")'
                                    class="lg:flex grid grid-rows-1 gap-0 place-items-center text-center items-center justify-center w-full lg:p-4 p-2 text-gray-900 bg-gray-100 rounded-e-lg <?php echo e($ride_type === 'self_drive' ? 'main-color text-white' : 'hover:bg-gray-50'); ?> transition duration-200">
                                    <div class="flex justify-center">
                                        <img src="/cab_images/self_drive.webp" alt="Self-Drive Car Rental Service" 
                                             class="<?php echo e($ride_type === 'self_drive' ? 'grayscale-0' : 'grayscale'); ?> mr-2 lg:w-12 w-8" />
                                    </div>
                                    <h2 class="font-bold lg:text-sm text-xs uppercase <?php echo e($ride_type === 'self_drive' ? 'text-white font-extrabold' : ''); ?>">
                                        Self-drive car
                                    </h2>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Search Forms -->
                    <div class="bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-6 pb-8 shadow-xl">
            
                        <!-- One Way Form -->
                        <form wire:submit='updateQuery' autocomplete="off" class="<?php echo e($ride_type == 'one_way' ? 'block' : 'hidden'); ?>">
                            <div class="lg:flex grid items-center mx-0 w-full gap-4">
                                <!-- From City -->
                                <div class="relative lg:w-1/4 md:mt-0 mt-4">
                                    <label for="">
                                        <span class="font-semibold">From City</span>
                                    </label>
                                    <input type="text" wire:model.live='query_search' placeholder="From City.."
                                        class="lg:mt-3 bg-gray-50 border border-gray-300 lg:py-10 text-black font-extrabold lg:text-2xl text-xl focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        
                        <?php if(!empty($query_search) && !empty($cities_from)): ?>
                        <div class="absolute z-10 w-full bg-white rounded-lg shadow-lg mt-1">
                            <?php $__currentLoopData = $cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a wire:click='update1("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                class="block px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                <?php echo e($city['name']); ?>

                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php endif; ?>
                        <?php $__errorArgs = ['query_search'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- To City -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">To City</label>
                        <input type="text" wire:model.live='query2_search' placeholder="To City.."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        
                        <?php if(!empty($query2_search) && !empty($cities_to)): ?>
                        <div class="absolute z-10 w-full bg-white rounded-lg shadow-lg mt-1">
                            <?php $__currentLoopData = $cities_to; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a wire:click='update2("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                class="block px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                <?php echo e($city['name']); ?>

                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php endif; ?>
                        <?php $__errorArgs = ['query2_search'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- PickUp Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Date</label>
                        <input type="date" wire:model='date' min="<?php echo e(date('Y-m-d')); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- PickUp Time -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Time</label>
                        <input type="time" wire:model='time'
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </form>

            <!-- Return Form -->
            <form wire:submit='updateQuery' autocomplete="off" class="<?php echo e($ride_type == 'return' ? 'block' : 'hidden'); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <!-- From City -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                        <input type="text" wire:model.live='queryFrom_search' placeholder="From City.."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        
                        <?php if(!empty($queryFrom_search) && !empty($dataFrom)): ?>
                        <div class="absolute z-10 w-full bg-white rounded-lg shadow-lg mt-1">
                            <?php $__currentLoopData = $dataFrom; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a wire:click='updateCityFrom("<?php echo e($city['description']); ?>")'
                                class="block px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                <?php echo e($city['description']); ?>

                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php endif; ?>
                        <?php $__errorArgs = ['queryFrom_search'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- To City -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">To City</label>
                        <input type="text" wire:model.live='queryTo_search' placeholder="To City.."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        
                        <?php if(!empty($queryTo_search) && !empty($dataTo)): ?>
                        <div class="absolute z-10 w-full bg-white rounded-lg shadow-lg mt-1">
                            <?php $__currentLoopData = $dataTo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a wire:click='updateCityTo("<?php echo e($city['description']); ?>")'
                                class="block px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                <?php echo e($city['description']); ?>

                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php endif; ?>
                        <?php $__errorArgs = ['queryTo_search'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                        <input type="date" wire:model='date' min="<?php echo e(date('Y-m-d')); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                        <input type="date" wire:model='dateto' min="<?php echo e(date('Y-m-d')); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['dateto'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- PickUp Time -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Time</label>
                        <input type="time" wire:model='time'
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </form>

            <!-- Local Form -->
            <form wire:submit='updateQuery' autocomplete="off" class="<?php echo e($ride_type == 'local' ? 'block' : 'hidden'); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <!-- From City -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                        <input type="text" wire:model.live='queryLocal' placeholder="From City.."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        
                        <?php if(!empty($queryLocal) && !empty($cities_from)): ?>
                        <div class="absolute z-10 w-full bg-white rounded-lg shadow-lg mt-1">
                            <?php $__currentLoopData = $cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a wire:click='update3("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                class="block px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                <?php echo e($city['name']); ?>

                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php endif; ?>
                        <?php $__errorArgs = ['queryLocal'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Select Plan -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Select Plan</label>
                        <select wire:model='plan'
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium">
                            <option value="4 Hour / 40 Km">4 Hour / 40 Km</option>
                            <option value="8 Hour / 80 Km">8 Hour / 80 Km</option>
                            <option value="12 Hour / 120 Km">12 Hour / 120 Km</option>
                        </select>
                        <?php $__errorArgs = ['plan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                        <input type="date" wire:model='date' min="<?php echo e(date('Y-m-d')); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Start Time -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time</label>
                        <input type="time" wire:model='time'
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Number of Cars -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Cars</label>
                        <input type="number" wire:model='cars' min="1" placeholder="No. of cars"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['cars'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </form>

            <!-- Self Drive Form -->
            <form wire:submit='updateQuery' autocomplete="off" class="<?php echo e($ride_type == 'self_drive' ? 'block' : 'hidden'); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <!-- From City -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                        <input type="text" wire:model.live='querySelfDrive' placeholder="From City.."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        
                        <?php if(!empty($querySelfDrive) && !empty($cities_from)): ?>
                        <div class="absolute z-10 w-full bg-white rounded-lg shadow-lg mt-1">
                            <?php $__currentLoopData = $cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a wire:click='update4("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                class="block px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                <?php echo e($city['name']); ?>

                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php endif; ?>
                        <?php $__errorArgs = ['querySelfDrive'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                        <input type="date" wire:model='date' min="<?php echo e(date('Y-m-d')); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Start Time -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time</label>
                        <input type="time" wire:model='time'
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                        <input type="date" wire:model='dateto' min="<?php echo e(date('Y-m-d')); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['dateto'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- End Time -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">End Time</label>
                        <input type="time" wire:model='endTime'
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-medium" />
                        <?php $__errorArgs = ['endTime'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </form>

                        <!-- Submit Button -->
                        <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-6">
                            <button type="submit" wire:click="updateQuery"
                                class="p-2.5 uppercase my-2 w-60 text-2xl font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800 transition duration-200 shadow-lg">
                                Update Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .main-color {
        background-color: #1e9cfd;
    }
    .main-color-text {
        color: #1e9cfd;
    }
    </style>
</div>
<?php /**PATH /var/www/duracabs/duracabs/resources/views/livewire/edit-query-page.blade.php ENDPATH**/ ?>