<?php

namespace App\Services;

use App\Models\PriceHistory;
use Illuminate\Support\Facades\DB;

class PricePredictorService
{
    /**
     * Predict next month's price for a product in a store.
     *
     * Uses simple moving average of the same month across prior years
     * plus recent trend analysis.
     */
    public function predict(?string $product = null, ?int $storeId = null): array
    {
        $query = PriceHistory::with('store')
            ->where('scraped_at', '>=', now()->subMonths(12));

        if ($product) {
            $query->where('product', 'like', "%{$product}%");
        }

        if ($storeId) {
            $query->where('grocery_store_id', $storeId);
        }

        $records = $query->orderBy('scraped_at')->get();

        if ($records->isEmpty()) {
            return [
                'product' => $product ?? 'N/A',
                'store' => null,
                'current_price' => null,
                'next_month_prediction' => null,
                'trend' => 'unknown',
                'best_month' => null,
                'worst_month' => null,
                'recommendation' => 'Pas assez de données pour une prédiction.',
                'monthly_data' => [],
            ];
        }

        // Group by month
        $byMonth = $records->groupBy(fn ($r) => $r->scraped_at->format('Y-m'));

        $monthlyData = [];
        foreach ($byMonth as $ym => $items) {
            $avgPrice = $items->avg('sale_price');
            $monthlyData[] = [
                'month' => $ym,
                'avg_price' => round($avgPrice, 2),
                'min_price' => round($items->min('sale_price'), 2),
                'max_price' => round($items->max('sale_price'), 2),
                'count' => $items->count(),
            ];
        }

        usort($monthlyData, fn ($a, $b) => strcmp($a['month'], $b['month']));

        $currentPrice = !empty($monthlyData) ? end($monthlyData)['avg_price'] : null;

        // Calculate trend from last 3 months
        $last3 = array_slice($monthlyData, -3);
        $trend = 'stable';
        if (count($last3) >= 2) {
            $first = $last3[0]['avg_price'];
            $last = end($last3)['avg_price'];
            $change = (($last - $first) / $first) * 100;

            $trend = match (true) {
                $change > 5 => 'up',
                $change < -5 => 'down',
                default => 'stable',
            };
        }

        // Find best and worst months (seasonal pattern)
        $byCalendarMonth = collect($monthlyData)->groupBy(fn ($d) => substr($d['month'], 5, 2));
        $monthNames = ['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril',
                       '05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août',
                       '09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'];

        $bestMonth = null;
        $worstMonth = null;
        $bestPrice = PHP_FLOAT_MAX;
        $worstPrice = 0;

        foreach ($byCalendarMonth as $m => $entries) {
            $monthAvg = collect($entries)->avg('avg_price');
            if ($monthAvg < $bestPrice) {
                $bestPrice = $monthAvg;
                $bestMonth = $monthNames[$m] ?? $m;
            }
            if ($monthAvg > $worstPrice) {
                $worstPrice = $monthAvg;
                $worstMonth = $monthNames[$m] ?? $m;
            }
        }

        // Predict next month using simple linear regression on last 6 months
        $last6 = array_slice($monthlyData, -6);
        $prediction = $currentPrice;
        if (count($last6) >= 3) {
            $x = range(1, count($last6));
            $y = array_column($last6, 'avg_price');
            $n = count($x);

            $sumX = array_sum($x);
            $sumY = array_sum($y);
            $sumXY = 0;
            $sumX2 = 0;

            for ($i = 0; $i < $n; $i++) {
                $sumXY += $x[$i] * $y[$i];
                $sumX2 += $x[$i] * $x[$i];
            }

            $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
            $intercept = ($sumY - $slope * $sumX) / $n;

            $prediction = round($intercept + $slope * ($n + 1), 2);
        }

        // Generate recommendation
        $rec = $this->generateRecommendation($trend, $currentPrice, $prediction, $bestMonth);

        $store = $records->first()->store ?? null;

        return [
            'product' => $product ?? ($records->first()->product ?? 'N/A'),
            'store' => $store ? $store->name : null,
            'current_price' => $currentPrice,
            'next_month_prediction' => $prediction,
            'trend' => $trend,
            'best_month' => $bestMonth,
            'worst_month' => $worstMonth,
            'recommendation' => $rec,
            'monthly_data' => $monthlyData,
        ];
    }

    private function generateRecommendation(string $trend, ?float $current, ?float $prediction, ?string $bestMonth): string
    {
        if ($current === null) {
            return 'Pas assez de données pour une recommandation.';
        }

        return match ($trend) {
            'up' => "📈 Les prix sont en hausse saisonnière. En {$bestMonth}, prévoir +" .
                    ($prediction && $current ? round((($prediction - $current) / $current) * 100) : '5') .
                    "%. Achetez maintenant avant la hausse.",
            'down' => "📉 Les prix sont en baisse. Attendez encore quelques semaines pour profiter du meilleur prix.",
            'stable' => "➡️ Les prix sont stables. Bon moment pour acheter. Le meilleur mois est généralement {$bestMonth}.",
            default => "Achetez maintenant — prix compétitif.",
        };
    }
}
