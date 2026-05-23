<?php

namespace App\Services;

use App\Models\GroceryDeal;
use App\Models\MealPlanUsage;
use App\Models\MealPlanTracking;
use App\Models\NutritionLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecipeMatcherService
{
    /**
     * Complete recipe database with full macros and meal slot suggestions.
     *
     * Macros per serving (1 portion). Calorie target: ~1,900 kcal/day.
     * Suggested distribution: breakfast ~400, lunch ~550, dinner ~600, snack ~350.
     */
    private array $recipes = [
        'Pâtes à la carbonara' => [
            'ingredients' => ['pâtes', 'oeufs', 'bacon', 'parmesan', 'crème'],
            'icon' => '🍝',
            'kcal' => 550, 'proteines' => 28, 'carbs_g' => 45, 'fat_g' => 22, 'fiber_g' => 3, 'sugar_g' => 4,
            'suggested_slot' => 'dinner', 'tags' => ['pâtes', 'italien'],
        ],
        'Salade César' => [
            'ingredients' => ['laitue', 'poulet', 'parmesan', 'croûtons', 'sauce césar'],
            'icon' => '🥗',
            'kcal' => 380, 'proteines' => 25, 'carbs_g' => 15, 'fat_g' => 24, 'fiber_g' => 4, 'sugar_g' => 3,
            'suggested_slot' => 'lunch', 'tags' => ['salade', 'poulet'],
        ],
        'Sauté de légumes' => [
            'ingredients' => ['poivrons', 'courgettes', 'oignons', 'ail', 'sauce soya'],
            'icon' => '🥘',
            'kcal' => 280, 'proteines' => 12, 'carbs_g' => 22, 'fat_g' => 14, 'fiber_g' => 6, 'sugar_g' => 8,
            'suggested_slot' => 'dinner', 'tags' => ['légumes', 'végé'],
        ],
        'Poulet rôti et légumes' => [
            'ingredients' => ['poulet', 'pommes de terre', 'carottes', 'oignons', 'ail'],
            'icon' => '🍗',
            'kcal' => 450, 'proteines' => 35, 'carbs_g' => 28, 'fat_g' => 18, 'fiber_g' => 5, 'sugar_g' => 6,
            'suggested_slot' => 'dinner', 'tags' => ['poulet', 'classique'],
        ],
        'Tacos au poulet' => [
            'ingredients' => ['poulet', 'tortillas', 'laitue', 'fromage', 'salsa'],
            'icon' => '🌮',
            'kcal' => 420, 'proteines' => 30, 'carbs_g' => 32, 'fat_g' => 18, 'fiber_g' => 3, 'sugar_g' => 4,
            'suggested_slot' => 'dinner', 'tags' => ['mexicain', 'poulet'],
        ],
        'Lasagne maison' => [
            'ingredients' => ['pâtes', 'viande hachée', 'tomates', 'fromage', 'oignons'],
            'icon' => '🍝',
            'kcal' => 600, 'proteines' => 32, 'carbs_g' => 48, 'fat_g' => 28, 'fiber_g' => 4, 'sugar_g' => 8,
            'suggested_slot' => 'dinner', 'tags' => ['pâtes', 'italien', 'gras'],
        ],
        'Poisson pané et frites' => [
            'ingredients' => ['poisson', 'pommes de terre', 'oeufs', 'farine', 'citron'],
            'icon' => '🐟',
            'kcal' => 500, 'proteines' => 25, 'carbs_g' => 42, 'fat_g' => 22, 'fiber_g' => 3, 'sugar_g' => 2,
            'suggested_slot' => 'dinner', 'tags' => ['poisson', 'friture'],
        ],
        'Omelette du jardin' => [
            'ingredients' => ['oeufs', 'poivrons', 'champignons', 'fromage', 'oignons'],
            'icon' => '🍳',
            'kcal' => 350, 'proteines' => 22, 'carbs_g' => 8, 'fat_g' => 26, 'fiber_g' => 2, 'sugar_g' => 3,
            'suggested_slot' => 'breakfast', 'tags' => ['oeufs', 'rapide'],
        ],
        'Bol de riz au thon' => [
            'ingredients' => ['riz', 'thon', 'avocat', 'sauce soya', 'sésame'],
            'icon' => '🍚',
            'kcal' => 400, 'proteines' => 28, 'carbs_g' => 40, 'fat_g' => 14, 'fiber_g' => 4, 'sugar_g' => 2,
            'suggested_slot' => 'lunch', 'tags' => ['riz', 'poisson', 'asiatique'],
        ],
        'Soupe aux légumes' => [
            'ingredients' => ['carottes', 'céleri', 'oignons', 'pommes de terre', 'poireaux'],
            'icon' => '🍲',
            'kcal' => 200, 'proteines' => 8, 'carbs_g' => 28, 'fat_g' => 6, 'fiber_g' => 7, 'sugar_g' => 6,
            'suggested_slot' => 'lunch', 'tags' => ['soupe', 'végé', 'léger'],
        ],
        'Sandwich club' => [
            'ingredients' => ['pain', 'poulet', 'laitue', 'tomates', 'bacon'],
            'icon' => '🥪',
            'kcal' => 450, 'proteines' => 30, 'carbs_g' => 35, 'fat_g' => 20, 'fiber_g' => 3, 'sugar_g' => 4,
            'suggested_slot' => 'lunch', 'tags' => ['sandwich', 'rapide'],
        ],
        'Pizza maison' => [
            'ingredients' => ['farine', 'tomates', 'fromage', 'champignons', 'jambon'],
            'icon' => '🍕',
            'kcal' => 500, 'proteines' => 22, 'carbs_g' => 48, 'fat_g' => 22, 'fiber_g' => 3, 'sugar_g' => 5,
            'suggested_slot' => 'dinner', 'tags' => ['pizza', 'italien'],
        ],
        'Buddha bowl' => [
            'ingredients' => ['quinoa', 'avocat', 'pois chiches', 'légumes grillés', 'sauce'],
            'icon' => '🥣',
            'kcal' => 420, 'proteines' => 18, 'carbs_g' => 38, 'fat_g' => 22, 'fiber_g' => 10, 'sugar_g' => 5,
            'suggested_slot' => 'lunch', 'tags' => ['bowl', 'végé', 'santé'],
        ],
        'Smoothie bowl' => [
            'ingredients' => ['bananes', 'fruits rouges', 'yogourt', 'granola', 'miel'],
            'icon' => '🥤',
            'kcal' => 320, 'proteines' => 12, 'carbs_g' => 52, 'fat_g' => 8, 'fiber_g' => 6, 'sugar_g' => 28,
            'suggested_slot' => 'breakfast', 'tags' => ['petit-déjeuner', 'fruits'],
        ],
        'Filet de saumon et asperges' => [
            'ingredients' => ['saumon', 'asperges', 'citron', 'beurre', 'ail'],
            'icon' => '🐠',
            'kcal' => 480, 'proteines' => 35, 'carbs_g' => 8, 'fat_g' => 30, 'fiber_g' => 3, 'sugar_g' => 2,
            'suggested_slot' => 'dinner', 'tags' => ['poisson', 'protéiné', 'faible glucides'],
        ],
    ];

    /**
     * Helper: get nutrition data for a recipe name.
     */
    public function getRecipeNutrition(string $name): ?array
    {
        return $this->recipes[$name] ?? null;
    }

    /**
     * Match recipes against current grocery deals.
     */
    public function matchRecipes(Collection $deals): Collection
    {
        $results = collect([]);

        foreach ($this->recipes as $name => $recipe) {
            $matched = 0;
            $matchedDeals = collect([]);
            $notMatched = [];

            foreach ($recipe['ingredients'] as $ingredient) {
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

            $estimatedCost = $matchedDeals->count() > 0
                ? round($matchedDeals->sum(fn ($d) => (float) $d->price) * 0.5, 2)
                : 0;

            $results->push([
                'name' => $name,
                'icon' => $recipe['icon'],
                'kcal' => $recipe['kcal'],
                'proteines' => $recipe['proteines'],
                'carbs_g' => $recipe['carbs_g'],
                'fat_g' => $recipe['fat_g'],
                'fiber_g' => $recipe['fiber_g'],
                'sugar_g' => $recipe['sugar_g'],
                'suggested_slot' => $recipe['suggested_slot'],
                'tags' => $recipe['tags'],
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
     * Generate a meal plan for N days with meal slot assignments.
     *
     * @param Collection $deals Current grocery deals
     * @param int $days Number of days to plan (default 7)
     * @param array $excludeRecipes Recipe names to exclude
     * @return array ['schedule' => [...], 'daily_totals' => [...]]
     */
    public function generateMealPlan(Collection $deals, int $days = 7, array $excludeRecipes = []): array
    {
        $matched = $this->matchRecipes($deals);

        $excludeLower = array_map('mb_strtolower', $excludeRecipes);
        $available = $matched->reject(fn($r) => in_array(mb_strtolower($r['name']), $excludeLower));

        $weekDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $schedule = [];
        $dailyTotals = [];
        $usedRecipeNames = [];

        // Meal slots by day: breakfast (400), lunch (550), dinner (600), snack (350)
        $slotCalorieTargets = ['breakfast' => 400, 'lunch' => 550, 'dinner' => 600, 'snack' => 350];

        for ($i = 0; $i < min($days, 7); $i++) {
            $day = $weekDays[$i] ?? "Jour " . ($i + 1);
            $schedule[$day] = [];
            $dayCalories = 0;

            foreach ($slotCalorieTargets as $slot => $target) {
                // Find best recipe for this slot that hasn't been used
                $best = $available->first(function ($r) use ($usedRecipeNames, $slot) {
                    return !in_array($r['name'], $usedRecipeNames)
                        && $r['suggested_slot'] === $slot
                        && $r['match_ratio'] > 0;
                });

                if (!$best) {
                    // Fallback: any recipe that fits the calorie budget for this slot
                    $budget = $target * 1.3; // allow 30% over
                    $best = $available->first(function ($r) use ($usedRecipeNames, $budget) {
                        return !in_array($r['name'], $usedRecipeNames) && $r['kcal'] <= $budget;
                    });
                }

                if (!$best) {
                    // Last resort: any unused recipe
                    $best = $available->first(function ($r) use ($usedRecipeNames) {
                        return !in_array($r['name'], $usedRecipeNames);
                    });
                }

                if (!$best) {
                    // Recycle from full pool
                    $best = $matched->first(function ($r) use ($usedRecipeNames) {
                        return !in_array($r['name'], $usedRecipeNames);
                    });
                }

                if (!$best) break;

                $usedRecipeNames[] = $best['name'];
                $best['assigned_slot'] = $slot;
                $schedule[$day][] = $best;
                $dayCalories += $best['kcal'];
            }

            $dailyTotals[$day] = [
                'total_calories' => $dayCalories,
                'total_protein' => collect($schedule[$day])->sum('proteines'),
                'total_carbs' => collect($schedule[$day])->sum('carbs_g'),
                'total_fat' => collect($schedule[$day])->sum('fat_g'),
                'total_fiber' => collect($schedule[$day])->sum('fiber_g'),
                'total_sugar' => collect($schedule[$day])->sum('sugar_g'),
                'target_calories' => 1900,
                'calorie_budget' => max(0, 1900 - $dayCalories),
            ];
        }

        return compact('schedule', 'dailyTotals');
    }

    /**
     * Save the meal plan to the tracking table (pre-populates daily meals).
     * Also creates NutritionLog entries for pre-logging.
     */
    public function saveMealPlanToTracking(int $userId, array $schedule, array $dailyTotals): void
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $slotOrder = ['breakfast' => 0, 'lunch' => 1, 'dinner' => 2, 'snack' => 3];
        $slotLabels = ['breakfast' => 'Déjeuner', 'lunch' => 'Dîner', 'dinner' => 'Souper', 'snack' => 'Collation'];

        foreach ($schedule as $dayName => $meals) {
            $dayIndex = array_search($dayName, ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']);
            if ($dayIndex === false) continue;

            $plannedDate = $weekStart->copy()->addDays($dayIndex);

            // Clear existing tracking for this date
            MealPlanTracking::forUser($userId)->forDate($plannedDate)->delete();

            foreach ($meals as $meal) {
                $existing = MealPlanTracking::forUser($userId)
                    ->forDate($plannedDate)
                    ->where('meal_slot', $meal['assigned_slot'])
                    ->first();

                if (!$existing) {
                    MealPlanTracking::create([
                        'user_id' => $userId,
                        'recipe_name' => $meal['name'],
                        'planned_date' => $plannedDate,
                        'meal_slot' => $meal['assigned_slot'],
                        'calories' => $meal['kcal'],
                        'protein_g' => $meal['proteines'],
                        'carbs_g' => $meal['carbs_g'],
                        'fat_g' => $meal['fat_g'],
                        'fiber_g' => $meal['fiber_g'],
                        'sugar_g' => $meal['sugar_g'],
                        'icon' => $meal['icon'],
                        'eaten' => false,
                        'context' => [
                            'deal_count' => $meal['matched_deals']->count(),
                            'estimated_cost' => $meal['estimated_cost'],
                            'match_pct' => $meal['match_pct'],
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * Get recipes that fit within a remaining calorie budget.
     */
    public function suggestByCalorieBudget(int $remainingCalories, string $preferredSlot = null): array
    {
        $suggestions = [];

        foreach ($this->recipes as $name => $recipe) {
            if ($recipe['kcal'] <= $remainingCalories) {
                if ($preferredSlot && $recipe['suggested_slot'] !== $preferredSlot) {
                    continue;
                }
                $suggestions[] = [
                    'name' => $name,
                    'icon' => $recipe['icon'],
                    'kcal' => $recipe['kcal'],
                    'proteines' => $recipe['proteines'],
                    'carbs_g' => $recipe['carbs_g'],
                    'fat_g' => $recipe['fat_g'],
                    'fiber_g' => $recipe['fiber_g'],
                    'sugar_g' => $recipe['sugar_g'],
                    'suggested_slot' => $recipe['suggested_slot'],
                ];
            }
        }

        return collect($suggestions)
            ->sortByDesc('proteines')
            ->values()
            ->toArray();
    }

    /**
     * Mark a meal as eaten and create NutritionLog entry.
     */
    public function markMealEaten(int $trackingId): ?MealPlanTracking
    {
        $tracking = MealPlanTracking::find($trackingId);
        if (!$tracking || $tracking->eaten) return $tracking;

        DB::transaction(function () use ($tracking) {
            // Create nutrition log entry
            $log = NutritionLog::create([
                'user_id' => $tracking->user_id,
                'logged_at' => $tracking->planned_date,
                'calories' => $tracking->calories,
                'protein_g' => $tracking->protein_g,
                'carbs_g' => $tracking->carbs_g,
                'fat_g' => $tracking->fat_g,
                'fiber_g' => $tracking->fiber_g,
                'sugar_g' => $tracking->sugar_g,
                'source' => 'meal_plan_' . $tracking->meal_slot,
                'raw_data' => [
                    'recipe' => $tracking->recipe_name,
                    'icon' => $tracking->icon,
                    'meal_slot' => $tracking->meal_slot,
                ],
            ]);

            $tracking->update([
                'eaten' => true,
                'eaten_at' => now(),
                'nutrition_log_id' => $log->id,
            ]);
        });

        return $tracking->fresh();
    }

    public function getAllRecipes(): array
    {
        return $this->recipes;
    }
}
