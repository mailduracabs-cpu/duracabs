<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer',
            'mobile' => 'nullable|string|max:20',
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:120',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|max:1000',
            'booking_id' => 'nullable|integer',
        ];
    }
}
