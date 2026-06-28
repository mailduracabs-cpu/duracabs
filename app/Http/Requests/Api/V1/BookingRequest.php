<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'digits:10'],
            'email' => ['nullable', 'email', 'max:255'],
            'product_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
            'ride_type' => ['nullable', 'string', 'max:50'],
            'taxi_type' => ['nullable', 'string', 'max:100'],
            'plan' => ['nullable', 'string', 'max:100'],
            'pickup_city' => ['nullable', 'string', 'max:255'],
            'drop_city' => ['nullable', 'string', 'max:255'],
            'pickup_address' => ['required', 'string', 'max:2000'],
            'drop_address' => ['nullable', 'string', 'max:2000'],
            'pickup_date' => ['required', 'date'],
            'return_date' => ['nullable', 'date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'coupon_name' => ['nullable', 'string', 'max:255'],
            'coupon_value' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total_km' => ['nullable', 'string', 'max:255'],
            'number_travellers' => ['nullable', 'string', 'max:255'],
            'number_luggage' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors(),
        ], 422));
    }
}
