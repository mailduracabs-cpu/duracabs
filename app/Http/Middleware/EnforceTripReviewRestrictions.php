<?php
namespace App\Http\Middleware;

use App\Services\TripReviewService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnforceTripReviewRestrictions
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) return $next($request);
        $action = $request->route()?->getActionName() ?? '';
        $protected = preg_match('/BookingController@(store|create|confirm)$/', $action)
            || preg_match('/SelfDriveController@(booking|bikeBooking|confirmBooking)$/', $action)
            || preg_match('/PartnerController@(acceptBooking|createVehicle)$/', $action)
            || preg_match('/PartnerDispatchController@assign$/', $action);
        if (! $protected) return $next($request);
        $user = $request->user() ?? Auth::guard('sanctum')->user();
        $ids = array_filter([(int) ($user?->id ?? 0), (int) $request->input('user_id', 0), (int) $request->input('customer_id', 0)]);
        $service = app(TripReviewService::class);
        return DB::transaction(function () use ($request, $next, $ids, $service) {
            $service->lockUsers($ids);
            foreach ($ids as $id) {
                if ($service->blocked($id)) {
                    if ($request->expectsJson()) return response()->json(['status' => false,
                        'message' => TripReviewService::BLOCK_MESSAGE, 'code' => 'ACCOUNT_REVIEW_BLOCKED'], 403);
                    $service->assertAllowed($id);
                }
            }
            return $next($request);
        }, 3);
    }
}
