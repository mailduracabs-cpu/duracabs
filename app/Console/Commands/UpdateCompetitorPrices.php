<?php

namespace App\Console\Commands;

use App\Services\CompetitorPricingService;
use Illuminate\Console\Command;

class UpdateCompetitorPrices extends Command
{
    protected $signature = 'competitor-prices:update
        {--apply : Apply verified prices to the prices table}
        {--limit=0 : Limit routes for a controlled test}';

    protected $description = 'Compare public one-way competitor fares and update DuraCabs category prices safely.';

    public function handle(CompetitorPricingService $service): int
    {
        $apply = (bool) $this->option('apply');
        $limit = max(0, (int) $this->option('limit'));
        $this->info($apply ? 'Applying verified competitor pricing...' : 'Running competitor pricing dry-run...');

        $summary = $service->updateAll($apply, $limit);
        $this->table(['Routes', 'Checked', 'Updated', 'Skipped', 'Failed'], [[
            $summary['routes'],
            $summary['checked'],
            $summary['updated'],
            $summary['skipped'],
            $summary['failed'],
        ]]);

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
