<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerSearchActivity;
use App\Services\CustomerJourneyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CustomerJourneyController extends Controller
{
    public function __construct(
        private readonly CustomerJourneyService $customerJourneyService
    ) {
    }

    public function searchPerformed(Request $request): JsonResponse
    {
        return $this->handleJourneyAction(
            request: $request,
            serviceMethod: 'searchPerformed',
            rules: [
                'customer_id' => ['nullable', 'integer'],
                'user_id' => ['nullable', 'integer'],
                'mobile' => ['nullable', 'string', 'max:30'],
                'session_id' => ['nullable', 'string', 'max:255'],
                'service_type' => ['required', 'string', 'max:100'],
                'search_type' => ['nullable', 'string', 'max:100'],
                'pickup_location' => ['nullable', 'string'],
                'pickup_latitude' => ['nullable', 'numeric'],
                'pickup_longitude' => ['nullable', 'numeric'],
                'drop_location' => ['nullable', 'string'],
                'drop_latitude' => ['nullable', 'numeric'],
                'drop_longitude' => ['nullable', 'numeric'],
                'start_datetime' => ['nullable', 'date'],
                'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
                'vehicle_category_id' => ['nullable', 'integer'],
                'estimated_amount' => ['nullable', 'numeric', 'min:0'],
                'metadata' => ['nullable', 'array'],
            ],
            successMessage: 'Search activity recorded successfully.'
        );
    }

    public function checkoutStarted(Request $request): JsonResponse
    {
        return $this->handleJourneyAction(
            request: $request,
            serviceMethod: 'checkoutStarted',
            rules: $this->activityRules([
                'vehicle_id' => ['nullable', 'integer'],
                'vehicle_category_id' => ['nullable', 'integer'],
                'quoted_amount' => ['nullable', 'numeric', 'min:0'],
                'metadata' => ['nullable', 'array'],
            ]),
            successMessage: 'Checkout activity recorded successfully.'
        );
    }

    public function paymentStarted(Request $request): JsonResponse
    {
        return $this->handleJourneyAction(
            request: $request,
            serviceMethod: 'paymentStarted',
            rules: $this->activityRules([
                'booking_id' => ['nullable'],
                'payment_method' => ['nullable', 'string', 'max:100'],
                'amount' => ['nullable', 'numeric', 'min:0'],
                'transaction_id' => ['nullable', 'string', 'max:255'],
                'metadata' => ['nullable', 'array'],
            ]),
            successMessage: 'Payment start recorded successfully.'
        );
    }

    public function paymentSucceeded(Request $request): JsonResponse
    {
        return $this->handleJourneyAction(
            request: $request,
            serviceMethod: 'paymentSucceeded',
            rules: $this->activityRules([
                'booking_id' => ['nullable'],
                'payment_id' => ['nullable', 'string', 'max:255'],
                'transaction_id' => ['nullable', 'string', 'max:255'],
                'amount' => ['nullable', 'numeric', 'min:0'],
                'metadata' => ['nullable', 'array'],
            ]),
            successMessage: 'Successful payment recorded.'
        );
    }

    public function paymentFailed(Request $request): JsonResponse
    {
        return $this->handleJourneyAction(
            request: $request,
            serviceMethod: 'paymentFailed',
            rules: $this->activityRules([
                'booking_id' => ['nullable'],
                'payment_id' => ['nullable', 'string', 'max:255'],
                'transaction_id' => ['nullable', 'string', 'max:255'],
                'failure_reason' => ['nullable', 'string'],
                'metadata' => ['nullable', 'array'],
            ]),
            successMessage: 'Failed payment recorded.'
        );
    }

    public function bookingCompleted(Request $request): JsonResponse
    {
        return $this->handleJourneyAction(
            request: $request,
            serviceMethod: 'bookingCompleted',
            rules: $this->activityRules([
                'booking_id' => ['required'],
                'booking_type' => ['nullable', 'string', 'max:100'],
                'final_amount' => ['nullable', 'numeric', 'min:0'],
                'metadata' => ['nullable', 'array'],
            ]),
            successMessage: 'Booking completion recorded successfully.'
        );
    }

    public function bookingCancelled(Request $request): JsonResponse
    {
        return $this->handleJourneyAction(
            request: $request,
            serviceMethod: 'bookingCancelled',
            rules: $this->activityRules([
                'booking_id' => ['required'],
                'cancellation_reason' => ['nullable', 'string'],
                'cancelled_by' => ['nullable', 'string', 'max:100'],
                'metadata' => ['nullable', 'array'],
            ]),
            successMessage: 'Booking cancellation recorded successfully.'
        );
    }

    public function show(string $identifier): JsonResponse
    {
        try {
            $activity = $this->resolveActivity($identifier);

            if ($activity === null) {
                return $this->notFoundResponse();
            }

            return $this->successResponse(
                data: $activity,
                message: 'Customer journey activity fetched successfully.'
            );
        } catch (Throwable $exception) {
            Log::error('Customer journey activity fetch failed.', [
                'identifier' => $identifier,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->errorResponse(
                message: 'Unable to fetch customer journey activity.',
                exception: $exception
            );
        }
    }

    private function handleJourneyAction(
        Request $request,
        string $serviceMethod,
        array $rules,
        string $successMessage
    ): JsonResponse {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if (!method_exists($this->customerJourneyService, $serviceMethod)) {
                throw new \BadMethodCallException(
                    "CustomerJourneyService::{$serviceMethod}() is not available."
                );
            }

            $data = $validator->validated();

            /*
             * The journey service is expected to accept the validated payload
             * and return the updated CustomerSearchActivity or response data.
             */
            $result = $this->customerJourneyService->{$serviceMethod}($data);

            return $this->successResponse(
                data: $result,
                message: $successMessage
            );
        } catch (Throwable $exception) {
            Log::error('Customer journey action failed.', [
                'action' => $serviceMethod,
                'payload' => $request->except([
                    'password',
                    'token',
                    'payment_token',
                ]),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->errorResponse(
                message: 'Unable to process customer journey activity.',
                exception: $exception
            );
        }
    }

    private function activityRules(array $additionalRules = []): array
    {
        return array_merge([
            'activity_id' => ['nullable', 'integer'],
            'activity_uuid' => ['nullable', 'string', 'max:255'],
        ], $additionalRules);
    }

    private function resolveActivity(string $identifier): ?CustomerSearchActivity
    {
        $query = CustomerSearchActivity::query();

        if (ctype_digit($identifier)) {
            return $query->whereKey((int) $identifier)->first();
        }

        return $query
            ->where('uuid', $identifier)
            ->orWhere('activity_uuid', $identifier)
            ->first();
    }

    private function successResponse(
        mixed $data,
        string $message,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Customer journey activity not found.',
        ], 404);
    }

    private function errorResponse(
        string $message,
        Throwable $exception,
        int $statusCode = 500
    ): JsonResponse {
        $response = [
            'status' => false,
            'message' => $message,
        ];

        if (config('app.debug')) {
            $response['debug'] = $exception->getMessage();
        }

        return response()->json($response, $statusCode);
    }
}