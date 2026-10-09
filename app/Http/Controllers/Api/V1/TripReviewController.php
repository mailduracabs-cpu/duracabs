<?php
namespace App\Http\Controllers\Api\V1;

use App\Services\TripReviewService;
use Illuminate\Http\Request;

class TripReviewController extends BaseApiController
{
    public function profile(Request $request, TripReviewService $service)
    {
        return response()->json(['status' => true, 'data' => $service->profile($request->user())])->header('Cache-Control', 'no-store, private');
    }
    public function show(Request $request, string $type, string $trip, TripReviewService $service)
    {
        return response()->json(['status' => true, 'data' => $service->context($request->user(), $type, $trip)])->header('Cache-Control', 'no-store, private');
    }
    public function store(Request $request, string $type, string $trip, TripReviewService $service)
    {
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:2000']]);
        return response()->json(['status' => true, 'message' => 'Review saved.',
            'data' => $service->submit($request->user(), $type, $trip, (int) $data['rating'], $data['comment'] ?? null)], 201);
    }
}
