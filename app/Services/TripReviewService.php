<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripReviewService
{
    public const BLOCK_MESSAGE = 'Account restricted after a 1-star trip review. Only admin can reactivate it. Contact support.';

    public function blocked(int $userId): bool
    {
        return $userId > 0 && DB::table('trip_account_restrictions')->where('user_id', $userId)->whereNull('resolved_at')->exists();
    }

    public function assertAllowed(int $userId): void
    {
        if ($this->blocked($userId)) throw ValidationException::withMessages(['account' => self::BLOCK_MESSAGE]);
    }

    public function lockUsers(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        sort($ids);
        // Same order for reviews, restrictions and protected HTTP transactions.
        foreach ($ids as $id) DB::table('users')->where('id', $id)->lockForUpdate()->first();
    }

    public function score(int $userId): array
    {
        $rows = DB::table('trip_reviews')->where('subject_id', $userId)->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->get();
        $histogram = array_fill(1, 5, 0); $sum = 0; $count = 0;
        foreach ($rows as $row) { $histogram[(int) $row->rating] = (int) $row->total; $sum += $row->rating * $row->total; $count += $row->total; }
        return ['average' => $count ? round($sum / $count, 2) : null, 'count' => $count,
            'stars' => $histogram, 'blocked' => $this->blocked($userId)];
    }

    public function profile(User $user): array
    {
        return ['name' => $user->name, 'score' => $this->score((int) $user->id),
            'restriction_message' => $this->blocked((int) $user->id) ? self::BLOCK_MESSAGE : null,
            'reviews' => DB::table('trip_reviews')->where('subject_id', $user->id)->orderByDesc('id')->limit(20)
                ->get(['rating', 'comment', 'created_at'])->map(fn ($row) => (array) $row)->all()];
    }

    public function trip(string $type, string $key, bool $lock = false): array
    {
        abort_unless(in_array($type, ['taxi', 'self_drive', 'bike_rental'], true), 422, 'Invalid trip type.');
        $kind = $type === 'taxi' ? 'taxi' : 'self_drive';
        $table = $kind === 'taxi' ? 'orders' : 'self_drive_bookings';
        $numberColumn = 'booking_no';
        // Numeric internal IDs and booking numbers are both accepted, scoped to one service table.
        $query = DB::table($table);
        ctype_digit($key) ? $query->where('id', (int) $key) : $query->where($numberColumn, $key);
        if ($lock) $query->lockForUpdate();
        $row = $query->first();
        abort_unless($row, 404, 'Trip not found.');
        $customer = (int) ($kind === 'taxi' ? ($row->user_id ?? 0) : ($row->customer_id ?? 0));
        $partner = $kind === 'taxi' ? (int) ($row->transporter_id ?? 0) : $this->profileOwner((int) ($row->transporter_profile_id ?? 0));
        $states = array_filter(array_map(fn ($value) => strtolower(trim((string) $value)), [$row->status ?? '', $row->booking_status ?? '']));
        $cancelled = count(array_intersect($states, ['cancelled', 'canceled', 'cancel', 'rejected', 'failed'])) > 0;
        $completed = ! $cancelled && count(array_intersect($states, ['completed', 'complete', 'closed'])) > 0;
        return ['type' => $kind, 'id' => (int) $row->id, 'customer' => $customer, 'partner' => $partner,
            'completed' => $completed, 'booking_no' => $row->{$numberColumn} ?? (string) $row->id];
    }

    public function profileOwner(int $profileId): int
    {
        return $profileId > 0 ? (int) DB::table('fleet_transporter_profiles')->where('id', $profileId)->value('user_id') : 0;
    }

    public function vehicleOwner(int $vehicleId): int
    {
        if ($vehicleId <= 0) return 0;
        $vehicle = DB::table('vehicles')->where('id', $vehicleId)->first();
        return $vehicle ? ($this->profileOwner((int) ($vehicle->transporter_profile_id ?? 0)) ?: (int) ($vehicle->user_id ?? 0)) : 0;
    }

    private function subject(array $trip, int $actor): int
    {
        abort_unless($trip['customer'] > 0 && $trip['partner'] > 0 && $trip['customer'] !== $trip['partner'], 422, 'This trip needs a customer and assigned vendor/host.');
        abort_unless(in_array($actor, [$trip['customer'], $trip['partner']], true), 403, 'You are not a participant in this trip.');
        abort_unless(DB::table('users')->whereIn('id', [$trip['customer'], $trip['partner']])->count() === 2, 422, 'Trip participant account no longer exists.');
        return $actor === $trip['customer'] ? $trip['partner'] : $trip['customer'];
    }

    public function context(User $actor, string $type, string $key): array
    {
        $trip = $this->trip($type, $key); $subject = $this->subject($trip, (int) $actor->id);
        $base = DB::table('trip_reviews')->where('trip_type', $trip['type'])->where('trip_id', $trip['id']);
        $mine = (clone $base)->where('reviewer_id', $actor->id)->first(['rating', 'comment', 'created_at']);
        $received = (clone $base)->where('subject_id', $actor->id)->first(['rating', 'comment', 'created_at']);
        return ['trip_type' => $trip['type'], 'trip_id' => $trip['id'], 'booking_no' => $trip['booking_no'],
            'completed' => $trip['completed'], 'can_review' => $trip['completed'] && ! $mine,
            'reviewing' => (int) $actor->id === $trip['customer'] ? 'Vendor / Host' : 'Customer',
            'counterparty' => ['name' => DB::table('users')->where('id', $subject)->value('name'), 'score' => $this->score($subject)],
            'my_review' => $mine ? (array) $mine : null, 'received_review' => $received ? (array) $received : null];
    }

    public function submit(User $actor, string $type, string $key, int $rating, ?string $comment): array
    {
        $comment = trim((string) $comment);
        if ($rating < 1 || $rating > 5 || mb_strlen($comment) > 2000 || ($rating === 1 && mb_strlen($comment) < 5)) {
            throw ValidationException::withMessages(['rating' => 'Choose 1–5 stars. A 1-star review needs a reason of at least 5 characters.']);
        }
        $initial = $this->trip($type, $key);
        $this->subject($initial, (int) $actor->id);
        return DB::transaction(function () use ($initial, $actor, $type, $key, $rating, $comment): array {
            $this->lockUsers([$initial['customer'], $initial['partner']]);
            $trip = $this->trip($type, $key, true); $subject = $this->subject($trip, (int) $actor->id);
            abort_unless($initial['customer'] === $trip['customer'] && $initial['partner'] === $trip['partner'], 409, 'Trip participants changed. Refresh before reviewing.');
            abort_unless($trip['completed'], 422, 'Reviews unlock only after the trip is completed.');
            abort_if(DB::table('trip_reviews')->where('trip_type', $trip['type'])->where('trip_id', $trip['id'])->where('reviewer_id', $actor->id)->exists(), 409, 'You have already reviewed this trip.');
            $review = DB::table('trip_reviews')->insertGetId(['trip_type' => $trip['type'], 'trip_id' => $trip['id'],
                'reviewer_id' => $actor->id, 'subject_id' => $subject, 'rating' => $rating, 'comment' => $comment ?: null,
                'created_at' => now(), 'updated_at' => now()]);
            if ($rating === 1) DB::table('trip_account_restrictions')->insert(['user_id' => $subject, 'review_id' => $review,
                'created_at' => now(), 'updated_at' => now()]);
            return $this->context($actor, $type, $key);
        }, 3);
    }

    public function reactivate(User $admin, int $userId, string $note): void
    {
        abort_unless($admin->isAdmin(), 403, 'Only admin can reactivate accounts.');
        if (mb_strlen(trim($note)) < 5) throw ValidationException::withMessages(['note' => 'Enter the resolution reason.']);
        DB::transaction(function () use ($admin, $userId, $note): void {
            $this->lockUsers([$userId]);
            DB::table('trip_account_restrictions')->where('user_id', $userId)->whereNull('resolved_at')
                ->update(['resolved_at' => now(), 'resolved_by' => $admin->id, 'resolution_note' => trim($note), 'updated_at' => now()]);
        }, 3);
    }
}
