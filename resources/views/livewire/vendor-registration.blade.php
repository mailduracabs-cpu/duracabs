<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @section('title', 'Partner Registration - Dura Cabs Services')
    @section('description', 'Register as a Dura Cabs partner and grow your business with us.')

    <div class="flex h-full items-center">
        <main class="w-full max-w-xl mx-auto p-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 sm:p-7">
                    <div class="text-center">
                        <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">
                            Partner Registration
                        </h1>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Join Dura Cabs as Host, Vendor, or Both.
                        </p>
                    </div>

                    <hr class="my-5 border-slate-300">

                    @if ($step === 'mobile')
                        <form wire:submit.prevent="sendOtp">
                            <div>
                                <label class="block text-sm mb-2 dark:text-white">
                                    Partner Type
                                </label>

                                <select wire:model="partnerType"
                                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                    <option value="host">Host (Self Drive)</option>
                                    <option value="vendor">Vendor (With Driver)</option>
                                    <option value="both">Host + Vendor</option>
                                </select>

                                @error('partnerType')
                                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm mb-2 dark:text-white">
                                    Mobile Number
                                </label>

                                <input type="number" wire:model="mobile"
                                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400"
                                    placeholder="Enter mobile number">

                                @error('mobile')
                                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                class="w-full mt-5 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                Send OTP
                            </button>
                        </form>
                    @endif

                    @if ($step === 'otp')
                        <form wire:submit.prevent="verifyOtp">
                            <div class="text-center mb-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    OTP sent to <strong>{{ $mobile }}</strong>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm mb-2 dark:text-white">
                                    Enter OTP
                                </label>

                                <input type="number" wire:model="otp"
                                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm text-center tracking-widest focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400"
                                    placeholder="6 digit OTP">

                                @error('otp')
                                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            @if ($generatedOtp)
                                <p class="mt-3 text-xs text-gray-500 text-center">
                                    Testing OTP: <strong>{{ $generatedOtp }}</strong>
                                </p>
                            @endif

                            <button type="submit"
                                class="w-full mt-5 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                Verify OTP
                            </button>

                            <button type="button" wire:click="$set('step', 'mobile')"
                                class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">
                                Change Mobile
                            </button>
                        </form>
                    @endif
					
					@error('mobile')
    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>

    <a href="{{ route('partner.login') }}"
       class="w-full mt-3 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-blue-600 text-blue-600 hover:bg-blue-50">
        Login with this mobile number
    </a>
@enderror

                    @if ($step === 'profile')
                        <form wire:submit.prevent="save">
                            <div class="lg:flex grid">
                                <div class="lg:w-1/2 px-1">
                                    <label class="block text-sm mb-2 dark:text-white">Name</label>
                                    <input type="text" wire:model="name"
                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                    @error('name')
                                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="lg:w-1/2 px-1">
                                    <label class="block text-sm mb-2 dark:text-white">Mobile</label>
                                    <input type="number" wire:model="mobile" readonly
                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm bg-gray-100 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                    @error('mobile')
                                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="lg:flex grid mt-2">
                                <div class="lg:w-1/2 px-1">
                                    <label class="block text-sm mb-2 dark:text-white">Email Address</label>
                                    <input type="email" wire:model="email"
                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                    @error('email')
                                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="lg:w-1/2 px-1">
                                    <label class="block text-sm mb-2 dark:text-white">Create Password</label>
                                    <input type="password" wire:model="password"
                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                    @error('password')
                                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="lg:flex grid mt-2">
                                <div class="lg:w-1/2 px-1">
                                    <label class="block text-sm mb-2 dark:text-white">Company Name</label>
                                    <input type="text" wire:model="companyName"
                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                    @error('companyName')
                                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="lg:w-1/2 px-1">
                                    <label class="block text-sm mb-2 dark:text-white">City</label>
                                    <input type="text" wire:model="city"
                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                    @error('city')
                                        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-2">
                                <label class="block text-sm mb-2 dark:text-white">State</label>
                                <input type="text" wire:model="state"
                                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400">
                                @error('state')
                                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-2">
                                <label class="block text-sm mb-2 dark:text-white">Office Address</label>
                                <textarea wire:model="address"
                                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400"
                                    rows="3"></textarea>
                                @error('address')
                                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                class="w-full mt-5 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                                Complete Registration
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>