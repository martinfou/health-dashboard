<?php

namespace App\Services;

use App\Models\GroceryStore;
use App\Models\PriceHistory;
use App\Models\PriceStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GroceryAnnualStatsService
{
    /**
     * Full annual report: all stores compared over the last 12 months.
     */
    public function fullReport(): array
    {
        $stores = GroceryStore::all();
        $startOfYear = Carbon::now()->startOfYear();

        return [
            'year' => Carbon::now()->year,
            'store_ranking' => $this->storeRanking($stores, $startOfYear),
            'monthly_trends' => $this->monthlyTrends($stores, $startOfYear),
            'category_leaders' => $this->categoryLeaders($stores, $startOfYear),
            'best_deals_overall' => $this->bestDealsOverall($stores, $startOfYear),
            'seasonal_insights' => $this->seasonalInsights($stores, $startOfYear),
        ];
    }

    /**
     * Rank stores by overall savings, deal count, and avg savings %.
     */
    public function storeRanking(Collection $stores, Carbon $since): array
    {
        $ranking = [];

        foreach ($stores as $store) {
            $yearDeals = PriceHistory::where('grocery_store_id', $store->id)
                ->where('scraped_at', '>=', $since)
                ->get();

            if ($yearDeals->isEmpty()) {
                $ranking[] = [
                    'store' => $store->name,
                    'store_id' => $store->id,
                    'total_deals' => 0,
                    'total_savings' => 0,
                    'avg_savings_pct' => 0,
                    'avg_sale_price' => 0,
                    'best_deal' => null,
                    'score' => 0,
                ];
                continue;
            }

            $savings = $yearDeals->map(fn($d) => max(0, ($d->regular_price ?? 0) - $d->sale_price));
            $savingsPct = $yearDeals->filter(fn($d) => ($d->regular_price ?? 0) > 0)
                ->map(fn($d) => round((($d->regular_price - $d->sale_price) / $d->regular_price) * 100, 1));

            $bestDeal = $yearDeals->sortByDesc(fn($d) => ($d->regular_price ?? 0) - $d->sale_price)->first();

            $totalSavings = round($savings->sum(), 2);
            $avgPct = $savingsPct->isNotEmpty() ? round($savingsPct->avg(), 1) : 0;

            // Composite score: deals * savings * avg_pct (normalized)
            $score = $yearDeals->count() > 0
                ? round($yearDeals->count() * $totalSavings * ($avgPct / 100 + 1), 2)
                : 0;

            $ranking[] = [
                'store' => $store->name,
                'store_id' => $store->id,
                'total_deals' => $yearDeals->count(),
                'total_savings' => $totalSavings,
                'avg_savings_pct' => $avgPct,
                'avg_sale_price' => round($yearDeals->avg('sale_price'), 2),
                'best_deal' => $bestDeal ? [
                    'product' => $bestDeal->product,
                    'savings' => round(($bestDeal->regular_price ?? 0) - $bestDeal->sale_price, 2),
                    'sale_price' => $bestDeal->sale_price,
                ] : null,
                'score' => $score,
            ];
        }

        return collect($ranking)->sortByDesc('score')->values()->toArray();
    }

    /**
     * Monthly savings trends per store.
     */
    public function monthlyTrends(Collection $stores, Carbon $since): array
    {
        $trends = [];
        $months = [];

        // Build month labels
        $cursor = $since->copy();
        while ($cursor <= Carbon::now()) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        foreach ($stores as $store) {
            $storeDeals = PriceHistory::where('grocery_store_id', $store->id)
                ->where('scraped_at', '>=', $since)
                ->get()
                ->groupBy(fn($d) => Carbon::parse($d->scraped_at)->format('Y-m'));

            $monthData = [];
            foreach ($months as $month) {
                $monthDeals = $storeDeals->get($month, collect());
                $monthData[] = $monthDeals->isNotEmpty() ? [
                    'deals' => $monthDeals->count(),
                    'avg_savings_pct' => round(
                        $monthDeals->filter(fn($d) => ($d->regular_price ?? 0) > 0)
                            ->map(fn($d) => (($d->regular_price - $d->sale_price) / $d->regular_price) * 100)
                            ->avg() ?? 0, 1
                    ),
                    'total_savings' => round(
                        $monthDeals->sum(fn($d) => max(0, ($d->regular_price ?? 0) - $d->sale_price)), 2
                    ),
                ] : null;
            }

            $trends[] = [
                'store' => $store->name,
                'months' => $months,
                'data' => $monthData,
            ];
        }

        return [
            'months' => $months,
            'stores' => $trends,
        ];
    }

    /**
     * Which store leads in each grocery category.
     */
    public function categoryLeaders(Collection $stores, Carbon $since): array
    {
        $allCategories = PriceHistory::where('scraped_at', '>=', $since)
            ->whereNotNull('category')
            ->distinct('category')
            ->pluck('category');

        $leaders = [];

        foreach ($allCategories as $category) {
            $best = null;
            $bestAvgSavings = -1;

            foreach ($stores as $store) {
                $catDeals = PriceHistory::where('grocery_store_id', $store->id)
                    ->where('category', $category)
                    ->where('scraped_at', '>=', $since)
                    ->get();

                if ($catDeals->isEmpty()) continue;

                $avgSavings = $catDeals->filter(fn($d) => ($d->regular_price ?? 0) > 0)
                    ->map(fn($d) => (($d->regular_price - $d->sale_price) / $d->regular_price) * 100)
                    ->avg() ?? 0;

                $totalSavings = $catDeals->sum(fn($d) => max(0, ($d->regular_price ?? 0) - $d->sale_price));

                if ($avgSavings > $bestAvgSavings) {
                    $bestAvgSavings = $avgSavings;
                    $best = [
                        'store' => $store->name,
                        'avg_savings_pct' => round($avgSavings, 1),
                        'total_deals' => $catDeals->count(),
                        'total_savings' => round($totalSavings, 2),
                    ];
                }
            }

            $leaders[] = [
                'category' => $category,
                'best_store' => $best ? $best['store'] : '—',
                'avg_savings_pct' => $best ? $best['avg_savings_pct'] : 0,
                'total_deals' => $best ? $best['total_deals'] : 0,
            ];
        }

        return collect($leaders)->sortByDesc('avg_savings_pct')->values()->toArray();
    }

    /**
     * Top 10 best deals of the year overall.
     */
    public function bestDealsOverall(Collection $stores, Carbon $since): array
    {
        $allDeals = collect([]);

        foreach ($stores as $store) {
            $storeDeals = PriceHistory::where('grocery_store_id', $store->id)
                ->where('scraped_at', '>=', $since)
                ->whereNotNull('regular_price')
                ->whereColumn('regular_price', '>', 'sale_price')
                ->get()
                ->map(fn($d) => [
                    'store' => $store->name,
                    'product' => $d->product,
                    'category' => $d->category,
                    'sale_price' => $d->sale_price,
                    'regular_price' => $d->regular_price,
                    'savings' => round(($d->regular_price - $d->sale_price), 2),
                    'savings_pct' => round((($d->regular_price - $d->sale_price) / $d->regular_price) * 100, 1),
                    'date' => Carbon::parse($d->scraped_at)->format('d M'),
                ]);

            $allDeals = $allDeals->merge($storeDeals);
        }

        return $allDeals->sortByDesc('savings')->take(10)->values()->toArray();
    }

    /**
     * Seasonal insights: best month to buy by category.
     */
    public function seasonalInsights(Collection $stores, Carbon $since): array
    {
        $allDeals = PriceHistory::where('scraped_at', '>=', $since)
            ->whereNotNull('category')
            ->whereNotNull('regular_price')
            ->whereColumn('regular_price', '>', 'sale_price')
            ->get();

        if ($allDeals->isEmpty()) return [];

        $byCategoryMonth = $allDeals->groupBy(fn($d) => $d->category . '|' . Carbon::parse($d->scraped_at)->format('m'));

        $insights = [];

        foreach ($byCategoryMonth as $key => $deals) {
            [$category, $monthNum] = explode('|', $key);
            $monthName = Carbon::createFromFormat('m', $monthNum)->format('F');

            $avgSavings = $deals->map(fn($d) => (($d->regular_price - $d->sale_price) / $d->regular_price) * 100)->avg();

            if (!isset($insights[$category])) {
                $insights[$category] = [
                    'category' => $category,
                    'best_month' => $monthName,
                    'best_avg_savings_pct' => $avgSavings,
                ];
            } elseif ($avgSavings > $insights[$category]['best_avg_savings_pct']) {
                $insights[$category]['best_month'] = $monthName;
                $insights[$category]['best_avg_savings_pct'] = round($avgSavings, 1);
            }
        }

        return collect($insights)->sortByDesc('best_avg_savings_pct')->values()->toArray();
    }
}
