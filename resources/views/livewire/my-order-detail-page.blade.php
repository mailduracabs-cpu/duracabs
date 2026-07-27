<div class="w-full max-w-7xl py-4 px-3 sm:px-4 lg:px-6 mx-auto">
    @section('title', 'Order Details - Duracabs')
    @section('description', 'View your order details and manage your booking')

    <!-- Compact Header -->
    <div class="mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Order #{{ $order->id }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $order->created_at->format('M d, Y h:i A') }}</p>
            </div>
            <a href="/my-orders" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition duration-200 self-start sm:self-auto">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Orders
            </a>
        </div>
    </div>

    <!-- Compact Status Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <!-- Order Status -->
        <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
            <div class="p-3 flex items-center gap-x-3">
                <div class="flex-shrink-0 w-10 h-10 {{ $this->getStatusColor($order->status) }} rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $this->getStatusText($order->status) }}</p>
                </div>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
            <div class="p-3 flex items-center gap-x-3">
                <div class="flex-shrink-0 w-10 h-10 {{ $order->payment_status === 'paid' ? 'bg-green-500' : ($order->payment_status === 'pending' ? 'bg-yellow-500' : 'bg-red-500') }} rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Payment</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ ucfirst($order->payment_status) }}</p>
                </div>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
            <div class="p-3 flex items-center gap-x-3">
                <div class="flex-shrink-0 w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">₹{{ number_format($order->grand_total) }}</p>
                </div>
            </div>
        </div>

        <!-- Ride Type -->
        <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
            <div class="p-3 flex items-center gap-x-3">
                <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Type</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $rideDetails['title'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid lg:grid-cols-2 gap-4">
        <!-- Left Column - Trip Details & Order Items -->
        <div class="space-y-4">
            
            <!-- Trip Details -->
            <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        {{ $rideDetails['title'] }} Details
                    </h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($rideDetails['fields'] as $label => $value)
                            @if($value)
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">{{ $label }}</label>
                                <p class="text-gray-900 dark:text-white font-semibold">{{ $value }}</p>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    @if($order->taxi_type || $order->productName)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="grid grid-cols-1 gap-4">
                            @if($order->taxi_type)
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Vehicle Type</label>
                                <p class="text-gray-900 dark:text-white font-semibold">{{ $order->taxi_type }}</p>
                            </div>
                            @endif
                            @if($order->productName)
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Service</label>
                                <p class="text-gray-900 dark:text-white font-semibold">{{ $order->productName }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Order Items -->
            @if($order_items->count() > 0)
            <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Booked Services</h3>
                </div>
                <div class="p-4">
                    @foreach ($order_items as $item)
                    <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="flex items-center min-w-0 flex-1">
                            @if($item->order->image)
                            <img class="h-10 w-10 rounded-lg object-cover mr-3 flex-shrink-0" 
                                 src="{{ url('storage') }}/{{ $item->order->image }}" 
                                 alt="{{ $item->order->productName ?? $item->name }}">
                            @else
                            <div class="h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white text-sm truncate">
                                    {{ $item->order->productName ?? $item->name }}
                                </p>
                                <p class="text-xs text-gray-500">₹{{ number_format($item->unit_ammount) }} × 1</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-3">
                            <p class="font-semibold text-gray-900 dark:text-white">₹{{ number_format($item->total_ammount) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column - Customer Info & Summary -->
        <div class="space-y-4">
            
            <!-- Customer Information -->
            @if($address)
            <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Customer Information</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Full Name</label>
                            <p class="text-gray-900 dark:text-white font-semibold">{{ $address->full_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Phone</label>
                            <p class="text-gray-900 dark:text-white font-semibold">{{ $address->phone }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Email</label>
                            <p class="text-gray-900 dark:text-white font-semibold">{{ $address->email }}</p>
                        </div>
                        @if($address->phone2)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Alternate Phone</label>
                            <p class="text-gray-900 dark:text-white font-semibold">{{ $address->phone2 }}</p>
                        </div>
                        @endif
                    </div>
                    
                    @if($address->pickup_address || $address->drop_address || $address->comments)
                    <div class="mt-4 pt-4 border-t border-gray-200 space-y-3">
                        @if($address->pickup_address)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Pickup Address</label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $address->pickup_address }}</p>
                        </div>
                        @endif
                        @if($address->drop_address)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Drop Address</label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $address->drop_address }}</p>
                        </div>
                        @endif
                        @if($address->comments)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Special Instructions</label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $address->comments }}</p>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Order Summary -->
            <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Order Summary</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium">₹{{ number_format($order->grand_total - ($order->shipping_ammount ?? 0)) }}</span>
                    </div>
                    @if($order->shipping_ammount)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Service Charges</span>
                        <span class="font-medium">₹{{ number_format($order->shipping_ammount) }}</span>
                    </div>
                    @endif
                    @if($order->coupon_value)
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount ({{ $order->coupon_name }})</span>
                        <span>-₹{{ number_format($order->coupon_value) }}</span>
                    </div>
                    @endif
                    <hr class="border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between font-semibold">
                        <span>Total Amount</span>
                        <span>₹{{ number_format($order->grand_total) }}</span>
                    </div>
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Payment Method</span>
                            <span class="font-medium">{{ ucfirst($order->payment_method) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Actions -->
            <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Quick Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    @if($this->canModify())
                    <button wire:click="showModificationDialog" 
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-orange-300 text-sm font-medium rounded-md text-orange-700 bg-orange-50 hover:bg-orange-100 transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Request Modification
                    </button>
                    @endif

                    @if($this->canCancel())
                    <button wire:click="showCancelDialog" 
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel Order
                    </button>
                    @endif

                    @if($this->canRebook())
                    <button wire:click="rebookOrder" 
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-green-300 text-sm font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Book Again
                    </button>
                    @endif

                    <a href="/contact-us" 
                       class="w-full inline-flex items-center justify-center px-3 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Contact Support
                    </a>
                </div>
            </div>

            <!-- Compact Timeline -->
            <div class="bg-white border shadow-sm rounded-lg dark:bg-slate-900 dark:border-gray-800">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Order Timeline</h3>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between items-center">
                                    <p class="text-sm font-medium text-gray-900">Order placed</p>
                                    <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                        
                        @if(in_array($order->status, ['confirm', 'start', 'closed']))
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between items-center">
                                    <p class="text-sm font-medium text-gray-900">Order confirmed</p>
                                    <p class="text-xs text-gray-500">{{ $order->updated_at->format('M d, h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if(in_array($order->status, ['start', 'closed']))
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between items-center">
                                    <p class="text-sm font-medium text-gray-900">Trip started</p>
                                    <p class="text-xs text-gray-500">{{ $order->updated_at->format('M d, h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if($order->status === 'closed')
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-6 h-6 bg-gray-500 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between items-center">
                                    <p class="text-sm font-medium text-gray-900">Trip completed</p>
                                    <p class="text-xs text-gray-500">{{ $order->updated_at->format('M d, h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    @if($showCancelModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click.self="$set('showCancelModal', false)">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="mt-2 px-7 py-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center">Cancel Order</h3>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for cancellation</label>
                        <textarea wire:model="cancelReason" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                                  placeholder="Please provide a reason for cancelling this order..."></textarea>
                        @error('cancelReason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <div class="flex space-x-3">
                        <button wire:click="cancelOrder" 
                                class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition duration-200">
                            Cancel Order
                        </button>
                        <button wire:click="$set('showCancelModal', false)" 
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 transition duration-200">
                            Keep Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modification Request Modal -->
    @if($showModificationModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click.self="$set('showModificationModal', false)">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100">
                    <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <div class="mt-2 px-7 py-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center">Request Modification</h3>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Modification details</label>
                        <textarea wire:model="modificationDetails" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm"
                                  placeholder="Please describe what you would like to modify..."></textarea>
                        @error('modificationDetails') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <div class="flex space-x-3">
                        <button wire:click="requestModification" 
                                class="flex-1 px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-md hover:bg-orange-700 transition duration-200">
                            Send Request
                        </button>
                        <button wire:click="$set('showModificationModal', false)" 
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 transition duration-200">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>