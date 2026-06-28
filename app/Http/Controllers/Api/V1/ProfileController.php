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
    public function __construct(private readonly ProfileService $profileService) {}

    public function profile(Request $request)
    {
        $profile = $this->profileService->getProfile($request->user());

        return response()->json([
            'status' => true,
            'message' => 'Profile loaded successfully',
            'data' => new ProfileResource($profile),
        ]);
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = $this->profileService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => new ProfileResource($user),
        ]);
    }

    public function addresses(Request $request)
    {
        $addresses = $this->profileService->addresses($request->user());

        return response()->json([
            'status' => true,
            'message' => 'Addresses loaded successfully',
            'data' => AddressResource::collection($addresses),
        ]);
    }

    public function saveAddress(AddressRequest $request)
    {
        $address = $this->profileService->saveAddress($request->user(), $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Address saved successfully',
            'data' => new AddressResource($address),
        ]);
    }

    public function deleteAddress(Request $request)
    {
        $request->validate(['address_id' => 'required|integer']);

        $this->profileService->deleteAddress($request->user(), (int) $request->address_id);

        return response()->json([
            'status' => true,
            'message' => 'Address deleted successfully',
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user() && method_exists($request->user(), 'currentAccessToken')) {
            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
