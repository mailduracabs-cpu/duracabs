<?php

namespace App\Traits;

trait ApiResponse
{
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        $response = [
            'status' => true,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    protected function error(string $message = 'Something went wrong', int $code = 400, $errors = null)
    {
        $response = [
            'status' => false,
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
