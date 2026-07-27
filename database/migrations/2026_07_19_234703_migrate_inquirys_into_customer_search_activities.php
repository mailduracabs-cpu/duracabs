<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Purani inquiries ko Customer Leads me migrate karein.
     */
    public function up(): void
    {
        if (
            ! Schema::hasTable('inquirys') ||
            ! Schema::hasTable('customer_search_activities')
        ) {
            return;
        }

        DB::table('inquirys')
            ->orderBy('id')
            ->chunkById(500, function ($inquiries): void {
                foreach ($inquiries as $inquiry) {
                    $legacySessionId = 'legacy-inquiry-' . $inquiry->id;

                    // Migration dobara run hone par duplicate lead na bane.
                    $alreadyMigrated = DB::table('customer_search_activities')
                        ->where('session_id', $legacySessionId)
                        ->exists();

                    if ($alreadyMigrated) {
                        continue;
                    }

                    $customerName = trim(
                        (string) ($inquiry->name ?? 'Dura Cabs Customer')
                    );

                    if ($customerName === '') {
                        $customerName = 'Dura Cabs Customer';
                    }

                    $serviceType = trim(
                        (string) ($inquiry->service ?? 'one_way')
                    );

                    if ($serviceType === '') {
                        $serviceType = 'one_way';
                    }

                    $leadNotes = $this->buildLeadNotes($inquiry);

                    DB::table('customer_search_activities')->insert([
                        'uuid' => (string) Str::uuid(),

                        'user_id' => null,
                        'mobile' => $inquiry->mobile ?? null,
                        'customer_name' => $customerName,
                        'customer_email' => $inquiry->email ?? null,

                        'session_id' => $legacySessionId,
                        'device_id' => null,

                        'source' => 'legacy_inquiry',
                        'platform' => 'website',

                        'module' => 'inquiry',
                        'service_type' => $serviceType,
                        'stage' => 'inquiry_submitted',

                        'pickup_location' => $inquiry->address ?? null,
                        'pickup_city' => $inquiry->city ?? null,
                        'pickup_state' => $inquiry->state ?? null,
                        'pickup_pincode' => $inquiry->pincode ?? null,

                        'vehicle_name' => $inquiry->cab_name ?? null,

                        'currency' => 'INR',

                        'search_status' => 'completed',
                        'is_converted' => false,
                        'is_abandoned' => false,

                        'intent_score' => 50,
                        'priority' => 'medium',
                        'lead_status' => 'new',
                        'lead_notes' => $leadNotes,

                        'admin_notified' => false,
                        'whatsapp_notified' => false,
                        'sms_notified' => false,
                        'push_notified' => false,
                        'email_notified' => false,

                        'metadata' => json_encode([
                            'legacy_source' => 'inquirys',
                            'legacy_inquiry_id' => $inquiry->id,
                            'inquiry_type' => $inquiry->type ?? null,
                            'organization_name' => $inquiry->oraganization_name ?? null,
                            'original_message' => $inquiry->message ?? null,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

                        'searched_at' => $inquiry->created_at ?? now(),
                        'last_activity_at' => $inquiry->updated_at
                            ?? $inquiry->created_at
                            ?? now(),

                        'created_at' => $inquiry->created_at ?? now(),
                        'updated_at' => $inquiry->updated_at
                            ?? $inquiry->created_at
                            ?? now(),
                    ]);
                }
            }, 'id');
    }

    /**
     * Sirf isi migration se create hui legacy leads remove karein.
     */
    public function down(): void
    {
        if (! Schema::hasTable('customer_search_activities')) {
            return;
        }

        DB::table('customer_search_activities')
            ->where('source', 'legacy_inquiry')
            ->where('session_id', 'like', 'legacy-inquiry-%')
            ->delete();
    }

    /**
     * Inquiry ki useful information lead notes me store karein.
     */
    private function buildLeadNotes(object $inquiry): ?string
    {
        $notes = [];

        if (! empty($inquiry->message)) {
            $notes[] = 'Message: ' . trim((string) $inquiry->message);
        }

        if (! empty($inquiry->oraganization_name)) {
            $notes[] = 'Organization: '
                . trim((string) $inquiry->oraganization_name);
        }

        if (! empty($inquiry->cab_name)) {
            $notes[] = 'Cab/Vehicle: '
                . trim((string) $inquiry->cab_name);
        }

        if (! empty($inquiry->type)) {
            $notes[] = 'Inquiry Type: '
                . trim((string) $inquiry->type);
        }

        return $notes === [] ? null : implode(PHP_EOL, $notes);
    }
};