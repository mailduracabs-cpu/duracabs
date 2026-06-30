<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InquiryService
{
    public function create(array $data): array
    {
        if (!Schema::hasTable('inquirys')) {
            return [
                'status' => false,
                'message' => 'Inquiry table not found.',
                'code' => 500,
            ];
        }

        $mobile = $data['mobile'] ?? null;
        $service = $data['service'] ?? 'one_way';
        $type = $data['type'] ?? 'quick_inquiry';
        $message = trim($data['message'] ?? '');
        $city = trim($data['city'] ?? '');
        $address = trim($data['address'] ?? '');

        $duplicateKey = 'inquiry_lock_' . md5(
            $mobile . '|' . $service . '|' . $type . '|' . $message . '|' . $city . '|' . $address
        );

        if (!Cache::add($duplicateKey, true, now()->addSeconds(60))) {
            $latest = DB::table('inquirys')
                ->where('mobile', $mobile)
                ->where('created_at', '>=', now()->subSeconds(60))
                ->orderByDesc('id')
                ->first();

            return [
                'status' => true,
                'message' => 'Inquiry already submitted. Our team will contact you shortly.',
                'data' => $latest,
            ];
        }

        try {
            $recentDuplicate = DB::table('inquirys')
                ->where('mobile', $mobile)
                ->where('service', $service)
                ->where('type', $type)
                ->where('created_at', '>=', now()->subSeconds(60))
                ->orderByDesc('id')
                ->first();

            if ($recentDuplicate) {
                return [
                    'status' => true,
                    'message' => 'Inquiry already submitted. Our team will contact you shortly.',
                    'data' => $recentDuplicate,
                ];
            }

            $id = DB::table('inquirys')->insertGetId([
                'name' => $data['name'] ?? 'Dura Cabs Customer',
                'cab_name' => $data['cab_name'] ?? null,
                'mobile' => $mobile,
                'email' => $data['email'] ?? null,
                'message' => $data['message'] ?? null,
                'oraganization_name' => $data['oraganization_name'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'type' => $type,
                'service' => $service,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'status' => true,
                'message' => 'Inquiry submitted successfully',
                'data' => DB::table('inquirys')->where('id', $id)->first(),
            ];

        } catch (\Throwable $e) {
            Cache::forget($duplicateKey);

            Log::error('V1 Inquiry Create Error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'status' => false,
                'message' => 'Unable to submit inquiry. Please try again.',
                'code' => 500,
                'errors' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    public function myInquiries(?string $mobile = null, ?string $email = null, int $limit = 20)
    {
        if (!Schema::hasTable('inquirys')) {
            return [];
        }

        $query = DB::table('inquirys')->orderByDesc('id');

        if ($mobile) {
            $query->where('mobile', $mobile);
        }

        if ($email) {
            $query->orWhere('email', $email);
        }

        return $query->limit(min(max($limit, 1), 100))->get();
    }
}
