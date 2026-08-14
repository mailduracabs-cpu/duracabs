<?php

namespace App\Livewire;

use App\Http\Controllers\Api\V1\WalletController;
use App\Models\Address;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class MyAccount extends Component
{
    use WithFileUploads, LivewireAlert;

    public $user;
    public string $activeTab = 'overview';

    // Profile
    public $name;
    public $email;
    public $mobile;
    public $company_name;
    public $gst_number;
    public $aadhar_number;
    public $driving_licence_number;
    public $office_address;

    // Address
    public $full_name;
    public $address_email;
    public $phone;
    public $phone2;
    public $pickup_address;
    public $drop_address;
    public $state;
    public $city;

    // Password
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    // Document uploads
    public $gst_image;
    public $aadhar_image;
    public $aadhar_front;
    public $aadhar_back;
    public $driving_licence_front;
    public $driving_licence_back;

    // Wallet
    public $walletRechargeAmount = 500;
    public bool $walletProcessing = false;

    // Edit modes
    public bool $editingProfile = false;
    public bool $editingAddress = false;

    public function mount(): void
    {
        $this->user = auth()->user();

        abort_unless($this->user, 401);

        $this->user->loadMissing('address');
        $this->loadUserData();
    }

    public function loadUserData(): void
    {
        $this->user->refresh()->loadMissing('address');

        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->mobile = $this->user->mobile;
        $this->company_name = $this->user->company_name;
        $this->gst_number = $this->user->gst_number;
        $this->aadhar_number = $this->user->aadhar_number;
        $this->driving_licence_number = $this->user->driving_licence_number;
        $this->office_address = $this->user->office_address;

        if ($this->user->address) {
            $this->full_name = $this->user->address->full_name;
            $this->address_email = $this->user->address->email;
            $this->phone = $this->user->address->phone;
            $this->phone2 = $this->user->address->phone2;
            $this->pickup_address = $this->user->address->pickup_address;
            $this->drop_address = $this->user->address->drop_address;
            $this->state = $this->user->address->state;
            $this->city = $this->user->address->city;
        }
    }

    public function changeTab(string $tab): void
    {
        $allowed = ['overview', 'profile', 'address', 'orders', 'wallet', 'security'];
        $this->activeTab = in_array($tab, $allowed, true) ? $tab : 'overview';
        $this->editingProfile = false;
        $this->editingAddress = false;
        $this->resetValidation();
    }

    public function toggleEditProfile(): void
    {
        $this->editingProfile = ! $this->editingProfile;

        if (! $this->editingProfile) {
            $this->resetProfileUploads();
            $this->loadUserData();
        }
    }

    public function toggleEditAddress(): void
    {
        $this->editingAddress = ! $this->editingAddress;

        if (! $this->editingAddress) {
            $this->loadUserData();
        }
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->user->id],
            'mobile' => ['required', 'string', 'max:15', 'unique:users,mobile,' . $this->user->id],
            'company_name' => ['nullable', 'string', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:20'],
            'aadhar_number' => ['nullable', 'string', 'max:20'],
            'driving_licence_number' => ['nullable', 'string', 'max:30'],
            'office_address' => ['nullable', 'string', 'max:500'],
            'gst_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'aadhar_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'aadhar_front' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'aadhar_back' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'driving_licence_front' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'driving_licence_back' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $updateData = collect($validated)
            ->only([
                'name',
                'email',
                'mobile',
                'company_name',
                'gst_number',
                'aadhar_number',
                'driving_licence_number',
                'office_address',
            ])
            ->toArray();

        $this->storeProfileDocument($updateData, 'gst_image', $this->gst_image);
        $this->storeProfileDocument($updateData, 'aadhar_image', $this->aadhar_image);
        $this->storeProfileDocument($updateData, 'aadhar_front', $this->aadhar_front);
        $this->storeProfileDocument($updateData, 'aadhar_back', $this->aadhar_back);
        $this->storeProfileDocument($updateData, 'driving_licence_front', $this->driving_licence_front);
        $this->storeProfileDocument($updateData, 'driving_licence_back', $this->driving_licence_back);

        // forceFill is intentional here: every key above is explicitly validated/whitelisted.
        $this->user->forceFill($updateData)->save();
        $this->user->refresh()->loadMissing('address');

        $this->resetProfileUploads();
        $this->editingProfile = false;
        $this->loadUserData();

        $this->alert('success', 'Profile updated successfully!', [
            'position' => 'center',
            'timer' => 2500,
            'toast' => true,
        ]);
    }

    public function updateAddress(): void
    {
        $this->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'address_email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:15'],
            'phone2' => ['nullable', 'string', 'max:15'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'drop_address' => ['nullable', 'string', 'max:500'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
        ]);

        $addressData = [
            'user_id' => $this->user->id,
            'full_name' => $this->full_name,
            'email' => $this->address_email,
            'phone' => $this->phone,
            'phone2' => $this->phone2,
            'pickup_address' => $this->pickup_address,
            'drop_address' => $this->drop_address,
            'state' => $this->state,
            'city' => $this->city,
        ];

        if ($this->user->address) {
            $this->user->address->update($addressData);
        } else {
            Address::create($addressData);
        }

        $this->user->refresh()->load('address');
        $this->editingAddress = false;

        $this->alert('success', 'Address updated successfully!', [
            'position' => 'center',
            'timer' => 2500,
            'toast' => true,
        ]);
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check((string) $this->current_password, (string) $this->user->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        $this->user->forceFill([
            'password' => Hash::make($this->new_password),
        ])->save();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        $this->alert('success', 'Password changed successfully!', [
            'position' => 'center',
            'timer' => 2500,
            'toast' => true,
        ]);
    }

    public function createWalletRecharge(): void
    {
        $this->validate([
            'walletRechargeAmount' => ['required', 'numeric', 'min:1', 'max:500000'],
        ]);

        if ($this->walletProcessing) {
            return;
        }

        $this->walletProcessing = true;

        try {
            $result = $this->callWalletController('createRechargeOrder', [
                'amount' => round((float) $this->walletRechargeAmount, 2),
                'currency' => 'INR',
            ]);

            if (! ($result['status'] ?? false)) {
                throw new \RuntimeException((string) ($result['message'] ?? 'Unable to create wallet recharge order.'));
            }

            $order = is_array($result['data'] ?? null) ? $result['data'] : [];

            if (empty($order['razorpay_order_id']) || empty($order['key'])) {
                throw new \RuntimeException('Invalid Razorpay wallet recharge response.');
            }

            $this->dispatch('wallet-recharge-ready', order: array_merge($order, [
                'name' => (string) ($this->user->name ?? ''),
                'email' => (string) ($this->user->email ?? ''),
                'mobile' => (string) ($this->user->mobile ?? ''),
            ]));
        } catch (Throwable $e) {
            $this->walletProcessing = false;
            $this->alert('error', $e->getMessage(), [
                'position' => 'center',
                'timer' => 4000,
                'toast' => true,
            ]);
        }
    }

    #[On('wallet-recharge-verify')]
    public function verifyWalletRecharge(string $orderId, string $paymentId, string $signature): void
    {
        try {
            $result = $this->callWalletController('verifyRechargePayment', [
                'razorpay_order_id' => trim($orderId),
                'razorpay_payment_id' => trim($paymentId),
                'razorpay_signature' => trim($signature),
            ]);

            if (! ($result['status'] ?? false)) {
                throw new \RuntimeException((string) ($result['message'] ?? 'Wallet recharge verification failed.'));
            }

            $this->walletProcessing = false;
            $this->activeTab = 'wallet';

            $this->alert('success', 'Wallet recharged successfully!', [
                'position' => 'center',
                'timer' => 3000,
                'toast' => true,
            ]);
        } catch (Throwable $e) {
            $this->walletProcessing = false;
            $this->alert('error', $e->getMessage(), [
                'position' => 'center',
                'timer' => 4500,
                'toast' => true,
            ]);
        }
    }

    #[On('wallet-recharge-dismissed')]
    public function walletRechargeDismissed(): void
    {
        $this->walletProcessing = false;
    }

    private function callWalletController(string $method, array $payload): array
    {
        $request = Request::create('/api/v1/wallet/recharge', 'POST', $payload);
        $request->setUserResolver(fn () => $this->user);

        $response = app()->call(
            [app(WalletController::class), $method],
            ['request' => $request]
        );

        if (! method_exists($response, 'getData')) {
            throw new \RuntimeException('Invalid wallet service response.');
        }

        return (array) $response->getData(true);
    }

    private function storeProfileDocument(array &$updateData, string $column, $upload): void
    {
        if (! $upload) {
            return;
        }

        $oldPath = (string) ($this->user->{$column} ?? '');

        if ($oldPath !== '') {
            Storage::disk('public')->delete($oldPath);
        }

        $updateData[$column] = $upload->store('documents/customer-' . $this->user->id, 'public');
    }

    private function resetProfileUploads(): void
    {
        $this->reset([
            'gst_image',
            'aadhar_image',
            'aadhar_front',
            'aadhar_back',
            'driving_licence_front',
            'driving_licence_back',
        ]);
    }

    public function render()
    {
        $ordersQuery = Order::query()->where('user_id', $this->user->id);

        $recentOrders = (clone $ordersQuery)
            ->latest()
            ->limit(5)
            ->get();

        $totalOrders = (clone $ordersQuery)->count();

        $wallet = Wallet::query()->firstOrCreate(
            [
                'user_id' => $this->user->id,
                'wallet_type' => 'customer',
            ],
            [
                'balance' => 0,
                'hold_balance' => 0,
                'currency' => 'INR',
                'is_active' => true,
            ]
        );

        $walletTransactions = WalletTransaction::query()
            ->where('user_id', $this->user->id)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('livewire.my-account', [
            'user' => $this->user,
            'recentOrders' => $recentOrders,
            'totalOrders' => $totalOrders,
            'wallet' => $wallet,
            'walletTransactions' => $walletTransactions,
        ]);
    }
}