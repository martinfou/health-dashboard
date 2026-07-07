<?php

namespace App\Services;

use App\Models\PriceHistory;
use Illuminate\Support\Facades\DB;

class PriceHeatmapService
{
    /**
     * Generate a weekly price heatmap for a product+store over N months.
     *
     * Returns array of week entries with percentile rating.
     */
    public function generate(?string $product = null, ?int $storeId = null, int $months = 12): array
    {
        $query = PriceHistory::with('store')
            ->where('scraped_at', '>=', now()->subMonths($months));

        if ($product) {
            $query->where('product', 'like', "%{$product}%");
        }

        if ($storeId) {
            $query->where('grocery_store_id', $storeId);
        }

        $records = $query->orderBy('scraped_at')->get();

        if ($records->isEmpty()) {
            return [];
        }

        // Group by ISO week
        $weekly = $records->groupBy(fn ($r) => $r->scraped_at->format('Y-\WW'));

        $weeks = [];
        foreach ($weekly as $week => $items) {
            $prices = $items->pluck('sale_price')->filter()->values();
            if ($prices->isEmpty()) continue;

            $min = $prices->min();
            $max = $prices->max();
            $avg = round($prices->avg(), 2);
            $count = $prices->count();

            // Determine percentile: how good is the average price this week?
            // 0 = best price (lowest), 100 = worst price (highest)
            $allPrices = $records->pluck('sale_price')->filter()->sort()->values();
            $belowCount = $allPrices->filter(fn ($p) => $p <= $avg)->count();
            $percentile = $allPrices->isNotEmpty()
                ? round(($belowCount / $allPrices->count()) * 100)
                : 50;

            // Color coding based on percentile
            $color = match (true) {
                $percentile < 20 => 'green',      // Best prices
                $percentile < 40 => 'light-green',
                $percentile < 60 => 'yellow',
                $percentile < 80 => 'orange',
                default          => 'red',         // Worst prices
            };

            $weeks[] = [
                'week' => $week,
                'date' => $items->first()->scraped_at->format('Y-m-d'),
                'avg_price' => $avg,
                'min_price' => $min,
                'max_price' => $max,
                'count' => $count,
                'percentile' => $percentile,
                'color' => $color,
                'label' => $this->colorLabel($color),
            ];
        }

        // Sort by week ascending
        usort($weeks, fn ($a, $b) => strcmp($a['week'], $b['week']));

        return $weeks;
    }

    private function colorLabel(string $color): string
    {
        return match ($color) {
            'green'       => '🔥 Excellent prix',
            'light-green' => '👍 Bon prix',
            'yellow'      => '😐 Prix moyen',
            'orange'      => '⚠️ Prix élevé',
            'red'         => '❌ Prix très élevé',
            default       => 'Données insuffisantes',
        };
    }
}
