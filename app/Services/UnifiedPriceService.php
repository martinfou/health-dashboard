<?php

namespace App\Services;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use Illuminate\Support\Facades\DB;

class UnifiedPriceService
{
    /**
     * Search for a product across all stores and return unified pricing.
     * Fuzzy matches product names across all current deals.
     */
    public function search(?string $product = null, ?string $category = null, string $sortBy = 'price'): array
    {
        $query = GroceryDeal::current()->with('store');

        if ($product) {
            $query->where('product', 'like', "%{$product}%");
        }

        if ($category) {
            $query->where('category', $category);
        }

        $deals = $query->get();

        if ($deals->isEmpty()) {
            return [];
        }

        // Group by normalized product name (if no search, group all)
        if ($product) {
            // Exact fuzzy match grouping
            $results = $this->groupByProductMatch($deals, $product);
        } else {
            // Group by exact product name
            $results = $deals->groupBy('product')
                ->map(function ($items, $name) {
                    return $this->buildProductEntry($items, $name);
                })
                ->values()
                ->toArray();
        }

        // Sort
        usort($results, function ($a, $b) use ($sortBy) {
            return match ($sortBy) {
                'price' => $a['best_price'] <=> $b['best_price'],
                'savings' => ($b['max_savings'] ?? 0) <=> ($a['max_savings'] ?? 0),
                'store' => strcmp($a['best_store'] ?? '', $b['best_store'] ?? ''),
                default => $a['best_price'] <=> $b['best_price'],
            };
        });

        return $results;
    }

    /**
     * Get all categories with counts.
     */
    public function getCategories(): array
    {
        return GroceryDeal::current()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->pluck('count', 'category')
            ->toArray();
    }

    private function groupByProductMatch($deals, string $searchTerm): array
    {
        $results = [];

        foreach ($deals->groupBy('product') as $name => $items) {
            // Score relevancy
            $similar = similar_text(mb_strtolower($searchTerm), mb_strtolower($name), $pct);
            if ($pct < 30) continue;

            $results[] = $this->buildProductEntry($items, $name);
        }

        return $results;
    }

    private function buildProductEntry($items, string $name): array
    {
        $bestPrice = $items->min('price');
        $cheapestItems = $items->where('price', $bestPrice);
        $bestStore = $cheapestItems->first()->store->name ?? 'N/A';
        $maxSavings = $items->max(fn ($d) => $d->savings());
        $categories = $items->pluck('category')->unique()->values()->toArray();
        $stores = $items->pluck('store.name')->unique()->values()->toArray();

        $availabilities = $items->map(function ($deal) {
            return [
                'store_id' => $deal->grocery_store_id,
                'store' => $deal->store->name ?? 'Inconnu',
                'price' => (float) $deal->price,
                'regular_price' => $deal->regular_price ? (float) $deal->regular_price : null,
                'savings' => $deal->savings(),
                'savings_pct' => $deal->savingsPercent(),
                'unit' => $deal->unit,
                'valid_until' => $deal->valid_until?->format('d/m/Y'),
                'store_brand' => $deal->store_brand,
                'is_bio' => $deal->is_bio,
            ];
        })->sortBy('price')->values()->toArray();

        return [
            'product' => $name,
            'best_price' => (float) $bestPrice,
            'best_store' => $bestStore,
            'max_savings' => $maxSavings,
            'categories' => $categories,
            'stores' => $stores,
            'all_availabilities' => $availabilities,
        ];
    }
}
