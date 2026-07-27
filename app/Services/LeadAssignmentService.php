<?php

namespace App\Services;

use App\Models\CustomerActivity;
use App\Models\CustomerSearchActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LeadAssignmentService
{
    /*
    |--------------------------------------------------------------------------
    | Assignment Strategies
    |--------------------------------------------------------------------------
    */

    public const STRATEGY_LEAST_LOADED = 'least_loaded';
    public const STRATEGY_ROUND_ROBIN = 'round_robin';
    public const STRATEGY_MANUAL = 'manual';

    /*
    |--------------------------------------------------------------------------
    | Default Configuration
    |--------------------------------------------------------------------------
    */

    private const DEFAULT_OPEN_LEAD_DAYS = 30;
    private const DEFAULT_MAX_ACTIVE_LEADS = 100;
    private const HIGH_VALUE_AMOUNT = 15000;
    private const VIP_CONVERSION_COUNT = 5;

    /**
     * Automatically assign a lead to an eligible executive.
     */
    public function assign(
        CustomerSearchActivity $search,
        bool $force = false,
        ?string $strategy = null
    ): CustomerSearchActivity {
        $search->refresh();

        if (!$force && $this->alreadyAssigned($search)) {
            return $search;
        }

        if ($search->is_converted) {
            return $search;
        }

        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            Log::warning(
                'Lead assignment skipped because no assignment column exists.',
                [
                    'customer_search_activity_id' => $search->id,
                ]
            );

            return $search;
        }

        $strategy ??= (string) config(
            'crm.lead_assignment.strategy',
            self::STRATEGY_LEAST_LOADED
        );

        try {
            return DB::transaction(function () use (
                $search,
                $assignmentColumn,
                $strategy
            ): CustomerSearchActivity {
                $lockedSearch = CustomerSearchActivity::query()
                    ->lockForUpdate()
                    ->findOrFail($search->id);

                if ($this->alreadyAssigned($lockedSearch)) {
                    return $lockedSearch->fresh([
                        'user',
                        'assignedUser',
                    ]);
                }

                $executives = $this->eligibleExecutives($lockedSearch);

                if ($executives->isEmpty()) {
                    $this->recordAssignmentFailure(
                        $lockedSearch,
                        'No eligible executive was found.'
                    );

                    return $lockedSearch->fresh([
                        'user',
                        'assignedUser',
                    ]);
                }

                $executive = $this->selectExecutive(
                    search: $lockedSearch,
                    executives: $executives,
                    strategy: $strategy
                );

                if ($executive === null) {
                    $this->recordAssignmentFailure(
                        $lockedSearch,
                        'Executive selection returned no result.'
                    );

                    return $lockedSearch->fresh([
                        'user',
                        'assignedUser',
                    ]);
                }

                $previousExecutiveId = $this->assignedExecutiveId(
                    $lockedSearch
                );

                $metadata = $this->mergeMetadata(
                    $lockedSearch->metadata,
                    [
                        'lead_assignment' => [
                            'executive_id' => $executive->id,
                            'previous_executive_id' =>
                                $previousExecutiveId,
                            'strategy' => $strategy,
                            'reason' => $this->assignmentReason(
                                $lockedSearch,
                                $executive
                            ),
                            'assigned_automatically' => true,
                            'assigned_at' => now()->toIso8601String(),
                        ],
                    ]
                );

                $attributes = [
                    $assignmentColumn => $executive->id,
                    'metadata' => $metadata,
                ];

                if ($this->searchTableHasColumn('assigned_at')) {
                    $attributes['assigned_at'] = now();
                }

                if ($this->searchTableHasColumn('lead_status')) {
                    $attributes['lead_status'] =
                        $this->resolvedAssignedLeadStatus(
                            $lockedSearch
                        );
                }

                if ($this->searchTableHasColumn('last_activity_at')) {
                    $attributes['last_activity_at'] = now();
                }

                $lockedSearch->forceFill($attributes);
                $lockedSearch->save();

                $this->createAssignmentActivity(
                    search: $lockedSearch,
                    executive: $executive,
                    strategy: $strategy,
                    isManual: false
                );

                return $lockedSearch->fresh([
                    'user',
                    'assignedUser',
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Automatic lead assignment failed.', [
                'customer_search_activity_id' => $search->id,
                'message' => $exception->getMessage(),
            ]);

            return $search->fresh([
                'user',
                'assignedUser',
            ]);
        }
    }

    /**
     * Manually assign or reassign a lead.
     */
    public function assignManually(
        CustomerSearchActivity $search,
        User|int $executive,
        ?User $assignedBy = null,
        ?string $note = null
    ): CustomerSearchActivity {
        $executive = $executive instanceof User
            ? $executive
            : User::query()->findOrFail($executive);

        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            throw new \RuntimeException(
                'Customer search activity assignment column not found.'
            );
        }

        return DB::transaction(function () use (
            $search,
            $executive,
            $assignedBy,
            $note,
            $assignmentColumn
        ): CustomerSearchActivity {
            $lockedSearch = CustomerSearchActivity::query()
                ->lockForUpdate()
                ->findOrFail($search->id);

            $previousExecutiveId = $this->assignedExecutiveId(
                $lockedSearch
            );

            $metadata = $this->mergeMetadata(
                $lockedSearch->metadata,
                [
                    'lead_assignment' => [
                        'executive_id' => $executive->id,
                        'previous_executive_id' =>
                            $previousExecutiveId,
                        'assigned_by_user_id' => $assignedBy?->id,
                        'strategy' => self::STRATEGY_MANUAL,
                        'reason' => $note ?? 'Manual assignment',
                        'assigned_automatically' => false,
                        'assigned_at' => now()->toIso8601String(),
                    ],
                ]
            );

            $attributes = [
                $assignmentColumn => $executive->id,
                'metadata' => $metadata,
            ];

            if ($this->searchTableHasColumn('assigned_at')) {
                $attributes['assigned_at'] = now();
            }

            if ($this->searchTableHasColumn('lead_status')) {
                $attributes['lead_status'] =
                    $this->resolvedAssignedLeadStatus(
                        $lockedSearch
                    );
            }

            if ($this->searchTableHasColumn('last_activity_at')) {
                $attributes['last_activity_at'] = now();
            }

            $lockedSearch->forceFill($attributes);
            $lockedSearch->save();

            $this->createAssignmentActivity(
                search: $lockedSearch,
                executive: $executive,
                strategy: self::STRATEGY_MANUAL,
                isManual: true,
                assignedBy: $assignedBy,
                note: $note
            );

            return $lockedSearch->fresh([
                'user',
                'assignedUser',
            ]);
        });
    }

    /**
     * Remove the assigned executive from a lead.
     */
    public function unassign(
        CustomerSearchActivity $search,
        ?User $unassignedBy = null,
        ?string $reason = null
    ): CustomerSearchActivity {
        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            return $search;
        }

        return DB::transaction(function () use (
            $search,
            $unassignedBy,
            $reason,
            $assignmentColumn
        ): CustomerSearchActivity {
            $lockedSearch = CustomerSearchActivity::query()
                ->lockForUpdate()
                ->findOrFail($search->id);

            $previousExecutiveId = $this->assignedExecutiveId(
                $lockedSearch
            );

            $metadata = $this->mergeMetadata(
                $lockedSearch->metadata,
                [
                    'lead_assignment' => [
                        'executive_id' => null,
                        'previous_executive_id' =>
                            $previousExecutiveId,
                        'unassigned_by_user_id' =>
                            $unassignedBy?->id,
                        'unassigned_reason' =>
                            $reason ?? 'Lead unassigned',
                        'unassigned_at' =>
                            now()->toIso8601String(),
                    ],
                ]
            );

            $attributes = [
                $assignmentColumn => null,
                'metadata' => $metadata,
            ];

            if ($this->searchTableHasColumn('assigned_at')) {
                $attributes['assigned_at'] = null;
            }

            if ($this->searchTableHasColumn('last_activity_at')) {
                $attributes['last_activity_at'] = now();
            }

            $lockedSearch->forceFill($attributes);
            $lockedSearch->save();

            $this->createUnassignmentActivity(
                search: $lockedSearch,
                previousExecutiveId: $previousExecutiveId,
                unassignedBy: $unassignedBy,
                reason: $reason
            );

            return $lockedSearch->fresh([
                'user',
                'assignedUser',
            ]);
        });
    }

    /**
     * Assign multiple unassigned leads.
     */
    public function assignPendingLeads(
        int $limit = 500,
        ?string $strategy = null
    ): int {
        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            return 0;
        }

        $assignedCount = 0;

        CustomerSearchActivity::query()
            ->whereNull($assignmentColumn)
            ->where('is_converted', false)
            ->whereNotIn('lead_status', $this->closedLeadStatuses())
            ->orderByDesc('intent_score')
            ->orderBy('id')
            ->limit(max(1, min($limit, 2000)))
            ->get()
            ->each(function (
                CustomerSearchActivity $search
            ) use (
                &$assignedCount,
                $strategy,
                $assignmentColumn
            ): void {
                $updatedSearch = $this->assign(
                    search: $search,
                    force: false,
                    strategy: $strategy
                );

                if ($updatedSearch->getAttribute(
                    $assignmentColumn
                ) !== null) {
                    $assignedCount++;
                }
            });

        return $assignedCount;
    }

    /**
     * Reassign leads from one executive to another.
     */
    public function transferLeads(
        User|int $fromExecutive,
        User|int $toExecutive,
        int $limit = 500,
        ?User $transferredBy = null,
        ?string $reason = null
    ): int {
        $fromExecutiveId = $fromExecutive instanceof User
            ? $fromExecutive->id
            : $fromExecutive;

        $toExecutive = $toExecutive instanceof User
            ? $toExecutive
            : User::query()->findOrFail($toExecutive);

        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            return 0;
        }

        $transferred = 0;

        CustomerSearchActivity::query()
            ->where($assignmentColumn, $fromExecutiveId)
            ->where('is_converted', false)
            ->whereNotIn('lead_status', $this->closedLeadStatuses())
            ->orderBy('id')
            ->limit(max(1, min($limit, 2000)))
            ->get()
            ->each(function (
                CustomerSearchActivity $search
            ) use (
                &$transferred,
                $toExecutive,
                $transferredBy,
                $reason
            ): void {
                $this->assignManually(
                    search: $search,
                    executive: $toExecutive,
                    assignedBy: $transferredBy,
                    note: $reason ?? 'Bulk lead transfer'
                );

                $transferred++;
            });

        return $transferred;
    }

    /**
     * Return eligible executives for the lead.
     */
    public function eligibleExecutives(
        CustomerSearchActivity $search
    ): Collection {
        $query = User::query();

        $configuredIds = collect(
            config('crm.lead_assignment.executive_user_ids', [])
        )
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($configuredIds->isNotEmpty()) {
            $query->whereIn('id', $configuredIds->all());
        } else {
            $this->applyExecutiveRoleFilter($query);
        }

        $this->applyActiveUserFilter($query);
        $this->applyServiceFilter($query, $search);
        $this->applyCityFilter($query, $search);
        $this->applyPriorityFilter($query, $search);

        $executives = $query
            ->orderBy('id')
            ->get();

        return $executives->filter(
            fn (User $executive): bool =>
                $this->executiveHasCapacity($executive)
        )->values();
    }

    /**
     * Choose an executive according to the configured strategy.
     */
    private function selectExecutive(
        CustomerSearchActivity $search,
        Collection $executives,
        string $strategy
    ): ?User {
        $preferredExecutive = $this->preferredExecutive(
            $search,
            $executives
        );

        if ($preferredExecutive !== null) {
            return $preferredExecutive;
        }

        return match ($strategy) {
            self::STRATEGY_ROUND_ROBIN =>
                $this->selectByRoundRobin($executives),

            default =>
                $this->selectLeastLoaded($executives),
        };
    }

    /**
     * Resolve special routing rules.
     */
    private function preferredExecutive(
        CustomerSearchActivity $search,
        Collection $executives
    ): ?User {
        $priorityRules = config(
            'crm.lead_assignment.priority_executive_ids',
            []
        );

        if (
            in_array(
                $search->priority,
                [
                    CustomerSearchActivity::PRIORITY_URGENT,
                    CustomerSearchActivity::PRIORITY_HIGH,
                ],
                true
            )
        ) {
            $priorityExecutive = $this->firstMatchingExecutive(
                $executives,
                $priorityRules[$search->priority] ?? []
            );

            if ($priorityExecutive !== null) {
                return $priorityExecutive;
            }
        }

        if ($this->isHighValueLead($search)) {
            $highValueExecutive = $this->firstMatchingExecutive(
                $executives,
                config(
                    'crm.lead_assignment.high_value_executive_ids',
                    []
                )
            );

            if ($highValueExecutive !== null) {
                return $highValueExecutive;
            }
        }

        if ($this->isVipCustomer($search)) {
            $vipExecutive = $this->firstMatchingExecutive(
                $executives,
                config(
                    'crm.lead_assignment.vip_executive_ids',
                    []
                )
            );

            if ($vipExecutive !== null) {
                return $vipExecutive;
            }
        }

        $serviceRules = config(
            'crm.lead_assignment.service_executive_ids',
            []
        );

        $serviceExecutive = $this->firstMatchingExecutive(
            $executives,
            $serviceRules[$search->service_type]
                ?? $serviceRules[$search->module]
                ?? []
        );

        if ($serviceExecutive !== null) {
            return $serviceExecutive;
        }

        $cityRules = config(
            'crm.lead_assignment.city_executive_ids',
            []
        );

        $city = strtolower(trim((string) $search->pickup_city));

        if ($city !== '') {
            $cityExecutive = $this->firstMatchingExecutive(
                $executives,
                $cityRules[$city] ?? []
            );

            if ($cityExecutive !== null) {
                return $cityExecutive;
            }
        }

        return null;
    }

    /**
     * Select executive with the lowest number of active leads.
     */
    private function selectLeastLoaded(
        Collection $executives
    ): ?User {
        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            return null;
        }

        return $executives
            ->map(function (User $executive) use (
                $assignmentColumn
            ): array {
                return [
                    'executive' => $executive,
                    'active_leads' =>
                        CustomerSearchActivity::query()
                            ->where(
                                $assignmentColumn,
                                $executive->id
                            )
                            ->where('is_converted', false)
                            ->whereNotIn(
                                'lead_status',
                                $this->closedLeadStatuses()
                            )
                            ->where(
                                'last_activity_at',
                                '>=',
                                now()->subDays(
                                    self::DEFAULT_OPEN_LEAD_DAYS
                                )
                            )
                            ->count(),
                ];
            })
            ->sortBy([
                ['active_leads', 'asc'],
                ['executive.id', 'asc'],
            ])
            ->pluck('executive')
            ->first();
    }

    /**
     * Select next executive using the previous assignment.
     */
    private function selectByRoundRobin(
        Collection $executives
    ): ?User {
        if ($executives->isEmpty()) {
            return null;
        }

        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            return $executives->first();
        }

        $lastExecutiveId = CustomerSearchActivity::query()
            ->whereNotNull($assignmentColumn)
            ->latest('assigned_at')
            ->latest('id')
            ->value($assignmentColumn);

        if ($lastExecutiveId === null) {
            return $executives->first();
        }

        $executiveIds = $executives
            ->pluck('id')
            ->values();

        $currentIndex = $executiveIds->search(
            (int) $lastExecutiveId
        );

        if ($currentIndex === false) {
            return $executives->first();
        }

        $nextIndex = ($currentIndex + 1)
            % $executiveIds->count();

        return $executives->firstWhere(
            'id',
            $executiveIds[$nextIndex]
        );
    }

    /**
     * Apply role checks when explicit executive IDs are not configured.
     */
    private function applyExecutiveRoleFilter(
        Builder $query
    ): void {
        $table = (new User())->getTable();

        if (Schema::hasColumn($table, 'user_type')) {
            $query->whereIn('user_type', [
                'admin',
                'executive',
                'sales',
                'sales_executive',
                'crm',
                'crm_executive',
                'manager',
            ]);

            return;
        }

        if (Schema::hasColumn($table, 'role')) {
            $query->whereIn('role', [
                'admin',
                'executive',
                'sales',
                'sales_executive',
                'crm',
                'crm_executive',
                'manager',
            ]);

            return;
        }

        if (Schema::hasColumn($table, 'is_admin')) {
            $query->where('is_admin', true);

            return;
        }

        /*
         * Without an executive identifier, do not assign a customer
         * account accidentally. Configure executive_user_ids in crm.php.
         */
        $query->whereRaw('1 = 0');
    }

    /**
     * Filter inactive or blocked users where supported.
     */
    private function applyActiveUserFilter(
        Builder $query
    ): void {
        $table = (new User())->getTable();

        if (Schema::hasColumn($table, 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn($table, 'status')) {
            $query->whereIn('status', [
                'active',
                'approved',
                'verified',
                1,
            ]);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
    }

    /**
     * Optional service-wise filtering.
     */
    private function applyServiceFilter(
        Builder $query,
        CustomerSearchActivity $search
    ): void {
        $table = (new User())->getTable();

        if (!Schema::hasColumn($table, 'crm_service_type')) {
            return;
        }

        $query->where(function (Builder $builder) use (
            $search
        ): void {
            $builder
                ->whereNull('crm_service_type')
                ->orWhere('crm_service_type', '')
                ->orWhere(
                    'crm_service_type',
                    $search->service_type
                )
                ->orWhere(
                    'crm_service_type',
                    $search->module
                );
        });
    }

    /**
     * Optional city-wise filtering.
     */
    private function applyCityFilter(
        Builder $query,
        CustomerSearchActivity $search
    ): void {
        $table = (new User())->getTable();

        if (
            !Schema::hasColumn($table, 'crm_city')
            || blank($search->pickup_city)
        ) {
            return;
        }

        $query->where(function (Builder $builder) use (
            $search
        ): void {
            $builder
                ->whereNull('crm_city')
                ->orWhere('crm_city', '')
                ->orWhereRaw(
                    'LOWER(crm_city) = ?',
                    [
                        strtolower(
                            trim((string) $search->pickup_city)
                        ),
                    ]
                );
        });
    }

    /**
     * Optional senior executive filtering for urgent leads.
     */
    private function applyPriorityFilter(
        Builder $query,
        CustomerSearchActivity $search
    ): void {
        $table = (new User())->getTable();

        if (
            !in_array(
                $search->priority,
                [
                    CustomerSearchActivity::PRIORITY_URGENT,
                    CustomerSearchActivity::PRIORITY_HIGH,
                ],
                true
            )
        ) {
            return;
        }

        if (Schema::hasColumn($table, 'is_senior_executive')) {
            $query->orderByDesc('is_senior_executive');
        }
    }

    /**
     * Determine whether an executive can receive another lead.
     */
    private function executiveHasCapacity(
        User $executive
    ): bool {
        $assignmentColumn = $this->assignmentColumn();

        if ($assignmentColumn === null) {
            return false;
        }

        $maximumLeads = (int) (
            $executive->getAttribute('maximum_active_leads')
            ?? config(
                'crm.lead_assignment.maximum_active_leads',
                self::DEFAULT_MAX_ACTIVE_LEADS
            )
        );

        if ($maximumLeads <= 0) {
            return true;
        }

        $activeLeadCount = CustomerSearchActivity::query()
            ->where($assignmentColumn, $executive->id)
            ->where('is_converted', false)
            ->whereNotIn(
                'lead_status',
                $this->closedLeadStatuses()
            )
            ->where(
                'last_activity_at',
                '>=',
                now()->subDays(
                    self::DEFAULT_OPEN_LEAD_DAYS
                )
            )
            ->count();

        return $activeLeadCount < $maximumLeads;
    }

    /**
     * Check whether this customer is a VIP/repeat customer.
     */
    private function isVipCustomer(
        CustomerSearchActivity $search
    ): bool {
        $query = CustomerSearchActivity::query()
            ->where('id', '!=', $search->id)
            ->where('is_converted', true);

        if ($search->user_id !== null) {
            $query->where('user_id', $search->user_id);
        } elseif (filled($search->mobile)) {
            $query->where(
                'mobile',
                CustomerSearchActivity::normalizeMobile(
                    $search->mobile
                )
            );
        } else {
            return false;
        }

        return $query->count() >= (int) config(
            'crm.lead_assignment.vip_conversion_count',
            self::VIP_CONVERSION_COUNT
        );
    }

    /**
     * Check high-value lead amount.
     */
    private function isHighValueLead(
        CustomerSearchActivity $search
    ): bool {
        $amount = (float) (
            $search->grand_total
            ?? $search->estimated_amount
            ?? 0
        );

        return $amount >= (float) config(
            'crm.lead_assignment.high_value_amount',
            self::HIGH_VALUE_AMOUNT
        );
    }

    /**
     * Explain why an executive was assigned.
     */
    private function assignmentReason(
        CustomerSearchActivity $search,
        User $executive
    ): string {
        if (
            $search->priority
            === CustomerSearchActivity::PRIORITY_URGENT
        ) {
            return 'Urgent lead routing';
        }

        if ($this->isHighValueLead($search)) {
            return 'High-value lead routing';
        }

        if ($this->isVipCustomer($search)) {
            return 'VIP customer routing';
        }

        if (filled($search->pickup_city)) {
            return sprintf(
                'Automatic assignment for %s lead from %s',
                $search->service_type,
                $search->pickup_city
            );
        }

        return sprintf(
            'Automatic %s assignment to executive #%d',
            config(
                'crm.lead_assignment.strategy',
                self::STRATEGY_LEAST_LOADED
            ),
            $executive->id
        );
    }

    /**
     * Create CustomerActivity entry for assignment.
     */
    private function createAssignmentActivity(
        CustomerSearchActivity $search,
        User $executive,
        string $strategy,
        bool $isManual,
        ?User $assignedBy = null,
        ?string $note = null
    ): void {
        try {
            CustomerActivity::create([
                'user_id' => $search->user_id,
                'mobile' => $search->mobile,
                'customer_name' => $search->customer_name,
                'session_id' => $search->session_id,
                'device_id' => $search->device_id,
                'platform' => $search->platform,
                'device_name' => $search->device_name,
                'operating_system' =>
                    $search->operating_system,
                'app_version' => $search->app_version,
                'source' => $search->source,

                'event' => $isManual
                    ? 'lead_manually_assigned'
                    : 'lead_automatically_assigned',

                'module' => $search->module,
                'service_type' => $search->service_type,
                'stage' => $search->stage,

                'pickup_location' =>
                    $search->pickup_location,
                'pickup_city' => $search->pickup_city,
                'drop_location' => $search->drop_location,
                'drop_city' => $search->drop_city,

                'vehicle_id' => $search->vehicle_id,
                'vehicle_name' => $search->vehicle_name,
                'estimated_amount' =>
                    $search->grand_total
                    ?? $search->estimated_amount,

                'intent_score' => $search->intent_score,
                'priority' => $search->priority,
                'lead_status' => $search->lead_status,

                'related_type' => User::class,
                'related_id' => $executive->id,

                'data' => [
                    'customer_search_activity_id' =>
                        $search->id,
                    'executive_id' => $executive->id,
                    'executive_name' => $executive->name,
                    'strategy' => $strategy,
                    'is_manual' => $isManual,
                    'assigned_by_user_id' =>
                        $assignedBy?->id,
                    'note' => $note,
                ],

                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning(
                'Lead assignment activity could not be created.',
                [
                    'customer_search_activity_id' =>
                        $search->id,
                    'executive_id' => $executive->id,
                    'message' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Create CustomerActivity entry when unassigned.
     */
    private function createUnassignmentActivity(
        CustomerSearchActivity $search,
        ?int $previousExecutiveId,
        ?User $unassignedBy,
        ?string $reason
    ): void {
        try {
            CustomerActivity::create([
                'user_id' => $search->user_id,
                'mobile' => $search->mobile,
                'customer_name' => $search->customer_name,
                'session_id' => $search->session_id,
                'source' => $search->source,

                'event' => 'lead_unassigned',
                'module' => $search->module,
                'service_type' => $search->service_type,
                'stage' => $search->stage,

                'pickup_location' =>
                    $search->pickup_location,
                'pickup_city' => $search->pickup_city,
                'drop_location' => $search->drop_location,
                'drop_city' => $search->drop_city,

                'estimated_amount' =>
                    $search->grand_total
                    ?? $search->estimated_amount,

                'intent_score' => $search->intent_score,
                'priority' => $search->priority,
                'lead_status' => $search->lead_status,

                'data' => [
                    'customer_search_activity_id' =>
                        $search->id,
                    'previous_executive_id' =>
                        $previousExecutiveId,
                    'unassigned_by_user_id' =>
                        $unassignedBy?->id,
                    'reason' => $reason,
                ],

                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning(
                'Lead unassignment activity could not be created.',
                [
                    'customer_search_activity_id' =>
                        $search->id,
                    'message' => $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Save assignment failure in metadata.
     */
    private function recordAssignmentFailure(
        CustomerSearchActivity $search,
        string $reason
    ): void {
        $metadata = $this->mergeMetadata(
            $search->metadata,
            [
                'lead_assignment' => [
                    'executive_id' => null,
                    'failed' => true,
                    'failure_reason' => $reason,
                    'attempted_at' => now()->toIso8601String(),
                ],
            ]
        );

        $search->forceFill([
            'metadata' => $metadata,
        ])->save();

        Log::warning('Lead could not be assigned.', [
            'customer_search_activity_id' => $search->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Return assignment field used by the migration/model.
     */
    private function assignmentColumn(): ?string
    {
        foreach (
            [
                'assigned_user_id',
                'assigned_to',
                'assigned_to_id',
                'executive_id',
            ] as $column
        ) {
            if ($this->searchTableHasColumn($column)) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Get current assigned executive ID.
     */
    private function assignedExecutiveId(
        CustomerSearchActivity $search
    ): ?int {
        $column = $this->assignmentColumn();

        if ($column === null) {
            return null;
        }

        $value = $search->getAttribute($column);

        return is_numeric($value)
            ? (int) $value
            : null;
    }

    /**
     * Check duplicate assignment.
     */
    private function alreadyAssigned(
        CustomerSearchActivity $search
    ): bool {
        return $this->assignedExecutiveId($search) !== null;
    }

    /**
     * Match configured IDs against eligible executives.
     */
    private function firstMatchingExecutive(
        Collection $executives,
        mixed $configuredIds
    ): ?User {
        $ids = collect(
            is_array($configuredIds)
                ? $configuredIds
                : [$configuredIds]
        )
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id);

        if ($ids->isEmpty()) {
            return null;
        }

        return $executives->first(
            fn (User $executive): bool =>
                $ids->contains((int) $executive->id)
        );
    }

    /**
     * Keep assigned leads open unless already progressed.
     */
    private function resolvedAssignedLeadStatus(
        CustomerSearchActivity $search
    ): string {
        $currentStatus = $search->lead_status;

        if (
            filled($currentStatus)
            && $currentStatus
                !== CustomerSearchActivity::LEAD_NEW
        ) {
            return $currentStatus;
        }

        return defined(
            CustomerSearchActivity::class . '::LEAD_CONTACT_PENDING'
        )
            ? constant(
                CustomerSearchActivity::class
                . '::LEAD_CONTACT_PENDING'
            )
            : CustomerSearchActivity::LEAD_NEW;
    }

    /**
     * Lead statuses which must not be automatically processed.
     */
    private function closedLeadStatuses(): array
    {
        return array_values(
            array_filter([
                $this->modelConstant('LEAD_CONVERTED'),
                $this->modelConstant('LEAD_LOST'),
                $this->modelConstant('LEAD_NOT_INTERESTED'),
                $this->modelConstant('LEAD_CLOSED'),
            ])
        );
    }

    /**
     * Read optional model constant safely.
     */
    private function modelConstant(
        string $constant
    ): mixed {
        $name = CustomerSearchActivity::class
            . '::'
            . $constant;

        return defined($name)
            ? constant($name)
            : null;
    }

    /**
     * Check search activity table column.
     */
    private function searchTableHasColumn(
        string $column
    ): bool {
        return Schema::hasColumn(
            (new CustomerSearchActivity())->getTable(),
            $column
        );
    }

    /**
     * Merge assignment information into metadata.
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