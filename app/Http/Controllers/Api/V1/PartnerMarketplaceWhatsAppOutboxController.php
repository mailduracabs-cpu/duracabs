<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PartnerMarketplaceSchedule;

final class PartnerMarketplaceWhatsAppOutboxController extends Controller
{
    private function input(Request $request): array
    {
        abort_if(strlen($request->getContent()) > 20000, 413);
        $secret = (string) config('live_bookings.qr_secret');
        $provided = (string) $request->header('X-Hub-Signature-256');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        abort_unless(
            $request->header('X-Taxi-Sathi-Bridge') === 'group-broadcast-v1'
            && strlen($secret) >= 32 && hash_equals($expected, $provided),
            403, 'Invalid bridge signature.'
        );
        $input = $request->json()->all();
        $timestamp = (int) ($input['timestamp'] ?? 0);
        abort_unless($timestamp > 0 && abs(time() - $timestamp) <= 60, 403, 'Expired bridge request.');
        return $input;
    }

    public function pull(Request $request)
    {
        $input = $this->input($request);
        if (!config('taxi_sathi_bridge.group_broadcast_enabled')) {
            return response()->json(['status' => true, 'data' => ['jobs' => []]]);
        }
        $limit = max(1, min(10, (int) ($input['limit'] ?? 5)));
        $jobs = DB::transaction(function () use ($limit) {
            $rows = DB::table('partner_market_whatsapp_group_outbox')
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('status', 'pending')->where(fn ($a) => $a->whereNull('available_at')->orWhere('available_at', '<=', now()));
                    })->orWhere(function ($q) {
                        $q->where('status', 'claimed')->where('claimed_at', '<=', now()->subHours(2));
                    });
                })->orderBy('id')->limit($limit)->lockForUpdate()->get();
            $jobs = [];
            foreach ($rows as $row) {
                $post = DB::table('partner_market_posts')->find($row->post_id);
                if (!$post || $post->status !== 'open' || (int) $post->revision !== (int) $row->post_revision || PartnerMarketplaceSchedule::due($post)) {
                    DB::table('partner_market_whatsapp_group_outbox')->where('id', $row->id)->update([
                        'status' => 'obsolete', 'completed_at' => now(), 'updated_at' => now(),
                    ]);
                    continue;
                }
                $lease = bin2hex(random_bytes(32));
                DB::table('partner_market_whatsapp_group_outbox')->where('id', $row->id)->update([
                    'status' => 'claimed', 'claimed_at' => now(), 'lease_token' => $lease,
                    'attempts' => DB::raw('attempts + 1'), 'updated_at' => now(),
                ]);
                $payload = json_decode((string) $row->payload, true, 32, JSON_THROW_ON_ERROR);
                $jobs[] = ['id' => (int) $row->id, 'lease_token' => $lease] + $payload;
            }
            return $jobs;
        }, 3);
        return response()->json(['status' => true, 'data' => ['jobs' => $jobs]]);
    }

    public function acknowledge(Request $request)
    {
        $input = $this->input($request);
        $jobId = (int) ($input['job_id'] ?? 0);
        $result = (string) ($input['result'] ?? '');
        $lease = (string) ($input['lease_token'] ?? '');
        abort_unless(strlen($lease) === 64 && ctype_xdigit($lease), 422, 'Invalid lease.');
        abort_unless($jobId > 0 && in_array($result, ['delivered', 'partial', 'retry'], true), 422, 'Invalid result.');
        $error = mb_substr(trim((string) ($input['error'] ?? '')), 0, 1000);
        $values = $result === 'retry'
            ? ['status' => 'pending', 'available_at' => now()->addMinutes(5), 'claimed_at' => null,
                'last_error' => $error ?: 'bridge_retry_requested', 'updated_at' => now()]
            : ['status' => $result, 'completed_at' => now(), 'last_error' => $error ?: null, 'updated_at' => now()];
        DB::table('partner_market_whatsapp_group_outbox')->where('id', $jobId)->where('status', 'claimed')->where('lease_token', $lease)->update($values);
        return response()->json(['status' => true]);
    }
}
