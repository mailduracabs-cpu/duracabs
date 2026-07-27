<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\MapsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MapsController extends BaseApiController
{
    public function placeAutocomplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $result = MapsService::placeAutocomplete($request->keyword);

        if (!($result['status'] ?? false)) {
            return $this->error($result['message'] ?? 'Unable to fetch places', 422, $result);
        }

        return $this->success($result['data'] ?? [], 'Place autocomplete fetched successfully');
    }

    public function placeDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $result = MapsService::placeDetails($request->place_id);

        if (!($result['status'] ?? false)) {
            return $this->error($result['message'] ?? 'Unable to fetch place details', 422, $result);
        }

        return $this->success($result['data'] ?? [], 'Place details fetched successfully');
    }

    public function distance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin' => 'required|string',
            'destination' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $result = MapsService::distance($request->origin, $request->destination);

        if (!($result['status'] ?? false)) {
            return $this->error($result['message'] ?? 'Unable to fetch distance', 422, $result);
        }

        return $this->success($result['data'] ?? [], 'Distance fetched successfully');
    }

    public function route(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin' => 'required|string',
            'destination' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $result = MapsService::directions($request->origin, $request->destination);

        if (!($result['status'] ?? false)) {
            return $this->error($result['message'] ?? 'Unable to fetch route', 422, $result);
        }

        return $this->success($result['data'] ?? [], 'Route fetched successfully');
    }

    public function geocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $result = MapsService::geocode($request->address);

        if (!($result['status'] ?? false)) {
            return $this->error($result['message'] ?? 'Unable to fetch geocode', 422, $result);
        }

        return $this->success($result['data'] ?? [], 'Geocode fetched successfully');
    }

    public function reverseGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422, $validator->errors());
        }

        $result = MapsService::reverseGeocode(
            (string) $request->lat,
            (string) $request->lng
        );

        if (!($result['status'] ?? false)) {
            return $this->error($result['message'] ?? 'Unable to fetch reverse geocode', 422, $result);
        }

        return $this->success($result['data'] ?? [], 'Reverse geocode fetched successfully');
    }
}