<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Send OTP
    |--------------------------------------------------------------------------
    | Old mobile API support.
    | Ye ab same new OtpService use karega.
    | OTP SMS + WhatsApp + Email par fallback ke saath jayega.
    */

    public function sendOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
        ]);

        $result = $otpService->send($request->mobile);

        return response()->json(
            $result,
            ($result['status'] ?? false) ? 200 : 422
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */

    public function verifyOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'otp' => 'required|digits:4',
        ]);

        $result = $otpService->verify(
            $request->mobile,
            $request->otp
        );

        return response()->json(
            $result,
            ($result['status'] ?? false) ? 200 : 401
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Old Home API
    |--------------------------------------------------------------------------
    */

    public function home()
    {
        return response()->json([
            'status' => true,
            'message' => 'Home data loaded successfully',
            'data' => [
                'banners' => $this->tableRows('banners', 5),
                'categories' => $this->activeRows('categories', 10),
                'self_drive_cars' => $this->safeTableRows('vehicles', 10),
                'popular_routes' => $this->activeRows('products', 10),
                'offers' => $this->tableRows('coupons', 5),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Old Cars API
    |--------------------------------------------------------------------------
    */

    public function cars()
    {
        return response()->json([
            'status' => true,
            'message' => 'Cars loaded successfully',
            'data' => [
                'cars' => $this->safeTableRows('vehicles', 20),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Old Routes API
    |--------------------------------------------------------------------------
    */

    public function routes()
    {
        return response()->json([
            'status' => true,
            'message' => 'Routes loaded successfully',
            'data' => [
                'routes' => $this->activeRows('products', 20),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Old Booking API
    |--------------------------------------------------------------------------
    */

    public function booking(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Booking API ready',
            'data' => [
                'request' => $request->all(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function activeRows(string $table, int $limit = 10)
    {
        try {
            if (!Schema::hasTable($table)) {
                return [];
            }

            $query = DB::table($table);

            if (Schema::hasColumn($table, 'is_active')) {
                $query->where('is_active', 1);
            }

            return $query
                ->latest('id')
                ->limit($limit)
                ->get();

        } catch (\Throwable $e) {
            return [];
        }
    }

    private function tableRows(string $table, int $limit = 10)
    {
        try {
            if (!Schema::hasTable($table)) {
                return [];
            }

            return DB::table($table)
                ->latest('id')
                ->limit($limit)
                ->get();

        } catch (\Throwable $e) {
            return [];
        }
    }

    private function safeTableRows(string $table, int $limit = 10)
    {
        try {
            if (!Schema::hasTable($table)) {
                return [];
            }

            $query = DB::table($table);

            if (Schema::hasColumn($table, 'is_active')) {
                $query->where('is_active', 1);
            }

            return $query
                ->latest('id')
                ->limit($limit)
                ->get();

        } catch (\Throwable $e) {
            return [];
        }
    }
}