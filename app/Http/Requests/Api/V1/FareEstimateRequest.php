<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class FareEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => 'nullable|integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'from' => 'nullable|string|max:120',
            'to' => 'nullable|string|max:120',
            'trip_type' => 'nullable|string|in:one_way,round_trip,local,airport',
            'pickup_date' => 'nullable|date',
            'pickup_time' => 'nullable|string|max:20',
            'distance_km' => 'nullable|numeric|min:0',
            'duration_hr' => 'nullable|numeric|min:0',
            'night_charge' => 'nullable|numeric|min:0',
            'gst_percent' => 'nullable|numeric|min:0|max:28',
        ];
    }

    public function messages(): array
    {
        return [
            'route_id.exists' => 'Selected route does not exist.',
            'category_id.exists' => 'Selected vehicle category does not exist.',
        ];
    }
}
