<?php

namespace App\Services;

use App\Models\CustomerSearchActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeadScoringService
{
    /*
    |--------------------------------------------------------------------------
    | Maximum Score
    |--------------------------------------------------------------------------
    */

    private const MAX_SCORE = 100;

    /*
    |--------------------------------------------------------------------------
    | Journey Stage Scores
    |--------------------------------------------------------------------------
    */

    private const SCORE_SEARCHED = 15;
    private const SCORE_RESULTS_VIEWED = 25;
    private const SCORE_VEHICLE_VIEWED = 35;
    private const SCORE_VEHICLE_SELECTED = 50;
    private const SCORE_CHECKOUT_STARTED = 70;
    private const SCORE_PAYMENT_STARTED = 85;
    private const SCORE_CONVERTED = 100;
    private const SCORE_ABANDONED = 45;

    /*
    |--------------------------------------------------------------------------
    | Behaviour Bonuses
    |--------------------------------------------------------------------------
    */

    private const BONUS_REGISTERED_CUSTOMER = 5;
    private const BONUS_REPEAT_CUSTOMER = 10;
    private const BONUS_MULTIPLE_SEARCHES = 5;
    private const BONUS_AVAILABLE_VEHICLE = 5;
    private const BONUS_VEHICLE_SELECTED = 5;
    private const BONUS_COUPON_APPLIED = 3;
    private const BONUS_FUTURE_TRIP_SOON = 5;
    private const BONUS_PAYMENT_FAILED_RECOVERY = 8;

    /*
    |--------------------------------------------------------------------------
    | Fare Bonuses
    |--------------------------------------------------------------------------
    */

    private const BONUS_FARE_3000 = 3;
    private const BONUS_FARE_5000 = 5;
    private const BONUS_FARE_10000 = 8;
    private const BONUS_FARE_20000 = 12;

    /*
    |--------------------------------------------------------------------------
    | Negative Scores
    |--------------------------------------------------------------------------
    */

    private const PENALTY_NO_AVAILABLE_VEHICLE = 10;
    private const PENALTY_OLD_INACTIVE_LEAD = 10;
    private const PENALTY_NOT_INTERESTED = 30;
    private const PENALTY_LOST = 25;

    /**
     * Calculate and persist the score of one lead.
     */
    public function calculate(
        CustomerSearchActivity $search,
        bool $save = true
    ): CustomerSearchActivity {
        if ($search->is_converted) {
            return $this->markAsConverted($search, $save);
        }

        $result = $this->buildScore($search);

        if (!$save) {
            $search->setAttribute('intent_score', $result['score']);
            $search->setAttribute('priority', $result['priority']);

            return $search;
        }

        try {
            return DB::transaction(function () use (
                $search,
                $result
            ): CustomerSearchActivity {
                $metadata = $this->mergeMetadata(
                    $search->metadata,
                    [
                        'lead_scoring' => [
                            'score' => $result['score'],
                            'priority' => $result['priority'],
                            'is_hot_lead' => $result['is_hot_lead'],
                            'conversion_probability' =>
                                $result['conversion_probability'],
                            'breakdown' => $result['breakdown'],
                            'calculated_at' => now()->toIso8601String(),
                        ],
                    ]
                );

                $search->update([
                    'intent_score' => $result['score'],
                    'priority' => $result['priority'],
                    'metadata' => $metadata,
                    'last_activity_at' =>
                        $search->last_activity_at ?? now(),
                ]);

                return $search->fresh([
                    'user',
                    'assignedUser',
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Lead score could not be calculated.', [
                'customer_search_activity_id' => $search->id,
                'message' => $exception->getMessage(),
            ]);

            return $search;
        }
    }

    /**
     * Calculate score without updating the database.
     */
    public function preview(CustomerSearchActivity $search): array
    {
        return $this->buildScore($search);
    }

    /**
     * Recalculate multiple open leads.
     */
    public function recalculateOpenLeads(
        int $limit = 500
    ): int {
        $updated = 0;

        CustomerSearchActivity::query()
            ->notConverted()
            ->openLeads()
            ->orderBy('id')
            ->limit(max(1, min($limit, 2000)))
            ->get()
            ->each(function (CustomerSearchActivity $search) use (
                &$updated
            ): void {
                $this->calculate($search);
                $updated++;
            });

        return $updated;
    }

    /**
     * Recalculate abandoned leads.
     */
    public function recalculateAbandonedLeads(
        int $limit = 500
    ): int {
        $updated = 0;

        CustomerSearchActivity::query()
            ->abandoned()
            ->notConverted()
            ->orderBy('id')
            ->limit(max(1, min($limit, 2000)))
            ->get()
            ->each(function (CustomerSearchActivity $search) use (
                &$updated
            ): void {
                $this->calculate($search);
                $updated++;
            });

        return $updated;
    }

    /**
     * Return hot leads ordered by score.
     */
    public function hotLeads(
        int $minimumScore = 70,
        int $limit = 100
    ) {
        return CustomerSearchActivity::query()
            ->with([
                'user',
                'assignedUser',
            ])
            ->notConverted()
            ->openLeads()
            ->where(
                'intent_score',
                '>=',
                max(0, min($minimumScore, self::MAX_SCORE))
            )
            ->orderByDesc('intent_score')
            ->orderByDesc('last_activity_at')
            ->limit(max(1, min($limit, 500)))
            ->get();
    }

    /**
     * Check whether a lead is hot.
     */
    public function isHotLead(
        CustomerSearchActivity $search
    ): bool {
        if ($search->is_converted) {
            return false;
        }

        return (int) $search->intent_score >= 70
            || in_array(
                $search->priority,
                [
                    CustomerSearchActivity::PRIORITY_HIGH,
                    CustomerSearchActivity::PRIORITY_URGENT,
                ],
                true
            );
    }

    /**
     * Return estimated conversion probability.
     */
    public function conversionProbability(
        CustomerSearchActivity $search
    ): int {
        $result = $this->buildScore($search);

        return $result['conversion_probability'];
    }

    /**
     * Build complete scoring result.
     */
    private function buildScore(
        CustomerSearchActivity $search
    ): array {
        $score = 0;
        $breakdown = [];

        $stageScore = $this->stageScore($search);

        $score += $stageScore;

        $breakdown['journey_stage'] = [
            'label' => $this->stageLabel($search),
            'points' => $stageScore,
        ];

        $this->applyRegisteredCustomerBonus(
            $search,
            $score,
            $breakdown
        );

        $this->applyRepeatCustomerBonus(
            $search,
            $score,
            $breakdown
        );

        $this->applySearchFrequencyBonus(
            $search,
            $score,
            $breakdown
        );

        $this->applyAvailabilityScore(
            $search,
            $score,
            $breakdown
        );

        $this->applyVehicleSelectionBonus(
            $search,
            $score,
            $breakdown
        );

        $this->applyFareScore(
            $search,
            $score,
            $breakdown
        );

        $this->applyCouponBonus(
            $search,
            $score,
            $breakdown
        );

        $this->applyTripUrgencyBonus(
            $search,
            $score,
            $breakdown
        );

        $this->applyPaymentRecoveryBonus(
            $search,
            $score,
            $breakdown
        );

        $this->applyInactivityPenalty(
            $search,
            $score,
            $breakdown
        );

        $this->applyLeadStatusPenalty(
            $search,
            $score,
            $breakdown
        );

        $score = $this->normalizeScore($score);

        $priority = $this->resolvePriority(
            $search,
            $score
        );

        return [
            'score' => $score,
            'priority' => $priority,
            'is_hot_lead' =>
                !$search->is_converted && $score >= 70,
            'conversion_probability' =>
                $this->scoreToProbability($search, $score),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get score according to customer journey stage.
     */
    private function stageScore(
        CustomerSearchActivity $search
    ): int {
        if ($search->is_converted) {
            return self::SCORE_CONVERTED;
        }

        if ($search->is_abandoned) {
            return self::SCORE_ABANDONED;
        }

        return match ($search->stage) {
            CustomerSearchActivity::STAGE_CONVERTED =>
                self::SCORE_CONVERTED,

            CustomerSearchActivity::STAGE_PAYMENT_STARTED =>
                self::SCORE_PAYMENT_STARTED,

            CustomerSearchActivity::STAGE_CHECKOUT_STARTED =>
                self::SCORE_CHECKOUT_STARTED,

            CustomerSearchActivity::STAGE_VEHICLE_SELECTED =>
                self::SCORE_VEHICLE_SELECTED,

            CustomerSearchActivity::STAGE_VEHICLE_VIEWED =>
                self::SCORE_VEHICLE_VIEWED,

            CustomerSearchActivity::STAGE_RESULTS_VIEWED =>
                self::SCORE_RESULTS_VIEWED,

            CustomerSearchActivity::STAGE_ABANDONED =>
                self::SCORE_ABANDONED,

            default => self::SCORE_SEARCHED,
        };
    }

    /**
     * Human-readable stage name.
     */
    private function stageLabel(
        CustomerSearchActivity $search
    ): string {
        return match ($search->stage) {
            CustomerSearchActivity::STAGE_RESULTS_VIEWED =>
                'Results Viewed',

            CustomerSearchActivity::STAGE_VEHICLE_VIEWED =>
                'Vehicle Viewed',

            CustomerSearchActivity::STAGE_VEHICLE_SELECTED =>
                'Vehicle Selected',

            CustomerSearchActivity::STAGE_CHECKOUT_STARTED =>
                'Checkout Started',

            CustomerSearchActivity::STAGE_PAYMENT_STARTED =>
                'Payment Started',

            CustomerSearchActivity::STAGE_CONVERTED =>
                'Converted',

            CustomerSearchActivity::STAGE_ABANDONED =>
                'Abandoned',

            default => 'Search Performed',
        };
    }

    /**
     * Registered customer bonus.
     */
    private function applyRegisteredCustomerBonus(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        if ($search->user_id === null) {
            return;
        }

        $score += self::BONUS_REGISTERED_CUSTOMER;

        $breakdown['registered_customer'] = [
            'label' => 'Registered Customer',
            'points' => self::BONUS_REGISTERED_CUSTOMER,
        ];
    }

    /**
     * Previous successful booking bonus.
     */
    private function applyRepeatCustomerBonus(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        $hasPreviousConversion = $this->customerQuery($search)
            ->where('id', '!=', $search->id)
            ->converted()
            ->exists();

        if (!$hasPreviousConversion) {
            return;
        }

        $score += self::BONUS_REPEAT_CUSTOMER;

        $breakdown['repeat_customer'] = [
            'label' => 'Previous Converted Customer',
            'points' => self::BONUS_REPEAT_CUSTOMER,
        ];
    }

    /**
     * Multiple recent searches indicate purchase intent.
     */
    private function applySearchFrequencyBonus(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        $recentSearchCount = $this->customerQuery($search)
            ->where('id', '!=', $search->id)
            ->where(
                'searched_at',
                '>=',
                now()->subDays(7)
            )
            ->count();

        if ($recentSearchCount < 2) {
            return;
        }

        $score += self::BONUS_MULTIPLE_SEARCHES;

        $breakdown['recent_searches'] = [
            'label' => 'Multiple Searches in Last 7 Days',
            'points' => self::BONUS_MULTIPLE_SEARCHES,
            'count' => $recentSearchCount,
        ];
    }

    /**
     * Vehicle availability score.
     */
    private function applyAvailabilityScore(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        if ($search->has_available_vehicle === true) {
            $score += self::BONUS_AVAILABLE_VEHICLE;

            $breakdown['vehicle_availability'] = [
                'label' => 'Vehicle Available',
                'points' => self::BONUS_AVAILABLE_VEHICLE,
            ];

            return;
        }

        if ($search->has_available_vehicle === false) {
            $score -= self::PENALTY_NO_AVAILABLE_VEHICLE;

            $breakdown['vehicle_unavailable'] = [
                'label' => 'No Vehicle Available',
                'points' => -self::PENALTY_NO_AVAILABLE_VEHICLE,
            ];
        }
    }

    /**
     * Selected vehicle bonus.
     */
    private function applyVehicleSelectionBonus(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        if (
            blank($search->vehicle_id)
            && blank($search->vehicle_name)
        ) {
            return;
        }

        $score += self::BONUS_VEHICLE_SELECTED;

        $breakdown['vehicle_selected'] = [
            'label' => 'Vehicle Selected',
            'points' => self::BONUS_VEHICLE_SELECTED,
        ];
    }

    /**
     * High-value booking bonus.
     */
    private function applyFareScore(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        $amount = (float) (
            $search->grand_total
            ?? $search->estimated_amount
            ?? 0
        );

        $points = match (true) {
            $amount >= 20000 => self::BONUS_FARE_20000,
            $amount >= 10000 => self::BONUS_FARE_10000,
            $amount >= 5000 => self::BONUS_FARE_5000,
            $amount >= 3000 => self::BONUS_FARE_3000,
            default => 0,
        };

        if ($points === 0) {
            return;
        }

        $score += $points;

        $breakdown['booking_value'] = [
            'label' => 'High Value Enquiry',
            'points' => $points,
            'amount' => round($amount, 2),
        ];
    }

    /**
     * Coupon usage indicates checkout intent.
     */
    private function applyCouponBonus(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        if (
            blank($search->coupon_code)
            && blank($search->coupon_id)
        ) {
            return;
        }

        $score += self::BONUS_COUPON_APPLIED;

        $breakdown['coupon_applied'] = [
            'label' => 'Coupon Applied',
            'points' => self::BONUS_COUPON_APPLIED,
        ];
    }

    /**
     * Trip starting soon gets additional priority.
     */
    private function applyTripUrgencyBonus(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        if ($search->start_datetime === null) {
            return;
        }

        $startDateTime = $search->start_datetime;

        if (
            $startDateTime->isPast()
            || $startDateTime->greaterThan(now()->addHours(48))
        ) {
            return;
        }

        $score += self::BONUS_FUTURE_TRIP_SOON;

        $breakdown['trip_starting_soon'] = [
            'label' => 'Trip Starting Within 48 Hours',
            'points' => self::BONUS_FUTURE_TRIP_SOON,
        ];
    }

    /**
     * Payment failure is a high-intent recovery opportunity.
     */
    private function applyPaymentRecoveryBonus(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        if (
            $search->payment_status
            !== CustomerSearchActivity::PAYMENT_FAILED
        ) {
            return;
        }

        $score += self::BONUS_PAYMENT_FAILED_RECOVERY;

        $breakdown['payment_recovery'] = [
            'label' => 'Payment Failed Recovery Opportunity',
            'points' => self::BONUS_PAYMENT_FAILED_RECOVERY,
        ];
    }

    /**
     * Reduce score of stale leads.
     */
    private function applyInactivityPenalty(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        if ($search->last_activity_at === null) {
            return;
        }

        if (
            $search->last_activity_at->greaterThan(
                now()->subDays(7)
            )
        ) {
            return;
        }

        $score -= self::PENALTY_OLD_INACTIVE_LEAD;

        $breakdown['inactive_lead'] = [
            'label' => 'No Activity for 7 Days',
            'points' => -self::PENALTY_OLD_INACTIVE_LEAD,
        ];
    }

    /**
     * Reduce score according to final lead status.
     */
    private function applyLeadStatusPenalty(
        CustomerSearchActivity $search,
        int &$score,
        array &$breakdown
    ): void {
        if (
            $search->lead_status
            === CustomerSearchActivity::LEAD_NOT_INTERESTED
        ) {
            $score -= self::PENALTY_NOT_INTERESTED;

            $breakdown['not_interested'] = [
                'label' => 'Customer Not Interested',
                'points' => -self::PENALTY_NOT_INTERESTED,
            ];

            return;
        }

        if (
            $search->lead_status
            === CustomerSearchActivity::LEAD_LOST
        ) {
            $score -= self::PENALTY_LOST;

            $breakdown['lost_lead'] = [
                'label' => 'Lead Lost',
                'points' => -self::PENALTY_LOST,
            ];
        }
    }

    /**
     * Resolve CRM priority.
     */
    private function resolvePriority(
        CustomerSearchActivity $search,
        int $score
    ): string {
        if ($search->is_converted) {
            return CustomerSearchActivity::PRIORITY_LOW;
        }

        if (
            $search->payment_status
            === CustomerSearchActivity::PAYMENT_FAILED
        ) {
            return CustomerSearchActivity::PRIORITY_URGENT;
        }

        if (
            $search->start_datetime !== null
            && $search->start_datetime->isFuture()
            && $search->start_datetime->lessThanOrEqualTo(
                now()->addHours(24)
            )
            && $score >= 60
        ) {
            return CustomerSearchActivity::PRIORITY_URGENT;
        }

        return match (true) {
            $score >= 85 =>
                CustomerSearchActivity::PRIORITY_URGENT,

            $score >= 70 =>
                CustomerSearchActivity::PRIORITY_HIGH,

            $score >= 40 =>
                CustomerSearchActivity::PRIORITY_MEDIUM,

            default =>
                CustomerSearchActivity::PRIORITY_LOW,
        };
    }

    /**
     * Convert score into estimated probability.
     */
    private function scoreToProbability(
        CustomerSearchActivity $search,
        int $score
    ): int {
        if ($search->is_converted) {
            return 100;
        }

        $probability = match (true) {
            $score >= 90 => 90,
            $score >= 80 => 80,
            $score >= 70 => 70,
            $score >= 60 => 58,
            $score >= 50 => 45,
            $score >= 40 => 32,
            $score >= 30 => 22,
            $score >= 20 => 14,
            default => 7,
        };

        if (
            $search->payment_status
            === CustomerSearchActivity::PAYMENT_FAILED
        ) {
            $probability = max($probability, 65);
        }

        return min(100, $probability);
    }

    /**
     * Set converted lead score.
     */
    private function markAsConverted(
        CustomerSearchActivity $search,
        bool $save
    ): CustomerSearchActivity {
        if (!$save) {
            $search->setAttribute(
                'intent_score',
                self::SCORE_CONVERTED
            );

            $search->setAttribute(
                'priority',
                CustomerSearchActivity::PRIORITY_LOW
            );

            return $search;
        }

        $metadata = $this->mergeMetadata(
            $search->metadata,
            [
                'lead_scoring' => [
                    'score' => self::SCORE_CONVERTED,
                    'priority' =>
                        CustomerSearchActivity::PRIORITY_LOW,
                    'is_hot_lead' => false,
                    'conversion_probability' => 100,
                    'breakdown' => [
                        'conversion' => [
                            'label' => 'Booking Converted',
                            'points' => self::SCORE_CONVERTED,
                        ],
                    ],
                    'calculated_at' => now()->toIso8601String(),
                ],
            ]
        );

        $search->update([
            'intent_score' => self::SCORE_CONVERTED,
            'priority' => CustomerSearchActivity::PRIORITY_LOW,
            'metadata' => $metadata,
        ]);

        return $search->fresh([
            'user',
            'assignedUser',
        ]);
    }

    /**
     * Build a query matching the same customer.
     */
    private function customerQuery(
        CustomerSearchActivity $search
    ): Builder {
        return CustomerSearchActivity::query()
            ->where(function (Builder $query) use ($search): void {
                if ($search->user_id !== null) {
                    $query->where(
                        'user_id',
                        $search->user_id
                    );

                    return;
                }

                if (filled($search->mobile)) {
                    $query->where(
                        'mobile',
                        CustomerSearchActivity::normalizeMobile(
                            $search->mobile
                        )
                    );

                    return;
                }

                if (filled($search->session_id)) {
                    $query->where(
                        'session_id',
                        $search->session_id
                    );

                    return;
                }

                $query->whereRaw('1 = 0');
            });
    }

    /**
     * Keep score between zero and 100.
     */
    private function normalizeScore(int $score): int
    {
        return max(
            0,
            min(self::MAX_SCORE, $score)
        );
    }

    /**
     * Merge scoring information into existing metadata.
     */
    private function mergeMetadata(
        mixed $currentMetadata,
        array $newMetadata
    ): array {
        $currentMetadata = is_array($currentMetadata)
            ? $currentMetadata
            : [];

        return array_replace_recursive(
            $currentMetadata,
            $newMetadata
        );
    }
}