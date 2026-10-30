<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PartnerMarketplaceReservations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcilePartnerMarketPayments extends Command
{
    protected $signature = 'partner-market:reconcile-payments {--limit=100 : Maximum expired reservations to check}';
    protected $description = 'Verify expired marketplace payment reservations before safely releasing them';

    public function handle(): int
    {
        $ids = DB::table('partner_market_posts as p')->join('partner_market_secure_payments as l', 'l.id', '=', 'p.active_payment_id')
            ->where('l.status', 'pending')->where('l.reserved_until', '<=', now())
            ->orderBy('l.reserved_until')->limit(max(1, min(1000, (int) $this->option('limit'))))->pluck('p.id');
        $failed = 0;
        foreach ($ids as $id) {
            try { PartnerMarketplaceReservations::reconcile((int) $id); }
            catch (\Throwable $e) { $failed++; Log::warning('Marketplace reservation verification deferred', ['post_id' => $id, 'error' => $e->getMessage()]); }
        }
        $this->info('Checked ' . $ids->count() . ' expired reservations; ' . $failed . ' deferred. Uncertain bank payments remain locked.');
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
