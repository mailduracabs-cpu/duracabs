<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SelfDriveBooking;
use App\Services\PartnerReturnCompletionService;
use Illuminate\Http\Request;

/** Reading a bill must never recalculate or mark a trip as returned. */
class SelfDriveReturnBillController extends Controller
{
    public function show(Request $request, string $bookingId)
    {
        $row = SelfDriveBooking::query()->where('customer_id', $request->user()->id)
            ->where(function ($query) use ($bookingId) {
                $query->where('booking_no', $bookingId);
                if (ctype_digit($bookingId)) $query->orWhere('id', (int) $bookingId);
            })->firstOrFail();
        if (! $row->final_bill_generated_at || ! $row->trip_end_datetime) {
            return response()->json(['status' => false, 'message' => 'Host has not confirmed return and generated the final bill yet.'], 409);
        }
        $bill = app(PartnerReturnCompletionService::class)->bill($row);
        return response()->json(['status' => true, 'message' => 'Final bill loaded', 'data' => [
            'booking_id' => $row->id, 'booking_no' => $row->booking_no,
            'base_rent' => $bill['base_rental'], 'actual_hours' => (float) $row->actual_hours,
            'actual_km' => (float) $row->actual_km, 'extra_hours' => $bill['extra_hours'],
            'extra_hour_amount' => $bill['extra_hour_amount'], 'extra_km' => $bill['extra_km'],
            'extra_km_amount' => $bill['extra_km_amount'], 'damage_amount' => $bill['damage_amount'],
            'fuel_charge' => $bill['fuel_charge'], 'cleaning_charge' => $bill['cleaning_charge'],
            'late_return_charge' => $bill['late_return_charge'], 'other_charge' => $bill['other_charge'],
            'final_amount' => $bill['rental'], 'paid_amount' => $bill['paid'],
            'security_deposit' => $bill['security_deposit'], 'full_booking_amount' => $bill['payable'],
            'refund_amount' => (float) $row->refund_amount, 'balance_due' => $bill['balance_due'],
        ]]);
    }
}
