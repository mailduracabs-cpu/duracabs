<?php

namespace App\Listeners;

use App\Events\SearchPerformed;
use App\Models\CustomerActivity;
use App\Models\CustomerSearchActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleSearchPerformed
{
    /**
     * Handle the event.
     */
    public function handle(SearchPerformed $event): void
    {
        $search = $event->searchActivity->fresh();

        if (!$search instanceof CustomerSearchActivity) {
            return;
        }

        try {
            DB::transaction(function () use ($search): void {
                $intentScore = $this->calculateIntentScore($search);

                $priority = CustomerSearchActivity::resolvePriority(
                    intentScore: $intentScore,
                    stage: $search->stage,
                    isConverted: (bool) $search->is_converted
                );

                $updates = [
                    'intent_score' => $intentScore,
                    'priority' => $priority,
                    'last_activity_at' => now(),
                ];

                if (
                    $this->shouldScheduleFollowUp($search, $priority)
                    && $search->follow_up_at === null
                ) {
                    $updates['follow_up_at'] = $this->resolveFollowUpTime(
                        $search,
                        $priority
                    );
                }

                if (
                    blank($search->lead_status)
                    && !$search->is_converted
                ) {
                    $updates['lead_status'] =
                        CustomerSearchActivity::LEAD_NEW;
                }

                $search->update($updates);

                $this->syncCustomerActivity($search->fresh());
            });
        } catch (Throwable $exception) {
            Log::error('SearchPerformed listener failed.', [
                'search_activity_id' => $search->id,
                'search_activity_uuid' => $search->uuid,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Calculate customer intent score from search information.
     */
    private function calculateIntentScore(
        CustomerSearchActivity $search
    ): int {
        $score = max(0, (int) $search->intent_score);

        /*
        |--------------------------------------------------------------------------
        | Customer Identification
        |--------------------------------------------------------------------------
        */

        if ($search->user_id !== null) {
            $score += 10;
        } elseif (filled($search->mobile)) {
            $score += 6;
        } elseif (filled($search->session_id)) {
            $score += 2;
        }

        /*
        |--------------------------------------------------------------------------
        | Route Information
        |--------------------------------------------------------------------------
        */

        if (filled($search->pickup_location)) {
            $score += 3;
        }

        if (filled($search->pickup_city)) {
            $score += 2;
        }

        if (filled($search->drop_location)) {
            $score += 3;
        }

        if (filled($search->drop_city)) {
            $score += 2;
        }

        if ((int) $search->total_stops > 0) {
            $score += min(8, (int) $search->total_stops * 2);
        }

        /*
        |--------------------------------------------------------------------------
        | Date and Time Selection
        |--------------------------------------------------------------------------
        */

        if ($search->start_datetime !== null) {
            $score += 5;

            if (
                $search->start_datetime->isFuture()
                && $search->start_datetime->diffInHours(now()) <= 24
            ) {
                $score += 8;
            }
        }

        if (
            $search->return_datetime !== null
            || $search->end_datetime !== null
        ) {
            $score += 4;
        }

        /*
        |--------------------------------------------------------------------------
        | Vehicle and Plan Selection
        |--------------------------------------------------------------------------
        */

        if ($search->vehicle_category_id !== null) {
            $score += 4;
        }

        if ($search->vehicle_id !== null) {
            $score += 7;
        }

        if (filled($search->vehicle_name)) {
            $score += 3;
        }

        if (
            filled($search->plan_type)
            || filled($search->plan_name)
        ) {
            $score += 4;
        }

        /*
        |--------------------------------------------------------------------------
        | Fare and Availability
        |--------------------------------------------------------------------------
        */

        if ($search->estimated_amount !== null) {
            $score += 5;
        }

        if ($search->grand_total !== null) {
            $score += 5;
        }

        if ($search->has_available_vehicle === true) {
            $score += 4;
        }

        if (
            $search->result_count !== null
            && $search->result_count > 0
        ) {
            $score += 3;
        }

        /*
        |--------------------------------------------------------------------------
        | Search Lifecycle Stage
        |--------------------------------------------------------------------------
        */

        $score += match ($search->stage) {
            CustomerSearchActivity::STAGE_INITIATED => 0,
            CustomerSearchActivity::STAGE_SEARCHED => 5,
            CustomerSearchActivity::STAGE_RESULTS_VIEWED => 10,
            CustomerSearchActivity::STAGE_VEHICLE_VIEWED => 15,
            CustomerSearchActivity::STAGE_VEHICLE_SELECTED => 22,
            CustomerSearchActivity::STAGE_CHECKOUT_STARTED => 30,
            CustomerSearchActivity::STAGE_PAYMENT_STARTED => 38,
            CustomerSearchActivity::STAGE_CONVERTED => 0,
            CustomerSearchActivity::STAGE_ABANDONED => 8,
            default => 0,
        };

        if ($search->is_converted) {
            return 0;
        }

        return max(0, min(100, $score));
    }

    /**
     * Determine whether CRM follow-up should be scheduled.
     */
    private function shouldScheduleFollowUp(
        CustomerSearchActivity $search,
        string $priority
    ): bool {
        if ($search->is_converted) {
            return false;
        }

        if (
            in_array($search->lead_status, [
                CustomerSearchActivity::LEAD_CONVERTED,
                CustomerSearchActivity::LEAD_LOST,
                CustomerSearchActivity::LEAD_NOT_INTERESTED,
            ], true)
        ) {
            return false;
        }

        return in_array($priority, [
            CustomerSearchActivity::PRIORITY_HIGH,
            CustomerSearchActivity::PRIORITY_URGENT,
        ], true);
    }

    /**
     * Resolve CRM follow-up time based on customer priority and stage.
     */
    private function resolveFollowUpTime(
        CustomerSearchActivity $search,
        string $priority
    ) {
        if (
            $priority === CustomerSearchActivity::PRIORITY_URGENT
            || in_array($search->stage, [
                CustomerSearchActivity::STAGE_CHECKOUT_STARTED,
                CustomerSearchActivity::STAGE_PAYMENT_STARTED,
            ], true)
        ) {
            return now()->addMinutes(5);
        }

        if ($search->is_abandoned) {
            return now()->addMinutes(15);
        }

        return now()->addMinutes(30);
    }

    /**
     * Synchronize the linked general customer activity record.
     */
    private function syncCustomerActivity(
        CustomerSearchActivity $search
    ): void {
        if ($search->customer_activity_id === null) {
            return;
        }

        $customerActivity = CustomerActivity::query()
            ->find($search->customer_activity_id);

        if (!$customerActivity instanceof CustomerActivity) {
            return;
        }

        $customerActivity->update([
            'user_id' => $search->user_id,
            'mobile' => $search->mobile,
            'customer_name' => $search->customer_name,
            'session_id' => $search->session_id,
            'device_id' => $search->device_id,

            'module' => $search->module,
            'service_type' => $search->service_type,
            'stage' => $this->resolveGeneralActivityStage($search),

            'pickup_location' => $search->pickup_location,
            'pickup_city' => $search->pickup_city,
            'pickup_latitude' => $search->pickup_latitude,
            'pickup_longitude' => $search->pickup_longitude,

            'drop_location' => $search->drop_location,
            'drop_city' => $search->drop_city,
            'drop_latitude' => $search->drop_latitude,
            'drop_longitude' => $search->drop_longitude,

            'vehicle_id' => $search->vehicle_id,
            'vehicle_name' => $search->vehicle_name,
            'plan' => $search->plan_name
                ?? $search->plan_type,

            'estimated_amount' => $search->grand_total
                ?? $search->estimated_amount,

            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
            'lead_status' => $search->lead_status,

            'data' => [
                'search_activity_id' => $search->id,
                'search_activity_uuid' => $search->uuid,
                'route' => [
                    'pickup_location' => $search->pickup_location,
                    'pickup_city' => $search->pickup_city,
                    'drop_location' => $search->drop_location,
                    'drop_city' => $search->drop_city,
                    'via_locations' => $search->via_locations,
                    'total_stops' => $search->total_stops,
                ],
                'schedule' => [
                    'start_datetime' => $search->start_datetime
                        ?->toIso8601String(),
                    'end_datetime' => $search->end_datetime
                        ?->toIso8601String(),
                    'return_datetime' => $search->return_datetime
                        ?->toIso8601String(),
                ],
                'vehicle' => [
                    'vehicle_category_id' =>
                        $search->vehicle_category_id,
                    'vehicle_category_name' =>
                        $search->vehicle_category_name,
                    'vehicle_id' => $search->vehicle_id,
                    'vehicle_name' => $search->vehicle_name,
                    'vehicle_type' => $search->vehicle_type,
                ],
                'fare' => [
                    'currency' => $search->currency,
                    'estimated_amount' =>
                        $search->estimated_amount,
                    'grand_total' => $search->grand_total,
                    'fare_breakdown' =>
                        $search->fare_breakdown,
                ],
                'conversion' => [
                    'search_status' => $search->search_status,
                    'checkout_status' => $search->checkout_status,
                    'payment_status' => $search->payment_status,
                    'is_converted' => $search->is_converted,
                    'is_abandoned' => $search->is_abandoned,
                    'booking_type' => $search->booking_type,
                    'booking_id' => $search->booking_id,
                    'booking_number' => $search->booking_number,
                ],
            ],

            'occurred_at' => $search->last_activity_at
                ?? $search->searched_at
                ?? now(),
        ]);
    }

    /**
     * Convert search-specific stage into general customer activity stage.
     */
    private function resolveGeneralActivityStage(
        CustomerSearchActivity $search
    ): string {
        return match ($search->stage) {
            CustomerSearchActivity::STAGE_INITIATED => 'initiated',
            CustomerSearchActivity::STAGE_SEARCHED => 'searched',
            CustomerSearchActivity::STAGE_RESULTS_VIEWED =>
                'results_viewed',
            CustomerSearchActivity::STAGE_VEHICLE_VIEWED => 'viewed',
            CustomerSearchActivity::STAGE_VEHICLE_SELECTED => 'selected',
            CustomerSearchActivity::STAGE_CHECKOUT_STARTED => 'checkout',
            CustomerSearchActivity::STAGE_PAYMENT_STARTED => 'payment',
            CustomerSearchActivity::STAGE_CONVERTED => 'booked',
            CustomerSearchActivity::STAGE_ABANDONED => 'abandoned',
            default => (string) $search->stage,
        };
    }
}