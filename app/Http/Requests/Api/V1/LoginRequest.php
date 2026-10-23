<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\CustomerPhoneNumber;
use InvalidArgumentException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    protected ?string $phoneError = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mobile = $this->input('mobile');
        $code = $this->input('country_code');
        if (! is_scalar($mobile) || ($code !== null && ! is_scalar($code))) {
            $this->merge(['mobile' => 'invalid']);
            return;
        }
        try {
            $this->merge(['mobile' => CustomerPhoneNumber::normalize(
                (string) $mobile, $code === null ? null : (string) $code
            )]);
        } catch (InvalidArgumentException $e) {
            $this->phoneError = $e->getMessage();
            $this->merge(['mobile' => 'invalid']);
        }
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^(?:[0-9]{10}|\+[1-9][0-9]{6,14})$/D'],
            'country_code' => ['nullable', 'regex:/^\+?[1-9][0-9]{0,2}$/D'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => $this->phoneError ?? $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
