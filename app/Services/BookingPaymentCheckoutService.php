<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\SelfDriveBooking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Payment attempts live here; booking tables contain no unpaid checkout drafts. */
class BookingPaymentCheckoutService
{
    public function stage(int $customerId, string $source, array $payload, float $total, string $type, float $amount): object
    {
        if (! in_array($source, ['self_drive', 'order'], true) || $customerId <= 0
            || $amount <= 0 || $total <= 0 || $amount > $total) {
            throw new RuntimeException('Invalid checkout.');
        }
        $id = DB::table('booking_payment_checkouts')->insertGetId([
            'customer_id' => $customerId,
            'source' => $source,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'amount' => round($amount, 2),
            'total' => round($total, 2),
            'payment_type' => $type,
            'state' => 'pending',
            'gateway_order_id' => null, 'gateway_payment_id' => null, 'booking_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return DB::table('booking_payment_checkouts')->find($id);
    }

    public function owned(int $reference, int $customerId): object
    {
        if ($reference >= 0 || $customerId <= 0) {
            throw new RuntimeException('Invalid payment checkout reference.');
        }
        $checkout = DB::table('booking_payment_checkouts')
            ->where('id', -$reference)->where('customer_id', $customerId)->first();
        if (! $checkout) {
            throw new RuntimeException('Payment checkout not found.');
        }
        return $checkout;
    }

    public function byOrder(string $orderId): ?object
    {
        if ($orderId === '') {
            return null;
        }
        return DB::table('booking_payment_checkouts')->where('gateway_order_id', $orderId)->first();
    }

    public function gatewayOrder(object $checkout): array
    {
        return DB::transaction(function () use ($checkout): array {
            $row = DB::table('booking_payment_checkouts')->where('id', $checkout->id)->lockForUpdate()->first();
            if (! $row || $row->booking_id || $row->state === 'paid_review_required') {
                throw new RuntimeException('Checkout already paid. Please check your bookings.');
            }
            if (! $row->gateway_order_id) {
                if (Carbon::parse($row->created_at)->lt(now()->subMinutes(30))) {
                    throw new RuntimeException('Checkout expired. Please select your vehicle again.');
                }
                $result = RazorpayService::createOrder((float) $row->amount, 'CT' . $row->id, [
                    'checkout_id' => (string) $row->id,
                    'customer_id' => (string) $row->customer_id,
                    'booking_source' => $row->source,
                ]);
                if (! ($result['status'] ?? false) || empty($result['data']['id'])) {
                    throw new RuntimeException($result['message'] ?? 'Unable to start payment.');
                }
                $gatewayId = (string) $result['data']['id'];
                DB::table('booking_payment_checkouts')->where('id', $row->id)->update([
                    'gateway_order_id' => $gatewayId, 'updated_at' => now(),
                ]);
            } else {
                $gatewayId = $row->gateway_order_id;
            }
            return [
                'id' => $gatewayId, 'order_id' => $gatewayId, 'razorpay_order_id' => $gatewayId,
                'amount' => (int) round((float) $row->amount * 100), 'currency' => 'INR',
                'key' => RazorpayService::publicKey(),
                // Negative IDs are checkout references, never persisted booking IDs.
                'booking_id' => -(int) $row->id, 'checkout_id' => (int) $row->id,
                'booking_no' => null, 'booking_source' => $row->source,
                'payment_type' => $row->payment_type, 'payment_amount' => (float) $row->amount,
                'paid_amount' => 0, 'advance_amount' => 0,
                'payable_amount' => (float) $row->total, 'remaining_amount' => (float) $row->total,
                'booking_created' => false, 'payment_status' => 'pending',
            ];
        });
    }

    public function verify(object $checkout, string $orderId, string $paymentId, string $signature): array
    {
        $fresh = DB::table('booking_payment_checkouts')->find($checkout->id);
        if (! $fresh || ! $fresh->gateway_order_id || ! hash_equals($fresh->gateway_order_id, $orderId)) {
            throw new RuntimeException('Payment order does not belong to this checkout.');
        }
        $verification = RazorpayService::verifyPayment($fresh->gateway_order_id, $paymentId, $signature);
        if (! ($verification['status'] ?? false)) {
            throw new RuntimeException($verification['message'] ?? 'Payment verification failed.');
        }
        return $this->completeCaptured($fresh, $paymentId);
    }

    /** Call only from a verified checkout callback or signature-verified webhook. */
    public function completeCaptured(object $checkout, string $paymentId): array
    {
        $result = RazorpayService::fetchPayment($paymentId);
        if (! ($result['status'] ?? false) || ! is_array($result['data'] ?? null)) {
            throw new RuntimeException('Unable to confirm payment with Razorpay. Please retry verification.');
        }
        $payment = $result['data'];
        $received = BookingPaymentRules::received($payment, (string) $checkout->gateway_order_id, $paymentId, (float) $checkout->amount);

        try {
            return DB::transaction(function () use ($checkout, $paymentId, $received): array {
                $row = DB::table('booking_payment_checkouts')->where('id', $checkout->id)->lockForUpdate()->first();
                if (! $row || $row->gateway_order_id !== $checkout->gateway_order_id) {
                    throw new RuntimeException('Checkout changed. Please contact support.');
                }
                if ($row->booking_id) {
                    if ($row->gateway_payment_id !== $paymentId) {
                        throw new RuntimeException('Checkout already paid with a different payment.');
                    }
                    return $this->response($row, true);
                }
                $this->ensureNotUsed($paymentId);
                $payload = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
                $attributes = $payload['attributes'];
                unset($attributes['id'], $attributes['booking_no'], $attributes['booking_number']);
                $this->checkAvailability($row->source, $attributes, $payload);
                $remaining = max(0, round((float) $row->total - $received, 2));
                $money = [
                    'paid_amount' => $received, 'advance_amount' => $row->payment_type === 'advance' ? $received : 0,
                    'remaining_amount' => $remaining, 'balance_due' => $remaining,
                    'payment_method' => 'razorpay', 'payment_type' => $row->payment_type,
                    'payment_status' => $remaining > 0 ? 'partial' : 'paid',
                    'payment_reference' => $paymentId, 'razorpay_order_id' => $row->gateway_order_id,
                    'razorpay_payment_id' => $paymentId, 'payment_completed_at' => $remaining <= 0 ? now() : null, 'paid_at' => now(),
                ];
                if ($row->source === 'self_drive') {
                    $model = new SelfDriveBooking();
                    $attributes['booking_status'] = 'pending_vendor_confirmation';
                    $attributes['booking_no'] = 'SD' . now()->format('ymdHis') . Str::upper(Str::random(6));
                } else {
                    $model = new Order();
                    // Preserve existing order observers/number generation at the first real save.
                    $meta = $attributes['extraOptions'] ?? [];
                    if (is_string($meta)) {
                        $meta = json_decode($meta, true) ?: [];
                    }
                    $meta = array_merge((array) $meta, $money, ['last_payment_amount' => $received]);
                    $meta['reservation']['paid_amount'] = $received;
                    $meta['reservation']['balance_amount'] = $remaining;
                    if (Schema::hasColumn('orders', 'extraOptions')) {
                        $attributes['extraOptions'] = json_encode($meta, JSON_THROW_ON_ERROR);
                    }
                }
                foreach ($money as $column => $value) {
                    if (Schema::hasColumn($model->getTable(), $column)) {
                        $attributes[$column] = $value;
                    }
                }
                // Payload consists solely of server-built raw Eloquent attributes.
                $model->setRawAttributes($attributes);
                $model->save();
                if ($row->source === 'order') {
                    $address = $payload['address'];
                    unset($address['id'], $address['order_id']);
                    $address['order_id'] = $model->id;
                    Address::query()->create($address);
                    foreach ($payload['items'] ?? [] as $item) {
                        $model->items()->create($item);
                    }
                }
                DB::table('booking_payment_checkouts')->where('id', $row->id)->update([
                    'booking_id' => $model->id, 'gateway_payment_id' => $paymentId,
                    'state' => 'completed', 'updated_at' => now(),
                ]);
                $row->booking_id = $model->id;
                $row->gateway_payment_id = $paymentId;
                $row->state = 'completed';
                DB::afterCommit(function () use ($model, $row): void {
                    try {
                        if ($model instanceof SelfDriveBooking) {
                            app(SelfDriveBookingService::class)->notifyVerifiedCreation($model);
                        } else {
                            $model->loadMissing('address');
                            WhatsAppService::dispatchEvent('booking.received', [
                                'booking_id' => $model->booking_number ?? $model->booking_no ?? (string) $model->id,
                                'customer_name' => $model->address?->full_name,
                                'customer_mobile' => $model->address?->phone,
                                'mobile' => $model->address?->phone,
                                'vehicle' => $model->productName,
                                'pickup' => $model->cityFrom, 'drop' => $model->cityTo,
                                'travel_date' => (string) $model->date, 'travel_time' => (string) $model->time,
                                'amount' => number_format((float) $row->amount, 2),
                                'total_amount' => number_format((float) $row->total, 2),
                            ]);
                        }
                    } catch (Throwable $e) {
                        Log::warning('Paid booking notification failed.', ['checkout_id' => $row->id, 'error' => $e->getMessage()]);
                    }
                });
                return $this->response($row, false);
            }, 3);
        } catch (Throwable $e) {
            // A captured payment is retained for retry/manual resolution; never silently lost.
            DB::table('booking_payment_checkouts')->where('id', $checkout->id)->whereNull('booking_id')->update([
                'state' => 'paid_review_required', 'gateway_payment_id' => $paymentId, 'updated_at' => now(),
            ]);
            Log::critical('Captured checkout needs booking recovery.', [
                'checkout_id' => $checkout->id, 'payment_id' => $paymentId, 'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Payment received, but booking needs review. Do not pay again. Contact support with payment ID ' . $paymentId, 0, $e);
        }
    }

    private function checkAvailability(string $source, array $attributes, array $payload): void
    {
        if ($source === 'self_drive') {
            $vehicleId = (int) $attributes['vehicle_id'];
            $start = Carbon::parse($attributes['start_datetime']);
            $end = Carbon::parse($attributes['end_datetime']);
        } elseif (($attributes['ride_type'] ?? '') === 'self_drive') {
            $vehicleId = (int) ($attributes['vehicle_id'] ?? 0);
            $dates = $payload['availability'];
            $start = Carbon::parse($dates['start_datetime']);
            $end = Carbon::parse($dates['end_datetime']);
        } else {
            return;
        }
        $vehicle = Vehicle::query()->availableForCustomer()->lockForUpdate()->find($vehicleId);
        if (! $vehicle) {
            throw new RuntimeException('Vehicle is no longer available.');
        }
        $availability = app(SelfDriveAvailabilityService::class)->check([
            'vehicle_id' => $vehicleId, 'start_datetime' => $start->toDateTimeString(), 'end_datetime' => $end->toDateTimeString(),
        ]);
        if (! ($availability['status'] ?? false)) {
            throw new RuntimeException('Vehicle is no longer available for these dates.');
        }
    }

    private function ensureNotUsed(string $paymentId): void
    {
        foreach (['self_drive_bookings', 'orders', 'bike_rental_bookings'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (['razorpay_payment_id', 'payment_reference'] as $column) {
                if (Schema::hasColumn($table, $column) && DB::table($table)->where($column, $paymentId)->exists()) {
                    throw new RuntimeException('Payment already belongs to a booking.');
                }
            }
        }
    }

    private function response(object $row, bool $duplicate): array
    {
        $model = $row->source === 'self_drive'
            ? SelfDriveBooking::query()->findOrFail($row->booking_id)
            : Order::query()->findOrFail($row->booking_id);
        $remaining = max(0, round((float) $row->total - (float) $row->amount, 2));
        return [
            'id' => $model->id, 'booking_id' => $model->id,
            'booking_no' => $model->booking_no ?? $model->booking_number ?? (string) $model->id,
            'booking_source' => $row->source, 'checkout_id' => $row->id,
            'booking_created' => true, 'duplicate' => $duplicate,
            'payment_status' => $model->payment_status, 'payment_method' => 'razorpay',
            'payment_type' => $row->payment_type,
            'booking_status' => $model->booking_status ?? $model->status,
            'paid_amount' => (float) ($model->paid_amount ?? $row->amount),
            'advance_amount' => (float) ($model->advance_amount ?? ($row->payment_type === 'advance' ? $row->amount : 0)),
            'remaining_amount' => (float) ($model->remaining_amount ?? $remaining),
            'payable_amount' => (float) $row->total,
            'razorpay_order_id' => $row->gateway_order_id, 'razorpay_payment_id' => $row->gateway_payment_id,
        ];
    }
}
