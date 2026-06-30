<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MobileApiController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
        ]);

        $mobile = $request->mobile;

        $cooldownKey = 'otp_cooldown_' . $mobile;
        $attemptKey = 'otp_attempts_' . $mobile;

        if (Cache::has($cooldownKey)) {
            return response()->json([
                'status' => false,
                'message' => 'OTP already sent. Please wait 30 seconds before requesting again.',
            ], 429);
        }

        $attempts = (int) Cache::get($attemptKey, 0);

        if ($attempts >= 5) {
            return response()->json([
                'status' => false,
                'message' => 'Too many OTP requests. Please try again after 30 minutes.',
            ], 429);
        }

        $otp = (string) random_int(1000, 9999);

        $message = "This is your 4-digit OTP {$otp} for Mobile Number Verification on Duracabs.com.\nValid for 5 Minutes only.\nFrom DURA CABS";

        $response = Http::timeout(15)->get('http://manage.sambsms.com/app/smsapi/index.php', [
            'key'      => config('services.sambsms.api_key'),
            'entity'   => config('services.sambsms.entity_id'),
            'campaign' => 0,
            'routeid'  => config('services.sambsms.route_id'),
            'type'     => 'text',
            'contacts' => $mobile,
            'senderid' => config('services.sambsms.sender_id'),
            'msg'      => $message,
            'tempid'   => config('services.sambsms.template_id'),
        ]);

        if (!$response->successful()) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to send OTP. SMS gateway error.',
                'debug' => config('app.debug') ? $response->body() : null,
            ], 500);
        }

        Cache::put('otp_' . $mobile, $otp, now()->addMinutes(5));
        Cache::put($cooldownKey, true, now()->addSeconds(30));
        Cache::put($attemptKey, $attempts + 1, now()->addMinutes(30));

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'otp' => 'required|digits:4',
        ]);

        $mobile = $request->mobile;
        $otp = $request->otp;

        $savedOtp = Cache::get('otp_' . $mobile);

        if (!$savedOtp) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired. Please resend OTP.',
            ], 401);
        }

        if ($savedOtp !== $otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
            ], 401);
        }

        Cache::forget('otp_' . $mobile);
        Cache::forget('otp_attempts_' . $mobile);
        Cache::forget('otp_cooldown_' . $mobile);

        return response()->json([
            'status' => true,
            'message' => 'Login Successful',
            'token' => 'duracabs_live_token_' . $mobile,
            'user' => [
                'id' => 1,
                'name' => 'Dura Cabs User',
                'mobile' => $mobile,
            ],
        ]);
    }

    public function home()
    {
        return response()->json([
            'status' => true,
            'banners' => DB::table('banners')->select('id', 'title', 'image', 'url')->latest()->limit(5)->get(),
            'categories' => DB::table('categories')->where('is_active', 1)->limit(10)->get(),
            'self_drive_cars' => DB::table('vehicles')->where('is_active', 1)->limit(10)->get(),
            'popular_routes' => DB::table('products')->where('is_active', 1)->limit(10)->get(),
            'offers' => DB::table('coupons')->limit(5)->get(),
        ]);
    }

    public function cars()
    {
        return response()->json([
            'status' => true,
            'cars' => DB::table('vehicles')->where('is_active', 1)->latest()->limit(20)->get(),
        ]);
    }

    public function routes()
    {
        return response()->json([
            'status' => true,
            'routes' => DB::table('products')->where('is_active', 1)->latest()->limit(20)->get(),
        ]);
    }

    public function booking(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Booking API ready',
        ]);
    }
}
