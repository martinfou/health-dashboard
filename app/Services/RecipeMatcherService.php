<?php

namespace App\Services;

use App\Models\GroceryDeal;
use Illuminate\Support\Collection;

class RecipeMatcherService
{
    private array $recipes = [
        'Pâtes à la carbonara' => [
            'ingredients' => ['pâtes', 'oeufs', 'bacon', 'parmesan', 'crème'],
            'icon' => '🍝',
            'kcal' => 550,
            'proteines' => 28,
        ],
        'Salade César' => [
            'ingredients' => ['laitue', 'poulet', 'parmesan', 'croûtons', 'sauce césar'],
            'icon' => '🥗',
            'kcal' => 380,
            'proteines' => 25,
        ],
        'Sauté de légumes' => [
            'ingredients' => ['poivrons', 'courgettes', 'oignons', 'ail', 'sauce soya'],
            'icon' => '🥘',
            'kcal' => 280,
            'proteines' => 12,
        ],
        'Poulet rôti et légumes' => [
            'ingredients' => ['poulet', 'pommes de terre', 'carottes', 'oignons', 'ail'],
            'icon' => '🍗',
            'kcal' => 450,
            'proteines' => 35,
        ],
        'Tacos au poulet' => [
            'ingredients' => ['poulet', 'tortillas', 'laitue', 'fromage', 'salsa'],
            'icon' => '🌮',
            'kcal' => 420,
            'proteines' => 30,
        ],
        'Lasagne maison' => [
            'ingredients' => ['pâtes', 'viande hachée', 'tomates', 'fromage', 'oignons'],
            'icon' => '🍝',
            'kcal' => 600,
            'proteines' => 32,
        ],
        'Poisson pané et frites' => [
            'ingredients' => ['poisson', 'pommes de terre', 'oeufs', 'farine', 'citron'],
            'icon' => '🐟',
            'kcal' => 500,
            'proteines' => 25,
        ],
        'Omelette du jardin' => [
            'ingredients' => ['oeufs', 'poivrons', 'champignons', 'fromage', 'oignons'],
            'icon' => '🍳',
            'kcal' => 350,
            'proteines' => 22,
        ],
        'Bol de riz au thon' => [
            'ingredients' => ['riz', 'thon', 'avocat', 'sauce soya', 'sésame'],
            'icon' => '🍚',
            'kcal' => 400,
            'proteines' => 28,
        ],
        'Soupe aux légumes' => [
            'ingredients' => ['carottes', 'céleri', 'oignons', 'pommes de terre', 'poireaux'],
            'icon' => '🍲',
            'kcal' => 200,
            'proteines' => 8,
        ],
        'Sandwich club' => [
            'ingredients' => ['pain', 'poulet', 'laitue', 'tomates', 'bacon'],
            'icon' => '🥪',
            'kcal' => 450,
            'proteines' => 30,
        ],
        'Pizza maison' => [
            'ingredients' => ['farine', 'tomates', 'fromage', 'champignons', 'jambon'],
            'icon' => '🍕',
            'kcal' => 500,
            'proteines' => 22,
        ],
        'Buddha bowl' => [
            'ingredients' => ['quinoa', 'avocat', 'pois chiches', 'légumes grillés', 'sauce'],
            'icon' => '🥣',
            'kcal' => 420,
            'proteines' => 18,
        ],
        'Smoothie bowl' => [
            'ingredients' => ['bananes', 'fruits rouges', 'yogourt', 'granola', 'miel'],
            'icon' => '🥤',
            'kcal' => 320,
            'proteines' => 12,
        ],
        'Filet de saumon et asperges' => [
            'ingredients' => ['saumon', 'asperges', 'citron', 'beurre', 'ail'],
            'icon' => '🐠',
            'kcal' => 480,
            'proteines' => 35,
        ],
    ];

    /**
     * Match recipes against current grocery deals.
     * Returns recipes sorted by match ratio (best match first).
     */
    public function matchRecipes(Collection $deals): Collection
    {
        $results = collect([]);

        foreach ($this->recipes as $name => $recipe) {
            $matched = 0;
            $matchedDeals = collect([]);
            $notMatched = [];

            foreach ($recipe['ingredients'] as $ingredient) {
                // Try to find this ingredient in current deals (fuzzy match)
                $deal = $deals->first(function ($d) use ($ingredient) {
                    $product = mb_strtolower($d->product);
                    $ing = mb_strtolower($ingredient);
                    return str_contains($product, $ing) || str_contains($ing, $product);
                });

                if ($deal) {
                    $matched++;
                    $matchedDeals->push($deal);
                } else {
                    $notMatched[] = $ingredient;
                }
            }

            $total = count($recipe['ingredients']);
            $ratio = $total > 0 ? round($matched / $total, 2) : 0;

            // Estimate cost based on matched deals (assume 1/2 portion per item)
            $estimatedCost = $matchedDeals->count() > 0
                ? round($matchedDeals->sum(fn ($d) => (float) $d->price) * 0.5, 2)
                : 0;

            $results->push([
                'name' => $name,
                'icon' => $recipe['icon'],
                'kcal' => $recipe['kcal'],
                'proteines' => $recipe['proteines'],
                'ingredients' => $recipe['ingredients'],
                'matched_ingredients_count' => $matched,
                'total_ingredients' => $total,
                'match_ratio' => $ratio,
                'match_pct' => round($ratio * 100),
                'matched_deals' => $matchedDeals,
                'not_matched' => $notMatched,
                'estimated_cost' => $estimatedCost,
                'savings' => $matchedDeals->sum(fn ($d) => $d->savings()),
            ]);
        }

        return $results->sortByDesc('match_ratio')->values();
    }

    /**
     * Generate a meal plan for N days based on best-matching recipes.
     *
     * @param Collection $deals Current grocery deals
     * @param int $days Number of days to plan (default 7)
     * @param array $excludeRecipes Recipe names to exclude (already used in recent weeks)
     * @return array
     */
    public function generateMealPlan(Collection $deals, int $days = 7, array $excludeRecipes = []): array
    {
        $matched = $this->matchRecipes($deals);

        // Filter out recently used recipes
        $excludeLower = array_map('mb_strtolower', $excludeRecipes);
        $available = $matched->reject(fn($r) => in_array(mb_strtolower($r['name']), $excludeLower));

        $weekDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $schedule = [];
        $usedRecipeNames = [];

        for ($i = 0; $i < min($days, 7); $i++) {
            // Pick the best recipe that hasn't been used yet this week
            $best = $available->first(function ($r) use ($usedRecipeNames) {
                return !in_array($r['name'], $usedRecipeNames) && $r['match_ratio'] > 0;
            });

            // If all recipes used or none match, fall back to the first unused
            if (!$best) {
                $best = $available->first(function ($r) use ($usedRecipeNames) {
                    return !in_array($r['name'], $usedRecipeNames);
                });
            }

            // If still nothing, fall back to the excluded recipes (better than empty)
            if (!$best) {
                $best = $matched->first(function ($r) use ($usedRecipeNames) {
                    return !in_array($r['name'], $usedRecipeNames);
                });
            }

            if (!$best) break;

            $usedRecipeNames[] = $best['name'];
            $day = $weekDays[$i] ?? "Jour " . ($i + 1);
            $schedule[$day] = $best;
        }

        return $schedule;
    }

    /**
     * Get the full recipe database.
     */
    public function getAllRecipes(): array
    {
        return $this->recipes;
    }
}
