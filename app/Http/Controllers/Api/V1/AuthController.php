<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\VerifyOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function sendOtp(LoginRequest $request, OtpService $otpService)
    {
        $result = $otpService->send($request->mobile);

        if (!$result['status']) {
            return $this->error($result['message'], 422, $result);
        }

        return $this->success(null, $result['message']);
    }

    public function verifyOtp(VerifyOtpRequest $request, OtpService $otpService)
    {
        $result = $otpService->verify($request->mobile, $request->otp);

        if (!$result['status']) {
            return $this->error($result['message'], 401);
        }

        return $this->success([
            'token' => $result['token'],
            'user' => $result['user'],
        ], $result['message']);
    }

    public function logout(Request $request)
    {
        return $this->success(null, 'Logged out successfully');
    }
}
