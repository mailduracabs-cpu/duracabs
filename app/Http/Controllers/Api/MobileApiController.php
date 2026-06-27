<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileApiController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:10']);

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully',
            'otp' => '123456'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'otp' => 'required|digits:6',
        ]);

        if ($request->otp !== '123456') {
            return response()->json(['status' => false, 'message' => 'Invalid OTP'], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login Successful',
            'token' => 'duracabs_demo_token',
            'user' => [
                'id' => 1,
                'name' => 'Demo User',
                'mobile' => $request->mobile,
            ]
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
            'cars' => DB::table('vehicles')->where('is_active', 1)->latest()->limit(20)->get()
        ]);
    }

    public function routes()
    {
        return response()->json([
            'status' => true,
            'routes' => DB::table('products')->where('is_active', 1)->latest()->limit(20)->get()
        ]);
    }

    public function booking(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Booking API ready'
        ]);
    }
}
