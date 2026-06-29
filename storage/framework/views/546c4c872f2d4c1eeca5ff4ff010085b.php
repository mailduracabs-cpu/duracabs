<div class="">
    <?php $__env->startSection('title', $page->meta_title); ?>
    <?php $__env->startSection('description', $page->meta_description); ?>
    <?php $__env->startSection('image', $imageMeta); ?>

    <div class="w-full py-10 px-2" style='background: linear-gradient(180deg,rgb(30, 156, 253) 0%, rgb(255, 255, 255) 98%);'
        class="background-options ">
        <div class="pt-6">
            <h2 class="font-extrabold text-4xl text-center text-white hidden md:block">Hot Deals In <?php echo e($page->name); ?></h2>
        </div>

        <div class="w-full from-blue-200 to-cyan-200 py-5 px-4 sm:px-6 lg:px-8 mx-auto">
            <div class="max-w-[85rem] mx-auto px-0 sm:px-6 lg:px-8">
                <!-- Grid -->
                <div class="grid md:grid-cols-1 gap-0 md:gap-8 xl:gap-20 md:items-center">
                    <div>

                        <ul
                            class="flex form-margine-negative relative text-sm font-medium text-center text-gray-500 rounded-lg shadow  dark:divide-gray-700 dark:text-gray-400 lg:mt-10 lg:mx-48">
                            <li class="w-1/2 ">

                                <a href="#" wire:click='changeTab("one_way")'
                                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-s-lg active dark:bg-gray-700 dark:text-white"
                                    aria-current="page">
                                    <div class="flex justify-center">
                                        <img fetchpriority="high"  src="/cab_images/one_way.webp" alt="Duracabs Cab"
                                            class="<?php echo e($selected_tab === 'one_way' ? 'grayscale-0' : 'grayscale'); ?> mr-2 lg:w-12 w-8" />
                                    </div>

                                    <h2
                                        class="font-bold lg:text-sm text-xs uppercase <?php echo e($selected_tab === 'one_way' ? 'main-color-text font-extrabold' : ''); ?> ">
                                        OneWay</h2>

                                </a>
                            </li>
                            <li class="w-1/2 ">
                                <a href="#" wire:click='changeTab("return")'
                                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 active dark:bg-gray-700 dark:text-white">

                                    <div class="flex justify-center">
                                        <img fetchpriority="high"  src="/cab_images/return.webp" alt="Duracabs Return"
                                            class="<?php echo e($selected_tab === 'return' ? 'grayscale-0' : 'grayscale'); ?> mr-2 lg:w-12 w-8" />
                                    </div>

                                    <h2
                                        class="font-bold lg:text-sm text-xs uppercase <?php echo e($selected_tab === 'return' ? 'main-color-text font-extrabold' : ''); ?> ">
                                        Return</h2>
                                </a>
                            </li>
                            <li class="w-1/2 ">
                                <a href="#" wire:click='changeTab("local")'
                                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700  active  dark:bg-gray-700 dark:text-white">

                                    <div class="flex justify-center">
                                        <img fetchpriority="high"  src="/cab_images/local.webp" alt="Duracabs local"
                                            class="<?php echo e($selected_tab === 'local' ? 'grayscale-0' : 'grayscale'); ?> mr-2 lg:w-12 w-8" />
                                    </div>

                                    <h2
                                        class="font-bold lg:text-sm text-xs uppercase <?php echo e($selected_tab === 'local' ? 'main-color-text font-extrabold' : ''); ?> ">
                                        Local</h2>

                                </a>
                            </li>
                            <li class="w-1/2 ">
                                <a href="#" wire:click='changeTab("self_drive")'
                                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-e-lg  active  dark:bg-gray-700 dark:text-white">

                                    <div class="flex justify-center">
                                        <img fetchpriority="high"  src="/cab_images/self_drive.webp" alt="Duracabs Self drive"
                                            class="<?php echo e($selected_tab === 'self_drive' ? 'grayscale-0' : 'grayscale'); ?> mr-2 lg:w-12 w-8" />
                                    </div>

                                    <h2
                                        class="font-bold lg:text-sm text-xs uppercase <?php echo e($selected_tab === 'self_drive' ? 'main-color-text font-extrabold' : ''); ?> ">
                                        Self Drive</h2>
                                </a>
                            </li>
                        </ul>




                       
                        <div class="fixed <?php echo e($sendOtp ? '' : 'hidden'); ?> z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
                            <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">
                                <span class="absolute top-0 right-0 p-3 cursor-pointer" wire:click="closeModal()">X</span>
                                    <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-xl shadow-gray-100/80">
                                        <!-- Header -->
                                        <div class="mb-8 text-center">
                                            <div class="mx-auto mb-4 rounded-2xl flex items-center justify-center">
                                                <img fetchpriority="high"  class="w-96" src="<?php echo e(asset('./img/loginimg.jpg')); ?>" alt="login" />
                                            </div>
                                            <h2 class="text-2xl font-semibold tracking-tight">Sign in with OTP</h2>
                                            <p class="mt-1 text-sm text-gray-600">No password required — quick & secure.</p>
                                        </div>
                                        <form wire:submit="sendOtpToBack" autocomplete="off">
                                            <div class="bg-white px-4 pb-4 sm:p-6 sm:pb-4">
                                                <div class="flex">

                                                    <div class="w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                        <h3 class="text-base font-semibold leading-6 text-gray-900"
                                                            id="modal-title">Send OTP To Mobile</h3>
                                                        <div class="mt-2 w-full">


                                                            <input type="number" wire:model.live='mobileNumber' maxlength="14"
                                                                placeholder="Ex: +91311234445"
                                                                class="mt-3 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />

                                                            <p class="mt-2 text-red-900 text-sm"> <?php echo e(strlen($mobileNumber)  == 10  ? '' : 'Kindly put 10 digit'); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                                
                                                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                        <button <?php echo e(strlen($mobileNumber) == 10 ? '' : 'disabled'); ?> type="submit"
                                            class="inline-flex cursor-pointer w-full justify-center rounded-md <?php echo e(strlen($mobileNumber) == 10 ? 'bg-green-600' : 'bg-gray-600'); ?>  px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Send OTP</button>
                                    </div>
                                                
                                            </div>
                                        </form>

                                    </div>
                            </div>
                        </div>
                   


                        <div class="fixed <?php echo e($sendOtpVerify ? '' : 'hidden'); ?> z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
                            <div class="relative top-1 mx-auto shadow-xl rounded-md bg-white max-w-md">
                                <span class="absolute top-0 right-0 p-3 cursor-pointer" wire:click="closeModal()">X</span>
                                <div
                                    class="rounded-2xl border border-gray-200 bg-white p-8 shadow-xl shadow-gray-100/80">
                                    <!-- Header -->
                                    <div class="mb-8 text-center">
                                        <div class="mx-auto rounded-2xl flex items-center justify-center">
                                            <img fetchpriority="high"  class="w-96" src="<?php echo e(asset('./img/passwordimg.jpg')); ?>" alt="password" />
                                        </div>
                                        <h2 class="text-2xl font-semibold tracking-tight">Verification by OTP</h2>
                                    </div>

                                    <form autocomplete="off">
                                        <div class="bg-white px-4 pb-4 sm:px-6 sm:pb-4">
                                            <div class="flex">

                                                <div class="w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                    <div class="flex justify-between mb-4">
                                                    <h3 class="text-base font-semibold leading-6 text-gray-900"
                                                        id="modal-title">Please enter OTP</h3> 
                                                        <button type="button" wire:click='resendOtp'>Resend OTP</button>
                                                    </div>
                                                    <div class="my-4 w-full">
                                                        <div class="flex justify-center items-center">
                                                            <input type="password" maxlength="1" wire:model.live="digit1" inputmode="numeric"
                                                                class="passwordHome w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition"
                                                                autofocus>
                                                            <input type="password" maxlength="1" wire:model.live="digit2" inputmode="numeric"
                                                                class="passwordHome w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                                            <input type="password" maxlength="1" wire:model.live="digit3" inputmode="numeric"
                                                                class="passwordHome w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                                            <input type="password" maxlength="1" wire:model.live="digit4" inputmode="numeric"
                                                                class="passwordHome w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                            <?php if($selected_tab == 'one_way'): ?>
                                                <button type="button" wire:click='verifySubmitOtp'
                                                    class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Verify
                                                    OTP
                                                </button>
                                            <?php endif; ?>
                                            <?php if($selected_tab == 'self_drive'): ?>
                                                <button type="button" wire:click='verifySubmitOtpSelfDrive'
                                                    class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Verify
                                                    OTP
                                                </button>
                                            <?php endif; ?>
                                            <?php if($selected_tab == 'return'): ?>
                                                <button type="button" wire:click='verifySubmitOtpReturn'
                                                    class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Verify
                                                    OTP
                                                </button>
                                            <?php endif; ?>

                                            <?php if($selected_tab == 'local'): ?>
                                                <button type="button" wire:click='verifySubmitLocal'
                                                    class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Verify
                                                    OTP
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" wire:click='backButton'
                                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Back</button>
                                        </div>
                                        
                                    </form>
                                </div>
                            </div>
                        </div>

                         <script>
                            document.addEventListener("DOMContentLoaded", () => {
                                let inputs = document.querySelectorAll(".passwordHome");
                                inputs.forEach((input, index) => {
                                    input.addEventListener("input", () => {
                                        if (input.value.length === 1 && index < inputs.length - 1) {
                                            inputs[index + 1].focus();
                                        }
                                    });
                                });
                            });

                            document.addEventListener("DOMContentLoaded", () => {
                                let inputs = document.querySelectorAll(".passwordHome");

                                inputs.forEach((input, index) => {
                                    // Typing: go forward
                                    input.addEventListener("input", () => {
                                        if (input.value.length === 1 && index < inputs.length - 1) {
                                            inputs[index + 1].focus();
                                        }
                                    });

                                    // Backspace: go backward
                                    input.addEventListener("keydown", (e) => {
                                        if (e.key === "Backspace" && input.value === "" && index > 0) {
                                            inputs[index - 1].focus();
                                        }
                                    });
                                });
                            });
                        </script>

                        <!-- SearcForm -->


                        <!-- One Way -->

                        <form wire:submit='searchPackage' autocomplete="off"
                            class="<?php echo e($selected_tab == 'one_way' ? 'block' : 'hidden'); ?> bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-2 block  items-center justify-center">
                            <div class="lg:flex grid mx-0 w-full ">
                                <label for="simple-search" class="sr-only">From</label>
                                <div class="relative lg:w-1/4 md:mt-0 mt-4">
                                    <label for="">
                                        <span class="font-semibold">From City</span>
                                    </label>
                                    <input type="text" wire:model.live='query' placeholder="From City.."
                                        autocomplete="false" id="simple-search-1"
                                        class="lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('query') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    <?php if($this->hasError('query')): ?>
                                        <div class="mt-1">
                                            <p class="text-sm text-red-600"><?php echo e($this->getError('query')); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($query)): ?>



                                        <div class="absolute z-10 w-40 bg-white rounded-t-none shadow-lg list-group ">

                                            <?php if(!empty($cities_from)): ?>

                                                <?php $__currentLoopData = $cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <a wire:click='update1("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                                        class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700  text-slate-100 border-spacing-1"><?php echo e($city['name']); ?></a>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                            <?php endif; ?>

                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="lg:w-1/4 md:mt-0 mt-3">
                                    <label for="">
                                        <span class="font-semibold">To City</span>
                                    </label>
                                    <input type="text" wire:model.live='query2' placeholder="To City.."
                                        id="simple-search-1"
                                        class="lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('query2') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    <?php if($this->hasError('query2')): ?>
                                        <div class="mt-1">
                                            <p class="text-sm text-red-600"><?php echo e($this->getError('query2')); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($query2)): ?>



                                        <div class="absolute z-10 w-40 bg-white rounded-t-none shadow-lg list-group ">

                                            <?php if(!empty($cities_to)): ?>

                                                <?php $__currentLoopData = $cities_to; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <a wire:click='update2("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                                        class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1"><?php echo e($city['name']); ?></a>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                            <?php endif; ?>

                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="lg:w-1/4 md:mt-0 mt-3">
                                    <label for="">
                                        <span class="font-semibold">PickUp Date</span>
                                    </label>
                                    <input type="date" wire:model='date' id="simple-search" min="<?php echo date('Y-m-d');?>"
                                        class="arriveDate lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('date') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-10 text-black font-extrabold lg:text-2xl block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="date" required />
                                    <?php if($this->hasError('date')): ?>
                                        <div class="mt-1">
                                            <p class="text-sm text-red-600"><?php echo e($this->getError('date')); ?></p>
                                        </div>
                                    <?php endif; ?>

                                </div>

                                <div class="lg:w-1/4 md:mt-0 mt-3">
                                    <label for="">
                                        <span class="font-semibold">PickUp Time</span>
                                    </label>
                                    <input type="time" wire:model='time' id="simple-search"
                                        class="arriveTime lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('time') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-10 text-black font-extrabold lg:text-2xl block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="date" required />
                                    <?php if($this->hasError('time')): ?>
                                        <div class="mt-1">
                                            <p class="text-sm text-red-600"><?php echo e($this->getError('time')); ?></p>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                            <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-2">
                                <button type="submit"
                                    class="p-2.5 uppercase  my-2 w-60 text-2xl fon font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800">

                                    Search

                                </button>
                            </div>

                        </form>


                        <!-- Local -->
                        <form wire:submit='searchPackage' autocomplete="off"
                            class="<?php echo e($selected_tab == 'local' ? 'block' : 'hidden'); ?> bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-2 block  items-center justify-center">
                            <div class="lg:flex grid items-center mx-0 w-full ">
                                <label for="simple-search" class="sr-only">From</label>
                                <div class="relative w-full">
                                    <div class="lg:mt-3 w-full lg:inline-flex">
                                        <div class="relative lg:w-1/5 md:mt-0 mt-4">
                                            <label for="">
                                                <span class="font-semibold">From City</span>
                                            </label>
                                            <input type="text" wire:model.live='queryLocal' placeholder="From City.."
                                                id="simple-search-1"
                                                class="lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('query') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                            <?php if($this->hasError('query')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('query')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if(!empty($queryLocal)): ?>



                                                <div
                                                    class="absolute z-10 w-40 bg-white rounded-t-none shadow-lg list-group rounded-lg">

                                                    <?php if(!empty($cities_from)): ?>

                                                        <?php $__currentLoopData = $cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <a wire:click='update1("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                                                class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1"><?php echo e($city['name']); ?></a>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php else: ?>
                                                    <?php endif; ?>

                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">Select Plan</span>
                                            </label>
                                            <select wire:model='plan' name="plan" id="plan"
                                                class="lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('plan') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[41.5px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                <option value="4 Hour / 40 Km">4 Hour / 40 Km</option>
                                                <option value="8 Hour / 80 Km">8 Hour / 80 Km</option>
                                                <option value="12 Hour / 120 Km">12 Hour / 120 Km</option>
                                            </select>
                                            <?php if($this->hasError('plan')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('plan')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">Start Date</span>
                                            </label>
                                            <input type="date" wire:model='date' id="simple-search" min="<?php echo date('Y-m-d');?>"
                                                class="arriveDate lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('date') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="date" required />
                                            <?php if($this->hasError('date')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('date')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">Start Time</span>
                                            </label>
                                            <input type="time" wire:model='time' id="simple-search"
                                                class="arriveTime lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('time') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="date" required />
                                            <?php if($this->hasError('time')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('time')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">Number of Cars</span>
                                            </label>
                                            <input type="number" wire:model='car' id="simple-search"
                                                class="lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('car') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="No. of cars" required />
                                            <?php if($this->hasError('car')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('car')); ?></p>
                                                </div>
                                            <?php endif; ?>


                                        </div>

                                    </div>




                                </div>

                            </div>

                            <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-2">
                                <button type="submit"
                                    class="p-2.5 uppercase  my-2 w-60 text-2xl fon font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800">

                                    Search

                                </button>
                            </div>
                        </form>


                        <!-- Return -->
                        <form wire:submit='searchPackage' autocomplete="off"
                            class="<?php echo e($selected_tab == 'return' ? 'block' : 'hidden'); ?> bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-2 block  items-center justify-center">
                            <div class="lg:flex grid items-center mx-0 w-full ">
                                <label for="simple-search" class="sr-only">From</label>
                                <div class="relative w-full">
                                    <div class="lg:mt-3 w-full lg:inline-flex">
                                        <div class="lg:w-1/5 md:mt-0 mt-4">
                                            <label for="">
                                                <span class="font-semibold">From City</span>
                                            </label>

                                            <input type="text" wire:model.live='queryFrom'
                                                placeholder="From City.." id="simple-search-1"
                                                class="lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('queryFrom') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                            <?php if($this->hasError('queryFrom')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('queryFrom')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if(!empty($queryFrom)): ?>



                                                <div
                                                    class="absolute z-10 w-96 bg-white rounded-t-none shadow-lg list-group rounded-lg">

                                                    <?php if(!empty($dataFrom)): ?>

                                                        <?php $__currentLoopData = $dataFrom; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <a wire:click='updateCityFrom("<?php echo e($city['description']); ?>")'
                                                                class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1 "><?php echo e($city['description']); ?></a>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php else: ?>
                                                    <?php endif; ?>

                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">To City</span>
                                            </label>
                                            <input type="text" wire:model.live='queryTo' placeholder="To City.."
                                                id="simple-search-1"
                                                class="lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('queryTo') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                            <?php if($this->hasError('queryTo')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('queryTo')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if(!empty($queryTo)): ?>



                                                <div
                                                    class="absolute z-10 w-96 bg-white rounded-t-none shadow-lg list-group rounded-lg">

                                                    <?php if(!empty($dataTo)): ?>

                                                        <?php $__currentLoopData = $dataTo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <a wire:click='updateCityTo("<?php echo e($city['description']); ?>")'
                                                                class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1"><?php echo e($city['description']); ?></a>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php else: ?>
                                                    <?php endif; ?>

                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">Start Date</span>
                                            </label>
                                            <input type="date" wire:model='date' id="simple-search" min="<?php echo date('Y-m-d');?>"
                                                class="arriveDate lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('date') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="date" required />
                                            <?php if($this->hasError('date')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('date')); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">End Date</span>
                                            </label>
                                            <input type="date" wire:model='dateto' id="simple-search" min="<?php echo date('Y-m-d');?>"
                                                class="arriveDate lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('dateto') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="date" required />
                                            <?php if($this->hasError('dateto')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('dateto')); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">PickUp Time</span>
                                            </label>
                                            <input type="time" wire:model='time' id="simple-search"
                                                class="arriveTime lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('time') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="date" required />
                                            <?php if($this->hasError('time')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('time')); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>






                                    </div>


                                </div>

                            </div>
                            <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-2">
                                <button type="submit"
                                    class="p-2.5 uppercase  my-2 w-60 text-2xl fon font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800">

                                    Search

                                </button>
                            </div>
                        </form>

                        <!-- Self Drive -->
                        <form wire:submit='searchPackage' autocomplete="off"
                            class="<?php echo e($selected_tab == 'self_drive' ? 'block' : 'hidden'); ?> bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-2 block  items-center justify-center">
                            <div class="lg:flex grid items-center mx-0 w-full ">
                                <label for="simple-search" class="sr-only">From</label>
                                <div class="relative w-full">

                                    <div class="lg:mt-3 w-full lg:inline-flex">

                                        <div class="lg:w-1/5 md:mt-0 mt-4">
                                            <label for="">
                                                <span class="font-semibold">From City</span>
                                            </label>
                                            <input type="text" wire:model.live='querySelfDrive' placeholder="From City.."
                                                id="simple-search-1"
                                                class="lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('query') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                            <?php if($this->hasError('query')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('query')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if(!empty($querySelfDrive)): ?>



                                                <div
                                                    class="absolute z-10 w-40 bg-white rounded-t-none shadow-lg list-group rounded-lg">

                                                    <?php if(!empty($cities_from)): ?>

                                                        <?php $__currentLoopData = $cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <a wire:click='update1("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                                                class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1"><?php echo e($city['name']); ?></a>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php else: ?>
                                                    <?php endif; ?>

                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">Start Date</span>
                                            </label>
                                            <input type="date" wire:model='date' id="simple-search" min="<?php echo date('Y-m-d');?>"
                                                name="book"
                                                class="arriveDate lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('date') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="date" required />
                                            <?php if($this->hasError('date')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('date')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">Start Time</span>
                                            </label>
                                            <input type="time" wire:model='time' id="simple-search" min="<?php echo date('Y-m-d');?>"
                                                class="arriveTime lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('time') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="date" required />
                                            <?php if($this->hasError('time')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('time')); ?></p>
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">End Date</span>
                                            </label>
                                            <input type="date" wire:model='dateto' id="simple-search" min="<?php echo date('Y-m-d');?>"
                                                class="arriveDate lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('dateto') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="date" required />
                                            <?php if($this->hasError('dateto')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('dateto')); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="lg:w-1/5 md:mt-0 mt-3">
                                            <label for="">
                                                <span class="font-semibold">End Time</span>
                                            </label>
                                            <input type="time" wire:model='endTime' id="simple-search"
                                                class="arriveTime lg:mt-3 bg-gray-50 border <?php echo e($this->hasError('endTime') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'); ?> lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                placeholder="End Date" required />
                                            <?php if($this->hasError('endTime')): ?>
                                                <div class="mt-1">
                                                    <p class="text-sm text-red-600"><?php echo e($this->getError('endTime')); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>



                                    </div>


                                </div>

                            </div>
                            <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-2">
                                <button type="submit"
                                    class="p-2.5 uppercase  my-2 w-60 text-2xl fon font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800">

                                    Search

                                </button>
                            </div>
                        </form>

                        <!-- Edit Query Button -->
                        <div class="w-full flex justify-center mt-6 mb-4">
                            <button wire:click="showEditQueryModal" 
                               class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-blue-600 bg-white border-2 border-blue-600 rounded-full hover:bg-blue-600 hover:text-white focus:ring-4 focus:ring-blue-300 transition duration-200 shadow-md">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span class="uppercase tracking-wide">Edit Search Query</span>
                            </button>
                        </div>

                        <!-- SearcForm End -->


                        <!-- Buttons -->
                        
                        <!-- End Buttons -->


                    </div>
                    <!-- End Col -->

                    
                    <!-- End Col -->
                </div>
                <!-- End Grid -->
            </div>
        </div>



    </div>

    <ul
        class="flex form-margine-negative relative text-sm font-medium text-center text-gray-500 rounded-lg shadow  dark:divide-gray-700 dark:text-gray-400 mt-20 lg:mx-48 mx-5">
        <li class="w-1/2 focus-within:z-10">

            <button type="button" href="#slide" wire:click="changeBanner('one_way')"
                class="flex  text-center items-center justify-center  w-full px-2 py-4 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-s-lg focus:ring-4 focus:ring-blue-300 active focus:outline-none dark:bg-gray-700 dark:text-white"
                aria-current="page">

                <h2
                    class="font-bold lg:text-sm text-xs uppercase <?php echo e($bannerTab === 'one_way' ? 'main-color-text font-extrabold' : ''); ?>">
                    One Way</h2>

            </button>
        </li>
        <li class="w-1/2 focus-within:z-10">
            <button type="button" wire:click="changeBanner('return')"
                class="flex text-center items-center justify-center  w-full px-2 py-4 bg-white border-r border-gray-200 dark:border-gray-700 hover:text-gray-700 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700">

                <h2
                    class="font-bold lg:text-sm text-xs uppercase <?php echo e($bannerTab === 'return' ? 'main-color-text font-extrabold' : ''); ?> ">
                    Return</h2>
            </button>
        </li>
        <li class="w-1/2 focus-within:z-10">
            <button type="button" href="#slide" wire:click='changeBanner("local")'
                class="inline-block flex text-center items-center justify-center  w-full px-2 py-4 bg-white border-r border-gray-200 dark:border-gray-700 hover:text-gray-700 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700">

                <h2
                    class="font-bold lg:text-sm text-xs uppercase  <?php echo e($bannerTab === 'local' ? 'main-color-text font-extrabold' : ''); ?> ">
                    Local</h2>

            </button>
        </li>
        <li class="w-1/2 focus-within:z-10">
            <button type="button" href="#slide" wire:click='changeBanner("self_drive")'
                class="inline-block flex text-center items-center justify-center  w-full px-0 py-4 bg-white border-s-0 border-gray-200 dark:border-gray-700 rounded-e-lg hover:text-gray-700 hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700">

                <h2
                    class="font-bold lg:text-sm text-xs uppercase <?php echo e($bannerTab === 'self_drive' ? 'main-color-text font-extrabold' : ''); ?> ">
                    Self Drive</h2>
            </button>
        </li>
    </ul>

    

    <div class="mt-10 lg:mx-20 md:mx-5 mx-2">
        <div class="mt-12 flex mx-auto items-center">
            <div x-data="carousel()" x-init="init()" class="relative overflow-hidden group">
                <div x-ref="container" class="-ml-4 flex overflow-x-scroll scroll-snap-x space-x-4 md:space-y-0 ">

                    <?php $__currentLoopData = $carousel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($item->url); ?>"
                            class="ml-4 flex-auto flex-grow-0 flex-shrink-0 lg:w-72 w-52 rounded-lg bg-gray-100 items-center justify-center snap-center overflow-hidden shadow-md">
                            <div><img fetchpriority="high"  src="<?php echo e(url('storage')); ?>/<?php echo e($item->image); ?>" alt="Duracabs <?php echo e($item->name); ?>"></div>
                            
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>



                </div>
                <div @click="scrollTo(prev)" x-show="prev !== null"
                    class="hidden md:block absolute top-1/2 left-0 bg-white p-2 rounded-full transition-transform ease-in-out transform -translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                    <div>&lt;</div>
                </div>
                <div @click="scrollTo(next)" x-show="next !== null"
                    class="hidden md:block absolute top-1/2 right-0 bg-white p-2 rounded-full transition-transform ease-in-out transform translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                    <div>&gt;</div>
                </div>
            </div>
        </div>
    </div>


    <div class="max-w-[70rem] mx-auto">

        
        

        <section class="py-10 lg:mx-20 mx-5">
            <div class="description mt-2">
                <?php echo str($page->description)->sanitizeHtml(); ?>


            </div>
        </section>

        <div class="lg:mx-10 mx-5 bg-white my-10">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 md:grid-cols-2">
                <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">
                    <div class="lg:w-2/12 w-20">
                        <img fetchpriority="high"  src="/img/home/24x7.png" width="100%" class="p-4" alt="Duracabs 24x7" />
                    </div>
                    <div class="w-full lg:text-left text-center">
                        <h4 class="text-xl font-extrabold main-color-text">
                            24 * 7 Support
                        </h4>
                        <p>
                            You just need to sit back and relax. Once you book Duracabs you just need to relax on your
                            journey.
                        </p>
                    </div>
                </div>
                <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">
                    <div class="lg:w-2/12 w-20">
                        <img fetchpriority="high"  src="/img/home/advance.png" width="100%" class="p-4" alt="Duracabs Advance" />
                    </div>
                    <div class="w-full lg:text-left text-center">
                        <h4 class="text-xl font-extrabold main-color-text">
                            Advanced Software
                        </h4>
                        <p>
                            Duracabs provides door-to-door pickup and drop. You will be picked up and dropped at your
                            preferred location with no extra charges.


                        </p>
                    </div>

                </div>
                <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">
                    <div class="lg:w-2/12 w-20">
                        <img fetchpriority="high"  src="/img/home/low_fixed.webp" width="100%" class="p-4" alt="Duracabs Low Fixed" />
                    </div>
                    <div class="w-full lg:text-left text-center">
                        <h4 class="text-xl font-extrabold main-color-text">
                            Low Fixed Prices

                        </h4>
                        <p>
                            The chauffeurs that Duracabs provides are experts and experienced. Your drivers will guide
                            you
                            to the best spots possible.
                        </p>
                    </div>

                </div>
                <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">
                    <div class="lg:w-2/12 w-20">
                        <img fetchpriority="high"  src="/img/home/tracking.png" width="100%" class="p-4" alt="Duracabs Tracking" />
                    </div>
                    <div class="w-full lg:text-left text-center">
                        <h4 class="text-xl font-extrabold main-color-text">
                            Tracking

                        </h4>
                        <p>
                            Duracabs provides flexible payment options. You can choose the mode of payment you want once
                            the
                            trip is complete. Duracabs charged no Cancellation Fees. We understand that plans can get
                            changed at the very last minute.
                        </p>
                    </div>

                </div>


            </div>



        </div>
        <section class="py-5 font-poppins dark:bg-gray-800 ">
            <div class="max-w-6xl px-4 py-6 mx-auto lg:py-4 md:px-6">
                <div class="max-w-xl mx-auto">
                    <div class="text-center ">
                        <div class="relative grid grid-flow-col items-center">
                            <div class="flex flex-col justify-center items-center ">
                                <h2 class="text-5xl font-bold dark:text-gray-200"> Customer <span
                                        class="text-blue-500">
                                        Reviews
                                    </span> </h2>
                                <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                                    <div class="flex-1 h-2 bg-blue-200">
                                    </div>
                                    <div class="flex-1 h-2 bg-blue-400">
                                    </div>
                                    <div class="flex-1 h-2 bg-blue-600">
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>



                <div class="mt-10 lg:mx-20 mx-0">
                    <div class="mt-12 flex mx-auto items-center">
                        <div x-data="carousel()" x-init="init()" class="relative overflow-hidden group">
                            <div x-ref="container"
                                class="-ml-4 flex overflow-x-scroll scroll-snap-x space-x-4 md:space-y-0">

                                <?php $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div
                                        class="ml-4 flex-auto flex-grow-0 flex-shrink-0 w-80 rounded-lg bg-gray-100 items-center justify-center snap-center overflow-hidden shadow-md">
                                        <div
                                            class="flex flex-wrap items-center justify-center py-2 pb-4 mb-6 space-x-2 border-b dark:border-gray-700">
                                            <div class="flex items-center mb-2 md:mb-0 ">
                                                <div class="flex mr-2 rounded-full">
                                                    <img fetchpriority="high"  src="<?php echo e(url('storage')); ?>/<?php echo e($review->image); ?>" alt="Duracabs <?php echo e($review->name); ?>"
                                                        alt="" class="object-cover w-12 h-12 rounded-full">
                                                </div>
                                                <div>
                                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-300">
                                                        <?php echo e($review->name); ?></h2>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        <?php echo e($review->designation); ?>

                                                    </p>
                                                    <div class="flex space-x-1 ">
                                                        <?php echo str_repeat(
                                                            ' <svg class="h-4 w-4 text-orange-400 dark:text-gray-200"  width="51" height="51"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <path
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        fill="currentColor" />
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </svg>',
                                                            $review->star,
                                                        ); ?>



                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="main">
                                            <div class="card px-10">

                                                <p
                                                    class="mb-6 text-base text-gray-500 dark:text-gray-400 text-ellipsis card mcard">
                                                    <?php echo e($review->description); ?>

                                                </p>
                                                <input type="checkbox" class="check" />
                                            </div>

                                        </div>


                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>



                            </div>
                            <div @click="scrollTo(prev)" x-show="prev !== null"
                                class="hidden md:block absolute top-1/2 left-0 bg-white p-2 rounded-full transition-transform ease-in-out transform -translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                                <div>&lt;</div>
                            </div>
                            <div @click="scrollTo(next)" x-show="next !== null"
                                class="hidden md:block absolute top-1/2 right-0 bg-white p-2 rounded-full transition-transform ease-in-out transform translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                                <div>&gt;</div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="flex flex-row justify-between items-center mt-10 lg:mx-20 mx-0">

                    <div class="py-5">
                        <div class="flex space-x-1">
                            <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                                viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                    fill="currentColor" />
                            </svg>
                            <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                                viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                    fill="currentColor" />
                            </svg>
                            <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                                viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                    fill="currentColor" />
                            </svg>
                            <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                                viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                    fill="currentColor" />
                            </svg>
                            <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                                viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                    fill="currentColor" />
                            </svg>
                        </div>

                        <p class="mt-3 text-sm text-gray-800 dark:text-gray-200">
                            <span class="font-bold">4.6</span> /5 - from 12k reviews
                        </p>

                        <div class="mt-5">

                            <svg class="h-auto w-16 text-gray-800 dark:text-white" width="80" height="27"
                                viewBox="0 0 80 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20.558 9.74046H11.576V12.3752H17.9632C17.6438 16.0878 14.5301 17.7245 11.6159 17.7245C7.86341 17.7245 4.58995 14.7704 4.58995 10.6586C4.58995 6.62669 7.70373 3.51291 11.6159 3.51291C14.6498 3.51291 16.4063 5.42908 16.4063 5.42908L18.2426 3.51291C18.2426 3.51291 15.8474 0.878184 11.4961 0.878184C5.94724 0.838264 1.67578 5.50892 1.67578 10.5788C1.67578 15.5289 5.70772 20.3592 11.6558 20.3592C16.8854 20.3592 20.7177 16.8063 20.7177 11.4969C20.7177 10.3792 20.558 9.74046 20.558 9.74046Z"
                                    fill="currentColor" />
                                <path
                                    d="M27.8621 7.78442C24.1894 7.78442 21.5547 10.6587 21.5547 14.012C21.5547 17.4451 24.1096 20.3593 27.9419 20.3593C31.415 20.3593 34.2094 17.7645 34.2094 14.0918C34.1695 9.94011 30.896 7.78442 27.8621 7.78442ZM27.902 10.2994C29.6984 10.2994 31.415 11.7764 31.415 14.0918C31.415 16.4072 29.7383 17.8842 27.902 17.8842C25.906 17.8842 24.3491 16.2874 24.3491 14.0519C24.3092 11.8962 25.8661 10.2994 27.902 10.2994Z"
                                    fill="currentColor" />
                                <path
                                    d="M41.5964 7.78442C37.9238 7.78442 35.2891 10.6587 35.2891 14.012C35.2891 17.4451 37.844 20.3593 41.6763 20.3593C45.1493 20.3593 47.9438 17.7645 47.9438 14.0918C47.9038 9.94011 44.6304 7.78442 41.5964 7.78442ZM41.6364 10.2994C43.4328 10.2994 45.1493 11.7764 45.1493 14.0918C45.1493 16.4072 43.4727 17.8842 41.6364 17.8842C39.6404 17.8842 38.0835 16.2874 38.0835 14.0519C38.0436 11.8962 39.6004 10.2994 41.6364 10.2994Z"
                                    fill="currentColor" />
                                <path
                                    d="M55.0475 7.82434C51.6543 7.82434 49.0195 10.7784 49.0195 14.0918C49.0195 17.8443 52.0934 20.3992 54.9676 20.3992C56.764 20.3992 57.6822 19.7205 58.4407 18.8822V20.1198C58.4407 22.2754 57.1233 23.5928 55.1273 23.5928C53.2111 23.5928 52.2531 22.1557 51.8938 21.3573L49.4587 22.3553C50.297 24.1517 52.0135 26.0279 55.0874 26.0279C58.4407 26.0279 60.9956 23.9122 60.9956 19.481V8.18362H58.3608V9.26147C57.6423 8.38322 56.5245 7.82434 55.0475 7.82434ZM55.287 10.2994C56.9237 10.2994 58.6403 11.7365 58.6403 14.1317C58.6403 16.6068 56.9636 17.9241 55.2471 17.9241C53.4507 17.9241 51.774 16.4471 51.774 14.1716C51.8139 11.6966 53.5305 10.2994 55.287 10.2994Z"
                                    fill="currentColor" />
                                <path
                                    d="M72.8136 7.78442C69.62 7.78442 66.9453 10.2994 66.9453 14.0519C66.9453 18.004 69.9393 20.3593 73.093 20.3593C75.7278 20.3593 77.4044 18.8822 78.3625 17.6048L76.1669 16.1277C75.608 17.006 74.6499 17.8443 73.093 17.8443C71.3365 17.8443 70.5381 16.8862 70.0192 15.9281L78.4423 12.4152L78.0032 11.3772C77.1649 9.46107 75.2886 7.78442 72.8136 7.78442ZM72.8934 10.2196C74.0511 10.2196 74.8495 10.8184 75.2487 11.5768L69.6599 13.9321C69.3405 12.0958 71.097 10.2196 72.8934 10.2196Z"
                                    fill="currentColor" />
                                <path d="M62.9531 19.9999H65.7076V1.47693H62.9531V19.9999Z" fill="currentColor" />
                            </svg>

                        </div>
                    </div>




                    <button wire:click="reviewFunction(true)" class="main-color text-white py-3 px-5 rounded-full">
                        Submit Review
                    </button>

                    <?php if($showReview): ?>
                        <div class="fixed  z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
                            <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">

                                <div class="flex justify-end p-2">
                                    <button type="button" wire:click="reviewFunction(false)"
                                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div class="p-6 pt-0 text-center">

                                    <form wire:submit="submitReview()" autocomplete="off">
                                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                            <div class="flex">

                                                <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                    <h3 class="text-base font-semibold leading-6 text-gray-900"
                                                        id="modal-title ">Submit Review</h3>
                                                    <div class="mt-2 w-full">

                                                        <div class="flex justify-center items-center">
                                                            <button class=""
                                                                wire:mouseover.prevent="changeStarValue(1)">
                                                                <svg class="h-4 w-4   <?php echo e($reviwerStar > 0 ? 'text-orange-400 !h-6 !w-6' : ''); ?> "
                                                                    width="51" height="51" viewBox="0 0 51 51"
                                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                                        fill="currentColor" />
                                                                </svg>
                                                            </button>
                                                            <button class=""
                                                                wire:mouseover.prevent="changeStarValue(2)">
                                                                <svg class="h-4 w-4  <?php echo e($reviwerStar > 1 ? 'text-orange-400 !h-6 !w-6' : ''); ?>"
                                                                    width="51" height="51" viewBox="0 0 51 51"
                                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                                        fill="currentColor" />
                                                                </svg>
                                                            </button>
                                                            <button class=""
                                                                wire:mouseover.prevent="changeStarValue(3)">
                                                                <svg class="h-4 w-4  <?php echo e($reviwerStar > 2 ? 'text-orange-400 !h-6 !w-6' : ''); ?>"
                                                                    width="51" height="51" viewBox="0 0 51 51"
                                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                                        fill="currentColor" />
                                                                </svg>
                                                            </button>
                                                            <button class=""
                                                                wire:mouseover.prevent="changeStarValue(4)">
                                                                <svg class="h-4 w-4  <?php echo e($reviwerStar > 3 ? 'text-orange-400 !h-6 !w-6' : ''); ?>"
                                                                    width="51" height="51" viewBox="0 0 51 51"
                                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                                        fill="currentColor" />
                                                                </svg>
                                                            </button>
                                                            <button class=""
                                                                wire:mouseover.prevent="changeStarValue(5)">
                                                                <svg class="h-4 w-4  <?php echo e($reviwerStar > 4 ? 'text-orange-400 !h-6 !w-6' : ''); ?>"
                                                                    width="51" height="51" viewBox="0 0 51 51"
                                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                                        fill="currentColor" />
                                                                </svg>
                                                            </button>


                                                        </div>

                                                        <div>
                                                            <label for="name"
                                                                class="block text-sm mb-2 dark:text-white">Name</label>
                                                            <div class="relative">
                                                                <input type="text" id="name"
                                                                    wire:model="name"
                                                                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                                                    aria-describedby="email-error">
                                                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                    <div
                                                                        class=" absolute inset-y-0 end-0 flex items-center pointer-events-none pe-3">
                                                                        <svg class="h-5 w-5 text-red-500" width="16"
                                                                            height="16" fill="currentColor"
                                                                            viewBox="0 0 16 16" aria-hidden="true">
                                                                            <path
                                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                                        </svg>
                                                                    </div>
                                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                            </div>
                                                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                <p class=" text-xs text-red-600 mt-2" id="email-error">
                                                                    <?php echo e($message); ?></p>
                                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                        </div>

                                                        <div>
                                                            <label for="designation"
                                                                class="block text-sm mb-2 dark:text-white">Designation</label>
                                                            <div class="relative">
                                                                <input type="text" id="designation"
                                                                    wire:model="designation"
                                                                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                                                    aria-describedby="email-error">
                                                                <?php $__errorArgs = ['designation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                    <div
                                                                        class=" absolute inset-y-0 end-0 flex items-center pointer-events-none pe-3">
                                                                        <svg class="h-5 w-5 text-red-500" width="16"
                                                                            height="16" fill="currentColor"
                                                                            viewBox="0 0 16 16" aria-hidden="true">
                                                                            <path
                                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                                        </svg>
                                                                    </div>
                                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                            </div>
                                                            <?php $__errorArgs = ['designation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                <p class=" text-xs text-red-600 mt-2" id="email-error">
                                                                    <?php echo e($message); ?></p>
                                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                        </div>

                                                        <div>
                                                            <label for="description"
                                                                class="block text-sm mb-2 dark:text-white">description</label>
                                                            <div class="relative">
                                                                <textarea type="text" id="description" wire:model="description"
                                                                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                                                    aria-describedby="email-error">
                                                            </textarea>
                                                                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                    <div
                                                                        class=" absolute inset-y-0 end-0 flex items-center pointer-events-none pe-3">
                                                                        <svg class="h-5 w-5 text-red-500" width="16"
                                                                            height="16" fill="currentColor"
                                                                            viewBox="0 0 16 16" aria-hidden="true">
                                                                            <path
                                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                                        </svg>
                                                                    </div>
                                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                            </div>
                                                            <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                <p class=" text-xs text-red-600 mt-2" id="email-error">
                                                                    <?php echo e($message); ?></p>
                                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">

                                            <button type="submit"
                                                class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                                                Submit</button>
                                        </div>
                                    </form>

                                </div>

                            </div>
                        </div>
                    <?php endif; ?>


                    <script type="text/javascript">
                        window.openModal = function(modalId) {
                            document.getElementById(modalId).style.display = 'block'
                            document.getElementsByTagName('body')[0].classList.add('overflow-y-hidden')
                        }

                        window.closeModal = function(modalId) {
                            document.getElementById(modalId).style.display = 'none'
                            document.getElementsByTagName('body')[0].classList.remove('overflow-y-hidden')
                        }

                        window.

                        // Close all modals when press ESC
                        document.onkeydown = function(event) {
                            event = event || window.event;
                            if (event.keyCode === 27) {
                                document.getElementsByTagName('body')[0].classList.remove('overflow-y-hidden')
                                let modals = document.getElementsByClassName('modal');
                                Array.prototype.slice.call(modals).forEach(i => {
                                    i.style.display = 'none'
                                })
                            }
                        };
                    </script>




                </div>
                <!-- End Review -->

            </div>
        </section>

        <?php if(!$page->brand_id = 112): ?>
      
        
            <section class="py-10 lg:mx-20 mx-5">
                <div class="">
                    <h2 class="text-2xl">Why choose Duracabs for cabs in <?php echo e($page->brand->name); ?> ?</h2>
                </div>
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-2">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead
                            class="text-xs text-gray-700 uppercase bg-sky-500 text-white dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th colspan="1" scope="col" class="px-6 py-3 bg-sky-500 dark:bg-sky-800">
                                    Cab/Taxi
                                </th>
                                <th colspan="5" scope="col" class="px-6 py-3">Amount in Inner/Rs. Per Cab</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="px-6 py-4 font-extrabold"><strong>Routes</strong></td>
                                <td class="px-6 py-4 font-extrabold"><strong>Oneway</strong></td>
                                <td class="px-6 py-4 font-extrabold"><strong>Round Trip Per Km</strong></td>
                            </tr>

                            <?php $__currentLoopData = $page->link_products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="px-6 py-4"><strong><?php echo e($item['name']); ?></strong></td>
                                    <td class="px-6 py-4"><strong><?php echo e($item['oneway']); ?></strong></td>
                                    <td class="px-6 py-4"><strong><?php echo e($item['perKM']); ?></strong></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


                        </tbody>
                    </table>

                </div>

            </section>

        <?php endif; ?>


        <section class="py-10 lg:mx-10 mx-5">
            <section class=" py-2 ">
                <div class="container flex flex-col justify-center p-4 mx-auto md:p-8">
                    <h2 class="text-left text-sky-500 text-3xl font-bold mb-5"> Frequently Asked Questions</h2>

                    <div class="flex flex-col divide-y sm:px-0 lg:px-2 xl:px-4 divide-gray-700">
                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">1. What is the minimum
                                price
                                Taxi Services in <?php echo e($page->name); ?> ?</summary>
                            <div class="px-4 pb-4">
                                <p>Minimum charge of Duracab is Rs.2250 a day. and Charge depends on the car model you
                                    choose.

                                </p>
                            </div>
                        </details>

                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">2. How to book Taxi
                                Services
                                in <?php echo e($page->name); ?> from DuraCabs ?</summary>
                            <div class="px-4 pb-4">
                                <p>Duracabs can be booked through an online website. You can also book cabs by calling
                                    customer care.


                                </p>
                            </div>
                        </details>

                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">3. What documents are
                                required to book a Taxi Services in <?php echo e($page->name); ?> ?</summary>
                            <div class="px-4 pb-4">
                                <p>In case you are booking a self drive car you are required to provide your information
                                    card and valid driving licence.
                                    Driving licence is must while you book a self drive car. You must insure that no
                                    damage
                                    is done to the vehicle.
                                </p>
                            </div>
                        </details>

                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">4. Can I
                                extend/Cancel/modify
                                ?</summary>
                            <div class="px-4 pb-4">
                                <p>Yes, You can extend/cancel/ modify without paying any fee </p>
                            </div>
                        </details>

                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">5. How much security
                                deposit
                                I need to pay ?</summary>
                            <div class="px-4 pb-4">
                                <p>There is a varying security deposit for renting a car depending of the location and
                                    type
                                    of car. This is given with the car listing.
                                </p>
                            </div>
                        </details>


                    </div>
                </div>
            </section>

        </section>

        <section class="py-0 lg:mx-20 mx-5">
            <div class="max-w-7xl mx-auto">
                <div class=" ">
                    <div class="relative flex flex-col items-left">
                        <h2 class="text-2xl font-bold dark:text-gray-200"> Popular<span class="text-blue-500">
                                Routes
                            </span> </h2>
                        <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                            <div class="flex-1 h-2 bg-blue-200">
                            </div>
                            <div class="flex-1 h-2 bg-blue-400">
                            </div>
                            <div class="flex-1 h-2 bg-blue-600">
                            </div>
                        </div>
                    </div>
                    <p class="mb-12 text-base  text-gray-500">
                        Most Visited routes in India
                    </p>
                </div>
            </div>
            <div class="mt-10 lg:mx-20 mx-0">
                <div class="mt-12 flex mx-auto items-center">
                    <div x-data="carousel()" x-init="init()" class="relative overflow-hidden group">
                        <div x-ref="container"
                            class="-ml-4 flex overflow-x-scroll scroll-snap-x space-x-4 md:space-y-0">



                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="ml-4 flex-auto flex-grow-0 flex-shrink-0 lg:w-96 w-52 rounded-lg bg-gray-100 items-center justify-center snap-center overflow-hidden shadow-md"
                                    wire:key='<?php echo e($product->id); ?>'>
                                    <a href="/route/<?php echo e($product->slug); ?>" class="">
                                        <img fetchpriority="high"  src="<?php echo e(url('storage')); ?>/<?php echo e($product->images[0]); ?>"
                                            alt="<?php echo e($product->name); ?>" class="object-cover w-full h-52 rounded-t-lg">
                                    </a>
                                    <div class="px-5 py-2 text-center">
                                        <a href="/route/<?php echo e($product->slug); ?>"
                                            class="text-lg font-bold tracking-tight main-color-text dark:text-gray-300">
                                            <?php echo e($product->name); ?>

                                        </a>

                                    </div>

                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>




                        </div>
                        <div @click="scrollTo(prev)" x-show="prev !== null"
                            class="hidden md:block absolute top-1/2 left-0 bg-white p-2 rounded-full transition-transform ease-in-out transform -translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                            <div>&lt;</div>
                        </div>
                        <div @click="scrollTo(next)" x-show="next !== null"
                            class="hidden md:block absolute top-1/2 right-0 bg-white p-2 rounded-full transition-transform ease-in-out transform translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                            <div>&gt;</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

<?php if(!$page->brand_id = 112): ?>
 <section class="py-10 lg:mx-20 mx-5  ">
            <div class="">
                <h2 class="text-2xl">Why choose Duracabs for cabs in <?php echo e($page->brand->name); ?> ?</h2>
                <p class="py-2">
                    <span><b>Duracabs have a list of benefits such as:- </b>Excellent cleanliness - Duracabs provides
                        excellent clean cabs to its customers.</span>
                </p>

                <p class="py-2">
                    <span><b>Sanitized vehicles :- </b>Vehicles provided by Duracabs are well-sanitized. Vehicles are
                        sanitised from time to time.</span>
                </p>
                <p class="py-2">
                    <span><b>Quick pick-up and drop-off process :- </b>The cabs or taxis of Duracabs provide
                        door-to-door
                        pickup and drop services. The chauffeur will pick you up and drop you at your preferred location
                        with no extra charges.</span>
                </p>

                <p class="py-2">
                    <span><b>Variety of vehicles :- </b>There are a variety of cars offered by Duracabs. You can get
                        your
                        vehicle within your budget. In case you are planning a trip with friends or family you can
                        choose
                        your vehicle accordingly.</span>
                </p>
                <p class="py-2">
                    <span><b>well experienced and trained chauffeurs :- </b>The drivers of the Duracabs are experts and
                        well-experienced. The drivers will guide you to the best spots in the city for visit.</span>
                </p>

                <p class="py-2">
                    <span><b>24/7 customer service :- </b>Duracabs provides 24/7 cab services. In case of any issue or
                        problem, you can contact customer support services.</span>
                </p>

                <p class="py-2">
                    <span><b>Payment options :- </b>Duracabs offers very good payment options. The mode of payment in
                        which
                        you want to pay depends on you.</span>
                </p>
            </div>
        </section>
    
<?php endif; ?>
       

        <section class="py-0 lg:mx-20 mx-5">
            <div class="">
                <h2 class="font-extrabold text-3xl">Serviceable Cities</h2>
            </div>

            <div class="">
                <div class="grid grid-cols-1 gap-2 lg:grid-cols-4 md:grid-cols-3">
                    <?php $__currentLoopData = $page->links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center"
                            href="<?php echo e($item['url']); ?>">
                            <?php echo e($item['name']); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

            </div>
        </section>
    </div>

    <!-- Edit Query Modal -->
    <?php if($showEditModal): ?>
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click.self="$set('showEditModal', false)">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-lg bg-white">
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Edit Your Search</h3>
                <button wire:click="$set('showEditModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Simple Tabs (like homepage) -->
            <ul class="flex text-sm font-medium text-center text-gray-500 rounded-lg shadow mb-4">
                <li class="w-1/4">
                    <button wire:click='changeEditTab("one_way")'
                        class="flex items-center justify-center w-full p-3 text-gray-900 bg-gray-100 border-r border-gray-200 rounded-l-lg <?php echo e($edit_ride_type === 'one_way' ? 'main-color text-white font-extrabold' : 'hover:bg-gray-50'); ?>">
                        <span class="font-bold text-xs uppercase">One Way</span>
                    </button>
                </li>
                <li class="w-1/4">
                    <button wire:click='changeEditTab("return")'
                        class="flex items-center justify-center w-full p-3 text-gray-900 bg-gray-100 border-r border-gray-200 <?php echo e($edit_ride_type === 'return' ? 'main-color text-white font-extrabold' : 'hover:bg-gray-50'); ?>">
                        <span class="font-bold text-xs uppercase">Return</span>
                    </button>
                </li>
                <li class="w-1/4">
                    <button wire:click='changeEditTab("local")'
                        class="flex items-center justify-center w-full p-3 text-gray-900 bg-gray-100 border-r border-gray-200 <?php echo e($edit_ride_type === 'local' ? 'main-color text-white font-extrabold' : 'hover:bg-gray-50'); ?>">
                        <span class="font-bold text-xs uppercase">Local</span>
                    </button>
                </li>
                <li class="w-1/4">
                    <button wire:click='changeEditTab("self_drive")'
                        class="flex items-center justify-center w-full p-3 text-gray-900 bg-gray-100 rounded-r-lg <?php echo e($edit_ride_type === 'self_drive' ? 'main-color text-white font-extrabold' : 'hover:bg-gray-50'); ?>">
                        <span class="font-bold text-xs uppercase">Self Drive</span>
                    </button>
                </li>
            </ul>

                <!-- Ride Type Tabs -->
                

                <!-- One Way Form -->
                <form wire:submit='updateQuery' autocomplete="off" class="<?php echo e($edit_ride_type == 'one_way' ? 'block' : 'hidden'); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- From City -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                            <input type="text" wire:model.live='edit_query_search' placeholder="From City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            
                            <?php if(!empty($edit_query_search) && !empty($edit_cities_from)): ?>
                            <div class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                <?php $__currentLoopData = $edit_cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" wire:click='editUpdate1("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                    <?php echo e($city['name']); ?>

                                </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <?php endif; ?>
                            <?php $__errorArgs = ['edit_query_search'];
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
                            <input type="text" wire:model.live='edit_query2_search' placeholder="To City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            
                            <?php if(!empty($edit_query2_search) && !empty($edit_cities_to)): ?>
                            <div class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                <?php $__currentLoopData = $edit_cities_to; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" wire:click='editUpdate2("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                    <?php echo e($city['name']); ?>

                                </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <?php endif; ?>
                            <?php $__errorArgs = ['edit_query2_search'];
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
                            <input type="date" wire:model='edit_date'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_date'];
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
                            <input type="time" wire:model='edit_time'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_time'];
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
                <form wire:submit='updateQuery' autocomplete="off" class="<?php echo e($edit_ride_type == 'return' ? 'block' : 'hidden'); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- From City -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                            <input type="text" wire:model.live='edit_queryFrom_search' placeholder="From City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            
                            <?php if(!empty($edit_queryFrom_search) && !empty($edit_dataFrom)): ?>
                            <div class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                <?php $__currentLoopData = $edit_dataFrom; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prediction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" wire:click='editUpdateCityFrom("<?php echo e($prediction['description']); ?>")'
                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                    <?php echo e($prediction['description']); ?>

                                </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <?php endif; ?>
                            <?php $__errorArgs = ['edit_queryFrom_search'];
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
                            <input type="text" wire:model.live='edit_queryTo_search' placeholder="To City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            
                            <?php if(!empty($edit_queryTo_search) && !empty($edit_dataTo)): ?>
                            <div class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                <?php $__currentLoopData = $edit_dataTo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prediction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" wire:click='editUpdateCityTo("<?php echo e($prediction['description']); ?>")'
                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                    <?php echo e($prediction['description']); ?>

                                </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <?php endif; ?>
                            <?php $__errorArgs = ['edit_queryTo_search'];
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
                            <input type="date" wire:model='edit_date'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Return Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Return Date</label>
                            <input type="date" wire:model='edit_dateto'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_dateto'];
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
                            <input type="time" wire:model='edit_time'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_time'];
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
                <form wire:submit='updateQuery' autocomplete="off" class="<?php echo e($edit_ride_type == 'local' ? 'block' : 'hidden'); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- From City -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                            <input type="text" wire:model.live='edit_queryLocal' placeholder="From City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            
                            <?php if(!empty($edit_queryLocal) && !empty($edit_cities_from)): ?>
                            <div class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                <?php $__currentLoopData = $edit_cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" wire:click='editUpdate3("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                    <?php echo e($city['name']); ?>

                                </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <?php endif; ?>
                            <?php $__errorArgs = ['edit_queryLocal'];
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
                            <select wire:model='edit_plan' class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="4 Hour / 40 Km">4 Hour / 40 Km</option>
                                <option value="8 Hour / 80 Km">8 Hour / 80 Km</option>
                                <option value="12 Hour / 120 Km">12 Hour / 120 Km</option>
                            </select>
                            <?php $__errorArgs = ['edit_plan'];
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
                            <input type="number" wire:model='edit_cars' min="1" placeholder="No. of cars"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_cars'];
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
                            <input type="date" wire:model='edit_date'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_date'];
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
                            <input type="time" wire:model='edit_time'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_time'];
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
                <form wire:submit='updateQuery' autocomplete="off" class="<?php echo e($edit_ride_type == 'self_drive' ? 'block' : 'hidden'); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- From City -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                            <input type="text" wire:model.live='edit_querySelfDrive' placeholder="From City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            
                            <?php if(!empty($edit_querySelfDrive) && !empty($edit_cities_from)): ?>
                            <div class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                <?php $__currentLoopData = $edit_cities_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" wire:click='editUpdate4("<?php echo e($city['name']); ?>", <?php echo e($city['id']); ?>)'
                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                    <?php echo e($city['name']); ?>

                                </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <?php endif; ?>
                            <?php $__errorArgs = ['edit_querySelfDrive'];
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
                            <input type="date" wire:model='edit_date'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Return Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Return Date</label>
                            <input type="date" wire:model='edit_dateto'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_dateto'];
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
                            <input type="time" wire:model='edit_time'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_time'];
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
                            <input type="time" wire:model='edit_endTime'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <?php $__errorArgs = ['edit_endTime'];
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

            <!-- Simple Submit Button (like homepage) -->
            <div class="w-full flex justify-center mt-6">
                <button type="button" wire:click="updateQuery" wire:loading.attr="disabled"
                    class="p-2.5 uppercase my-2 w-60 text-2xl font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 disabled:opacity-50">
                    <span wire:loading.remove wire:target="updateQuery">Update Search</span>
                    <span wire:loading wire:target="updateQuery">Searching...</span>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/duracabs/duracabs/resources/views/livewire/page.blade.php ENDPATH**/ ?>