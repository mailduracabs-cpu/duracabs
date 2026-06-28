<?php

namespace App\Services;

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

        try {
            $id = DB::table('inquirys')->insertGetId([
                'name' => $data['name'] ?? 'Dura Cabs Customer',
                'cab_name' => $data['cab_name'] ?? null,
                'mobile' => $data['mobile'],
                'email' => $data['email'] ?? null,
                'message' => $data['message'] ?? null,
                'oraganization_name' => $data['oraganization_name'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'type' => $data['type'] ?? 'quick_inquiry',
                'service' => $data['service'] ?? 'one_way',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'status' => true,
                'message' => 'Inquiry submitted successfully',
                'data' => DB::table('inquirys')->where('id', $id)->first(),
            ];
        } catch (\Throwable $e) {
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
