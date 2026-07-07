<?php

namespace App\Services;

use App\Models\GroceryDeal;
use Illuminate\Support\Collection;

class ShoppingListService
{
    private array $categoryMap = [
        'fruits' => '🍎 Fruits',
        'legumes' => '🥦 Légumes',
        'viande' => '🥩 Viande',
        'poisson' => '🐟 Poisson',
        'laitier' => '🧀 Laitier',
        'surgeles' => '🧊 Surgelés',
        'epicerie' => '🥫 Épicerie',
        'snacks' => '🍪 Snacks',
        'boissons' => '🥤 Boissons',
        'entretien' => '🧹 Entretien',
    ];

    /**
     * Generate a categorized shopping list from meal plan items,
     * cross-referenced with current deals.
     *
     * @param Collection $mealPlanItems Collection of GroceryDeal objects from the meal plan
     * @return array
     */
    public function generateFromMealPlan(Collection $mealPlanItems): array
    {
        // Get all current deals for cross-referencing
        $currentDeals = GroceryDeal::current()->with('store')->get();

        // Group meal plan items by category
        $byCategory = $mealPlanItems->groupBy(fn ($item) => $item->category ?? 'epicerie');

        $result = [];
        $totalCost = 0;
        $totalSavings = 0;

        foreach ($this->categoryMap as $catKey => $catLabel) {
            $items = $byCategory->get($catKey, collect([]));
            if ($items->isEmpty()) continue;

            $categoryItems = [];
            $catTotal = 0;
            $catSavings = 0;

            foreach ($items as $item) {
                // Check if item is on sale
                $bestDeal = $currentDeals->first(function ($d) use ($item) {
                    return str_contains(mb_strtolower($d->product), mb_strtolower($item->product))
                        || str_contains(mb_strtolower($item->product), mb_strtolower($d->product));
                });

                $onSale = $bestDeal !== null;
                $bestPrice = $bestDeal ? $bestDeal->price : ($item->regular_price ?? $item->price);
                $savings = $bestDeal ? $bestDeal->savings() : 0;
                $storeName = $bestDeal ? $bestDeal->store->name : ($item->store->name ?? 'N/A');

                $categoryItems[] = [
                    'name' => $item->product,
                    'qty' => 1,
                    'price' => (float) $item->price,
                    'on_sale' => $onSale,
                    'best_price' => (float) $bestPrice,
                    'savings' => $savings,
                    'store' => $storeName,
                    'regular_price' => $item->regular_price ?? null,
                    'is_bio' => $item->is_bio ?? false,
                ];

                $catTotal += (float) $item->price;
                $catSavings += $savings;
            }

            $result[] = [
                'key' => $catKey,
                'name' => $catLabel,
                'items' => $categoryItems,
                'total' => round($catTotal, 2),
                'savings' => round($catSavings, 2),
            ];

            $totalCost += $catTotal;
            $totalSavings += $catSavings;
        }

        return [
            'categories' => $result,
            'total_cost' => round($totalCost, 2),
            'total_savings' => round($totalSavings, 2),
            'items_count' => $mealPlanItems->count(),
        ];
    }

    /**
     * Generate shopping list from current best deals grouped by store and category.
     */
    public function generateFromBestDeals(int $limit = 30): array
    {
        $deals = GroceryDeal::current()
            ->whereNotNull('regular_price')
            ->whereColumn('regular_price', '>', 'price')
            ->orderByRaw('(regular_price - price) DESC')
            ->limit($limit)
            ->with('store')
            ->get();

        return $this->generateFromMealPlan($deals);
    }
}
