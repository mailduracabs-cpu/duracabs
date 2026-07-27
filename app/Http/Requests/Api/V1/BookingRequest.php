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

    protected function prepareForValidation(): void
    {
        $mobile = preg_replace(
            '/\D+/',
            '',
            (string) (
                $this->input('mobile')
                ?? $this->input('customer_mobile')
                ?? ''
            )
        );

        $rideType = strtolower(
            trim((string) (
                $this->input('ride_type')
                ?? $this->input('trip_type')
                ?? $this->input('service_type')
                ?? 'one_way'
            ))
        );

        $rideType = match ($rideType) {
            'roundtrip', 'round-trip' => 'round_trip',
            'selfdrive', 'self-drive' => 'self_drive',
            default => $rideType,
        };

        $this->merge([
            'name' => $this->input('name')
                ?? $this->input('customer_name'),
            'email' => $this->input('email')
                ?? $this->input('customer_email'),
            'mobile' => $mobile,
            'ride_type' => $rideType,
            'trip_type' => $this->input('trip_type')
                ?? $rideType,
            'service_type' => $this->input('service_type')
                ?? $rideType,
            'pickup_address' => $this->input('pickup_address')
                ?? $this->input('pickup')
                ?? $this->input('pickup_city'),
            'drop_address' => $this->input('drop_address')
                ?? $this->input('drop')
                ?? $this->input('drop_city'),
            'notes' => $this->input('notes')
                ?? $this->input('comments'),
            'coupon_name' => $this->input('coupon_name')
                ?? $this->input('coupon_code'),
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],

            'name' => ['required', 'string', 'max:120'],
            'customer_name' => ['nullable', 'string', 'max:120'],

            'mobile' => ['required', 'digits:10'],
            'customer_mobile' => ['nullable', 'string', 'max:20'],

            'email' => ['nullable', 'email', 'max:190'],
            'customer_email' => ['nullable', 'email', 'max:190'],

            'product_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],

            'ride_type' => [
                'required',
                'string',
                'in:one_way,round_trip,local,airport,self_drive',
            ],
            'trip_type' => [
                'nullable',
                'string',
                'in:one_way,round_trip,local,airport,self_drive',
            ],
            'service_type' => [
                'nullable',
                'string',
                'in:one_way,round_trip,local,airport,self_drive',
            ],

            'taxi_type' => ['nullable', 'string', 'max:120'],
            'vehicle_type' => ['nullable', 'string', 'max:120'],
            'plan' => ['nullable', 'string', 'max:120'],

            'pickup_city' => ['nullable', 'string', 'max:255'],
            'drop_city' => ['nullable', 'string', 'max:255'],

            'pickup_address' => ['required', 'string', 'max:2000'],
            'drop_address' => ['required', 'string', 'max:2000'],

            'pickup_date' => ['required', 'date'],
            'return_date' => [
                'nullable',
                'date',
                'after_or_equal:pickup_date',
            ],

            'pickup_time' => ['required', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],

            'base_fare' => ['nullable', 'numeric', 'min:0'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],

            'toll_status' => ['nullable', 'string', 'max:50'],
            'toll_included' => ['nullable'],
            'toll_amount' => ['nullable', 'numeric', 'min:0'],
            'toll' => ['nullable', 'numeric', 'min:0'],
            'toll_tax' => ['nullable', 'numeric', 'min:0'],

            'tax_status' => ['nullable', 'string', 'max:50'],
            'tax_included' => ['nullable'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'state_tax' => ['nullable', 'numeric', 'min:0'],
            'other_tax' => ['nullable', 'numeric', 'min:0'],

            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gst_amount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],

            'special_request_ids' => ['nullable', 'array'],
            'special_request_ids.*' => ['nullable', 'integer'],

            'special_requests' => ['nullable', 'array'],
            'special_requests.*.id' => ['nullable', 'integer'],
            'special_requests.*.name' => ['nullable', 'string', 'max:150'],
            'special_requests.*.price' => ['nullable', 'numeric', 'min:0'],
            'special_requests.*.included' => ['nullable'],

            'special_request_total' => ['nullable', 'numeric', 'min:0'],

            'coupon_name' => ['nullable', 'string', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'coupon_value' => ['nullable', 'numeric', 'min:0'],

            'total_km' => ['nullable'],
            'number_travellers' => ['nullable', 'string', 'max:50'],
            'number_luggage' => ['nullable', 'string', 'max:50'],

            'notes' => ['nullable', 'string', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],

            'payment_method' => [
                'nullable',
                'string',
                'in:cash,online,razorpay,razorpay_payment,card,upi',
            ],
            'payment_status' => [
                'nullable',
                'string',
                'in:pending,paid,failed,refunded',
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],

            'source' => ['nullable', 'string', 'max:100'],
            'extra_options' => ['nullable', 'array'],
            'route_meta' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'mobile.required' => 'Customer mobile number is required.',
            'mobile.digits' => 'Enter a valid 10 digit mobile number.',
            'ride_type.required' => 'Ride type is required.',
            'ride_type.in' => 'Selected ride type is invalid.',
            'pickup_address.required' => 'Pickup address is required.',
            'drop_address.required' => 'Drop address is required.',
            'pickup_date.required' => 'Pickup date is required.',
            'pickup_time.required' => 'Pickup time is required.',
            'return_date.after_or_equal' =>
                'Return date cannot be before pickup date.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}