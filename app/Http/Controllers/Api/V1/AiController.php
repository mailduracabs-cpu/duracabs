<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiController extends BaseApiController
{
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        return $this->success([
            'reply' => 'Hello, I am Dura AI Assistant. I can help you book taxi, self-drive car, tour package and airport transfer.',
            'user_message' => $request->message,
        ], 'AI chat response');
    }

    public function tripPlanner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|string',
            'to' => 'required|string',
            'days' => 'nullable|integer|min:1',
            'interest' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        return $this->success([
            'from' => $request->from,
            'to' => $request->to,
            'days' => $request->days ?? 1,
            'interest' => $request->interest ?? 'sightseeing',
            'plan' => [
                'Day 1: Pickup, sightseeing and hotel drop.',
                'Day 2: Local attractions and return journey if applicable.',
            ],
        ], 'AI trip plan generated');
    }

    public function tourItinerary(Request $request, \App\Services\DuraAiTourPlanner $planner)
    {
        $data = $planner->input($request->all());
        if (! config('dura_ai_tour.enabled') || ! $planner->isConfigured()) {
            return $this->error('AI itinerary is not enabled yet. Live vehicle fares are still available.', 503);
        }
        try {
            return $this->success($planner->generate($data), 'Personalised tour itinerary');
        } catch (\Throwable $exception) {
            Log::warning('Dura AI itinerary unavailable', ['exception_type' => get_class($exception)]);
            return $this->error('AI could not complete this itinerary. Try again or continue with the live vehicle fares.', 503);
        }
    }

    public function imageGenerate(Request $request)
    {
        return $this->success([
            'image_url' => null,
            'status' => 'ready',
            'message' => 'AI image generation API ready. OpenAI/Stability API can be connected here.',
        ], 'AI image generation placeholder');
    }

    public function recommendation(Request $request)
    {
        return $this->success([
            'recommended_services' => [
                [
                    'type' => 'taxi',
                    'title' => 'One Way Taxi',
                    'subtitle' => 'Best for outstation drop',
                ],
                [
                    'type' => 'self_drive',
                    'title' => 'Self Drive Car',
                    'subtitle' => 'Best for flexible travel',
                ],
                [
                    'type' => 'tour',
                    'title' => 'Agra Local Tour',
                    'subtitle' => 'Best for Taj Mahal sightseeing',
                ],
            ],
        ], 'AI recommendations fetched');
    }

    public function search(Request $request)
    {
        $keyword = $request->keyword ?? $request->q ?? '';

        return $this->success([
            'keyword' => $keyword,
            'results' => [
                [
                    'type' => 'route',
                    'title' => 'Agra to Delhi Cab',
                    'url' => '/route/agra-uttar-pradesh-to-new-delhi-delhi',
                ],
                [
                    'type' => 'service',
                    'title' => 'Self Drive Car Rental in Agra',
                    'url' => '/pages/self-drive-service-in-agra',
                ],
            ],
        ], 'AI search results');
    }

    public function selfDriveDamageDetection(Request $request)
    {
        abort_unless($request->user(), 401);
        $data = $request->validate(['booking_id' => 'required|integer|min:1']);
        $booking = \Illuminate\Support\Facades\DB::table('self_drive_bookings')->find($data['booking_id']);
        abort_unless($booking && ((int) $booking->customer_id === (int) $request->user()->id || $request->user()->canUseAdminLogin()), 404);
        $report = \Illuminate\Support\Facades\DB::table('self_drive_damage_reports')->where('booking_id', $booking->id)->orderByDesc('id')->first();
        return $this->success([
            'booking_id' => $booking->id,
            'status' => $report?->status ?? (config('self_drive_damage.enabled') ? 'awaiting_return_comparison' : 'manual_review'),
            'damage_detected' => $report?->status === 'possible_damage' ? true : ($report?->status === 'no_visible_change' ? false : null),
            'manual_review_required' => $report?->status !== 'no_visible_change',
        ], 'Inspection status; final decision belongs to admin');
    }

    public function bannerSuggestion(Request $request)
    {
        return $this->success([
            'banners' => [
                [
                    'title' => 'Agra to Delhi One Way Cab',
                    'subtitle' => 'Comfortable sedan and SUV rides',
                    'image_prompt' => 'Premium cab on highway from Agra to Delhi, blue modern theme',
                ],
                [
                    'title' => 'Self Drive Car Rental in Agra',
                    'subtitle' => 'Drive your way with Dura Cabs',
                    'image_prompt' => 'Modern self drive car rental banner with Agra city theme',
                ],
            ],
        ], 'AI banner suggestions generated');
    }
}