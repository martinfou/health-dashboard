<?php

namespace App\Http\Controllers;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use App\Models\MealPlanUsage;
use App\Services\RecipeMatcherService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MealPlanController extends Controller
{
    public function __construct(protected RecipeMatcherService $recipeMatcher) {}

    public function index()
    {
        $deals = GroceryDeal::current()->with('store')->get();

        // Exclude recipes used in the last 4 weeks to avoid repetition
        $recentlyUsed = MealPlanUsage::recentlyUsed(4);

        $matchedRecipes = $this->recipeMatcher->matchRecipes($deals);
        $schedule = $this->recipeMatcher->generateMealPlan($deals, 7, $recentlyUsed);

        // Save this week's meal plan to history
        $weekLabel = Carbon::now()->format('o-\\WW');
        MealPlanUsage::recordUsage($schedule, $weekLabel);

        // Collect all unique matched deals for the combined shopping list
        $allMatchedDeals = collect([]);
        foreach ($schedule as $meal) {
            foreach ($meal['matched_deals'] ?? [] as $deal) {
                $key = $deal->id ?? $deal->product . '|' . ($deal->store->name ?? '');
                $allMatchedDeals[$key] = $deal;
            }
        }

        $totalCost = array_sum(array_column($schedule, 'estimated_cost'));
        $totalProtein = array_sum(array_column($schedule, 'proteines'));
        $totalSavings = array_sum(array_column($schedule, 'savings'));

        $usageHistory = MealPlanUsage::usageHistory();

        return view('grocery.meal-plan', compact(
            'matchedRecipes',
            'schedule',
            'totalCost',
            'totalProtein',
            'totalSavings',
            'deals',
            'allMatchedDeals',
            'recentlyUsed',
            'usageHistory'
        ));
    }

    /**
     * Show meal plan usage history.
     */
    public function history()
    {
        $usages = MealPlanUsage::orderBy('used_on', 'desc')
            ->orderBy('recipe_name')
            ->paginate(30);

        $stats = MealPlanUsage::usageHistory();
        $totalPlans = MealPlanUsage::distinct('week_label')->count('week_label');
        $uniqueRecipes = MealPlanUsage::distinct('recipe_name')->count('recipe_name');

        return view('grocery.meal-plan-history', compact(
            'usages',
            'stats',
            'totalPlans',
            'uniqueRecipes'
        ));
    }
}
