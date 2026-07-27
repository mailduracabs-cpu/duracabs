<?php

namespace App\Services;

use App\Models\CustomerSearchActivity;
use Illuminate\Support\Collection;

class AiRecommendationService
{
    public function recommendForCustomer(?int $userId, ?string $mobile, int $limit = 5): array
    {
        $query = CustomerSearchActivity::query();

        $query->where(function ($q) use ($userId, $mobile): void {
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }

            if ($mobile) {
                $q->orWhere('mobile', $mobile);
            }
        });

        $history = $query->latest('id')->limit(50)->get();

        return $this->buildRecommendations($history, $limit);
    }

    private function buildRecommendations(Collection $history, int $limit): array
    {
        if ($history->isEmpty()) {
            return [];
        }

        $services = $history
            ->pluck('service_type')
            ->filter()
            ->countBy()
            ->sortDesc();

        $routes = $history
            ->map(fn ($item) => trim(
                ((string) ($item->pickup_city ?? $item->pickup_location ?? ''))
                . ' → ' .
                ((string) ($item->drop_city ?? $item->drop_location ?? ''))
            ))
            ->filter(fn ($route) => $route !== '→')
            ->countBy()
            ->sortDesc();

        $recommendations = [];

        foreach ($services->take($limit) as $service => $score) {
            $recommendations[] = [
                'type' => 'service',
                'value' => $service,
                'score' => (int) $score,
                'reason' => 'Frequently searched service',
            ];
        }

        foreach ($routes->take($limit) as $route => $score) {
            $recommendations[] = [
                'type' => 'route',
                'value' => $route,
                'score' => (int) $score,
                'reason' => 'Frequently searched route',
            ];
        }

        usort($recommendations, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($recommendations, 0, $limit);
    }
}
