<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-10">
    @section('title', 'Partner Login - Dura Cabs Services')
    @section('description', 'Login to your Dura Cabs partner account.')

    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

            <div class="bg-blue-600 px-6 pt-8 pb-10 text-center">
                <div class="w-20 h-20 mx-auto bg-white rounded-2xl flex items-center justify-center shadow-md">
                    <span class="text-blue-600 text-3xl font-bold">DC</span>
                </div>

                <h1 class="mt-5 text-2xl font-bold text-white">
                    Partner Login
                </h1>

                <p class="mt-2 text-sm text-blue-100">
                    Login with your registered mobile number
                </p>
            </div>

            <div class="px-6 py-7">

                @if ($step === 'mobile')
                    <form wire:submit.prevent="sendOtp">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Mobile Number
                        </label>

                        <div class="flex items-center border border-gray-200 rounded-2xl overflow-hidden bg-gray-50 focus-within:border-blue-500">
                            <div class="px-4 text-gray-500 text-sm font-semibold border-r border-gray-200">
                                +91
                            </div>

                            <input type="number"
                                   wire:model.defer="mobile"
                                   placeholder="Enter mobile number"
                                   class="w-full px-4 py-4 bg-transparent outline-none text-gray-800 text-base">
                        </div>

                        @error('mobile')
                            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                        @enderror

                        <button type="submit"
                                class="w-full mt-6 py-4 rounded-2xl bg-blue-600 text-white font-semibold text-base hover:bg-blue-700 shadow-md">
                            Send OTP
                        </button>

                        <div class="text-center mt-5">
                            <a href="{{ route('partner.register') }}"
                               class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                                New partner? Register now
                            </a>
                        </div>

                    </form>
                @endif

                @if ($step === 'otp')
                    <form wire:submit.prevent="verifyOtp">

                        <div class="text-center mb-5">
                            <h2 class="text-xl font-bold text-gray-800">
                                Verify OTP
                            </h2>

                            <p class="text-sm text-gray-500 mt-1">
                                OTP sent to <strong>{{ $mobile }}</strong>
                            </p>
                        </div>

                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Enter 6 Digit OTP
                        </label>

                        <input type="number"
                               wire:model.defer="otp"
                               placeholder="______"
                               class="w-full px-4 py-4 border border-gray-200 rounded-2xl text-center tracking-[12px] text-xl font-bold outline-none focus:border-blue-500">

                        @error('otp')
                            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                        @enderror

                        @if ($generatedOtp)
                            <p class="mt-3 text-xs text-gray-500 text-center">
                                Testing OTP: <strong>{{ $generatedOtp }}</strong>
                            </p>
                        @endif

                        <button type="submit"
                                class="w-full mt-6 py-4 rounded-2xl bg-blue-600 text-white font-semibold text-base hover:bg-blue-700 shadow-md">
                            Verify & Login
                        </button>

                        <button type="button"
                                wire:click="backToMobile"
                                class="w-full mt-3 py-4 rounded-2xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50">
                            Change Mobile Number
                        </button>

                    </form>
                @endif

            </div>
        </div>
    </div>
</div>