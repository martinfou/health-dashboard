<?php

namespace App\Http\Controllers;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use App\Models\MealPlanTracking;
use App\Models\MealPlanUsage;
use App\Models\NutritionLog;
use App\Services\RecipeMatcherService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MealPlanController extends Controller
{
    public function __construct(protected RecipeMatcherService $recipeMatcher) {}

    public function index()
    {
        $userId = auth()->id();
        $deals = GroceryDeal::current()->with('store')->get();

        // Exclude recipes used in the last 4 weeks
        $recentlyUsed = MealPlanUsage::recentlyUsed(4);

        $matchedRecipes = $this->recipeMatcher->matchRecipes($deals);
        $plan = $this->recipeMatcher->generateMealPlan($deals, 7, $recentlyUsed);

        $schedule = $plan['schedule'];
        $dailyTotals = $plan['dailyTotals'];

        // Save to tracking table (only if not already saved for this week)
        $weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY);
        $hasTracking = MealPlanTracking::forUser($userId)
            ->where('planned_date', '>=', $weekStart)
            ->exists();

        if (!$hasTracking) {
            $this->recipeMatcher->saveMealPlanToTracking($userId, $schedule, $dailyTotals);
        }

        // Save usage history
        $weekLabel = Carbon::now()->format('o-\\WW');
        MealPlanUsage::recordUsage($schedule, $weekLabel);

        // Collect all unique matched deals for shopping list
        $allMatchedDeals = collect([]);
        foreach ($schedule as $day => $meals) {
            foreach ($meals as $meal) {
                foreach ($meal['matched_deals'] ?? [] as $deal) {
                    $key = $deal->id ?? $deal->product . '|' . ($deal->store->name ?? '');
                    $allMatchedDeals[$key] = $deal;
                }
            }
        }

        $totalCost = 0;
        $totalProtein = 0;
        $totalSavings = 0;
        foreach ($schedule as $day => $meals) {
            foreach ($meals as $meal) {
                $totalCost += $meal['estimated_cost'];
                $totalProtein += $meal['proteines'];
                $totalSavings += $meal['savings'];
            }
        }

        $usageHistory = MealPlanUsage::usageHistory();

        // Today's tracking summary
        $todaySummary = MealPlanTracking::dailySummary($userId);

        return view('grocery.meal-plan', compact(
            'matchedRecipes',
            'schedule',
            'dailyTotals',
            'totalCost',
            'totalProtein',
            'totalSavings',
            'deals',
            'allMatchedDeals',
            'recentlyUsed',
            'usageHistory',
            'todaySummary',
            'weekLabel',
        ));
    }

    public function history()
    {
        $usages = MealPlanUsage::orderBy('used_on', 'desc')
            ->orderBy('recipe_name')
            ->paginate(30);

        $stats = MealPlanUsage::usageHistory();
        $totalPlans = MealPlanUsage::distinct('week_label')->count('week_label');
        $uniqueRecipes = MealPlanUsage::distinct('recipe_name')->count('recipe_name');

        return view('grocery.meal-plan-history', compact(
            'usages', 'stats', 'totalPlans', 'uniqueRecipes'
        ));
    }

    /**
     * Daily meal tracking page.
     */
    public function tracking(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
        $summary = MealPlanTracking::dailySummary(auth()->id(), $date);

        // Suggested recipes if remaining calories > 200
        $suggestions = [];
        if ($summary['remaining_calories'] > 200) {
            $suggestions = $this->recipeMatcher->suggestByCalorieBudget($summary['remaining_calories']);
        }

        return view('grocery.meal-tracking', compact('summary', 'suggestions', 'date'));
    }

    /**
     * Mark a meal as eaten.
     */
    public function eatMeal(Request $request, int $trackingId)
    {
        $tracking = $this->recipeMatcher->markMealEaten($trackingId);

        if (!$tracking) {
            return back()->with('error', 'Repas introuvable.');
        }

        return back()->with('success', '✅ ' . $tracking->recipe_name . ' marqué comme mangé !');
    }

    /**
     * Un-mark a meal (remove NutritionLog entry too).
     */
    public function uneatMeal(int $trackingId)
    {
        $tracking = MealPlanTracking::find($trackingId);
        if (!$tracking) {
            return back()->with('error', 'Repas introuvable.');
        }

        if ($tracking->nutrition_log_id) {
            NutritionLog::where('id', $tracking->nutrition_log_id)->delete();
        }

        $tracking->update(['eaten' => false, 'eaten_at' => null, 'nutrition_log_id' => null]);

        return back()->with('success', '↩️ ' . $tracking->recipe_name . ' remis dans le plan.');
    }

    /**
     * Regenerate this week's meal plan (clear and redo).
     */
    public function regenerate()
    {
        $userId = auth()->id();
        $weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY);
        $weekEnd = Carbon::today()->endOfWeek(Carbon::SUNDAY);

        MealPlanTracking::forUser($userId)
            ->whereBetween('planned_date', [$weekStart, $weekEnd])
            ->delete();

        return redirect()->route('grocery.meal-plan')
            ->with('success', '🔄 Plan repas regénéré !');
    }
}
