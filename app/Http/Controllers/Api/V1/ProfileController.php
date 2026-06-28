<?php

namespace App\Http\Controllers\Api\V1;

class ProfileController extends BaseApiController
{
    public function profile()
    {
        return $this->success([], 'Profile API coming in Package 5');
    }

    public function update()
    {
        return $this->success([], 'Profile update API coming in Package 5');
    }

    public function addresses()
    {
        return $this->success([], 'Addresses API coming in Package 5');
    }

    public function saveAddress()
    {
        return $this->success([], 'Address save API coming in Package 5');
    }

    public function deleteAddress()
    {
        return $this->success([], 'Address delete API coming in Package 5');
    }
}
