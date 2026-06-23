<?php

namespace App\Livewire;

use App\Models\address;
use App\Models\Order;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class MyAccount extends Component
{
    use WithFileUploads, LivewireAlert;
   
    public $user;
    public $activeTab = 'overview';
    
    // Profile editing properties
    public $name;
    public $email;
    public $mobile;
    public $company_name;
    public $gst_number;
    public $aadhar_number;
    public $driving_licence_number;
    public $office_address;
    
    // Address properties
    public $full_name;
    public $address_email;
    public $phone;
    public $phone2;
    public $pickup_address;
    public $drop_address;
    public $state;
    public $city;
    
    // Password change properties
    public $current_password;
    public $new_password;
    public $new_password_confirmation;
    
    // File uploads
    public $gst_image;
    public $aadhar_image;
    
    // Edit modes
    public $editingProfile = false;
    public $editingAddress = false;

    public function mount() {
        $this->user = auth()->user();
        $this->loadUserData();
    }
    
    public function loadUserData() {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->mobile = $this->user->mobile;
        $this->company_name = $this->user->company_name;
        $this->gst_number = $this->user->gst_number;
        $this->aadhar_number = $this->user->aadhar_number;
        $this->driving_licence_number = $this->user->driving_licence_number;
        $this->office_address = $this->user->office_address;
        
        // Load address data if exists
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
    
    public function changeTab($tab) {
        $this->activeTab = $tab;
        $this->editingProfile = false;
        $this->editingAddress = false;
    }
    
    public function toggleEditProfile() {
        $this->editingProfile = !$this->editingProfile;
        if (!$this->editingProfile) {
            $this->loadUserData(); // Reset data if cancelled
        }
    }
    
    public function toggleEditAddress() {
        $this->editingAddress = !$this->editingAddress;
        if (!$this->editingAddress) {
            $this->loadUserData(); // Reset data if cancelled
        }
    }
    
    public function updateProfile() {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'mobile' => 'required|string|max:15',
            'company_name' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:20',
            'aadhar_number' => 'nullable|string|max:20',
            'driving_licence_number' => 'nullable|string|max:30',
            'office_address' => 'nullable|string|max:500',
            'gst_image' => 'nullable|image|max:2048',
            'aadhar_image' => 'nullable|image|max:2048',
        ]);
        
        $updateData = [
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'company_name' => $this->company_name,
            'gst_number' => $this->gst_number,
            'aadhar_number' => $this->aadhar_number,
            'driving_licence_number' => $this->driving_licence_number,
            'office_address' => $this->office_address,
        ];
        
        // Handle file uploads
        if ($this->gst_image) {
            if ($this->user->gst_image) {
                Storage::disk('public')->delete($this->user->gst_image);
            }
            $updateData['gst_image'] = $this->gst_image->store('documents', 'public');
        }
        
        if ($this->aadhar_image) {
            if ($this->user->aadhar_image) {
                Storage::disk('public')->delete($this->user->aadhar_image);
            }
            $updateData['aadhar_image'] = $this->aadhar_image->store('documents', 'public');
        }
        
        $this->user->update($updateData);
        $this->user->refresh();
        $this->editingProfile = false;
        
        $this->alert('success', 'Profile updated successfully!', [
            'position' => 'center',
            'timer' => 3000,
            'toast' => true,
        ]);
    }
    
    public function updateAddress() {
        $this->validate([
            'full_name' => 'required|string|max:255',
            'address_email' => 'required|email',
            'phone' => 'required|string|max:15',
            'phone2' => 'nullable|string|max:15',
            'pickup_address' => 'required|string|max:500',
            'drop_address' => 'nullable|string|max:500',
            'state' => 'required|string|max:100',
            'city' => 'required|string|max:100',
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
            address::create($addressData);
        }
        
        $this->user->refresh();
        $this->editingAddress = false;
        
        $this->alert('success', 'Address updated successfully!', [
            'position' => 'center',
            'timer' => 3000,
            'toast' => true,
        ]);
    }
    
    public function changePassword() {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);
        
        if (!Hash::check($this->current_password, $this->user->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }
        
        $this->user->update([
            'password' => Hash::make($this->new_password),
        ]);
        
        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        
        $this->alert('success', 'Password changed successfully!', [
            'position' => 'center',
            'timer' => 3000,
            'toast' => true,
        ]);
    }
    
    public function render()
    {
        $recentOrders = Order::where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $totalOrders = Order::where('user_id', $this->user->id)->count();
        
        return view('livewire.my-account', [
            'user' => $this->user,
            'recentOrders' => $recentOrders,
            'totalOrders' => $totalOrders,
        ]);
    }
}
