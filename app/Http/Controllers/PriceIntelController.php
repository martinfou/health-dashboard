<?php

namespace App\Http\Controllers;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use App\Models\PriceStat;
use App\Models\PriceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceIntelController extends Controller
{
    /**
     * Price Intelligence Dashboard — analyse les deals actuels contre l'historique.
     */
    public function index()
    {
        $stores = GroceryStore::with('deals')->get();

        // Current deals with price intelligence ratings
        $currentDeals = GroceryDeal::current()
            ->with('store')
            ->get()
            ->map(function ($deal) {
                $stat = PriceStat::where('grocery_store_id', $deal->grocery_store_id)
                    ->where('product', $deal->product)
                    ->first();

                if ($stat && $deal->regular_price) {
                    $deal->rating = $stat->rateDeal($deal->price);
                    $deal->stat = $stat;
                } elseif ($deal->regular_price) {
                    // Fallback: rate vs regular price only
                    $savingsPct = (($deal->regular_price - $deal->price) / $deal->regular_price) * 100;
                    $deal->rating = match (true) {
                        $savingsPct >= 40 => ['rating' => 'excellent', 'label' => '🔥 EXCELLENT', 'score' => 5],
                        $savingsPct >= 25 => ['rating' => 'good', 'label' => '👍 Bon deal', 'score' => 4],
                        $savingsPct >= 15 => ['rating' => 'average', 'label' => '✅ Correct', 'score' => 3],
                        $savingsPct >= 5  => ['rating' => 'weak', 'label' => '😐 Moyen', 'score' => 2],
                        default           => ['rating' => 'bad', 'label' => '❌ Pas un deal', 'score' => 1],
                    };
                } else {
                    $deal->rating = ['rating' => 'no_data', 'label' => '📝 Aucune data', 'score' => 0];
                }

                return $deal;
            })
            ->sortByDesc(fn ($d) => $d->rating['score'] ?? 0)
            ->values();

        // Top deals by savings
        $topDeals = $currentDeals->whereIn('rating.rating', ['excellent', 'good'])->take(15);

        // Category breakdown
        $byCategory = $currentDeals->groupBy('category')->sortKeys();

        // Categories with best average savings
        $categorySavings = [];
        foreach ($byCategory as $cat => $deals) {
            $withRating = $deals->filter(fn ($d) => $d->rating['score'] > 0);
            $categorySavings[$cat] = [
                'count' => $deals->count(),
                'avg_score' => $withRating->avg(fn ($d) => $d->rating['score']),
                'top_score' => $withRating->max(fn ($d) => $d->rating['score']),
                'icons' => ['fruits'=>'🍎','legumes'=>'🥦','viande'=>'🥩','poisson'=>'🐟',
                            'laitier'=>'🧀','surgeles'=>'🧊','epicerie'=>'🥫',
                            'snacks'=>'🍪','boissons'=>'🥤','entretien'=>'🧹'],
            ];
        }
        arsort($categorySavings);

        // Stores with most excellent deals
        $storeRanking = $currentDeals->groupBy(fn ($d) => $d->store->name)
            ->map(function ($deals, $name) {
                return [
                    'name' => $name,
                    'total' => $deals->count(),
                    'excellent' => $deals->where('rating.rating', 'excellent')->count(),
                    'good' => $deals->where('rating.rating', 'good')->count(),
                    'bad' => $deals->whereIn('rating.rating', ['weak', 'bad'])->count(),
                ];
            })
            ->sortByDesc(fn ($s) => $s['excellent'])
            ->values();

        // Recommendation: best products to buy this week
        $recommendations = $currentDeals
            ->whereIn('rating.rating', ['excellent', 'good'])
            ->take(10)
            ->map(fn ($d) => [
                'product' => $d->product,
                'store' => $d->store->name,
                'price' => $d->price,
                'savings' => $d->savings(),
                'savings_pct' => $d->savingsPercent(),
                'rating' => $d->rating['label'],
                'category' => $d->category,
            ]);

        return view('grocery.price-intel', compact(
            'topDeals',
            'byCategory',
            'categorySavings',
            'storeRanking',
            'recommendations'
        ));
    }

    /**
     * Show historical price trends for a specific product.
     */
    public function history(Request $request)
    {
        $product = $request->get('product');
        $storeId = $request->get('store_id');

        $query = PriceHistory::with('store')
            ->when($product, fn ($q) => $q->where('product', 'like', "%{$product}%"))
            ->when($storeId, fn ($q) => $q->where('grocery_store_id', $storeId));

        $history = $query->orderBy('scraped_at', 'desc')
            ->orderBy('product')
            ->paginate(50);

        $products = PriceHistory::select('product')
            ->distinct()
            ->orderBy('product')
            ->pluck('product');

        $stores = GroceryStore::orderBy('name')->get();

        return view('grocery.history', compact('history', 'products', 'stores', 'product', 'storeId'));
    }

    /**
     * Generate meal plan suggestions based on current deals.
     */
    public function mealPlan()
    {
        $deals = GroceryDeal::current()
            ->with('store')
            ->get();

        // Define meal templates keyed by category
        $mealIdeas = [
            'poulet_legumes' => [
                'name' => 'Poulet rôti aux légumes de saison',
                'icon' => '🍗',
                'requires' => ['viande' => ['poulet'], 'legumes' => ['*']],
                'proteines' => 30,
                'kcal' => 450,
                'prix_estime' => 8.99,
            ],
            'saumon_legumes' => [
                'name' => 'Filet de saumon et légumes grillés',
                'icon' => '🐟',
                'requires' => ['poisson' => ['saumon'], 'legumes' => ['*']],
                'proteines' => 35,
                'kcal' => 500,
                'prix_estime' => 12.99,
            ],
            'salade_proteinee' => [
                'name' => 'Grande salade protéinée',
                'icon' => '🥗',
                'requires' => ['legumes' => ['salade', 'tomate', 'concombre']],
                'proteines' => 25,
                'kcal' => 380,
                'prix_estime' => 6.99,
            ],
            'pates_viande' => [
                'name' => 'Pâtes à la viande maison',
                'icon' => '🍝',
                'requires' => ['viande' => ['*'], 'epicerie' => ['*']],
                'proteines' => 28,
                'kcal' => 550,
                'prix_estime' => 7.99,
            ],
            'bol_fruits' => [
                'name' => 'Bol de fruits frais & yogourt',
                'icon' => '🥣',
                'requires' => ['fruits' => ['*'], 'laitier' => ['*']],
                'proteines' => 15,
                'kcal' => 320,
                'prix_estime' => 4.99,
            ],
            'poisson_legumes' => [
                'name' => 'Poisson blanc et légumes vapeur',
                'icon' => '🐠',
                'requires' => ['poisson' => ['*'], 'legumes' => ['*']],
                'proteines' => 32,
                'kcal' => 420,
                'prix_estime' => 10.99,
            ],
            'soupe_maison' => [
                'name' => 'Soupe maison et pain',
                'icon' => '🍲',
                'requires' => ['legumes' => ['*'], 'epicerie' => ['*']],
                'proteines' => 12,
                'kcal' => 280,
                'prix_estime' => 3.99,
            ],
            'fromages_charcuterie' => [
                'name' => 'Plateau fromages & charcuterie',
                'icon' => '🧀',
                'requires' => ['laitier' => ['*'], 'viande' => ['*']],
                'proteines' => 20,
                'kcal' => 450,
                'prix_estime' => 9.99,
            ],
        ];

        // Match deals to meal ideas
        $availableMeals = [];
        foreach ($mealIdeas as $key => $meal) {
            $matchedDeals = collect([]);
            $feasible = true;

            foreach ($meal['requires'] as $cat => $needs) {
                $catDeals = $deals->where('category', $cat);
                if ($catDeals->isEmpty()) {
                    $feasible = false;
                    break;
                }

                // Check if any needed product is on sale
                if (in_array('*', $needs)) {
                    $matched = $catDeals->first();
                    if ($matched) {
                        $matchedDeals[] = $matched;
                    }
                } else {
                    $found = false;
                    foreach ($needs as $need) {
                        $match = $catDeals->first(fn ($d) => stripos($d->product, $need) !== false);
                        if ($match) {
                            $matchedDeals[] = $match;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $feasible = false;
                        break;
                    }
                }
            }

            if ($feasible) {
                // Estimate cost based on matched deals
                $estimatedCost = $matchedDeals->sum(fn ($d) => $d->price * 0.5);

                $meal['estimated_cost'] = round(max($estimatedCost, $meal['prix_estime'] * 0.6), 2);
                $meal['matched_deals'] = $matchedDeals;
                $meal['ratio'] = $meal['proteines'] / max($meal['estimated_cost'], 1);
                $availableMeals[$key] = $meal;
            }
        }

        // Sort by best protein/cost ratio
        uasort($availableMeals, fn ($a, $b) => $b['ratio'] <=> $a['ratio']);

        // Weekly meal schedule (Mon-Fri)
        $weekDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $schedule = [];
        $mealKeys = array_keys($availableMeals);
        foreach ($weekDays as $i => $day) {
            if (isset($mealKeys[$i % max(count($mealKeys), 1)])) {
                $mk = $mealKeys[$i % count($mealKeys)];
                $schedule[$day] = $availableMeals[$mk];
            }
        }

        // Convert to collection for Blade
        $availableMeals = collect($availableMeals);

        // Stats
        $totalCost = array_sum(array_column($schedule, 'estimated_cost'));
        $totalProtein = array_sum(array_column($schedule, 'proteines'));

        return view('grocery.meal-plan', compact(
            'availableMeals',
            'schedule',
            'totalCost',
            'totalProtein',
            'deals'
        ));
    }
}
