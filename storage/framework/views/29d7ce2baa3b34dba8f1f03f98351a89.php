<div class="w-full max-w-[70rem] lg:px-10 px-4 mt-2  mx-auto">

 <?php $__env->startSection('title', $ride->meta_title); ?>

  <?php $__env->startSection('description',$ride->meta_description); ?>
    <?php $__env->startSection('image', $imageMeta); ?>

   

 <?php if($tab === 'one_way'): ?>
        <div class="fixed  z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
            <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">


                <form
                    wire:submit.prevent='submitOneWay([<?php echo e($ride->id); ?>,"<?php echo e($time); ?>","<?php echo e($tab); ?>","<?php echo e($date); ?>","<?php echo e($price); ?>","<?php echo e($name); ?>", "<?php echo e($categoryName); ?>", "<?php echo e($toll); ?>", "<?php echo e($newVehical); ?>", "<?php echo e($petFrindly); ?>", "<?php echo e($roof_career); ?>"])'>
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex">

                            <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-2xl text-center font-semibold leading-6 text-gray-900" id="modal-title">
                                    Pickup Details
                                </h3>
                                <div class="mt-2 w-full">

                                    <div class="">
                                        <label for="" class="font-semibold">PickUp Date</label>
                                        <input type="date" wire:model.live='date' maxlength="4" placeholder="date"
                                            required
                                            class="arriveDate bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>
                                    <div class="mt-3">
                                        <label for="" class="font-semibold">PickUp Time</label>
                                        <input type="time" wire:model.live='time' maxlength="4" placeholder="time"
                                            required
                                            class="arriveTime bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>

                                    <button type='submit'
                                        class="main-color mt-4 text-xl w-full text-white p-2 rounded-sm">
                                        Book Now
                                    </button>




                                </div>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    <?php endif; ?>

    <?php if($tab === 'local'): ?>
        <div class="fixed  z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
            <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">

                <form
                    wire:submit.prevent='submitLocal([<?php echo e($ride->id); ?>,"<?php echo e($time); ?>","<?php echo e($tab); ?>","<?php echo e($date); ?>","<?php echo e($plan); ?>","<?php echo e(1); ?>","<?php echo e($price); ?>","<?php echo e($name); ?>", "<?php echo e($categoryName); ?>", "<?php echo e($toll); ?>", "<?php echo e($newVehical); ?>", "<?php echo e($petFrindly); ?>", "<?php echo e($roof_career); ?>"])'>
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex">

                            <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-2xl text-center font-semibold leading-6 text-gray-900" id="modal-title">
                                    Pickup Details
                                </h3>
                                <div class="mt-2 w-full">

                                    <div class="">
                                        <label class="font-semibold" for="">PickUp Date</label>
                                        <input type="date" wire:model.live='date' maxlength="4" placeholder="date"
                                            required
                                            class="arriveDate bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>
                                    <div class="mt-3">
                                        <label class="font-semibold" for="">PickUp Time</label>
                                        <input type="time" wire:model.live='time' maxlength="4" placeholder="time"
                                            required
                                            class="arriveTime bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>
                                    <div class="mt-3">
                                        <?php echo e($plan); ?>

                                        <label class="font-semibold" for="">Select Plans</label>
                                        <select wire:model.live='plan' name="plan" id="plan" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="4 Hour / 40 Km">4 Hour / 40 Km</option>
                                            <option value="8 Hour / 80 Km">8 Hour / 80 Km</option>
                                            <option value="12 Hour / 120 Km">12 Hour / 120 Km</option>
                                        </select>
                                    </div>
                                    <button type="submit"
                                        class="main-color mt-4 text-xl w-full text-white p-2 rounded-sm">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    <?php endif; ?>

    <?php if($tab === 'self_drive'): ?>
        <div class="fixed z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full -top-20 ">
            <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">

                <form
                    wire:submit.prevent='submitSelfDrive([<?php echo e($ride->id); ?>,"<?php echo e($time); ?>","<?php echo e($endTime); ?>","<?php echo e($tab); ?>","<?php echo e($date); ?>","<?php echo e($endDate); ?>",<?php echo e($ride->price * $hours); ?>, "<?php echo e($security); ?>"])'>
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex">

                            <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-2xl text-center font-semibold leading-6 text-gray-900" id="modal-title">
                                    Pickup Details
                                </h3>
                                <div class="mt-2 w-full">

                                    <div class="">
                                        <label class="font-semibold w-100" for="">PickUp Date</label>
                                        
                                        <input type="date" wire:model.live='date' maxlength="4" placeholder="date"
                                            required
                                            class="arriveDate bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>
                                    <div class="mt-3">
                                        <label class="font-semibold" for="">PickUp Time</label>
                                        <input type="time" wire:model.live='time' maxlength="4" placeholder="time"
                                            required
                                            class="arriveTime bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>


                                    <div class="">
                                        <label class="font-semibold" for="">Drop Date</label>
                                        <input type="date" wire:model.live='endDate' maxlength="4"
                                            placeholder="date" required
                                            class="arriveDate bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>

                                    <div class="mt-3">
                                        <label class="font-semibold" for="">Drop Time</label>
                                        <input type="time" wire:model.live='endTime' maxlength="4"
                                            placeholder="time" required
                                            class="arriveTime bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>


                                    <?php if($hours): ?>
                                        <div class="flex items-center justify-between">
                                            <p class="text-center w-full text-xl text-green-600 font-extrabold mt-4">
                                                Total Hours: <?php echo e($hours); ?> </p>
                                            <p class="text-center w-full text-xl text-green-600 font-extrabold mt-4">
                                                Total Price: <?php echo e($ride->price * $hours); ?> </p>
                                        </div>
                                    <?php endif; ?>


                                    <button type="submit"
                                        class="main-color mt-4 text-xl w-full text-white p-2 rounded-sm">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    <?php endif; ?>

    

    <section class=" font-poppins dark:bg-gray-800 rounded-lg">

        <div class="px-4 py-4 mx-auto max-w-7xl lg:py-6 md:px-6">

            <div class="lg:flex mb-5 -mx-3">

                <div class="w-full pr-2 flex  p-2 main-color rounded-s">

                    <div class="mr-2 flex items-center">
                        <svg class="lg:w-5 w-5" fill="#fff" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                            <path
                                d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                        </svg>

                    </div>

                    <div class="">
                        <h2 class="lg:text-xl text-xm font-medium text-white dark:text-gray-400">Trip Packages For
                        </h2>

                        <?php if($ride->ride_type === 'one_way'): ?>
                            <div class="flex  text-white items-center">
                                <p><?php echo e($ride->brand->name); ?> </p>
                                <svg width="35" height="25" fill="#fff" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                    <path
                                        d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z" />
                                </svg>
                                <p><?php echo e($cityTo->name); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="flex ">
                                <p><?php echo e($ride->brand->name); ?> </p>
                            </div>
                        <?php endif; ?>



                    </div>




                </div>
                <div class="w-full pr-2 flex  p-2 md:mt-0 mt-2 rounded-r border-2 border-[#1e9cfd]">
                    <div class="mr-2 flex items-center">
                        <svg class="lg:w-5 w-5" fill="#1e9cfd" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                            <path
                                d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z" />
                        </svg>

                    </div>


                    <div class="">
                        <h2 class="lg:text-xl text-xm font-medium main-color-text dark:text-gray-400">Trip Type</h2>
                        <div class="flex">
                            <p class="uppercase main-color-text">
                                <?php echo e($ride->ride_type === 'one_way' ? 'Oneway' : $ride->ride_type); ?>

                            </p>


                        </div>
                    </div>




                </div>

            </div>


            <div class="flex flex-wrap mb-5 -mx-3">
                <div class="w-full pr-2 lg:w-1/4 lg:block">

                    


                    <div class="p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                        <h2 class="text-xl font-medium text-sky-500 dark:text-gray-400"> Need Help Booking?</h2>
                        <div class="flex mt-3">
                            <p>Call Our Customer Care Executive. We Are Available 24×7 Just Dial.</p>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-sky-600 p-1 text-sky-300 rounded-lg">
                                <svg width="30" fill="white" height="20" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                    <path
                                        d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
                                </svg>
                            </div>
                            &nbsp; <a href="tel:+91-7088873331"
                                class="text-sky-700 font-bold text-xl">+91-7088873331</a>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-green-500 p-1 text-sky-300 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" fill="white" height="20"
                                    viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                    <path
                                        d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                                </svg>
                            </div>
                            &nbsp; &nbsp; <a href="tel:+91-7088873331"
                                class="text-green-500 font-bold text-xl">+91-7088873331</a>

                        </div>
                    </div>
                </div>
                <div class="w-full lg:w-3/4">
                    <div class="px-3 mb-4">
                        <div
                            class="items-center justify-between hidden px-3 py-2 bg-gray-100 md:flex dark:bg-gray-900 ">
                            <div class="text-2xl"> Related Tour Package (<?php echo e(count($rides)); ?>)</div>
                            

                        </div>
                    </div>
                    <div class="lg:flex grid flex-wrap items-center">

                        <?php $__currentLoopData = $prices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $price): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div
                                class="w-full mb-2 bg-white p-1 border border-gray-300 dark:border-gray-700 shadow-2xl shadow-sky-100">
                                <div class="lg:flex  justify-between">
                                    <div class="relative ">
                                        <a href="/route/<?php echo e($ride->slug); ?>" class="flex items-center justify-center h-full w-full">
                                            <img src="<?php echo e(url('storage')); ?>/<?php echo e($price->category->image); ?>"
                                                alt="<?php echo e($ride->name); ?>"
                                                title="<?php echo e($ride->name); ?>"
                                                class="object-contain lg:w-30 w-full h-40 mx-auto">
                                        </a>
                                    </div>
                                    <div class="p-1 text-center flex items-center justify-center">
                                        
                                        <div
                                            class="lg:flex grid grid-rows-1 gap-0 place-items-center text-center items-end">
                                            <div class="px-2 grid grid-rows-1 gap-0 place-items-center mt-0 ">
                                                <h4 class="text-sm font-medium dark:text-gray-400">
                                                    <?php echo e($price->category->name); ?>

                                                </h4>
                                               

                                                <div class="flex">
                                                    <svg width="20" height="20" fill="gold"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                        <path
                                                            d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                    </svg>
                                                    <svg width="20" height="20" fill="gold"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                        <path
                                                            d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                    </svg>
                                                    <svg width="20" height="20" fill="gold"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                        <path
                                                            d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                    </svg>
                                                    <svg width="20" height="20" fill="gold"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                        <path
                                                            d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                    </svg>
                                                    <svg width="20" height="20" fill="gold"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                        <path
                                                            d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                    </svg>
                                                </div>
                                                <p class="text-[10px] leading-4 text-center w-3/4 mt-2">
                                                   <?php echo e($price->category->passanger_capacity); ?> <?php echo e($price->category->model); ?> 
                                                </p>
                                            </div>
                                            <div class="">

                                                <div class="flex mt-2">
                                                    <div class="p-1">
                                                        <img src="<?php echo e(url('/cab_images/plastic-bottle.png')); ?>"
                                                            alt="<?php echo e($ride->name); ?>"
                                                             title="<?php echo e($ride->name); ?>"
                                                            class="object-contain w-7 h-7 mx-auto ">
                                                        <p class="text-xs">Water Bottle</p>
                                                    </div>
                                                    <div class="p-1">
                                                        <img src="<?php echo e(url('/cab_images/cursor.png')); ?>"
                                                            alt="<?php echo e($ride->name); ?>"
                                                             title="<?php echo e($ride->name); ?>"
                                                            class="object-contain w-7 h-7 mx-auto ">
                                                        <p class="text-xs">Quick Booking</p>
                                                    </div>
                                                    <div class="p-1">
                                                      <?php if($ride->ride_type === 'self_drive'): ?>
                                                                        <img src="<?php echo e(url('/cab_images/car.webp')); ?>"
                                                                            alt="<?php echo e($ride->name); ?>"
                                                                            title="<?php echo e($ride->name); ?>"
                                                                            class="object-contain w-8 h-8 mx-auto ">
                                                                        <p class="text-xs ">Clean Car </p>
                                                                    <?php else: ?>
                                                                        <img src="<?php echo e(url('/cab_images/driver.png')); ?>"
                                                                            alt="<?php echo e($ride->name); ?>"
                                                                            title="<?php echo e($ride->name); ?>"
                                                                            class="object-contain w-6 h-6 mx-auto ">
                                                                        <p class="text-xs ">Qualified Driver </p>
                                                                    <?php endif; ?>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                    <div class="p-3 text-center">

                                        <div class="text-lg text-center">
                                            <p
                                                class="text-base font-normal text-gray-500 line-through dark:text-gray-400">
                                                <?php echo e(Number::currency($price->max_price, 'INR')); ?></p>
                                            <p class="text-black-600 text-xm font-bold dark:text-black-600">
                                                <?php echo e(Number::currency($price->price, 'INR')); ?><?php echo e($ride->ride_type === 'self_drive'? 'Per Hour' : ''); ?></p>
                                        </div>
                                        
                                        <?php if($ride->ride_type === 'one_way'): ?>
                                            <div class="flex flex-wrap items-center gap-4">
                                                <button
                                                    wire:click='tabValue(["one_way","<?php echo e($price->price); ?>","<?php echo e($ride->name); ?>","<?php echo e($price->category->name); ?>", "<?php echo e($ride->toll_tax); ?>","<?php echo e($price->category->new_vehicle); ?>","<?php echo e($price->category->pet_friendly); ?>","<?php echo e($price->category->roof_career); ?>"])'
                                                    class="w-full p-1 bg-sky-500 rounded-full  dark:text-gray-200 text-gray-50 hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-700">
                                                    <span wire:loading.remove wire:target='tabValue("one_way")'>
                                                        Book Now
                                                    </span>

                                                    <span wire:loading wire:target='tabValue("one_way")'>
                                                        Adding...
                                                    </span>

                                                </button>
                                            </div>
                                            <!-- Fare Summary Button for One Way -->
                                            <div class="mt-2">
                                                <button onclick="showFareSummaryOneWay('<?php echo e($ride->name); ?>', '<?php echo e($price->category->name); ?>', <?php echo e($price->price); ?>, <?php echo e($price->max_price); ?>, <?php echo e($ride->toll_tax); ?>, <?php echo e($ride->km_limit); ?>, <?php echo e($ride->hr_limit); ?>, <?php echo e($ride->extra_km_charge); ?>, <?php echo e($ride->extra_hr_charge); ?>)" 
                                                        class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                    </svg>
                                                    View Fare Breakdown
                                                </button>
                                            </div>
                                        <?php endif; ?>

                                        <?php if($ride->ride_type === 'local'): ?>
                                            <div class="flex flex-wrap items-center gap-4">
                                                <button
                                                    wire:click='tabValue(["local","<?php echo e($price->price); ?>","<?php echo e($ride->name); ?>","<?php echo e($price->category->name); ?>", "<?php echo e($ride->toll_tax); ?>","<?php echo e($price->category->new_vehicle); ?>","<?php echo e($price->category->pet_friendly); ?>","<?php echo e($price->category->roof_career); ?>"])'
                                                    class="w-full p-1 bg-sky-500 rounded-full  dark:text-gray-200 text-gray-50 hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-700">
                                                    <span wire:loading.remove wire:target='tabValue("one_way")'>
                                                        Book Now
                                                    </span>

                                                    <span wire:loading wire:target='tabValue("one_way")'>
                                                        Adding...
                                                    </span>

                                                </button>
                                            </div>
                                            <!-- Fare Summary Button for Local -->
                                            <div class="mt-2">
                                                <button onclick="showFareSummaryLocal('<?php echo e($ride->name); ?>', '<?php echo e($price->category->name); ?>', <?php echo e($price->price); ?>, <?php echo e($price->max_price); ?>, 1, 'Local Package', <?php echo e($ride->extra_km_charge); ?>, <?php echo e($ride->extra_hr_charge); ?>, <?php echo e($ride->driver_allowances); ?>)" 
                                                        class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-purple-600 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 hover:text-purple-700 transition duration-200">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    View Local Fare Details
                                                </button>
                                            </div>
                                        <?php endif; ?>

                                         <?php if($ride->ride_type === 'self_drive'): ?>
                                            <div class="flex flex-wrap items-center gap-4">
                                                <button
                                                    wire:click='tabValue(["self_drive","<?php echo e($price->price); ?>","<?php echo e($ride->name); ?>","<?php echo e($price->category->name); ?>","<?php echo e($price->category->security ?? 0); ?>"])'
                                                    class="w-full p-1 bg-sky-500 rounded-full  dark:text-gray-200 text-gray-50 hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-700">
                                                    <span wire:loading.remove wire:target='tabValue("one_way")'>
                                                        Book Now
                                                    </span>

                                                    <span wire:loading wire:target='tabValue("one_way")'>
                                                        Adding...
                                                    </span>

                                                </button>
                                            </div>
                                            <!-- Fare Summary Button for Self Drive -->
                                            <div class="mt-2">
                                                <button onclick="showFareSummarySelfDrive('<?php echo e($ride->name); ?>', '<?php echo e($price->category->name); ?>', <?php echo e($price->price); ?>, <?php echo e($price->max_price); ?>, 1, '<?php echo e($price->category->security ?? 0); ?>')" 
                                                        class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-green-600 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 hover:text-green-700 transition duration-200">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                                    </svg>
                                                    View Self Drive Fare
                                                </button>
                                            </div>
                                        <?php endif; ?>

                                    </div>

                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    </div>
                    <!-- pagination start -->
                    <div class="flex justify-end mt-6">
                        <?php echo e($rides->links()); ?>

                    </div>
                    <!-- pagination end -->
                </div>
            </div>
        </div>
    </section>
    <section class=" font-poppins dark:bg-gray-800 rounded-lg">
        <h2 class="text-left text-sky-500 text-3xl font-bold mb-5"> Book Cab in Easy 4 Step</h2>
        <div class="lg:flex grid bg-sky-500 p-2 rounded-xl gap-2">
            <div class="w-full">
                <div class="flex items-center justify-start">
                    <div class="bg-white rounded-full p-2">
                       
                        <svg width="25" height="25" fill="#0ea5e9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"/></svg>
                    </div>
                    <h5 class="text-white px-2 font-bold text-xm">Pick You Destination</h5>
                </div>
            </div>
            <div class="w-full">
                <div class="flex items-center justify-start mt-1">
                    <div class="bg-white rounded-full p-2">
                        <svg width="25" height="25" fill="#0ea5e9" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                            <path
                                d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                        </svg>
                    </div>
                    <h5 class="text-white px-2 font-bold text-xm">Choose You Cab</h5>
                </div>
            </div>
            <div class="w-full">
                <div class="flex items-center justify-start">
                    <div class="bg-white rounded-full p-2 mt-1">
                       
                        <svg width="25" height="25" fill="#0ea5e9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96l576 0c0-35.3-28.7-64-64-64L64 32C28.7 32 0 60.7 0 96zm0 32L0 416c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-288L0 128zM64 405.3c0-29.5 23.9-53.3 53.3-53.3l117.3 0c29.5 0 53.3 23.9 53.3 53.3c0 5.9-4.8 10.7-10.7 10.7L74.7 416c-5.9 0-10.7-4.8-10.7-10.7zM176 192a64 64 0 1 1 0 128 64 64 0 1 1 0-128zm176 16c0-8.8 7.2-16 16-16l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16z"/></svg>
                    </div>
                    <h5 class="text-white px-2 font-bold text-xm">Fill Details</h5>
                </div>
            </div>
            <div class="w-full">
                <div class="flex items-center justify-start">
                    <div class="bg-white rounded-full p-2 mt-1">
                        <svg width="25" height="25" fill="#0ea5e9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M112 112c0 35.3-28.7 64-64 64l0 160c35.3 0 64 28.7 64 64l352 0c0-35.3 28.7-64 64-64l0-160c-35.3 0-64-28.7-64-64l-352 0zM0 128C0 92.7 28.7 64 64 64l448 0c35.3 0 64 28.7 64 64l0 256c0 35.3-28.7 64-64 64L64 448c-35.3 0-64-28.7-64-64L0 128zM176 256a112 112 0 1 1 224 0 112 112 0 1 1 -224 0zm80-48c0 8.8 7.2 16 16 16l0 64-8 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l24 0 24 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-8 0 0-80c0-8.8-7.2-16-16-16l-16 0c-8.8 0-16 7.2-16 16z"/></svg>
                    </div>
                    <h5 class="text-white px-2 font-bold text-xm">Make Payment and Booked</h5>
                </div>
            </div>


        </div>

        <div class="bg-white p-0 mt-0 ">
            
            <h1 class="text-3xl mt-5 font-bold"><?php echo e($ride->name); ?> One Way Cab & Taxi Service – Book Affordable Ride with DuraCabs</h1>
            
            
            <div class="description mt-2">
                

                <?php echo str($ride->description)->sanitizeHtml(); ?>


               <h2> Quick Facts about <?php echo e($ride->name); ?></h2>

                <p><strong>Destination</strong> :<?php echo e($ride->name); ?> </p>

               <p><strong><?php echo e($ride->name); ?> Distance Oneway/One Side:</strong></p>

                <p> <strong>Route 1</strong> : <?php echo e($ride->hr_limit); ?> Hour  (<?php echo e($ride->km_limit); ?> KM) via NE 4</p>

               
            </div>
        </div>

        <div class="p-2 bg-white mt-3">
            <h2 class="text-sky-500 font-medium p-2 text-xl bg-slate-200"><?php echo e($ride->name); ?> Cab Oneway Price / Per
                k.m. Price</h2>



            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-2">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead
                        class="text-xs text-gray-700 uppercase bg-sky-500 text-white dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 bg-sky-500 dark:bg-sky-800">
                                Vehicle Type
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Model Type
                            </th>
                            <th scope="col" class="px-6 py-3 bg-sky-500 dark:bg-sky-800">
                                Passanger Capacity
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Luggage Capacity
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Rate/km
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Fare
                            </th>

                        </tr>
                    </thead>
                    <tbody>

                        <?php $__currentLoopData = $prices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th scope="row"
                                    class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap bg-gray-50 dark:text-white dark:bg-gray-800">
                                    <?php echo e($item->category->name); ?>

                                </th>
                                <td class="px-6 py-4">
                                    <?php echo e($item->category->model); ?>

                                </td>
                                <td class="px-6 py-4 bg-gray-50 dark:bg-gray-800">
                                    <?php echo e($item->category->passanger_capacity); ?>

                                </td>
                                <td class="px-6 py-4">
                                    <?php echo e($item->category->luggage_capacity); ?> Bags
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo e(Number::currency($item->category->km_charge, 'INR')); ?>

                                </td>
                                <td class="px-6 py-4">
                                    <?php echo e(Number::currency($item->price, 'INR')); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


                    </tbody>
                </table>
            </div>


        </div>
        <div class="p-2 bg-white mt-3">
            <section class=" py-2 ">
                <div class="container flex flex-col justify-center p-4 mx-auto md:p-8">
                    <h2 class="text-left text-sky-500 text-3xl font-bold mb-5"> Frequently Asked Questions</h2>

                    <div class="flex flex-col divide-y sm:px-0 lg:px-2 xl:px-4 divide-gray-700">
                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">1. What are the options
                                available for <?php echo e($ride->name); ?>?</summary>
                            <div class="px-4 pb-4">
                                <p>We have 2 options to travel from <?php echo e($ride->name); ?> Way Cab and Proud Trip.
                                </p>
                            </div>
                        </details>
                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">2. What is the minimum
                                cab fare from <?php echo e($ride->name); ?>?</summary>
                            <div class="px-4 pb-4">
                                <p>On Duracabs you get a cab from <?php echo e($ride->brand->name); ?> to <?php echo e($cityTo->name); ?> in
                                    only <?php echo e($ride->price); ?>.
                                </p>
                            </div>
                        </details>
                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">3. How much time will it
                                take to reach <?php echo e($ride->brand->name); ?> from <?php echo e($cityTo->name); ?> by car?</summary>
                            <div class="px-4 pb-4">
                                <p>It takes you about 4.5 hours to go from Agra to Delhi by car. </p>
                            </div>
                        </details>
                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">4. What are the
                                advantages of online cab booking from Duracab?</summary>
                            <div class="px-4 pb-4">
                                <p>There are many advantages of booking cabs online with Duracab. You can book cabs on
                                    call or website from the comfort of your home and save a lot of time and effort.
                                </p>
                            </div>
                        </details>
                        <details>
                            <summary class="py-2 outline-none cursor-pointer focus:underline">5. Which is the most
                                economical cab available in <?php echo e($ride->brand->name); ?>?</summary>
                            <div class="px-4 pb-4">
                                <p>The cheapest cab we have is the Hatchback Wagon R which can seat 4 people comfortably
                                    and is available at a fare of only Rs.9 per km.
                                </p>
                            </div>
                        </details>

                    </div>
                </div>
            </section>

        </div>
        <div class="p-2 bg-white mt-3">
            <h2 class="text-sky-500 font-medium p-2 text-xl bg-slate-200"> Why choose Duracabs for cabs in
                <?php echo e($ride->name); ?> ? </h2>



            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-2">
                <h3 class=" font-normal p-2 text-xm">Here are the reasons why you should choose Duracabs in <?php echo e($ride->name); ?>?. </h3>

                <div class="lg:px-10 px-2 py-5">
                    <p class="text-xm">1. You just need to sit back and relax. Once you book Duracabs you
                        just need to relax on your journey. </p>
                    <p class="text-xm">2. Duracabs provides door-to-door pickup and drop. You will be picked
                        up and dropped at your preferred location with no extra charges. </p>
                    <p class="text-xm">3. The chauffeurs that Duracabs provides are experts and experienced.
                        Your drivers will guide you to the best spots possible. </p>
                    <p class="text-xm">4. Duracabs provides flexible payment options. You can choose the mode
                        of payment you want once the trip is complete. </p>
                    <p class="text-xm">5. Duracabs charged no Cancellation Fees. We understand that plans can
                        get changed at the very last minute. </p>
                </div>
            </div>
        </div>
        <div class="p-2 bg-white mt-3">
            <h2 class="text-sky-500 font-medium p-2 text-xl bg-slate-200">One Way Cab/Taxi Route From
                <?php echo e($ride->brand->name); ?> </h2>
            <div class="overflow-x-auto shadow-md sm:rounded-lg mt-2 flex justify-center">
                <div class="p-5 flex  justify-between  w-2/3">
                    <div class="">
                        <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><a wire:navigate href="<?php echo e($link->url); ?>"
                                    class="underline flex-none w-50 text-center ">
                                    <span><?php echo e($link->title); ?> </span></a></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Fare Summary Popup -->
    <div id="fareSummaryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 scale-95">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="text-xl font-bold">Fare Breakdown</h3>
                    </div>
                    <button onclick="closeFareSummary()" class="text-white hover:text-gray-200 transition duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Content -->
            <div class="p-6 space-y-4">
                <!-- Vehicle Information -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 font-semibold flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
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
                    
                    <div id="driverAllowanceSection" class="flex justify-between items-center py-2 border-b border-gray-100">
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
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div>
                            <h4 class="font-semibold text-yellow-800 mb-2">Important Information:</h4>
                            <div id="fareNotes" class="text-sm text-yellow-700">
                                Extra Charge After: <span id="extraKmLimit"></span> KMS. will be ₹<span id="extraKmRate"></span>/KM.<br>
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
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Extra KM charges would be directly paid to the driver.
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="px-6 pb-6">
                <div class="flex space-x-3">
                    <button onclick="closeFareSummary()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Close
                    </button>
                    
                </div>
            </div>
        </div>
    </div>

    <script>
        function showFareSummaryOneWay(rideName, categoryName, price, maxPrice, tollTax, kmLimit, hrLimit, extra_km_charge, extra_hr_charge) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST on total amount (add GST on top, don't deduct)
            const baseFare = price;
            const gstAmount = (price * 5) / 100;
            const finalTotal = price + gstAmount;
            
            // Update popup content for one way
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Hide driver allowance for one way
            document.getElementById('driverAllowanceSection').style.display = 'none';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = tollTax > 0 ? 'Included' : 'Excluded';
            
            // Update notes for one way
            document.getElementById('fareNotes').innerHTML = 
                `Extra Charge After: <span id="extraKmLimit">${kmLimit}</span> KMS. will be ₹<span id="extraKmRate">${extra_km_charge}.00</span>/KM.<br>
                Extra Charge After: <span id="extraHrLimit">${hrLimit}</span> HRS. will be ₹<span id="extraHrRate">${extra_hr_charge}.00</span>/HR.<br>
                <strong>Toll-Tax:</strong> ${tollTax > 0 ? 'Included' : 'Excluded'} |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function showFareSummaryLocal(rideName, categoryName, price, maxPrice, cars, plan, extra_km_charge, extra_hr_charge, driver_allowances) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST
            const gstAmount = (price * 5) / 100;
            const baseFare = price;
            const finalTotal = price + gstAmount;
            
            // Update popup content for local
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Show driver allowance for local
            document.getElementById('driverAllowanceSection').style.display = 'block';
            document.getElementById('driverAllowance').textContent = 'Included';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = 'Excluded';
            
            // Update notes for local
            document.getElementById('fareNotes').innerHTML = 
                `Package: ${plan}<br>
                Extra KM Charge: ₹${extra_km_charge}.00/KM<br>
                Extra HR Charge: ₹${extra_hr_charge}.00/HR<br>
                <strong>Toll-Tax:</strong> Excluded |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function showFareSummarySelfDrive(rideName, categoryName, price, maxPrice, days, security) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST
            const gstAmount = (price * 5) / 100;
            const baseFare = price;
            const finalTotal = price + gstAmount;
            
            // Update popup content for self drive
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Hide driver allowance for self drive
            document.getElementById('driverAllowanceSection').style.display = 'none';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = 'Excluded';
            
            // Update notes for self drive
            document.getElementById('fareNotes').innerHTML = 
                `Self Drive Package: Per Hour Rate<br>
                Security Deposit: ₹${security}<br>
                <strong>Fuel:</strong> Extra |
                <strong>Toll-Tax:</strong> Excluded |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function closeFareSummary() {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Add closing animation
            modal.querySelector('.transform').classList.remove('scale-100');
            modal.querySelector('.transform').classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.display = '';
            }, 200);
        }
        
        // Initialize modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeFareSummary();
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('fareSummaryModal');
                    if (modal && !modal.classList.contains('hidden')) {
                        closeFareSummary();
                    }
                }
            });
        });
    </script>
</div>
<?php /**PATH /var/www/duracabs/duracabs/resources/views/livewire/product-detailed-page.blade.php ENDPATH**/ ?>