<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'mobile' => ['nullable', 'digits:10'],
        ];
    }
}
