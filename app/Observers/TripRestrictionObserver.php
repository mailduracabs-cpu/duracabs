<?php
namespace App\Observers;

use App\Services\TripReviewService;
use Illuminate\Database\Eloquent\Model;

class TripRestrictionObserver
{
    private function participants(Model $model): array
    {
        $s = app(TripReviewService::class); $table = $model->getTable();
        if ($table === 'orders') return [(int) $model->user_id, (int) $model->transporter_id];
        if ($table === 'self_drive_bookings') return [(int) $model->customer_id,
            $s->profileOwner((int) $model->transporter_profile_id) ?: $s->vehicleOwner((int) $model->vehicle_id)];
        // Both current Vehicle and legacy SelfDriveVehicle are protected.
        return [$s->profileOwner((int) $model->transporter_profile_id) ?: (int) $model->user_id];
    }
    private function check(Model $model): void
    {
        $s = app(TripReviewService::class); $ids = $this->participants($model);
        $s->lockUsers($ids);
        foreach ($ids as $id) $s->assertAllowed($id);
    }
    public function creating(Model $model): void { $this->check($model); }
    public function updating(Model $model): void
    {
        $table = $model->getTable();
        if (in_array($table, ['orders', 'self_drive_bookings'], true)) {
            $newAssignment = $model->isDirty(['user_id', 'customer_id', 'transporter_id', 'transporter_profile_id']);
            if ($newAssignment) $this->check($model);
            // Acceptance/dispatch APIs are protected by middleware. Do not interrupt
            // payment settlement or lifecycle changes on already-created trips.
        } elseif ($model->isDirty(['user_id', 'transporter_profile_id']) ||
            ($model->isDirty('is_live') && $model->is_live) || ($model->isDirty('is_active') && $model->is_active)) {
            $this->check($model);
        }
        // Existing trips can still complete/cancel; accounts can still contact support.
    }
}
