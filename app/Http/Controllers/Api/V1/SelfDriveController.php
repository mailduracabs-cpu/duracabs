<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SelfDriveBooking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SelfDriveAvailabilityService;
use App\Services\SelfDriveBookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SelfDriveController extends BaseApiController
{
    public function __construct(
        private readonly SelfDriveBookingService $bookingService,
        private readonly SelfDriveAvailabilityService $availabilityService
    ) {
    }

    public function index(Request $request)
    {
        $query = Vehicle::query()
            ->with('transporter')
            ->availableForCustomer()
            ->whereNotNull('transporter_profile_id');

        foreach (['fuel_type', 'transmission'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, strtolower(trim((string) $request->$field)));
            }
        }

        if ($request->filled('category')) {
            $query->where('car_classification', 'like', '%' . trim((string) $request->category) . '%');
        }

        $vehicles = $query->latest('id')->get()
            ->map(fn (Vehicle $vehicle) => $this->vehicleData($vehicle))
            ->values();

        return $this->success($vehicles, 'Self drive vehicles loaded');
    }

    public function heroVehicles(Request $request)
    {
        $limit = max(1, min((int) $request->input('limit', 10), 20));

        $pickupLat = $request->filled('pickup_lat')
            ? (float) $request->input('pickup_lat')
            : null;

        $pickupLng = $request->filled('pickup_lng')
            ? (float) $request->input('pickup_lng')
            : null;

        $vehicles = Vehicle::query()
            ->with('transporter')
            ->availableForCustomer()
            ->whereNotNull('transporter_profile_id')
            ->latest('id')
            ->get()
            ->map(function (Vehicle $vehicle) use ($pickupLat, $pickupLng) {
                $distance = null;
                $transporter = $vehicle->transporter;

                if ($pickupLat !== null && $pickupLng !== null && $transporter) {
                    $vendorLat = $transporter->pickup_latitude
                        ?? $transporter->pickup_lat
                        ?? null;

                    $vendorLng = $transporter->pickup_longitude
                        ?? $transporter->pickup_lng
                        ?? null;

                    if ($vendorLat !== null && $vendorLng !== null) {
                        $distance = $this->distanceKm(
                            $pickupLat,
                            $pickupLng,
                            (float) $vendorLat,
                            (float) $vendorLng
                        );

                        $serviceRadius = (float) (
                            $transporter->service_radius_km ?? 40
                        );

                        if ($distance > $serviceRadius) {
                            return null;
                        }
                    }
                }

                $data = $this->vehicleData($vehicle, $distance);
                $dailyPrice = $this->dailyVehiclePrice($vehicle);

                return [
                    ...$data,
                    'title' => $data['vehicle_name'],
                    'subtitle' => 'Self Drive Car',
                    'image_url' => $this->publicImageUrl(
                        $vehicle->front_image
                            ?? $vehicle->image
                            ?? null
                    ),
                    'daily_price' => $dailyPrice,
                    'price_24_hours' => $dailyPrice,
                    'banner_type' => 'vehicle',
                    'vehicle_id' => $vehicle->id,
                ];
            })
            ->filter()
            ->take($limit)
            ->values();

        return $this->success(
            $vehicles,
            'Self drive hero vehicles loaded'
        );
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'pickup_location' => 'required|string|max:500',
            'start_date' => 'required_without:start_datetime|date',
            'start_time' => 'required_without:start_datetime',
            'end_date' => 'required_without:end_datetime|date',
            'end_time' => 'required_without:end_datetime',
            'start_datetime' => 'nullable|date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'fuel_type' => 'nullable|string|max:50',
            'transmission' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'seats' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $result = $this->availabilityService->search(
            $validator->validated()
        );

        if (! ($result['status'] ?? false)) {
            return $this->serviceResponse($result);
        }

        // Existing Flutter and website clients expect the vehicle collection
        // directly in the data key, so the service metadata stays internal.
        return $this->success(
            data_get($result, 'data.vehicles', []),
            (string) ($result['message'] ?? 'Available vehicles loaded')
        );
    }

    public function show($id)
    {
        $vehicle = Vehicle::query()
            ->with('transporter')
            ->availableForCustomer()
            ->find($id);

        if (! $vehicle) {
            return $this->error('Self drive vehicle not found', 404);
        }

        return $this->success($this->vehicleData($vehicle), 'Self drive vehicle loaded');
    }

    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'pickup_lat' => 'nullable|numeric|between:-90,90',
            'pickup_lng' => 'nullable|numeric|between:-180,180',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'start_date' => 'required_without:start_datetime|date',
            'start_time' => 'required_without:start_datetime',
            'end_date' => 'required_without:end_datetime|date',
            'end_time' => 'required_without:end_datetime',
            'start_datetime' => 'nullable|date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'exclude_booking_id' => 'nullable|integer|exists:self_drive_bookings,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        return $this->serviceResponse(
            $this->availabilityService->check($validator->validated())
        );
    }

    public function customerProfile(Request $request)
    {
        $customer = $this->customerFromRequest($request);

        if (! $customer) {
            return $this->error('Customer not found. Please login again.', 401);
        }

        return $this->success($customer->customerProfileData(), 'Customer profile loaded');
    }

    public function updateCustomerProfile(Request $request)
    {
        $customer = $this->customerFromRequest($request);

        if (! $customer) {
            return $this->error('Customer not found. Please login again.', 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:190',
            'mobile' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $data = array_filter([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
        ], fn ($value) => $value !== null);

        if ($data) {
            $customer->update($data);
        }

        return $this->success($customer->fresh()->customerProfileData(), 'Customer profile updated');
    }

    public function booking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'customer_id' => 'nullable|integer|exists:users,id',
            'customer_name' => 'nullable|string|max:120',
            'customer_mobile' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:190',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:190',
            'pickup_location' => 'required|string|max:500',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'start_date' => 'required_without:start_datetime|date',
            'start_time' => 'required_without:start_datetime',
            'end_date' => 'required_without:end_datetime|date',
            'end_time' => 'required_without:end_datetime',
            'start_datetime' => 'nullable|date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'payment_type' => 'required|in:advance,full',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:255',
            'free_km' => 'nullable|numeric|min:0',
            'extra_hour_rate' => 'nullable|numeric|min:0',
            'extra_km_rate' => 'nullable|numeric|min:0',
            'unlimited_kms' => 'nullable|boolean',
            'unlimited_km_selected' => 'nullable|boolean',
            'special_request_total' => 'nullable|numeric|min:0',
            'extra_service_amount' => 'nullable|numeric|min:0',
            'toll_amount' => 'nullable|numeric|min:0',
            'parking_amount' => 'nullable|numeric|min:0',
            'government_tax_amount' => 'nullable|numeric|min:0',
            'permit_tax_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $user = auth()->user();

        return $this->serviceResponse(
            $this->bookingService->create(
                $request->all(),
                $user instanceof User ? $user : null
            )
        );
    }

    public function pendingBooking(Request $request)
    {
        $user = auth()->user();

        return $this->serviceResponse(
            $this->bookingService->pendingBooking(
                $request->all(),
                $user instanceof User ? $user : null
            )
        );
    }

    public function bookingStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $user = auth()->user();

        return $this->serviceResponse(
            $this->bookingService->details(
                $request->booking_id,
                $user instanceof User ? $user : null
            )
        );
    }

    public function bookingDetails($bookingId)
    {
        $user = auth()->user();

        return $this->serviceResponse(
            $this->bookingService->details(
                $bookingId,
                $user instanceof User ? $user : null
            )
        );
    }

    public function vendorConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        return $this->serviceResponse(
            $this->bookingService->vendorConfirm($request->booking_id)
        );
    }

    public function vendorReject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        return $this->serviceResponse(
            $this->bookingService->vendorReject(
                $request->booking_id,
                $request->reason
            )
        );
    }

    public function uploadAadhaar(Request $request)
    {
        return $this->uploadCustomerDocument(
            $request,
            'aadhaar',
            'aadhar_number',
            'aadhar_front',
            'aadhar_back'
        );
    }

    public function uploadDrivingLicence(Request $request)
    {
        return $this->uploadCustomerDocument(
            $request,
            'driving_licence',
            'driving_licence_number',
            'driving_licence_front',
            'driving_licence_back'
        );
    }

    public function verifyDocuments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
            'approved' => 'required|boolean',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $booking = $this->bookingRow($request->booking_id);

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        $customer = User::find($booking->customer_id);

        if (! $customer || ! $customer->hasCompleteKyc()) {
            return $this->error('Customer Aadhaar and Driving Licence are incomplete', 422);
        }

        $approved = $request->boolean('approved');
        $customer->update([
            'kyc_status' => $approved
                ? User::KYC_VENDOR_APPROVED
                : User::KYC_VENDOR_REJECTED,
        ]);

        $response = $this->updateBooking($request, $approved ? [
            'document_status' => 'approved',
            'documents_verified_at' => now(),
            'booking_confirmed_at' => now(),
            'booking_status' => 'confirmed',
            'status' => 'confirmed',
        ] : [
            'document_status' => 'rejected',
            'documents_rejected_at' => now(),
            'document_rejection_reason' => $request->reason,
            'booking_status' => 'documents_rejected',
        ], $approved ? 'Documents approved' : 'Documents rejected');

        $this->bookingService->notifyLifecycleEvent(
            $booking->id,
            $approved ? 'self_drive_kyc_approved' : 'self_drive_kyc_rejected',
            ['reason' => $request->reason]
        );

        return $response;
    }

    public function generatePickupOtp(Request $request)
    {
        $booking = $this->bookingRow($request->booking_id);

        if (! $booking || $booking->document_status !== 'approved') {
            return $this->error('Approved documents are required', 422);
        }

        $otp = (string) random_int(1000, 9999);

        DB::table('self_drive_bookings')->where('id', $booking->id)->update([
            'pickup_otp' => $otp,
            'pickup_otp_generated_at' => now(),
            'pickup_otp_expires_at' => now()->addMinutes(30),
            'pickup_otp_attempts' => 0,
            'booking_status' => 'pickup_otp_generated',
            'updated_at' => now(),
        ]);

        $this->bookingService->notifyLifecycleEvent(
            $booking->id,
            'self_drive_pickup_otp_generated',
            ['otp' => $otp]
        );

        return $this->success([
            'booking_id' => $booking->id,
            'pickup_otp' => $otp,
            'expires_in_minutes' => 30,
        ], 'Pickup OTP generated');
    }

    public function verifyPickupOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required',
            'otp' => 'required|digits:4',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $booking = $this->bookingRow($request->booking_id);

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        if (
            $booking->vendor_confirmation_status !== 'confirmed' ||
            $booking->document_status !== 'approved'
        ) {
            return $this->error(
                'Vendor confirmation and approved documents are required',
                422
            );
        }

        if (
            (string) $booking->pickup_otp !== (string) $request->otp ||
            ! $booking->pickup_otp_expires_at ||
            Carbon::parse($booking->pickup_otp_expires_at)->isPast()
        ) {
            DB::table('self_drive_bookings')
                ->where('id', $booking->id)
                ->increment('pickup_otp_attempts');

            return $this->error('Invalid or expired pickup OTP', 422);
        }

        DB::table('self_drive_bookings')
            ->where('id', $booking->id)
            ->update([
                'pickup_otp_verified_at' => now(),
                'registration_unlocked_at' => now(),
                'booking_status' => 'pickup_inspection_required',
                'status' => 'confirmed',
                'updated_at' => now(),
            ]);

        $this->bookingService->notifyLifecycleEvent(
            $booking->id,
            'self_drive_pickup_otp_verified'
        );

        return $this->success([
            ...$this->unlockedVehicleData($booking->vehicle_id),
            'booking_id' => $booking->id,
            'booking_status' => 'pickup_inspection_required',
            'next_required_step' => 'pickup_photos_and_start_km',
        ], 'Pickup OTP verified. Upload pickup photos and Start KM to start trip.');
    }

    public function generateEndOtp(Request $request)
    {
        $booking = $this->bookingRow($request->booking_id);

        if (! $booking || ! $booking->canReturn()) {
            return $this->error('Running booking not found', 422);
        }

        $otp = (string) random_int(1000, 9999);

        DB::table('self_drive_bookings')->where('id', $booking->id)->update([
            'return_otp' => $otp,
            'return_otp_generated_at' => now(),
            'return_otp_expires_at' => now()->addMinutes(30),
            'return_otp_attempts' => 0,
            'end_requested_at' => now(),
            'booking_status' => 'end_otp_generated',
            'updated_at' => now(),
        ]);

        $this->bookingService->notifyLifecycleEvent(
            $booking->id,
            'self_drive_return_otp_generated',
            ['otp' => $otp]
        );

        return $this->success([
            'booking_id' => $booking->id,
            'end_otp' => $otp,
            'expires_in_minutes' => 30,
        ], 'End OTP generated');
    }

    public function verifyEndOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
            'otp' => 'required|digits:4',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $booking = $this->bookingRow($request->booking_id);

        if (
            ! $booking ||
            $booking->return_otp !== $request->otp ||
            ! $booking->return_otp_expires_at ||
            Carbon::parse($booking->return_otp_expires_at)->isPast()
        ) {
            return $this->error('Invalid or expired end OTP', 422);
        }

        DB::table('self_drive_bookings')->where('id', $booking->id)->update([
            'return_otp_verified_at' => now(),
            'trip_end_datetime' => now(),
            'booking_status' => 'inspection_pending',
            'updated_at' => now(),
        ]);

        $this->bookingService->notifyLifecycleEvent(
            $booking->id,
            'self_drive_return_otp_verified'
        );

        return $this->success([
            'booking_id' => $booking->id,
            'booking_status' => 'inspection_pending',
        ], 'End OTP verified');
    }

    public function pickupUpload(Request $request)
    {
        return $this->inspectionUpload($request, true);
    }

    public function dropUpload(Request $request)
    {
        return $this->inspectionUpload($request, false);
    }

    public function finalBill($bookingId)
    {
        $booking = $this->bookingRow($bookingId);

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        $tripStart = Carbon::parse($booking->trip_start_datetime ?: $booking->start_datetime);
        $tripEnd = Carbon::parse($booking->trip_end_datetime ?: now());
        $actualHours = max(1, (int) ceil($tripStart->diffInMinutes($tripEnd) / 60));
        $extraHours = max(0, $actualHours - $booking->booked_hours);
        $extraHourRate = (float) ($booking->extra_hour_rate ?: $booking->hourly_price);
        $extraHourAmount = $extraHours * $extraHourRate;
        $actualKm = max(0, (float) $booking->end_km - (float) $booking->start_km);
        $extraKm = max(0, $actualKm - (float) $booking->free_km);
        $extraKmAmount = $extraKm * (float) $booking->extra_km_rate;
        $charges = $extraHourAmount + $extraKmAmount +
            (float) $booking->damage_amount + (float) $booking->fuel_charge +
            (float) $booking->cleaning_charge + (float) $booking->late_return_charge +
            (float) $booking->other_charge;
        $finalAmount = (float) $booking->total_amount + $charges;
        $paidAmount = (float) $booking->paid_amount;
        $refund = max(0, $paidAmount - $finalAmount);
        $balance = max(0, $finalAmount - $paidAmount);

        DB::table('self_drive_bookings')->where('id', $booking->id)->update([
            'actual_hours' => $actualHours,
            'actual_km' => $actualKm,
            'extra_hours' => $extraHours,
            'extra_km' => $extraKm,
            'extra_hour_amount' => $extraHourAmount,
            'extra_km_amount' => $extraKmAmount,
            'final_amount' => $finalAmount,
            'refund_amount' => $refund,
            'balance_due' => $balance,
            'refund_status' => $refund > 0 ? 'pending' : 'not_applicable',
            'settlement_status' => $balance > 0 ? 'balance_due' : ($refund > 0 ? 'refund_pending' : 'settled'),
            'booking_status' => 'final_bill_generated',
            'final_bill_generated_at' => now(),
            'updated_at' => now(),
        ]);

        $this->bookingService->notifyLifecycleEvent(
            $booking->id,
            'self_drive_final_bill_generated',
            [
                'amount' => round($finalAmount, 2),
                'balance_due' => round($balance, 2),
                'refund_amount' => round($refund, 2),
            ]
        );

        if ($refund > 0) {
            $this->bookingService->notifyLifecycleEvent(
                $booking->id,
                'self_drive_refund_pending',
                ['amount' => round($refund, 2)]
            );
        }

        return $this->success([
            'booking_id' => $booking->id,
            'booking_no' => $booking->booking_no,
            'base_rent' => (float) $booking->total_amount,
            'actual_hours' => $actualHours,
            'extra_hours' => $extraHours,
            'extra_hour_amount' => round($extraHourAmount, 2),
            'actual_km' => round($actualKm, 2),
            'extra_km' => round($extraKm, 2),
            'extra_km_amount' => round($extraKmAmount, 2),
            'damage_amount' => (float) $booking->damage_amount,
            'fuel_charge' => (float) $booking->fuel_charge,
            'cleaning_charge' => (float) $booking->cleaning_charge,
            'other_charge' => (float) $booking->other_charge,
            'final_amount' => round($finalAmount, 2),
            'paid_amount' => round($paidAmount, 2),
            'refund_amount' => round($refund, 2),
            'balance_due' => round($balance, 2),
        ], 'Final bill generated');
    }

    public function refundStatus($bookingId)
    {
        $booking = $this->bookingRow($bookingId);

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        return $this->success([
            'booking_id' => $booking->id,
            'refund_amount' => (float) $booking->refund_amount,
            'refund_status' => $booking->refund_status,
            'refund_reference' => $booking->refund_reference,
            'settlement_status' => $booking->settlement_status,
            'balance_due' => (float) $booking->balance_due,
        ], 'Refund status loaded');
    }

    public function kmCalculate(Request $request)
    {
        $request->validate([
            'pickup_km' => 'required|numeric|min:0',
            'drop_km' => 'required|numeric|gte:pickup_km',
            'free_km' => 'nullable|numeric|min:0',
            'extra_km_rate' => 'nullable|numeric|min:0',
        ]);

        $total = (float) $request->drop_km - (float) $request->pickup_km;
        $extra = max(0, $total - (float) ($request->free_km ?? 0));

        return $this->success([
            'total_km' => $total,
            'extra_km' => $extra,
            'extra_amount' => $extra * (float) ($request->extra_km_rate ?? 0),
        ], 'KM calculated');
    }

    public function damageDetect(Request $request)
    {
        return $this->success([
            'booking_id' => $request->booking_id,
            'damage_detected' => false,
            'status' => 'manual_review_required',
        ], 'Damage review request created');
    }

    private function customerFromRequest(Request $request): ?User
    {
        $authenticated = auth()->user();

        if ($authenticated instanceof User) {
            return $authenticated;
        }

        if ($request->filled('customer_id')) {
            return User::find((int) $request->customer_id);
        }

        if ($request->filled('mobile')) {
            return User::where('mobile', trim((string) $request->mobile))->first();
        }

        return null;
    }

    private function uploadCustomerDocument(
        Request $request,
        string $fileField,
        string $numberColumn,
        string $frontColumn,
        string $backColumn
    ) {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
            'side' => 'required|in:front,back',
            'document_number' => 'nullable|string|max:50',
            $fileField => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $booking = $this->bookingRow($request->booking_id);

        if (! $booking || $booking->vendor_confirmation_status !== 'confirmed') {
            return $this->error('Vendor confirmation is required first', 422);
        }

        $customer = User::find($booking->customer_id);

        if (! $customer) {
            return $this->error('Customer not found', 404);
        }

        if (blank($customer->$numberColumn) && blank($request->document_number)) {
            return $this->error('Document number is required on first upload', 422);
        }

        $path = $request->file($fileField)->store('self-drive/customer-documents/' . $customer->id, 'public');
        $imageColumn = $request->side === 'front' ? $frontColumn : $backColumn;
        $updates = [$imageColumn => $path];

        if ($request->filled('document_number')) {
            $updates[$numberColumn] = trim((string) $request->document_number);
        }

        $customer->update($updates);
        $customer->refresh();

        $kycStatus = $customer->hasCompleteKyc()
            ? User::KYC_UPLOADED
            : User::KYC_NOT_UPLOADED;

        $customer->update(['kyc_status' => $kycStatus]);

        DB::table('self_drive_bookings')->where('id', $booking->id)->update([
            'document_status' => $customer->hasCompleteKyc() ? 'under_verification' : 'partially_uploaded',
            'documents_uploaded_at' => now(),
            'booking_status' => $customer->hasCompleteKyc()
                ? 'documents_under_verification'
                : 'documents_required',
            'updated_at' => now(),
        ]);

        return $this->success([
            'booking_id' => $booking->id,
            'side' => $request->side,
            'path' => $path,
            'customer' => $customer->fresh()->customerProfileData(),
        ], 'Document uploaded successfully');
    }

    private function bookingDates(Request $request): array
    {
        $start = Carbon::parse($request->start_date . ' ' . $request->start_time);
        $end = Carbon::parse($request->end_date . ' ' . $request->end_time);

        if ($start->lt(now()->subMinute())) {
            throw new \Exception('Start time cannot be in the past');
        }

        if ($end->lte($start)) {
            throw new \Exception('End time must be after start time');
        }

        return [$start, $end];
    }

    private function isAvailable(int $vehicleId, Carbon $start, Carbon $end): bool
    {
        return ! DB::table('self_drive_bookings')
            ->where('vehicle_id', $vehicleId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();
    }

    private function vehicleData(
        Vehicle $vehicle,
        ?float $distance = null,
        ?string $pickup = null,
        ?Carbon $start = null,
        ?Carbon $end = null
    ): array {
        $hours = $start && $end
            ? max(1, (int) ceil($start->diffInMinutes($end) / 60))
            : null;
        $chargeable = $hours
            ? max($hours, (int) ($vehicle->minimum_booking_hours ?? 1))
            : null;

        return [
            'id' => $vehicle->id,
            'vehicle_name' => $this->vehicleName($vehicle),
            'brand' => $vehicle->car_company_name,
            'model' => $vehicle->model_name,
            'category' => $vehicle->car_classification,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'color' => $vehicle->car_color,
            'manufacture_year' => $vehicle->manufacture_year,
            'seats' => (int) ($vehicle->seats ?? 0),
            'bags' => (int) ($vehicle->bags ?? 0),
            'hourly_price' => (float) ($vehicle->hourly_price ?? 0),
            'security_deposit' => (float) ($vehicle->security_deposit ?? 0),
            'minimum_booking_hours' => (int) ($vehicle->minimum_booking_hours ?? 1),
            'front_image' => $vehicle->front_image,
            'back_image' => $vehicle->back_image,
            'interior_image' => $vehicle->interior_image,
            'distance_km' => $distance !== null ? round($distance, 2) : null,
            'pickup_location' => $pickup,
            'selected_hours' => $hours,
            'chargeable_hours' => $chargeable,
            'estimated_amount' => $chargeable
                ? round($chargeable * (float) $vehicle->hourly_price, 2)
                : null,
            'partner' => [
                'id' => $vehicle->transporter?->id,
                'company_name' => $vehicle->transporter?->company_name,
            ],
        ];
    }

    private function secureBookingData(object $booking): array
    {
        $unlocked = ! empty($booking->pickup_otp_verified_at);
        $vehicle = Vehicle::query()
            ->with('transporter')
            ->find($booking->vehicle_id);
        $customer = User::find($booking->customer_id);
        $transporter = $vehicle?->transporter;

        $vehicleName = $vehicle
            ? $this->vehicleName($vehicle)
            : 'Self Drive Car';

        $pickupAddress = $transporter?->pickup_address
            ?: $transporter?->office_address
            ?: $booking->pickup_location;

        $startAt = $booking->start_datetime
            ? Carbon::parse($booking->start_datetime)
            : null;

        $endAt = $booking->end_datetime
            ? Carbon::parse($booking->end_datetime)
            : null;

        $hourlyPrice = (float) ($booking->hourly_price ?? 0);
        $bookedHours = (int) ($booking->booked_hours ?? 0);
        $rent = (float) ($booking->total_amount ?? 0);
        $deposit = (float) ($booking->security_deposit ?? 0);
        $fullAmount = round($rent + $deposit, 2);
        $paidAmount = (float) ($booking->paid_amount ?? 0);
        $remainingAmount = max(
            0,
            (float) ($booking->remaining_amount ?? ($fullAmount - $paidAmount))
        );

        return [
            'id' => $booking->id,
            'booking_id' => $booking->id,
            'booking_no' => $booking->booking_no,
            'booking_status' => $booking->booking_status,
            'status' => $booking->status,
            'vendor_confirmation_status' =>
                $booking->vendor_confirmation_status,
            'document_status' => $booking->document_status,

            'pickup_location' => $booking->pickup_location,
            'vendor_pickup_address' => $pickupAddress,
            'pickup_address' => $pickupAddress,
            'pickup_latitude' => $transporter?->pickup_latitude
                ?? $booking->pickup_latitude,
            'pickup_longitude' => $transporter?->pickup_longitude
                ?? $booking->pickup_longitude,

            'start_datetime' => $startAt?->toIso8601String(),
            'end_datetime' => $endAt?->toIso8601String(),
            'start_date' => $startAt?->format('Y-m-d'),
            'start_time' => $startAt?->format('H:i'),
            'end_date' => $endAt?->format('Y-m-d'),
            'end_time' => $endAt?->format('H:i'),

            'vehicle_id' => $vehicle?->id,
            'vehicle_name' => $vehicleName,
            'vehicle' => $vehicleName,
            'brand' => $vehicle?->car_company_name,
            'model' => $vehicle?->model_name,
            'fuel_type' => $vehicle?->fuel_type,
            'transmission' => $vehicle?->transmission,

            'customer' => [
                'id' => $customer?->id,
                'name' => $customer?->name,
                'mobile' => $customer?->mobile,
                'email' => $customer?->email,
                'photo' => $customer?->photo,
            ],
            'customer_name' => $customer?->name,
            'mobile' => $customer?->mobile,
            'email' => $customer?->email,

            'hourly_price' => $hourlyPrice,
            'booked_hours' => $bookedHours,
            'chargeable_hours' => $bookedHours,
            'estimated_rent' => $rent,
            'security_deposit' => $deposit,
            'total_amount' => $rent,
            'full_booking_amount' => $fullAmount,
            'grand_total' => $fullAmount,
            'payment_type' => $booking->payment_type,
            'payment_status' => $booking->payment_status,
            'payment_method' => $booking->payment_method,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'final_amount' => (float) ($booking->final_amount ?? 0),
            'balance_due' => (float) ($booking->balance_due ?? 0),

            'next_required_step' => $customer?->isKycApproved()
                ? null
                : $customer?->nextRequiredStep(),

            'registration_unlocked' => $unlocked,
            'pickup_otp_generated' => ! empty($booking->pickup_otp),
            'pickup_otp_verified' =>
                ! empty($booking->pickup_otp_verified_at),
            'pickup_inspection_uploaded' =>
                ! empty($booking->pickup_images)
                && $booking->start_km !== null,
            'pickup_images_uploaded' =>
                ! empty($booking->pickup_images),
            'trip_started' => $booking->isRunning(),
            'start_km' => $booking->start_km !== null
                ? (float) $booking->start_km
                : null,
            'end_km' => $booking->end_km !== null
                ? (float) $booking->end_km
                : null,

            'vehicle_number' => $unlocked
                ? $vehicle?->vehicle_number
                : null,
            'documents' => $unlocked ? [
                'rc_image' => $vehicle?->rc_image,
                'insurance_image' => $vehicle?->insurance_image,
                'pollution_image' => $vehicle?->polution_image,
            ] : null,
        ];
    }

    private function unlockedVehicleData(int $vehicleId): array
    {
        $vehicle = Vehicle::find($vehicleId);

        return [
            'vehicle_id' => $vehicle?->id,
            'vehicle_number' => $vehicle?->vehicle_number,
            'documents' => [
                'rc_image' => $vehicle?->rc_image,
                'insurance_image' => $vehicle?->insurance_image,
                'pollution_image' => $vehicle?->polution_image,
            ],
        ];
    }

    private function inspectionUpload(Request $request, bool $pickup)
    {
        $request->validate([
            'booking_id' => 'required',
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            $pickup ? 'pickup_km' : 'drop_km' => 'required|numeric|min:0',
            'fuel_level' => 'nullable|string|max:50',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $booking = $this->bookingRow($request->booking_id);

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        if ((int) $booking->vehicle_id !== (int) $request->vehicle_id) {
            return $this->error('Selected vehicle does not match booking', 422);
        }

        if ($pickup && empty($booking->pickup_otp_verified_at)) {
            return $this->error('Pickup OTP verification required', 422);
        }

        if (! $pickup && empty($booking->return_otp_verified_at)) {
            return $this->error('End OTP verification required', 422);
        }

        if (
            ! $pickup &&
            (float) $request->drop_km < (float) $booking->start_km
        ) {
            return $this->error('End KM cannot be less than Start KM', 422);
        }

        $images = [];

        foreach ($request->file('images', []) as $image) {
            $images[] = $image->store(
                $pickup
                    ? 'self-drive/inspections/pickup'
                    : 'self-drive/inspections/drop',
                'public'
            );
        }

        if ($pickup) {
            DB::table('self_drive_bookings')
                ->where('id', $booking->id)
                ->update([
                    'start_km' => $request->pickup_km,
                    'pickup_fuel_level' => $request->fuel_level,
                    'pickup_images' => json_encode($images),
                    'trip_start_datetime' => now(),
                    'booking_status' => 'running',
                    'status' => 'running',
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('self_drive_bookings')
                ->where('id', $booking->id)
                ->update([
                    'end_km' => $request->drop_km,
                    'drop_fuel_level' => $request->fuel_level,
                    'drop_images' => json_encode($images),
                    'booking_status' => 'final_bill_pending',
                    'status' => 'return_pending',
                    'updated_at' => now(),
                ]);
        }

        if ($pickup) {
            $this->bookingService->notifyLifecycleEvent(
                $booking->id,
                'self_drive_trip_started',
                ['start_km' => (float) $request->pickup_km]
            );
        }

        return $this->success([
            'booking_id' => $booking->id,
            'booking_status' => $pickup
                ? 'running'
                : 'final_bill_pending',
            'images' => $images,
            'start_km' => $pickup
                ? (float) $request->pickup_km
                : (float) $booking->start_km,
            'end_km' => $pickup
                ? null
                : (float) $request->drop_km,
        ], $pickup
            ? 'Pickup inspection uploaded and trip started'
            : 'Drop inspection uploaded');
    }

    private function updateBooking(Request $request, array $data, string $message)
    {
        $request->validate(['booking_id' => 'required|integer']);

        if (! $this->bookingRow($request->booking_id)) {
            return $this->error('Booking not found', 404);
        }

        $data['updated_at'] = now();

        DB::table('self_drive_bookings')
            ->where('id', $request->booking_id)
            ->update($data);

        return $this->success([
            'booking_id' => (int) $request->booking_id,
            'booking_status' => $data['booking_status'] ?? null,
        ], $message);
    }

    private function bookingRow($bookingId): ?SelfDriveBooking
    {
        if (! $bookingId) {
            return null;
        }

        return SelfDriveBooking::query()
            ->where(function ($query) use ($bookingId): void {
                if (is_numeric($bookingId)) {
                    $query->whereKey((int) $bookingId);
                }

                $query->orWhere('booking_no', (string) $bookingId);
            })
            ->first();
    }

    private function vehicleName(Vehicle $vehicle): string
    {
        return trim(($vehicle->car_company_name ?? '') . ' ' . ($vehicle->model_name ?? ''));
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $lat = deg2rad($lat2 - $lat1);
        $lng = deg2rad($lng2 - $lng1);
        $a = sin($lat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function dailyVehiclePrice(Vehicle $vehicle): float
    {
        $dailyPrice = (float) ($vehicle->daily_price ?? 0);

        if ($dailyPrice > 0) {
            return round($dailyPrice, 2);
        }

        return round(
            (float) ($vehicle->hourly_price ?? 0) * 24,
            2
        );
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return url($path);
        }

        return Storage::disk('public')->url($path);
    }

    private function serviceResponse(array $result)
    {
        $status = (bool) ($result['status'] ?? false);
        $message = (string) ($result['message'] ?? ($status ? 'Success' : 'Request failed'));
        $code = (int) ($result['code'] ?? ($status ? 200 : 422));
        $data = $result['data'] ?? null;

        $response = $status
            ? $this->success($data, $message)
            : $this->error($message, $code);

        if ($status && method_exists($response, 'setStatusCode')) {
            $response->setStatusCode($code);
        }

        return $response;
    }


    public function bikeIndex(Request $request)
    {
        $query = Vehicle::query()->customerVisibleBikeRental();

        foreach (['bike_type', 'fuel_type', 'transmission'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, strtolower(trim((string) $request->input($field))));
            }
        }

        $vehicles = $query->latest('id')->get()
            ->map(fn (Vehicle $vehicle) => $this->bikeVehicleData($vehicle))
            ->values();

        return $this->success($vehicles, 'Bike rental vehicles loaded');
    }

    public function bikeShow(int $id)
    {
        $vehicle = Vehicle::query()->customerVisibleBikeRental()->find($id);

        if (! $vehicle) {
            return $this->error('Bike not found', 404);
        }

        return $this->success($this->bikeVehicleData($vehicle), 'Bike details loaded');
    }

    public function bikeSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'pickup_location' => 'required|string|max:500',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date',
            'end_time' => 'required',
            'bike_type' => 'nullable|string|max:40',
            'plan_type' => 'nullable|in:hourly,daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        try {
            [$start, $end] = $this->bookingDates($request);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }

        $pickupLat = (float) $request->pickup_lat;
        $pickupLng = (float) $request->pickup_lng;

        $query = Vehicle::query()->with('transporter')->customerVisibleBikeRental();

        if ($request->filled('bike_type')) {
            $query->where('bike_type', strtolower(trim((string) $request->bike_type)));
        }

        $vehicles = $query->get()->map(function (Vehicle $vehicle) use ($pickupLat, $pickupLng, $start, $end) {
            $profile = $vehicle->transporter;
            if (! $profile) {
                return null;
            }

            $lat = $profile->pickup_latitude ?? $profile->pickup_lat;
            $lng = $profile->pickup_longitude ?? $profile->pickup_lng;
            if ($lat === null || $lng === null) {
                return null;
            }

            $distance = $this->distanceKm($pickupLat, $pickupLng, (float) $lat, (float) $lng);
            $radius = (float) ($profile->service_radius_km ?? 40);

            if ($distance > $radius || ! $this->isAvailable($vehicle->id, $start, $end)) {
                return null;
            }

            return $this->bikeVehicleData($vehicle, $distance);
        })->filter()->sortBy('distance_km')->values();

        return $this->success($vehicles, 'Available bikes loaded');
    }

    public function bikeCheckAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date',
            'end_time' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        try {
            [$start, $end] = $this->bookingDates($request);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }

        $vehicle = Vehicle::query()->customerVisibleBikeRental()->find((int) $request->vehicle_id);

        return $this->success([
            'vehicle_id' => (int) $request->vehicle_id,
            'available' => $vehicle !== null && $this->isAvailable((int) $request->vehicle_id, $start, $end),
        ], 'Availability checked');
    }

    public function bikeBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'pickup_location' => 'required|string|max:500',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date',
            'end_time' => 'required',
            'plan_type' => 'required|in:hourly,daily,weekly,monthly',
            'helmet_option' => 'required|in:none,one_included,two',
            'payment_type' => 'required|in:advance,full',
            'customer_note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $customer = $request->user();
        if (! $customer) {
            return $this->error('Please login again', 401);
        }

        try {
            [$start, $end] = $this->bookingDates($request);
            $booking = DB::transaction(function () use ($request, $customer, $start, $end) {
                $vehicle = Vehicle::query()
                    ->customerVisibleBikeRental()
                    ->lockForUpdate()
                    ->find((int) $request->vehicle_id);

                if (! $vehicle || ! $this->isAvailable($vehicle->id, $start, $end)) {
                    throw new \DomainException('Bike is not available for selected time');
                }

                $pricing = $this->calculateBikePricing(
                    $vehicle,
                    $start,
                    $end,
                    (string) $request->plan_type,
                    (string) $request->helmet_option
                );

                $data = [
                    'booking_no' => $this->generateBikeBookingNumber(),
                    'booking_source' => 'bike_rental',
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'transporter_profile_id' => $vehicle->transporter_profile_id,
                    'customer_name' => $customer->name,
                    'customer_mobile' => $customer->mobile ?? null,
                    'customer_email' => $customer->email,
                    'pickup_location' => trim((string) $request->pickup_location),
                    'pickup_latitude' => $request->pickup_latitude,
                    'pickup_longitude' => $request->pickup_longitude,
                    'start_datetime' => $start,
                    'end_datetime' => $end,
                    'plan_type' => $request->plan_type,
                    'booked_hours' => $pricing['booked_hours'],
                    'base_rent' => $pricing['base_rent'],
                    'helmet_option' => $request->helmet_option,
                    'helmet_charge' => $pricing['helmet_charge'],
                    'security_deposit' => $pricing['security_deposit'],
                    'discount_amount' => $pricing['discount_amount'],
                    'total_amount' => $pricing['total_amount'],
                    'paid_amount' => 0,
                    'remaining_amount' => $pricing['total_amount'],
                    'payment_type' => $request->payment_type,
                    'payment_status' => 'pending',
                    'booking_status' => 'pending_payment',
                    'status' => 'pending',
                    'customer_note' => $request->customer_note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $columns = array_flip(Schema::getColumnListing('self_drive_bookings'));
                $data = array_intersect_key($data, $columns);
                $id = DB::table('self_drive_bookings')->insertGetId($data);

                return DB::table('self_drive_bookings')->where('id', $id)->first();
            }, 3);

            return $this->success([
                'id' => $booking->id,
                'booking_id' => $booking->id,
                'booking_no' => $booking->booking_no,
                'booking_source' => 'bike_rental',
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
                'payment_type' => $booking->payment_type,
                'total_amount' => (float) $booking->total_amount,
                'paid_amount' => (float) $booking->paid_amount,
                'remaining_amount' => (float) $booking->remaining_amount,
            ], 'Bike booking created. Please complete payment.');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('Bike rental booking creation failed', ['exception' => $e]);
            return $this->error('Unable to create bike booking. Please try again.', 500);
        }
    }

    public function bikePendingBooking(Request $request)
    {
        $customer = $request->user();
        if (! $customer) {
            return $this->error('Please login again', 401);
        }

        $query = DB::table('self_drive_bookings')
            ->where('customer_id', $customer->id)
            ->whereNotIn('booking_status', ['completed', 'cancelled']);

        if (Schema::hasColumn('self_drive_bookings', 'booking_source')) {
            $query->where('booking_source', 'bike_rental');
        }

        $booking = $query->latest('id')->first();

        return $this->success($booking, $booking ? 'Pending booking loaded' : 'No pending booking');
    }

    public function bikeCancelBooking(Request $request, $bookingId)
    {
        $customer = $request->user();
        if (! $customer) {
            return $this->error('Please login again', 401);
        }

        $booking = DB::table('self_drive_bookings')
            ->where(function ($q) use ($bookingId) {
                $q->where('id', $bookingId)->orWhere('booking_no', $bookingId);
            })
            ->where('customer_id', $customer->id)
            ->first();

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        DB::table('self_drive_bookings')->where('id', $booking->id)->update([
            'booking_status' => 'cancelled',
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        return $this->success(['booking_id' => $booking->id], 'Booking cancelled');
    }

    private function generateBikeBookingNumber(): string
    {
        do {
            $bookingNumber = 'BR' . now()->format('YmdHis') . random_int(1000, 9999);
        } while (DB::table('self_drive_bookings')->where('booking_no', $bookingNumber)->exists());

        return $bookingNumber;
    }

    private function calculateBikePricing(Vehicle $vehicle, Carbon $start, Carbon $end, string $plan, string $helmet): array
    {
        $hours = max((int) ($vehicle->minimum_booking_hours ?? 1), (int) ceil($start->diffInMinutes($end) / 60));
        $hourly = (float) ($vehicle->hourly_price ?? 0);
        $daily = (float) ($vehicle->daily_price ?? ($hourly * 24));

        $base = match ($plan) {
            'hourly' => $hours * $hourly,
            'daily' => (int) ceil($hours / 24) * $daily,
            'weekly' => (int) ceil($hours / 168) * ($daily * 7),
            'monthly' => (int) ceil($hours / 720) * ($daily * 30),
        };

        $discountRate = match ($plan) {
            'weekly' => (float) ($vehicle->weekly_discount ?? 20),
            'monthly' => (float) ($vehicle->monthly_discount ?? 30),
            default => 0,
        };

        $discount = round($base * ($discountRate / 100), 2);
        $helmetCharge = $helmet === 'two' ? (float) ($vehicle->second_helmet_charge ?? 0) : 0;
        $security = (float) ($vehicle->security_deposit ?? 0);

        return [
            'booked_hours' => $hours,
            'base_rent' => round($base, 2),
            'discount_amount' => $discount,
            'helmet_charge' => $helmetCharge,
            'security_deposit' => $security,
            'total_amount' => round($base - $discount + $helmetCharge + $security, 2),
        ];
    }

    private function bikeVehicleData(Vehicle $vehicle, ?float $distance = null): array
    {
        // Return public image URLs so Flutter can render bike photos directly.
        $frontImageUrl = $this->publicImageUrl($vehicle->front_image ?? $vehicle->image ?? null);

        $galleryImages = array_values(array_filter([
            $frontImageUrl,
            $this->publicImageUrl($vehicle->back_image),
            $this->publicImageUrl($vehicle->left_side_image),
            $this->publicImageUrl($vehicle->right_side_image),
            $this->publicImageUrl($vehicle->front_left_image),
            $this->publicImageUrl($vehicle->front_right_image),
            $this->publicImageUrl($vehicle->interior_image),
            $this->publicImageUrl($vehicle->front_seats_image),
            $this->publicImageUrl($vehicle->rear_seats_image),
            $this->publicImageUrl($vehicle->boot_image),
        ]));

        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name ?? $this->vehicleName($vehicle),
            'brand' => $vehicle->brand ?? $vehicle->car_company_name,
            'model' => $vehicle->model ?? $vehicle->model_name,
            'registration_number' => $vehicle->vehicle_number ?? $vehicle->registration_number,
            'bike_type' => $vehicle->bike_type,
            'bike_category' => $vehicle->bike_category,
            'vehicle_type' => $vehicle->vehicle_type,
            'service_type' => $vehicle->service_type,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'gear_type' => $vehicle->gear_type,
            'engine_cc' => $vehicle->engine_cc,
            'hourly_price' => (float) ($vehicle->hourly_price ?? 0),
            'daily_price' => (float) ($vehicle->daily_price ?? 0),
            'weekly_price' => (float) ($vehicle->weekly_price ?? 0),
            'monthly_price' => (float) ($vehicle->monthly_price ?? 0),
            'weekly_discount' => (float) ($vehicle->weekly_discount ?? 20),
            'monthly_discount' => (float) ($vehicle->monthly_discount ?? 30),
            'security_deposit' => (float) ($vehicle->security_deposit ?? 0),
            'minimum_booking_hours' => (int) ($vehicle->minimum_booking_hours ?? 1),
            'helmet_policy' => $vehicle->helmet_policy,
            'second_helmet_charge' => (float) ($vehicle->second_helmet_charge ?? 0),

            // Keep both keys for backward compatibility with existing Flutter screens.
            'image_url' => $frontImageUrl,
            'front_image_url' => $frontImageUrl,
            'back_image_url' => $this->publicImageUrl($vehicle->back_image),
            'left_side_image_url' => $this->publicImageUrl($vehicle->left_side_image),
            'right_side_image_url' => $this->publicImageUrl($vehicle->right_side_image),
            'front_left_image_url' => $this->publicImageUrl($vehicle->front_left_image),
            'front_right_image_url' => $this->publicImageUrl($vehicle->front_right_image),
            'interior_image_url' => $this->publicImageUrl($vehicle->interior_image),
            'front_seats_image_url' => $this->publicImageUrl($vehicle->front_seats_image),
            'rear_seats_image_url' => $this->publicImageUrl($vehicle->rear_seats_image),
            'boot_image_url' => $this->publicImageUrl($vehicle->boot_image),
            'gallery_images' => $galleryImages,

            'distance_km' => $distance === null ? null : round($distance, 2),
            'transporter_profile_id' => $vehicle->transporter_profile_id,
        ];
    }

}