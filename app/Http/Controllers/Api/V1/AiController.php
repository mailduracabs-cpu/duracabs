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
        $validator = Validator::make($request->all(), [
            'pickup_images' => 'nullable|array',
            'drop_images' => 'nullable|array',
            'pickup_km' => 'nullable|numeric',
            'drop_km' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $totalKm = null;

        if ($request->pickup_km !== null && $request->drop_km !== null) {
            $totalKm = max(0, (float) $request->drop_km - (float) $request->pickup_km);
        }

        return $this->success([
            'damage_detected' => false,
            'confidence' => 0,
            'new_damage' => [],
            'total_km' => $totalKm,
            'status' => 'manual_review_required',
            'message' => 'AI damage detection placeholder ready. Vision AI can be connected here.',
        ], 'Self-drive damage detection result');
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