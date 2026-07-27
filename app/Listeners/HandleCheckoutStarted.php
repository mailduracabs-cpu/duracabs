<?php

namespace App\Listeners;

use App\Events\CheckoutStarted;
use App\Models\CustomerSearchActivity;
use App\Services\CustomerSearchActivityService;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleCheckoutStarted
{
    public function __construct(
        private readonly CustomerSearchActivityService $searchActivityService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(CheckoutStarted $event): void
    {
        try {
            $searchActivity = $event->searchActivity->fresh();

            if (!$searchActivity instanceof CustomerSearchActivity) {
                Log::warning('CheckoutStarted listener received an invalid search activity.');

                return;
            }

            if ($searchActivity->is_converted) {
                Log::info('CheckoutStarted ignored because search is already converted.', [
                    'search_activity_id' => $searchActivity->id,
                    'search_activity_uuid' => $searchActivity->uuid,
                ]);

                return;
            }

            $checkoutData = $this->normalizeCheckoutData(
                $event->checkoutData
            );

            $this->searchActivityService->markCheckoutStarted(
                search: $searchActivity,
                data: $checkoutData
            );

            Log::info('Customer checkout activity tracked successfully.', [
                'search_activity_id' => $searchActivity->id,
                'search_activity_uuid' => $searchActivity->uuid,
                'user_id' => $searchActivity->user_id,
                'mobile' => $searchActivity->mobile,
                'module' => $searchActivity->module,
                'service_type' => $searchActivity->service_type,
                'vehicle_id' => $checkoutData['vehicle_id']
                    ?? $searchActivity->vehicle_id,
                'grand_total' => $checkoutData['grand_total']
                    ?? $searchActivity->grand_total,
            ]);
        } catch (Throwable $exception) {
            Log::error('HandleCheckoutStarted listener failed.', [
                'search_activity_id' => $event->searchActivity->id ?? null,
                'search_activity_uuid' => $event->searchActivity->uuid ?? null,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw $exception;
        }
    }

    /**
     * Normalize and whitelist checkout data before saving.
     */
    private function normalizeCheckoutData(array $data): array
    {
        $normalized = [];

        $integerFields = [
            'vehicle_id',
            'vehicle_category_id',
            'coupon_id',
            'helmet_quantity',
            'rental_hours',
            'rental_days',
            'rental_weeks',
            'rental_months',
            'trip_days',
            'package_hours',
            'minimum_hours',
            'included_km',
        ];

        foreach ($integerFields as $field) {
            if (
                array_key_exists($field, $data)
                && $data[$field] !== null
                && $data[$field] !== ''
                && is_numeric($data[$field])
            ) {
                $normalized[$field] = (int) $data[$field];
            }
        }

        $floatFields = [
            'estimated_amount',
            'grand_total',
            'base_fare',
            'discount_amount',
            'coupon_discount',
            'driver_allowance',
            'toll_amount',
            'parking_amount',
            'state_tax_amount',
            'waiting_charge',
            'tax_amount',
            'security_deposit',
            'helmet_charge',
            'price_per_hour',
            'price_per_day',
            'price_per_week',
            'price_per_month',
            'weekly_discount_percent',
            'monthly_discount_percent',
            'estimated_distance_km',
            'minimum_km',
            'billable_km',
        ];

        foreach ($floatFields as $field) {
            if (
                array_key_exists($field, $data)
                && $data[$field] !== null
                && $data[$field] !== ''
                && is_numeric($data[$field])
            ) {
                $normalized[$field] = (float) $data[$field];
            }
        }

        $stringFields = [
            'vehicle_name',
            'vehicle_category_name',
            'vehicle_type',
            'fuel_type',
            'transmission_type',
            'plan_type',
            'plan_name',
            'coupon_code',
            'currency',
            'helmet_option',
            'package_name',
        ];

        foreach ($stringFields as $field) {
            if (
                array_key_exists($field, $data)
                && $data[$field] !== null
            ) {
                $normalized[$field] = trim((string) $data[$field]);
            }
        }

        if (array_key_exists('is_all_inclusive', $data)) {
            $normalized['is_all_inclusive'] = filter_var(
                $data['is_all_inclusive'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if (
            array_key_exists('fare_breakdown', $data)
            && is_array($data['fare_breakdown'])
        ) {
            $normalized['fare_breakdown'] =
                $data['fare_breakdown'];
        }

        if (
            array_key_exists('metadata', $data)
            && is_array($data['metadata'])
        ) {
            $normalized['metadata'] = $data['metadata'];
        }

        return $normalized;
    }
}