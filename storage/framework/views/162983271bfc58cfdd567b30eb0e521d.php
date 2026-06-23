<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <?php $__env->startSection('title', 'User Login - Dura Cabs Services'); ?>
    <?php $__env->startSection('description',
        'Log in to manage bookings, view trips, and book self-drive cars or cabs with Dura Cabs
        Services. Travel made easy!'); ?>

        <div class="flex h-full items-center">
            <main class="w-full max-w-md mx-auto lg:px-6">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-5">
                        <div class="mb-4 text-center">
                            <div class="mx-auto mb-4 rounded-2xl flex items-center justify-center">
                                <img class="w-50" src="<?php echo e(asset('./img/loginimg.jpg')); ?>" alt="login"/>
                            </div>
                            <h1 class="text-2xl font-semibold tracking-tight">Sign in with OTP</h1>
                            <p class="mt-1 text-sm text-gray-600">No password required — quick & secure.</p>
                        </div>

                        <!-- Form -->

                        <!--[if BLOCK]><![endif]--><?php if(session('error')): ?>
                            <div class="mt-2 bg-red-500 text-sm text-white rounded-lg p-4 mb-4" role="alert"
                                tabindex="-1" aria-labelledby="hs-solid-color-danger-label">
                                <?php echo e(session('error')); ?>

                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        <div class="grid gap-y-4">
                            <!-- Form Group -->
                            <form wire:submit.prevent='save'>
                                <div class="<?php echo e($sendOtp ? 'hidden' : ''); ?>">
                                    <label for="mobile" class="block text-sm mb-2 dark:text-white">Mobile Number</label>
                                    <div class="relative">
                                        <input type="text" id="mobile" wire:model.live="mobile" inputmode="numeric"
                                            class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                            aria-describedby="mobile-error">
                                    </div>
                                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['mobile'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class=" text-xs text-red-600 mt-2" id="mobile-error"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                                    <button type="<?php echo e(strlen($mobile) == 10 ? 'submit' : 'button'); ?> "
                                        <?php echo e(strlen($mobile) == 10 ? '' : 'disabled'); ?>

                                        class="w-full py-3 mt-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                        Send Code</button>
                                </div>
                            </form>
                            <!-- End Form Group -->

                            <!-- Form Group -->
                            <form wire:submit.prevent='verifySubmitOtp'>
                                <div class="<?php echo e($sendOtp ? '' : 'hidden'); ?>">
                                    <div class="text-center">
                                        <label for="password" class="block text-lg mb-3 dark:text-white font-semibold">Enter 4 digit Code</label>
                                    </div>
                                    <div class="flex justify-center items-center">
                                        <input type="password" maxlength="1" wire:model.live="digit1" inputmode="numeric"
                                            class="passwordLogin w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition"
                                            autofocus>
                                        <input type="password" maxlength="1" wire:model.live="digit2" inputmode="numeric"
                                            class="passwordLogin w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                        <input type="password" maxlength="1" wire:model.live="digit3" inputmode="numeric"
                                            class="passwordLogin w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                        <input type="password" maxlength="1" wire:model.live="digit4" inputmode="numeric"
                                            class="passwordLogin w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                            <input type="hidden" maxlength="1" wire:model.live="digit5" inputmode="numeric"
                                            class="passwordLogin w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                    </div>
                                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = [$otpError];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class=" text-xs text-red-600 mt-2" id="otp-error"><?php echo e($otpError); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                                    
                                   
                                   
                                        <button type="submit" 
                                        class="w-full py-3 mt-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                        Verify Code</button>
                                        
                                        <!--[if BLOCK]><![endif]--><?php if($otpmessage == 1): ?>
                                    
                                            <div id="alertBox" class="bg-green-500 text-white p-1 mt-3 rounded-lg shadow-md transition-opacity duration-500 text-sm">
                                                  ✅  OTP has been sent successfully to your mobile number.
                                            </div>
                                        
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    
                                        <div class="flex justify-end mt-5">
                                            <button type="button" wire:click='save'>Resend OTP</button>
                                        </div>
                                </div>
                            </form>
                            <!-- End Form Group -->


                            <a class="inline-flex justify-center items-center" href="<?php echo e(url('auth/google')); ?>" rel="nofollow">
                                <img src="<?php echo e(asset('img/google.png')); ?>" alt="google"style="width:200px">
                            </a>
                        </div>

                        <!-- End Form -->
                    </div>
                </div>
        </div>
        
    </div>
    <script>
            document.addEventListener("DOMContentLoaded", () => {
    const inputs = document.querySelectorAll(".passwordLogin");

    inputs.forEach((input, index) => {
        // Typing: move forward
        input.addEventListener("input", (e) => {
            // Only allow 1 character
            if (input.value.length > 1) {
                input.value = input.value.slice(0, 1);
            }

            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        // Backspace/Delete: move backward
        input.addEventListener("keydown", (e) => {
            if (
                (e.key === "Backspace" || e.key === "Delete") &&
                input.value === "" &&
                index > 0
            ) {
                inputs[index - 1].focus();
            }
        });

        // Arrow keys navigation
        input.addEventListener("keydown", (e) => {
            if (e.key === "ArrowLeft" && index > 0) {
                inputs[index - 1].focus();
            }
            if (e.key === "ArrowRight" && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        // Paste handler: support pasting OTP/password
        input.addEventListener("paste", (e) => {
            e.preventDefault();
            const pastedData = (e.clipboardData || window.clipboardData).getData("text");
            const chars = pastedData.split("");
            chars.forEach((char, i) => {
                if (index + i < inputs.length) {
                    inputs[index + i].value = char;
                }
            });
            // Move focus to last filled input
            const lastIndex = Math.min(index + chars.length, inputs.length - 1);
            inputs[lastIndex].focus();
        });
    });
});


        </script><?php /**PATH /var/www/duracabs/duracabs/resources/views/livewire/auth/login.blade.php ENDPATH**/ ?>