<div class="w-full max-w-[85rem] py-8 px-4 sm:px-6 lg:px-8 mx-auto">
    @section('title', 'My Account - Duracabs')
    @section('description', 'Manage your Duracabs account, documents, wallet, orders, address and security')

    @php
        $availableBalance = max(0, (float) $wallet->balance - (float) $wallet->hold_balance);
        $docUrl = fn ($path) => $path ? Storage::disk('public')->url($path) : null;
    @endphp

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Account</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Profile, KYC documents, bookings and Dura Wallet in one place.</p>
    </div>

    <div class="border-b border-gray-200 dark:border-gray-700 mb-6 overflow-x-auto">
        <nav class="flex min-w-max gap-6" aria-label="Account tabs">
            @foreach([
                'overview' => ['Overview', 'M4 6h16M4 12h16M4 18h16'],
                'profile' => ['Profile', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                'address' => ['Address', 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                'orders' => ['Orders', 'M9 5H7a2 2 0 00-2 2v12h14V7a2 2 0 00-2-2h-2M9 5a2 2 0 004 0'],
                'wallet' => ['Wallet', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m0-6h4a2 2 0 012 2v2a2 2 0 01-2 2h-4V9z'],
                'security' => ['Security', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
            ] as $key => [$label, $path])
                <button type="button" wire:click="changeTab('{{ $key }}')"
                    class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-semibold transition {{ $activeTab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                    <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"></path>
                    </svg>
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    @if($activeTab === 'overview')
        <div class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Customer</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ $user->name ?: 'Customer' }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $user->mobile }}</p>
                </div>
                <div class="bg-white border rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Total Orders</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
                </div>
                <button type="button" wire:click="changeTab('wallet')" class="text-left bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 shadow-sm text-white">
                    <p class="text-xs uppercase tracking-wide text-blue-100">Dura Wallet</p>
                    <p class="mt-1 text-2xl font-bold">₹{{ number_format($availableBalance, 2) }}</p>
                    <p class="mt-1 text-xs text-blue-100">Available balance</p>
                </button>
                <button type="button" wire:click="changeTab('profile')" class="text-left bg-white border rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">KYC</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ ucfirst($user->kyc_status ?: 'pending') }}</p>
                    <p class="mt-1 text-xs text-gray-500">Manage Aadhaar & licence</p>
                </button>
            </div>

            @if($recentOrders->count())
                <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center justify-between">
                        <h3 class="font-bold text-gray-900">Recent Orders</h3>
                        <button type="button" wire:click="changeTab('orders')" class="text-sm text-blue-600 font-semibold">View all</button>
                    </div>
                    <div class="divide-y">
                        @foreach($recentOrders as $order)
                            <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $order->booking_from }} → {{ $order->booking_to }}</p>
                                    <p class="text-xs text-gray-500">#{{ $order->id }} • {{ optional($order->created_at)->format('d M Y, h:i A') }}</p>
                                </div>
                                <div class="sm:text-right">
                                    <p class="font-bold text-gray-900">₹{{ number_format((float) $order->grand_total, 2) }}</p>
                                    <p class="text-xs text-gray-500">{{ ucfirst($order->status) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($activeTab === 'profile')
        <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">Profile & KYC</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Update your identity and contact details.</p>
                </div>
                @if(!$editingProfile)
                    <button type="button" wire:click="toggleEditProfile" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold">Edit Profile</button>
                @endif
            </div>

            <div class="p-5">
                @if(!$editingProfile)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        @foreach([
                            'Full Name' => $user->name,
                            'Email' => $user->email,
                            'Mobile' => $user->mobile,
                            'Company Name' => $user->company_name,
                            'GST Number' => $user->gst_number,
                            'Aadhaar Number' => $user->aadhar_number ? '****-****-' . substr($user->aadhar_number, -4) : null,
                            'Driving Licence Number' => $user->driving_licence_number,
                            'Office Address' => $user->office_address,
                        ] as $label => $value)
                            <div>
                                <p class="text-xs font-semibold text-gray-500">{{ $label }}</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ $value ?: 'Not provided' }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 pt-5 border-t">
                        <h4 class="font-bold text-gray-900 mb-3">Documents</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach([
                                'GST Document' => $user->gst_image,
                                'Aadhaar Document' => $user->aadhar_image,
                                'Aadhaar Front' => $user->aadhar_front,
                                'Aadhaar Back' => $user->aadhar_back,
                                'Driving Licence Front' => $user->driving_licence_front,
                                'Driving Licence Back' => $user->driving_licence_back,
                            ] as $label => $path)
                                <div class="border rounded-xl p-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $label }}</p>
                                        <p class="text-xs {{ $path ? 'text-green-600' : 'text-gray-400' }}">{{ $path ? 'Uploaded' : 'Not uploaded' }}</p>
                                    </div>
                                    @if($path)
                                        <a href="{{ $docUrl($path) }}" target="_blank" class="text-xs font-semibold text-blue-600">View</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <form wire:submit="updateProfile" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            @foreach([
                                ['name','Full Name *','text'],
                                ['email','Email *','email'],
                                ['mobile','Mobile *','text'],
                                ['company_name','Company Name','text'],
                                ['gst_number','GST Number','text'],
                                ['aadhar_number','Aadhaar Number','text'],
                                ['driving_licence_number','Driving Licence Number','text'],
                            ] as [$model,$label,$type])
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $label }}</label>
                                    <input type="{{ $type }}" wire:model="{{ $model }}" class="w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @error($model)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Office Address</label>
                                <textarea wire:model="office_address" rows="3" class="w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500"></textarea>
                                @error('office_address')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="pt-5 border-t">
                            <h4 class="font-bold text-gray-900 mb-3">Upload / Replace Documents</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach([
                                    'gst_image' => 'GST Document',
                                    'aadhar_image' => 'Aadhaar Document (Legacy)',
                                    'aadhar_front' => 'Aadhaar Front',
                                    'aadhar_back' => 'Aadhaar Back',
                                    'driving_licence_front' => 'Driving Licence Front',
                                    'driving_licence_back' => 'Driving Licence Back',
                                ] as $model => $label)
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $label }}</label>
                                        <input type="file" wire:model="{{ $model }}" accept="image/jpeg,image/png,image/webp" class="w-full text-sm border rounded-xl px-3 py-2">
                                        <div wire:loading wire:target="{{ $model }}" class="text-xs text-blue-600 mt-1">Uploading...</div>
                                        @error($model)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="toggleEditProfile" class="px-4 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-semibold">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile,gst_image,aadhar_image,aadhar_front,aadhar_back,driving_licence_front,driving_licence_back" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold disabled:opacity-60">
                                <span wire:loading.remove wire:target="updateProfile">Save Changes</span>
                                <span wire:loading wire:target="updateProfile">Saving...</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if($activeTab === 'address')
        <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h3 class="font-bold text-gray-900">Address Information</h3>
                @if(!$editingAddress)
                    <button type="button" wire:click="toggleEditAddress" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold">{{ $user->address ? 'Edit Address' : 'Add Address' }}</button>
                @endif
            </div>
            <div class="p-5">
                @if(!$editingAddress && $user->address)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        @foreach([
                            'Full Name' => $user->address->full_name,
                            'Email' => $user->address->email,
                            'Phone' => $user->address->phone,
                            'Alternate Phone' => $user->address->phone2,
                            'City' => $user->address->city,
                            'State' => $user->address->state,
                            'Pickup Address' => $user->address->pickup_address,
                            'Drop Address' => $user->address->drop_address,
                        ] as $label => $value)
                            <div>
                                <p class="text-xs font-semibold text-gray-500">{{ $label }}</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $value ?: 'Not provided' }}</p>
                            </div>
                        @endforeach
                    </div>
                @elseif(!$editingAddress)
                    <div class="text-center py-10 text-gray-500">No address added yet.</div>
                @else
                    <form wire:submit="updateAddress" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            @foreach([
                                ['full_name','Full Name *'],['address_email','Email *'],['phone','Phone *'],['phone2','Alternate Phone'],['city','City *'],['state','State *']
                            ] as [$model,$label])
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $label }}</label>
                                    <input type="text" wire:model="{{ $model }}" class="w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500">
                                    @error($model)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Pickup Address *</label>
                                <textarea wire:model="pickup_address" rows="3" class="w-full px-3 py-2.5 border rounded-xl"></textarea>
                                @error('pickup_address')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Drop Address</label>
                                <textarea wire:model="drop_address" rows="3" class="w-full px-3 py-2.5 border rounded-xl"></textarea>
                                @error('drop_address')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="toggleEditAddress" class="px-4 py-2.5 rounded-xl bg-gray-100 font-semibold">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold">Save Address</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if($activeTab === 'orders')
        <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b"><h3 class="font-bold text-gray-900">My Orders</h3></div>
            @if($recentOrders->count())
                <div class="divide-y">
                    @foreach($recentOrders as $order)
                        <div class="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900">{{ $order->booking_from }} → {{ $order->booking_to }}</p>
                                <p class="text-xs text-gray-500 mt-1">Order #{{ $order->id }} • {{ optional($order->created_at)->format('d M Y, h:i A') }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ ucfirst($order->ride_type ?? 'ride') }} • {{ $order->taxi_type ?? '' }}</p>
                            </div>
                            <div class="md:text-right">
                                <p class="text-lg font-bold text-gray-900">₹{{ number_format((float) $order->grand_total, 2) }}</p>
                                <p class="text-xs text-gray-500">{{ ucfirst($order->status) }} • {{ ucfirst($order->payment_method ?? 'pending') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center text-gray-500">No orders yet.</div>
            @endif
        </div>
    @endif

    @if($activeTab === 'wallet')
        <div class="space-y-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl p-6 shadow-lg">
                    <p class="text-sm text-blue-100">Available Balance</p>
                    <p class="mt-2 text-4xl font-bold">₹{{ number_format($availableBalance, 2) }}</p>
                    <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                        <div><p class="text-blue-200">Total Balance</p><p class="font-bold">₹{{ number_format((float) $wallet->balance, 2) }}</p></div>
                        <div><p class="text-blue-200">On Hold</p><p class="font-bold">₹{{ number_format((float) $wallet->hold_balance, 2) }}</p></div>
                    </div>
                </div>
                <div class="bg-white border rounded-2xl p-5 shadow-sm">
                    <h3 class="font-bold text-gray-900">Add Money</h3>
                    <p class="text-xs text-gray-500 mt-1">Secure Razorpay recharge</p>
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500">₹</span>
                            <input type="number" min="1" step="1" wire:model="walletRechargeAmount" class="w-full pl-7 pr-3 py-2.5 border rounded-xl">
                        </div>
                        @error('walletRechargeAmount')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex gap-2 mt-3">
                        @foreach([500,1000,2000] as $quick)
                            <button type="button" wire:click="$set('walletRechargeAmount', {{ $quick }})" class="flex-1 py-2 text-xs font-bold border rounded-lg hover:bg-gray-50">₹{{ $quick }}</button>
                        @endforeach
                    </div>
                    <button type="button" wire:click="createWalletRecharge" wire:loading.attr="disabled" wire:target="createWalletRecharge" class="w-full mt-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold disabled:opacity-60">
                        <span wire:loading.remove wire:target="createWalletRecharge">Add Money</span>
                        <span wire:loading wire:target="createWalletRecharge">Preparing...</span>
                    </button>
                    <p class="mt-3 text-[11px] leading-4 text-gray-500">Money is credited only after Razorpay payment verification.</p>
                </div>
            </div>

            <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center justify-between">
                    <h3 class="font-bold text-gray-900">Wallet Transactions</h3>
                    <span class="text-xs text-gray-500">Last {{ $walletTransactions->count() }}</span>
                </div>
                @if($walletTransactions->count())
                    <div class="divide-y">
                        @foreach($walletTransactions as $transaction)
                            @php $isCredit = strtolower((string) $transaction->type) === 'credit'; @endphp
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-full flex items-center justify-center {{ $isCredit ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $isCredit ? '+' : '−' }}</span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $transaction->remarks ?: ucfirst($transaction->type) }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $transaction->transaction_id }} • {{ optional($transaction->created_at)->format('d M Y, h:i A') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-bold {{ $isCredit ? 'text-green-600' : 'text-red-600' }}">{{ $isCredit ? '+' : '-' }}₹{{ number_format((float) $transaction->amount, 2) }}</p>
                                    <p class="text-xs text-gray-500">Bal ₹{{ number_format((float) $transaction->closing_balance, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-10 text-center text-sm text-gray-500">No wallet transactions yet.</div>
                @endif
            </div>
        </div>
    @endif

    @if($activeTab === 'security')
        <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b"><h3 class="font-bold text-gray-900">Security Settings</h3></div>
            <div class="p-5 max-w-lg">
                <form wire:submit="changePassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Current Password</label>
                        <input type="password" wire:model="current_password" class="w-full px-3 py-2.5 border rounded-xl">
                        @error('current_password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">New Password</label>
                        <input type="password" wire:model="new_password" class="w-full px-3 py-2.5 border rounded-xl">
                        @error('new_password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm New Password</label>
                        <input type="password" wire:model="new_password_confirmation" class="w-full px-3 py-2.5 border rounded-xl">
                    </div>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold">Update Password</button>
                </form>
            </div>
        </div>
    @endif

    @once
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            (function registerDuraWalletCheckout() {
                const bind = () => {
                    if (!window.Livewire || window.__duraWalletRazorpayBound) return;
                    window.__duraWalletRazorpayBound = true;

                    Livewire.on('wallet-recharge-ready', (event) => {
                        const payload = Array.isArray(event) ? event[0] : event;
                        const data = payload && payload.order ? payload.order : payload;

                        if (!data || !data.key || !data.razorpay_order_id || !window.Razorpay) {
                            Livewire.dispatch('wallet-recharge-dismissed');
                            alert('Razorpay checkout could not be initialized.');
                            return;
                        }

                        const options = {
                            key: data.key,
                            amount: data.amount_paise,
                            currency: data.currency || 'INR',
                            name: 'Dura Cabs',
                            description: 'Dura Wallet Recharge',
                            order_id: data.razorpay_order_id,
                            prefill: {
                                name: data.name || '',
                                email: data.email || '',
                                contact: data.mobile || ''
                            },
                            theme: {},
                            handler: function (response) {
                                Livewire.dispatch('wallet-recharge-verify', {
                                    orderId: response.razorpay_order_id || data.razorpay_order_id,
                                    paymentId: response.razorpay_payment_id || '',
                                    signature: response.razorpay_signature || ''
                                });
                            },
                            modal: {
                                ondismiss: function () {
                                    Livewire.dispatch('wallet-recharge-dismissed');
                                }
                            }
                        };

                        const rzp = new Razorpay(options);
                        rzp.on('payment.failed', function () {
                            Livewire.dispatch('wallet-recharge-dismissed');
                        });
                        rzp.open();
                    });
                };

                if (window.Livewire) bind();
                document.addEventListener('livewire:init', bind, { once: true });
            })();
        </script>
    @endonce
</div>