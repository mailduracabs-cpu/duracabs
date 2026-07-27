<div class="dura-auth-page">
    <style>
        .dura-auth-page {
            width: 100%;
            min-height: calc(100vh - 72px);
            padding: 18px 20px 28px;
            background: linear-gradient(180deg, #eef6fd 0%, #f8fbff 100%);
            box-sizing: border-box;
        }

        .dura-auth-page *,
        .dura-auth-page *::before,
        .dura-auth-page *::after { box-sizing: border-box; }

        .dura-auth-shell {
            width: 100%;
            min-height: calc(100vh - 118px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dura-auth-card {
            width: min(1048px, calc(100vw - 40px));
            height: 520px;
            min-height: 520px;
            display: grid !important;
            grid-template-columns: minmax(0, 45%) minmax(0, 55%) !important;
            grid-template-rows: 1fr;
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid #dbe4ee;
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 24px 80px rgba(15, 23, 42, .16);
        }

        .dura-auth-left,
        .dura-auth-right {
            width: 100%;
            min-width: 0;
            height: 100%;
        }

        .dura-auth-left { padding: 28px 32px !important; }
        .dura-auth-right { padding: 24px 42px !important; overflow-y: auto; }
        .dura-auth-right > div { max-width: 430px; }

        @media (max-height: 760px) and (min-width: 1024px) {
            .dura-auth-page { padding-top: 12px; padding-bottom: 16px; }
            .dura-auth-shell { min-height: calc(100vh - 100px); align-items: flex-start; }
            .dura-auth-card { height: 520px; min-height: 520px; }
        }

        @media (max-width: 1023px) {
            .dura-auth-page { min-height: calc(100vh - 64px); padding: 12px; }
            .dura-auth-shell { min-height: auto; align-items: flex-start; }
            .dura-auth-card {
                display: block !important;
                width: min(560px, 100%);
                height: auto;
                min-height: 0;
                border-radius: 22px;
            }
            .dura-auth-left { display: none !important; }
            .dura-auth-right { height: auto; min-height: 540px; padding: 26px 22px !important; overflow: visible; }
        }

        @media (max-width: 640px) {
            .dura-auth-page { padding: 8px; }
            .dura-auth-card { border-radius: 18px; }
            .dura-auth-right { min-height: auto; padding: 22px 16px !important; }
        }
    </style>
    <?php if (! $__env->hasRenderedOnce('be533274-452f-4424-bb6c-97a04bf3c853')): $__env->markAsRenderedOnce('be533274-452f-4424-bb6c-97a04bf3c853'); ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer">
    <?php endif; ?>
    <?php $__env->startSection('title', 'Login & Register - Dura Cabs Services'); ?>
    <?php $__env->startSection('description', 'Customer and vendor login or registration with secure OTP verification at Dura Cabs Services.'); ?>

    <?php $__env->startPush('styles'); ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer">
    <?php $__env->stopPush(); ?>

    <div class="dura-auth-shell">
        <main class="w-full flex justify-center">
            <div class="dura-auth-card">

                
                <section class="dura-auth-left relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-600 to-sky-500 p-8 text-white lg:flex lg:flex-col lg:justify-between">
                    <div class="absolute -left-20 -top-20 h-56 w-56 rounded-full bg-white/10"></div>
                    <div class="absolute -bottom-24 -right-20 h-64 w-64 rounded-full bg-cyan-300/15"></div>

                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-3 rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 shadow-lg">
                                <i class="fa-solid fa-taxi text-xl"></i>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Dura Cabs</p>
                                <p class="text-lg font-black">Travel made simple</p>
                            </div>
                        </div>

                        <div class="mt-10">
                            <h2 class="max-w-sm text-4xl font-black leading-tight">
                                One secure account for every journey.
                            </h2>
                            <p class="mt-4 max-w-md text-sm leading-6 text-blue-100">
                                Book cabs as a customer or manage your transport business as a vendor using fast OTP verification.
                            </p>
                        </div>

                        <div class="mt-9 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                                <i class="fa-solid fa-shield-halved text-xl text-cyan-200"></i>
                                <p class="mt-3 text-sm font-bold">Secure OTP</p>
                                <p class="mt-1 text-xs text-blue-100">SMS and WhatsApp verification</p>
                            </div>

                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                                <i class="fa-solid fa-mobile-screen-button text-xl text-cyan-200"></i>
                                <p class="mt-3 text-sm font-bold">Quick access</p>
                                <p class="mt-1 text-xs text-blue-100">No password to remember</p>
                            </div>

                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                                <i class="fa-solid fa-route text-xl text-cyan-200"></i>
                                <p class="mt-3 text-sm font-bold">Easy booking</p>
                                <p class="mt-1 text-xs text-blue-100">Continue your trip instantly</p>
                            </div>

                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                                <i class="fa-solid fa-briefcase text-xl text-cyan-200"></i>
                                <p class="mt-3 text-sm font-bold">Vendor ready</p>
                                <p class="mt-1 text-xs text-blue-100">Manage business and fleet</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10 flex items-center gap-2 text-xs text-blue-100">
                        <i class="fa-solid fa-lock"></i>
                        <span>Your information is securely processed.</span>
                    </div>
                </section>

                
                <section class="dura-auth-right flex items-center bg-white px-5 py-5 sm:px-8 lg:px-10 lg:py-7">
                    <div class="mx-auto w-full max-w-[430px]">

                        
                        <div class="mb-4 flex items-center justify-center gap-2 lg:hidden">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20">
                                <i class="fa-solid fa-taxi"></i>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Dura Cabs</p>
                                <p class="text-sm font-extrabold text-slate-900">Secure Login</p>
                            </div>
                        </div>

                        
                        <div class="mb-5 flex items-center gap-2">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = [1, 2, 3, 4]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $progressStep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="h-1.5 flex-1 rounded-full <?php echo e($step >= $progressStep ? 'bg-blue-600' : 'bg-slate-200'); ?>"></div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                        </div>

                        
                        <!--[if BLOCK]><![endif]--><?php if(session('error')): ?>
                            <div class="mb-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
                                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                                <span><?php echo e(session('error')); ?></span>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['accountType'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                                <?php echo e($message); ?>

                            </div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php if($step === 1): ?>
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                    <i class="fa-solid fa-user-shield"></i>
                                    Login & Register
                                </span>

                                <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Welcome!</h1>
                                <p class="mt-1.5 text-sm leading-6 text-slate-500">
                                    Choose how you want to continue with Dura Cabs.
                                </p>

                                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                    <button
                                        type="button"
                                        wire:click="selectAccountType('customer')"
                                        wire:loading.attr="disabled"
                                        wire:target="selectAccountType"
                                        class="group rounded-2xl border-2 border-slate-200 bg-white p-5 text-left transition hover:-translate-y-0.5 hover:border-blue-500 hover:shadow-lg hover:shadow-blue-500/10 disabled:opacity-60"
                                    >
                                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-xl text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                                            <i class="fa-solid fa-user"></i>
                                        </span>
                                        <span class="mt-4 block text-base font-extrabold text-slate-900">Customer</span>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500">Book cabs, self-drive cars and manage trips.</span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="selectAccountType('vendor')"
                                        wire:loading.attr="disabled"
                                        wire:target="selectAccountType"
                                        class="group rounded-2xl border-2 border-slate-200 bg-white p-5 text-left transition hover:-translate-y-0.5 hover:border-blue-500 hover:shadow-lg hover:shadow-blue-500/10 disabled:opacity-60"
                                    >
                                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-xl text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white">
                                            <i class="fa-solid fa-car-side"></i>
                                        </span>
                                        <span class="mt-4 block text-base font-extrabold text-slate-900">Vendor</span>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500">Manage your transport business and vehicle fleet.</span>
                                    </button>
                                </div>

                                <p class="mt-5 text-center text-xs text-slate-400">
                                    Existing users will log in. New users will register after OTP verification.
                                </p>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php if($step === 2): ?>
                            <div>
                                <button type="button" wire:click="backToAccountType" class="mb-4 inline-flex items-center gap-2 text-xs font-bold text-slate-500 transition hover:text-blue-600">
                                    <i class="fa-solid fa-arrow-left"></i>
                                    Change account type
                                </button>

                                <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                    <i class="fa-solid <?php echo e($accountType === 'vendor' ? 'fa-car-side' : 'fa-user'); ?>"></i>
                                    <?php echo e(ucfirst($accountType)); ?> Login
                                </span>

                                <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Enter mobile number</h1>
                                <p class="mt-1.5 text-sm leading-6 text-slate-500">We will send a 4-digit OTP by SMS and WhatsApp.</p>

                                <form wire:submit.prevent="save" class="mt-6">
                                    <label for="mobile" class="mb-2 block text-sm font-bold text-slate-800">Mobile number</label>

                                    <div class="flex overflow-hidden rounded-xl border <?php echo e($errors->has('mobile') ? 'border-red-400 ring-4 ring-red-50' : 'border-slate-300 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-50'); ?> transition">
                                        <div class="flex min-w-[78px] items-center justify-center gap-1 border-r border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-700">
                                            <span>🇮🇳</span>
                                            <span>+91</span>
                                        </div>

                                        <input
                                            type="tel"
                                            id="mobile"
                                            wire:model.live="mobile"
                                            inputmode="numeric"
                                            autocomplete="tel"
                                            maxlength="10"
                                            pattern="[0-9]{10}"
                                            placeholder="Enter 10-digit number"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                            class="h-[54px] w-full border-0 bg-white px-4 text-[15px] font-semibold tracking-wide text-slate-900 outline-none placeholder:font-normal placeholder:tracking-normal placeholder:text-slate-400 focus:border-0 focus:outline-none focus:ring-0"
                                            autofocus
                                        >
                                    </div>

                                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['mobile'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <?php echo e($message); ?>

                                        </p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

                                    <button
                                        type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="save"
                                        <?php if(strlen((string) $mobile) !== 10): echo 'disabled'; endif; ?>
                                        class="mt-5 inline-flex h-[52px] w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-700 to-sky-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0"
                                    >
                                        <span wire:loading.remove wire:target="save">Send OTP</span>
                                        <i wire:loading.remove wire:target="save" class="fa-solid fa-arrow-right"></i>
                                        <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                            <i class="fa-solid fa-spinner animate-spin"></i>
                                            Sending OTP...
                                        </span>
                                    </button>
                                </form>

                                <!--[if BLOCK]><![endif]--><?php if($accountType === 'customer'): ?>
                                    <div class="my-4 flex items-center">
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                        <span class="px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Or</span>
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                    </div>

                                    <a href="<?php echo e(url('auth/google')); ?>" rel="nofollow" class="flex h-[48px] w-full items-center justify-center gap-3 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                        <img src="<?php echo e(asset('img/google.png')); ?>" alt="Google" class="h-6 w-auto object-contain">
                                        <span>Continue with Google</span>
                                    </a>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php if($step === 3): ?>
                            <div>
                                <button type="button" wire:click="changeMobile" class="mb-4 inline-flex items-center gap-2 text-xs font-bold text-slate-500 transition hover:text-blue-600">
                                    <i class="fa-solid fa-arrow-left"></i>
                                    Change mobile number
                                </button>

                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Secure verification
                                </span>

                                <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Verify your OTP</h1>
                                <p class="mt-1.5 text-sm leading-6 text-slate-500">
                                    Enter the 4-digit code sent to <span class="font-bold text-slate-700">+91 <?php echo e($mobile); ?></span>.
                                </p>

                                <!--[if BLOCK]><![endif]--><?php if($otpmessage === 1): ?>
                                    <div class="mt-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                                        <i class="fa-solid fa-circle-check mt-0.5"></i>
                                        <div>
                                            <p class="font-bold">OTP sent successfully</p>
                                            <p class="mt-0.5 text-xs text-emerald-700">Please check SMS or WhatsApp.</p>
                                        </div>
                                    </div>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                <form wire:submit.prevent="verifySubmitOtp" class="mt-5">
                                    <div id="otp-input-container" class="flex items-center justify-center gap-2.5 sm:gap-3">
                                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = ['digit1', 'digit2', 'digit3', 'digit4']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $digit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <input
                                                type="text"
                                                maxlength="1"
                                                wire:model.live="<?php echo e($digit); ?>"
                                                inputmode="numeric"
                                                <?php if($index === 0): ?> autocomplete="one-time-code" autofocus <?php endif; ?>
                                                aria-label="OTP digit <?php echo e($index + 1); ?>"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1)"
                                                class="passwordLogin h-14 w-14 rounded-xl border-2 border-slate-200 bg-slate-50 text-center text-xl font-black text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                                            >
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>

                                    <!--[if BLOCK]><![endif]--><?php if(!empty($otpError)): ?>
                                        <div class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-center text-xs font-bold text-red-600" role="alert">
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <?php echo e($otpError); ?>

                                        </div>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = ['digit1', 'digit2', 'digit3', 'digit4']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $digit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = [$digit];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <p class="mt-2 text-center text-xs font-semibold text-red-600"><?php echo e($message); ?></p>
                                            <?php break; ?>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                                    <button
                                        type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="verifySubmitOtp"
                                        class="mt-5 inline-flex h-[52px] w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-700 to-sky-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:opacity-60"
                                    >
                                        <span wire:loading.remove wire:target="verifySubmitOtp">Verify & Continue</span>
                                        <i wire:loading.remove wire:target="verifySubmitOtp" class="fa-solid fa-arrow-right"></i>
                                        <span wire:loading wire:target="verifySubmitOtp" class="inline-flex items-center gap-2">
                                            <i class="fa-solid fa-spinner animate-spin"></i>
                                            Verifying...
                                        </span>
                                    </button>

                                    <div class="mt-4 flex items-center justify-center">
                                        <button
                                            type="button"
                                            wire:click="save"
                                            wire:loading.attr="disabled"
                                            wire:target="save"
                                            class="inline-flex items-center gap-2 text-xs font-extrabold text-blue-600 transition hover:text-blue-800 disabled:opacity-50"
                                        >
                                            <i wire:loading.remove wire:target="save" class="fa-solid fa-rotate-right"></i>
                                            <i wire:loading wire:target="save" class="fa-solid fa-spinner animate-spin"></i>
                                            <span wire:loading.remove wire:target="save">Resend OTP</span>
                                            <span wire:loading wire:target="save">Resending...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php if($step === 4): ?>
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                    <i class="fa-solid fa-user-plus"></i>
                                    New <?php echo e(ucfirst($accountType)); ?>

                                </span>

                                <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Complete your profile</h1>
                                <p class="mt-1.5 text-sm leading-6 text-slate-500">Your mobile number is verified. Add basic details to create your account.</p>

                                <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

                                <form wire:submit.prevent="completeRegistration" class="mt-5 space-y-4">
                                    <div>
                                        <label for="name" class="mb-1.5 block text-sm font-bold text-slate-800">
                                            <?php echo e($accountType === 'vendor' ? 'Owner name' : 'Full name'); ?>

                                        </label>
                                        <div class="relative">
                                            <i class="fa-solid fa-user pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                            <input
                                                id="name"
                                                type="text"
                                                wire:model.blur="name"
                                                autocomplete="name"
                                                placeholder="Enter your name"
                                                class="h-[50px] w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-50"
                                                autofocus
                                            >
                                        </div>
                                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1.5 text-xs font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>

                                    <!--[if BLOCK]><![endif]--><?php if($accountType === 'vendor'): ?>
                                        <div>
                                            <label for="businessName" class="mb-1.5 block text-sm font-bold text-slate-800">Business name</label>
                                            <div class="relative">
                                                <i class="fa-solid fa-building pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                                <input
                                                    id="businessName"
                                                    type="text"
                                                    wire:model.blur="businessName"
                                                    placeholder="Enter business name"
                                                    class="h-[50px] w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-50"
                                                >
                                            </div>
                                            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['businessName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1.5 text-xs font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                                        </div>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                    <div>
                                        <label for="email" class="mb-1.5 block text-sm font-bold text-slate-800">Email <span class="font-medium text-slate-400">(optional)</span></label>
                                        <div class="relative">
                                            <i class="fa-solid fa-envelope pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                            <input
                                                id="email"
                                                type="email"
                                                wire:model.blur="email"
                                                autocomplete="email"
                                                placeholder="Enter email address"
                                                class="h-[50px] w-full rounded-xl border border-slate-300 bg-white pl-11 pr-4 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-50"
                                            >
                                        </div>
                                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1.5 text-xs font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>

                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-3">
                                        <input type="checkbox" wire:model="acceptTerms" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-xs leading-5 text-slate-600">
                                            I agree to Dura Cabs terms, privacy policy and OTP-based account access.
                                        </span>
                                    </label>
                                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['acceptTerms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="-mt-2 text-xs font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->

                                    <button
                                        type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="completeRegistration"
                                        class="inline-flex h-[52px] w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-700 to-sky-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:opacity-60"
                                    >
                                        <span wire:loading.remove wire:target="completeRegistration">Create Account & Continue</span>
                                        <i wire:loading.remove wire:target="completeRegistration" class="fa-solid fa-arrow-right"></i>
                                        <span wire:loading wire:target="completeRegistration" class="inline-flex items-center gap-2">
                                            <i class="fa-solid fa-spinner animate-spin"></i>
                                            Creating account...
                                        </span>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        <div class="mt-5 flex items-center justify-center gap-2 text-center text-[11px] text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                            <span>Your information is protected and securely processed.</span>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

        <?php
        $__scriptKey = '3801898464-0';
        ob_start();
    ?>
        <script>
            const initializeOtpInputs = () => {
                const container = document.getElementById('otp-input-container');

                if (!container || container.dataset.initialized === 'true') {
                    return;
                }

                container.dataset.initialized = 'true';
                const inputs = Array.from(container.querySelectorAll('.passwordLogin'));

                inputs.forEach((input, index) => {
                    input.addEventListener('input', function () {
                        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
                        this.dispatchEvent(new Event('change', { bubbles: true }));

                        if (this.value.length === 1 && index < inputs.length - 1) {
                            inputs[index + 1].focus();
                            inputs[index + 1].select();
                        }
                    });

                    input.addEventListener('keydown', function (event) {
                        if ((event.key === 'Backspace' || event.key === 'Delete') && this.value === '' && index > 0) {
                            inputs[index - 1].focus();
                            inputs[index - 1].select();
                        }

                        if (event.key === 'ArrowLeft' && index > 0) {
                            event.preventDefault();
                            inputs[index - 1].focus();
                        }

                        if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                            event.preventDefault();
                            inputs[index + 1].focus();
                        }
                    });

                    input.addEventListener('focus', function () {
                        this.select();
                    });

                    input.addEventListener('paste', function (event) {
                        event.preventDefault();

                        const pastedOtp = (event.clipboardData || window.clipboardData)
                            .getData('text')
                            .replace(/[^0-9]/g, '')
                            .slice(0, inputs.length);

                        pastedOtp.split('').forEach((digit, digitIndex) => {
                            const target = inputs[index + digitIndex];
                            if (!target) return;

                            target.value = digit;
                            target.dispatchEvent(new Event('input', { bubbles: true }));
                        });
                    });
                });

                window.setTimeout(() => inputs[0]?.focus(), 100);
            };

            initializeOtpInputs();
            document.addEventListener('livewire:navigated', initializeOtpInputs);
            document.addEventListener('otp-step-ready', () => window.setTimeout(initializeOtpInputs, 80));
        </script>
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
</div><?php /**PATH C:\xampp\htdocs\duracabs\resources\views/livewire/auth/login.blade.php ENDPATH**/ ?>