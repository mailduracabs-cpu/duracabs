<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddressRequest;
use App\Http\Requests\Api\V1\ProfileUpdateRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    public function profile(Request $request)
    {
        $profile = $this->profileService->getProfile($request->user());

        return response()->json([
            'status' => true,
            'message' => 'Profile loaded successfully',
            'data' => new ProfileResource($profile),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Profile
    |--------------------------------------------------------------------------
    */

    public function update(ProfileUpdateRequest $request)
    {
        $user = $this->profileService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => new ProfileResource($user),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Address List
    |--------------------------------------------------------------------------
    */

    public function addresses(Request $request)
    {
        $addresses = $this->profileService->addresses(
            $request->user()
        );

        return response()->json([
            'status' => true,
            'message' => 'Addresses loaded successfully',
            'data' => AddressResource::collection($addresses),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Save Address
    |--------------------------------------------------------------------------
    */

    public function saveAddress(AddressRequest $request)
    {
        $address = $this->profileService->saveAddress(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'status' => true,
            'message' => 'Address saved successfully',
            'data' => new AddressResource($address),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Address
    |--------------------------------------------------------------------------
    */

    public function deleteAddress(Request $request)
    {
        $request->validate([
            'address_id' => 'required|integer',
        ]);

        $this->profileService->deleteAddress(
            $request->user(),
            (int)$request->address_id
        );

        return response()->json([
            'status' => true,
            'message' => 'Address deleted successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Profile Image
    |--------------------------------------------------------------------------
    */

    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (method_exists($this->profileService, 'uploadProfileImage')) {

            $image = $this->profileService->uploadProfileImage(
                $request->user(),
                $request->file('image')
            );

            return response()->json([
                'status' => true,
                'message' => 'Profile image uploaded successfully',
                'data' => $image,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profile Image API Ready',
            'data' => [
                'image_url' => null,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Account
    |--------------------------------------------------------------------------
    */

    public function deleteAccount(Request $request)
    {
        if (method_exists($this->profileService, 'deleteAccount')) {

            $this->profileService->deleteAccount(
                $request->user()
            );

            return response()->json([
                'status' => true,
                'message' => 'Account deleted successfully',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Delete Account API Ready',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        if (
            $request->user() &&
            method_exists($request->user(), 'currentAccessToken')
        ) {
            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}