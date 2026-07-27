<div class="min-h-screen bg-slate-100">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex fixed left-0 top-0 h-screen w-72 bg-slate-950 text-white flex-col z-40">
        <div class="p-6 border-b border-blue-100">
            <h1 class="text-2xl font-black text-blue-400">DURA CABS</h1>
            <p class="text-xs text-slate-400 mt-1">Partner ERP</p>
        </div>

        <nav class="flex-1 p-4 space-y-2">
            @foreach([
                'dashboard' => ['🏠','Dashboard'],
                'profile' => ['👤','Profile'],
                'vehicles' => ['🚗','Fleet'],
				'Driver' => ['👤','Driver'],
                'bookings' => ['📅','Bookings'],
                'income' => ['💰','Income'],
                'documents' => ['📄','Documents'],
                'reviews' => ['⭐','Reviews'],
            ] as $tab => $item)
                <button wire:click="setTab('{{ $tab }}')"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-left transition
                    {{ $activeTab === $tab ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                    <span>{{ $item[0] }}</span>
                    <span class="font-semibold">{{ $item[1] }}</span>
                </button>
            @endforeach
        </nav>

        <div class="p-4">
            <button wire:click="logout"
                class="w-full py-3 rounded-2xl bg-red-500/15 text-red-300 font-bold">
                Logout
            </button>
        </div>
    </aside>

    <main class="lg:ml-72 pb-24">
        {{-- Header --}}
        <div class="bg-gradient-to-br from-blue-700 via-blue-600 to-sky-500 text-white px-5 lg:px-10 pt-8 pb-14 rounded-b-[36px] shadow-xl">
            <div class="max-w-6xl mx-auto flex justify-between items-center">
                <div>
                    <p class="text-blue-100 text-sm">Welcome back 👋</p>
                    <h2 class="text-3xl lg:text-4xl font-black mt-1">
                        {{ $contactPerson ?: ($user->name ?? 'Partner') }}
                    </h2>

                    <div class="flex flex-wrap gap-2 mt-4">
                        <span class="px-3 py-1 rounded-full bg-white text-blue-700 text-xs font-bold">
                            @if($partnerType === 'host')
                                Host Self Drive
                            @elseif($partnerType === 'vendor')
                                Vendor With Driver
                            @elseif($partnerType === 'both')
                                Host + Vendor
                            @else
                                Partner Type Pending
                            @endif
                        </span>

                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
                            {{ ucfirst($profile->verification_status ?? 'pending') }}
                        </span>
                    </div>
                </div>

                <button wire:click="logout" class="lg:hidden px-4 py-2 rounded-full bg-white/20 text-sm font-bold">
                    Logout
                </button>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 lg:px-10 -mt-7">
            @if(session()->has('success'))
                <div class="mb-5 p-4 rounded-2xl bg-green-100 text-green-700 font-semibold border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if(session()->has('error'))
                <div class="mb-5 p-4 rounded-2xl bg-red-100 text-red-700 font-semibold border border-red-200">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Dashboard --}}
            @if($activeTab === 'dashboard')
                <div class="grid lg:grid-cols-3 gap-5">
                    <div class="lg:col-span-2 bg-white rounded-[28px] p-6 shadow-sm border border-slate-200">
                        <div class="flex justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-black text-slate-800">Profile Completion</h3>
                                <p class="text-sm text-slate-500 mt-1">Complete profile, KYC and fleet to receive bookings.</p>
                            </div>
                            <p class="text-3xl font-black text-blue-700">{{ $profileCompletion }}%</p>
                        </div>

                        <div class="w-full h-4 bg-slate-200 rounded-full mt-5 overflow-hidden">
                            <div class="h-4 bg-blue-700 rounded-full" style="width: {{ $profileCompletion }}%"></div>
                        </div>

                        <div class="mt-5 flex flex-col sm:flex-row gap-3">
                            <button wire:click="setTab('profile')" class="px-5 py-3 rounded-2xl bg-blue-700 text-white font-bold">
                                Complete Profile
                            </button>
                            <button wire:click="setTab('vehicles')" class="px-5 py-3 rounded-2xl bg-blue-50 text-blue-700 font-bold">
                                Manage Fleet
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-[28px] p-6 shadow-sm border border-slate-200">
                        <p class="text-sm text-slate-500">Today Income</p>
                        <h3 class="text-4xl font-black text-slate-900 mt-2">
                            ₹{{ number_format($incomeToday, 0) }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-2">Updated today</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
                    @foreach([
                        ['bookings','📅','Bookings',$bookingsCount],
                        ['vehicles','🚗','Fleet',$vehiclesCount],
                        ['income','💰','Income','₹'.number_format($incomeToday,0)],
                        ['reviews','⭐','Reviews',$reviewsCount],
                    ] as $card)
                        <button wire:click="setTab('{{ $card[0] }}')"
                            class="bg-white rounded-[28px] p-5 text-left shadow-sm border border-slate-200">
                            <div class="text-3xl">{{ $card[1] }}</div>
                            <p class="text-sm text-slate-500 mt-4">{{ $card[2] }}</p>
                            <h3 class="text-2xl font-black text-slate-900">{{ $card[3] }}</h3>
                        </button>
                    @endforeach
                </div>

            @endif

            {{-- Profile --}}
            @if($activeTab === 'profile')
                <section class="bg-blu rounded-[28px] p-6 pb-28 lg:pb-6 shadow-sm border border-slate-200">
                    <button wire:click="setTab('dashboard')" class="mb-4 text-blue-700 font-bold">← Back</button>

                    <h2 class="text-2xl font-black mb-2">👤 Vendor Profile</h2>
                    <p class="text-slate-500 mb-6">Profile, KYC aur documents update karein.</p>

                    <form wire:submit.prevent="updateProfile" class="space-y-7">
                        <div>
                            <h3 class="text-lg font-black mb-4">Partner Account</h3>
                            <label class="font-bold text-sm">Partner Type*</label>

                            @if($partnerTypeLocked)
                                <div class="mt-2 rounded-2xl border bg-slate-100 p-3 font-bold">
                                    @if($partnerType=='host') Host Self Drive
                                    @elseif($partnerType=='vendor') Vendor With Driver
                                    @elseif($partnerType=='both') Host + Vendor
                                    @else Not Selected
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-2">Partner type ek baar select hone ke baad change nahi hoga.</p>
                            @else
                                <select wire:model.defer="partnerType" class="mt-2 w-full rounded-2xl border p-3">
                                    <option value="">Select an option</option>
                                    <option value="host">Host Self Drive</option>
                                    <option value="vendor">Vendor With Driver</option>
                                    <option value="both">Host + Vendor</option>
                                </select>
                            @endif
                            @error('partnerType') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <h3 class="text-lg font-black mb-4">Company Information</h3>
                            <div class="grid lg:grid-cols-2 gap-4">
                                <div>
                                    <label class="font-bold text-sm">Company Name*</label>
                                    <input type="text" wire:model.defer="companyName" class="mt-2 w-full rounded-2xl border p-3">
                                    @error('companyName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="font-bold text-sm">Contact Person*</label>
                                    <input type="text" wire:model.defer="contactPerson" class="mt-2 w-full rounded-2xl border p-3">
                                    @error('contactPerson') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="font-bold text-sm">Mobile*</label>
                                    <input type="text" wire:model.defer="mobile" class="mt-2 w-full rounded-2xl border p-3">
                                    @error('mobile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="font-bold text-sm">WhatsApp Number</label>
                                    <input type="text" wire:model.defer="whatsappNumber" class="mt-2 w-full rounded-2xl border p-3">
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="font-bold text-sm">Email</label>
                                    <input type="email" wire:model.defer="email" class="mt-2 w-full rounded-2xl border p-3">
                                    @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-black mb-4">KYC Details</h3>
                            <div class="grid lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="font-bold text-sm">Aadhaar Number</label>
                                    <input type="text" wire:model.defer="aadhaarNumber" class="mt-2 w-full rounded-2xl border p-3">
                                </div>

                                <div>
                                    <label class="font-bold text-sm">PAN Number</label>
                                    <input type="text" wire:model.defer="panNumber" class="mt-2 w-full rounded-2xl border p-3">
                                </div>

                                <div>
                                    <label class="font-bold text-sm">GST Number</label>
                                    <input type="text" wire:model.defer="gstNumber" class="mt-2 w-full rounded-2xl border p-3">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-black mb-4">Office Address</h3>
                            <div class="grid lg:grid-cols-3 gap-4">
                                <div class="lg:col-span-3">
                                    <label class="font-bold text-sm">Office Address</label>
                                    <textarea wire:model.defer="officeAddress" rows="3" class="mt-2 w-full rounded-2xl border p-3"></textarea>
                                </div>

                                <div>
                                    <label class="font-bold text-sm">City*</label>
                                    <input type="text" wire:model.defer="city" class="mt-2 w-full rounded-2xl border p-3">
                                    @error('city') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="font-bold text-sm">State*</label>
                                    <input type="text" wire:model.defer="state" class="mt-2 w-full rounded-2xl border p-3">
                                    @error('state') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="font-bold text-sm">Pincode</label>
                                    <input type="text" wire:model.defer="pincode" class="mt-2 w-full rounded-2xl border p-3">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-black mb-4">Documents</h3>
                            <div class="grid lg:grid-cols-2 gap-4">
                                @foreach([
                                    ['aadhaarImage','Aadhaar Image',$profile->aadhaar_image ?? null],
                                    ['panImage','PAN Image',$profile->pan_image ?? null],
                                    ['gstImage','GST Image',$profile->gst_image ?? null],
                                    ['companyDocument','Company Document',$profile->company_document ?? null],
                                    ['officePhoto','Office Photo',$profile->office_photo ?? null],
                                ] as $doc)
                                    <div class="rounded-2xl border p-4 bg-slate-50">
                                        <label class="font-bold text-sm">{{ $doc[1] }}</label>
                                        <input type="file" wire:model="{{ $doc[0] }}" class="mt-2 w-full rounded-2xl border p-3 bg-white">
                                        @if($doc[2])
                                            <p class="text-xs text-green-700 font-bold mt-2">Uploaded</p>
                                        @else
                                            <p class="text-xs text-yellow-600 font-bold mt-2">Pending</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="sticky bottom-16 lg:bottom-0 bg-white pt-4 pb-4 border-t">
                            <button type="submit" class="w-full lg:w-auto px-10 py-4 rounded-2xl bg-blue-700 text-white font-black shadow-lg">
                                Save Vendor Profile
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- Fleet --}}
            @if($activeTab === 'vehicles')
                <section class="bg-white rounded-[28px] p-6 shadow-sm border border-slate-200">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        <div>
                            <button wire:click="setTab('dashboard')" class="mb-3 text-blue-700 font-bold">← Back</button>
                            <h2 class="text-3xl font-black">🚗 Fleet Management</h2>
							
                            <p class="text-slate-500 mt-2">Add / Edit / Delete / Active / Inactive vehicles.</p>

                        </div>

                        <div class="flex flex-wrap gap-3">

    @if($partnerType === 'vendor')
        <button wire:click="openAddFleetForm('with_driver')"
            class="px-6 py-3 rounded-2xl bg-blue-700 text-white font-bold">
            + Add Vehicle
        </button>
    @elseif($partnerType === 'host')
        <button wire:click="openAddFleetForm('self_drive')"
            class="px-6 py-3 rounded-2xl bg-blue-700 text-white font-bold">
            + Add Self Drive Car / Bike
        </button>
    @elseif($partnerType === 'both')
        <button wire:click="openAddFleetForm('self_drive')"
            class="px-6 py-3 rounded-2xl bg-blue-700 text-white font-bold">
            + Add Self Drive
        </button>

        <button wire:click="openAddFleetForm('with_driver')"
            class="px-6 py-3 rounded-2xl bg-slate-900 text-white font-bold">
            + Add With Driver
        </button>
    @else
        <button wire:click="setTab('profile')"
            class="px-6 py-3 rounded-2xl bg-red-50 text-red-700 font-bold">
            Complete Partner Type
        </button>
    @endif

</div>
                    </div>

                    @if($showFleetForm)
                        <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6 mb-8">
                            <div class="flex justify-between items-center">
                                <h2 class="text-2xl font-black">{{ $editingFleet ? 'Edit Fleet' : 'Add Fleet' }}</h2>
                                <button wire:click="$set('showFleetForm',false)" class="text-red-600 font-bold">✕</button>
                            </div>

                            <form wire:submit.prevent="saveFleet" class="mt-6 space-y-8">
                                <div>
                                    <h3 class="text-xl font-black mb-4">Basic Information</h3>
                                    <div class="grid lg:grid-cols-2 gap-4">
                                        <div>
                                            <label class="font-bold text-sm">Service Type</label>
                                            <select wire:model="fleetServiceType" class="mt-2 w-full rounded-2xl border p-3">
                                                <option value="self_drive">Self Drive</option>
                                                <option value="with_driver">With Driver</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="font-bold text-sm">Vehicle Type</label>
                                            <select wire:model="vehicleType" class="mt-2 w-full rounded-2xl border p-3">
                                                <option value="car">Car</option>
                                                <option value="bike">Bike</option>
                                                <option value="traveller">Traveller</option>
                                                <option value="bus">Bus</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="font-bold text-sm">Brand</label>
                                            <select wire:model="brandId" class="mt-2 w-full rounded-2xl border p-3">
                                                <option value="">Select Brand</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand['id'] }}">{{ $brand['name'] ?? $brand['title'] ?? 'Brand' }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="font-bold text-sm">Category</label>
                                            <select wire:model="categoryId" class="mt-2 w-full rounded-2xl border p-3">
                                                <option value="">Select Category</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category['id'] }}">{{ $category['name'] ?? $category['title'] ?? 'Category' }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="font-bold text-sm">Title*</label>
                                            <input type="text" wire:model="titleName" class="mt-2 w-full rounded-2xl border p-3">
                                            @error('titleName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="font-bold text-sm">Slug</label>
                                            <input type="text" wire:model="slug" class="mt-2 w-full rounded-2xl border p-3">
                                        </div>

                                        <div class="lg:col-span-2">
                                            <label class="font-bold text-sm">Description</label>
                                            <textarea wire:model="description" rows="3" class="mt-2 w-full rounded-2xl border p-3"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xl font-black mb-4">Vehicle Details</h3>
                                    <div class="grid lg:grid-cols-3 gap-4">
                                        <div><label class="font-bold text-sm">Model Name</label><input type="text" wire:model="modelName" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">Manufacture Year</label><input type="text" wire:model="modelYear" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">Fuel</label><input type="text" wire:model="fuel" placeholder="Petrol/CNG" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">Transmission</label><input type="text" wire:model="transmission" placeholder="Manual/Automatic" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">Vehicle Number</label><input type="text" wire:model="vehicleNumber" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">Color</label><input type="text" wire:model="vehicleColor" class="mt-2 w-full rounded-2xl border p-3"></div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xl font-black mb-4">Pricing</h3>
                                    <div class="grid lg:grid-cols-3 gap-4">
                                        <div><label class="font-bold text-sm">KM Charge</label><input type="number" wire:model="kmCharge" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">Daily Charge</label><input type="number" wire:model="dailyCharge" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">Security Deposit</label><input type="number" wire:model="securityDeposit" class="mt-2 w-full rounded-2xl border p-3"></div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xl font-black mb-4">Documents</h3>
                                    <div class="grid lg:grid-cols-2 gap-4">
                                        <div><label class="font-bold text-sm">Vehicle Photo</label><input type="file" wire:model="vehicleImage" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">RC</label><input type="file" wire:model="rcImage" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">Insurance</label><input type="file" wire:model="insuranceImage" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        <div><label class="font-bold text-sm">PUC / Pollution</label><input type="file" wire:model="pollutionImage" class="mt-2 w-full rounded-2xl border p-3"></div>

                                        @if($fleetServiceType === 'with_driver')
                                            <div><label class="font-bold text-sm">Fitness</label><input type="file" wire:model="fitnessImage" class="mt-2 w-full rounded-2xl border p-3"></div>
                                            <div><label class="font-bold text-sm">Authority / Permit</label><input type="file" wire:model="authorityImage" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        @endif
                                    </div>
                                </div>

                                @if($fleetServiceType === 'with_driver')
                                    <div>
                                        <h3 class="text-xl font-black mb-4">Driver Details</h3>
                                        <div class="grid lg:grid-cols-3 gap-4">
                                            <div><label class="font-bold text-sm">Driver Name</label><input type="text" wire:model="driverName" class="mt-2 w-full rounded-2xl border p-3"></div>
                                            <div><label class="font-bold text-sm">Driver Mobile</label><input type="text" wire:model="driverMobile" class="mt-2 w-full rounded-2xl border p-3"></div>
                                            <div><label class="font-bold text-sm">License Number</label><input type="text" wire:model="driverLicenseNumber" class="mt-2 w-full rounded-2xl border p-3"></div>
                                        </div>
                                    </div>
                                @endif

                                <label class="flex items-center gap-3 font-bold">
                                    <input type="checkbox" wire:model="isActive">
                                    Active Vehicle
                                </label>

                                <div class="sticky bottom-16 lg:bottom-0 bg-white pt-4 pb-4 border-t">
                                    <button type="submit" class="w-full lg:w-auto px-10 py-4 rounded-2xl bg-blue-700 text-white font-black shadow-lg">
                                        {{ $editingFleet ? 'Update Fleet' : 'Save Fleet' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <div class="mt-8">
                        <h3 class="text-2xl font-black mb-5">My Fleet</h3>

                       @if(count($fleets) === 0)

    <div class="rounded-3xl bg-slate-50 border border-dashed border-slate-300 p-12 text-center">

        <div class="text-6xl">🚗</div>

        <h3 class="mt-4 text-2xl font-black">
            No Vehicle Added
        </h3>

        <p class="text-slate-500 mt-2">
            Add your first vehicle to start receiving bookings.
        </p>

        <div class="mt-8 flex justify-center gap-4 flex-wrap">

            @if($partnerType === 'vendor')
                <button
                    type="button"
                    wire:click="openAddFleetForm('with_driver')"
                    class="px-8 py-4 rounded-2xl bg-blue-700 text-white font-bold shadow-lg hover:bg-blue-800">
                    ➕ Add Vehicle
                </button>
            @elseif($partnerType === 'host')
                <button
                    type="button"
                    wire:click="openAddFleetForm('self_drive')"
                    class="px-8 py-4 rounded-2xl bg-blue-700 text-white font-bold shadow-lg hover:bg-blue-800">
                    ➕ Add Self Drive Vehicle
                </button>
            @elseif($partnerType === 'both')
                <button
                    type="button"
                    wire:click="openAddFleetForm('self_drive')"
                    class="px-8 py-4 rounded-2xl bg-blue-700 text-white font-bold shadow-lg hover:bg-blue-800">
                    ➕ Self Drive
                </button>

                <button
                    type="button"
                    wire:click="openAddFleetForm('with_driver')"
                    class="px-8 py-4 rounded-2xl bg-slate-900 text-white font-bold shadow-lg hover:bg-slate-800">
                    ➕ With Driver
                </button>
            @else
                <button
                    type="button"
                    wire:click="setTab('profile')"
                    class="px-8 py-4 rounded-2xl bg-red-50 text-red-700 font-bold shadow">
                    Complete Partner Type
                </button>
            @endif

        </div>
    </div>

@else

    <div class="grid lg:grid-cols-2 gap-5">
        @foreach($fleets as $fleet)
            @php
                $active = (bool)($fleet['status'] ?? $fleet['is_active'] ?? $fleet['active'] ?? false);
                $fleetImage = $fleet['image'] ?? $fleet['vehicle_image'] ?? $fleet['photo'] ?? null;
                $fleetName = $fleet['name'] ?? $fleet['title'] ?? 'Vehicle';
            @endphp

            <div class="rounded-3xl bg-white border border-slate-200 overflow-hidden shadow-sm">
                <div class="aspect-video bg-slate-200">
                    @if($fleetImage)
                        <img src="{{ asset('storage/'.$fleetImage) }}" class="w-full h-full object-cover">
                    @else
                        <div class="flex items-center justify-center h-full text-6xl">🚗</div>
                    @endif
                </div>

                <div class="p-5">
                    <div class="flex justify-between items-start gap-3">
                        <div>
                            <h3 class="text-xl font-black">{{ $fleetName }}</h3>
                            <p class="text-sm text-slate-500 mt-1">
                                {{ $fleet['model_name'] ?? $fleet['model'] ?? $fleet['model_similar'] ?? '' }}
                            </p>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">Vehicle Number</p>
                            <p class="font-bold mt-1">{{ $fleet['vehicle_number'] ?? $fleet['registration_number'] ?? '-' }}</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">Fuel</p>
                            <p class="font-bold mt-1">{{ $fleet['fuel'] ?? $fleet['fuel_type'] ?? '-' }}</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">Transmission</p>
                            <p class="font-bold mt-1">{{ $fleet['transmission'] ?? '-' }}</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">Price</p>
                            <p class="font-bold mt-1">
                                ₹{{ $fleet['daily_charge'] ?? $fleet['price_per_day'] ?? 0 }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6">
                        <button
                            type="button"
                            wire:click="editFleet({{ $fleet['id'] }})"
                            class="rounded-xl bg-blue-700 text-white py-3 text-sm font-bold">
                            Edit
                        </button>

                        <button
                            type="button"
                            wire:click="toggleFleetStatus({{ $fleet['id'] }})"
                            class="rounded-xl bg-yellow-100 text-yellow-700 py-3 text-sm font-bold">
                            {{ $active ? 'Deactivate' : 'Activate' }}
                        </button>

                        <button
                            type="button"
                            wire:click="deleteFleet({{ $fleet['id'] }})"
                            onclick="return confirm('Delete this vehicle?')"
                            class="rounded-xl bg-red-100 text-red-700 py-3 text-sm font-bold">
                            Delete
                        </button>

                        <button
                            type="button"
                            class="rounded-xl bg-slate-900 text-white py-3 text-sm font-bold">
                            View
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    
                            <div class="grid lg:grid-cols-2 gap-5">
                                @foreach($fleets as $fleet)
                                    @php
                                        $active = (bool)($fleet['status'] ?? $fleet['is_active'] ?? $fleet['active'] ?? false);
                                        $fleetImage = $fleet['image'] ?? $fleet['vehicle_image'] ?? $fleet['photo'] ?? null;
                                        $fleetName = $fleet['name'] ?? $fleet['title'] ?? 'Vehicle';
                                    @endphp

                                    <div class="rounded-3xl bg-white border border-slate-200 overflow-hidden shadow-sm">
                                        <div class="aspect-video bg-slate-200">
                                            @if($fleetImage)
                                                <img src="{{ asset('storage/'.$fleetImage) }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="flex items-center justify-center h-full text-6xl">🚗</div>
                                            @endif
                                        </div>

                                        <div class="p-5">
                                            <div class="flex justify-between items-start gap-3">
                                                <div>
                                                    <h3 class="text-xl font-black">{{ $fleetName }}</h3>
                                                    <p class="text-sm text-slate-500 mt-1">
                                                        {{ $fleet['model_name'] ?? $fleet['model'] ?? $fleet['model_similar'] ?? '' }}
                                                    </p>
                                                </div>

                                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                    {{ $active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3 mt-5">
                                                <div class="rounded-2xl bg-slate-50 p-3">
                                                    <p class="text-xs text-slate-500">Vehicle Number</p>
                                                    <p class="font-bold mt-1">{{ $fleet['vehicle_number'] ?? $fleet['registration_number'] ?? '-' }}</p>
                                                </div>

                                                <div class="rounded-2xl bg-slate-50 p-3">
                                                    <p class="text-xs text-slate-500">Fuel</p>
                                                    <p class="font-bold mt-1">{{ $fleet['fuel'] ?? $fleet['fuel_type'] ?? '-' }}</p>
                                                </div>

                                                <div class="rounded-2xl bg-slate-50 p-3">
                                                    <p class="text-xs text-slate-500">Transmission</p>
                                                    <p class="font-bold mt-1">{{ $fleet['transmission'] ?? '-' }}</p>
                                                </div>

                                                <div class="rounded-2xl bg-slate-50 p-3">
                                                    <p class="text-xs text-slate-500">Price</p>
                                                    <p class="font-bold mt-1">₹{{ $fleet['daily_charge'] ?? $fleet['price_per_day'] ?? 0 }}</p>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6">
                                                <button wire:click="editFleet({{ $fleet['id'] }})" class="rounded-xl bg-blue-700 text-white py-3 text-sm font-bold">Edit</button>
                                                <button wire:click="toggleFleetStatus({{ $fleet['id'] }})" class="rounded-xl bg-yellow-100 text-yellow-700 py-3 text-sm font-bold">
                                                    {{ $active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                                <button wire:click="deleteFleet({{ $fleet['id'] }})" onclick="return confirm('Delete this vehicle?')" class="rounded-xl bg-red-100 text-red-700 py-3 text-sm font-bold">Delete</button>
                                                <button class="rounded-xl bg-slate-900 text-white py-3 text-sm font-bold">View</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if($activeTab === 'bookings')
                <div class="bg-white rounded-[28px] p-8 shadow-sm"><h2 class="text-2xl font-black">Bookings</h2><p class="text-slate-500 mt-3">Coming Soon...</p></div>
            @endif

            @if($activeTab === 'income')
                <div class="bg-white rounded-[28px] p-8 shadow-sm"><h2 class="text-2xl font-black">Income</h2><p class="text-slate-500 mt-3">Coming Soon...</p></div>
            @endif

            @if($activeTab === 'documents')
                <div class="bg-white rounded-[28px] p-8 shadow-sm"><h2 class="text-2xl font-black">Documents</h2><p class="text-slate-500 mt-3">Coming Soon...</p></div>
            @endif

            @if($activeTab === 'reviews')
                <div class="bg-white rounded-[28px] p-8 shadow-sm"><h2 class="text-2xl font-black">Reviews</h2><p class="text-slate-500 mt-3">Coming Soon...</p></div>
            @endif
        </div>
    </main>

    {{-- Mobile Bottom Navigation --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 shadow-2xl z-50">
        <div class="grid grid-cols-5 h-16">
            @foreach([
                'dashboard'=>['🏠','Home'],
                'vehicles'=>['🚗','Fleet'],
                'bookings'=>['📅','Bookings'],
                'income'=>['💰','Income'],
                'profile'=>['👤','Profile'],
            ] as $tab=>$item)
                <button wire:click="setTab('{{ $tab }}')"
                    class="flex flex-col items-center justify-center {{ $activeTab==$tab ? 'text-blue-700' : 'text-slate-500' }}">
                    <span class="text-xl">{{ $item[0] }}</span>
                    <span class="text-[11px] mt-1 font-semibold">{{ $item[1] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>