<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:120'],
            'mobile' => ['nullable', 'digits:10'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'landmark' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', 'in:home,office,other'],
        ];
    }
}
