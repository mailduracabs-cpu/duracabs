<?php

namespace App\Services;

use App\Models\CustomerSearchActivity as Lead;
use App\Models\SelfDriveBooking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class SelfDriveCheckoutLeadService
{
    public function sync(int $checkoutId, bool $paymentFailed = false): ?Lead
    {
        return DB::transaction(function () use ($checkoutId, $paymentFailed): ?Lead {
            // Serialize lead changes with checkout completion and ignore late failures.
            $checkout = DB::table('booking_payment_checkouts')
                ->where('id', $checkoutId)->where('source', 'self_drive')->lockForUpdate()->first();
            if (! $checkout) {
                return null;
            }

            $session = 'self-drive-checkout:' . $checkoutId;
            $lead = Lead::withTrashed()->forSession($session)->forUser((int) $checkout->customer_id)
                ->module(Lead::MODULE_SELF_DRIVE)->first();
            if ($lead?->trashed() || $lead?->is_converted) {
                return $lead;
            }

            if (! $lead) {
                $payload = json_decode($checkout->payload, true, 512, JSON_THROW_ON_ERROR)['attributes'];
                $customer = User::query()->findOrFail($checkout->customer_id);
                $vehicle = Vehicle::query()->find($payload['vehicle_id'] ?? null);
                $lead = Lead::create([
                    'session_id' => $session,
                    'user_id' => $customer->id,
                    'mobile' => $customer->mobile,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'source' => Lead::SOURCE_FLUTTER_APP,
                    'module' => Lead::MODULE_SELF_DRIVE,
                    'service_type' => Lead::SERVICE_SELF_DRIVE,
                    'stage' => Lead::STAGE_CHECKOUT_STARTED,
                    'checkout_status' => Lead::CHECKOUT_STARTED,
                    'checkout_started_at' => $checkout->created_at,
                    'pickup_location' => $payload['pickup_location'] ?? null,
                    'start_datetime' => $payload['start_datetime'] ?? null,
                    'end_datetime' => $payload['end_datetime'] ?? null,
                    'vehicle_id' => $vehicle?->id,
                    'vehicle_name' => $vehicle?->display_name,
                    'rental_hours' => $payload['booked_hours'] ?? null,
                    'security_deposit' => $payload['security_deposit'] ?? 0,
                    'estimated_amount' => $checkout->total,
                    'grand_total' => $checkout->total,
                    'intent_score' => 55,
                ]);
            }

            if ($checkout->booking_id) {
                $booking = SelfDriveBooking::query()->findOrFail($checkout->booking_id);
                $lead->markConverted('self_drive', (int) $booking->id, $booking->booking_no, (float) $checkout->total);
            } elseif ($checkout->state === 'paid_review_required' && $lead->stage !== 'payment_review') {
                $lead->update([
                    'stage' => 'payment_review',
                    'payment_status' => 'review_required',
                    'intent_score' => 80,
                ]);
            } elseif ($checkout->state === 'pending' && $paymentFailed && $lead->payment_status !== Lead::PAYMENT_FAILED) {
                $lead->markPaymentFailed();
            } elseif ($checkout->state === 'pending' && $checkout->gateway_order_id
                && $lead->payment_status === Lead::PAYMENT_NOT_STARTED) {
                $lead->markPaymentStarted();
            }

            return $lead;
        }, 3);
    }
}
