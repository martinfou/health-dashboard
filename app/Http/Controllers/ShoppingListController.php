<?php

namespace App\Http\Controllers;

use App\Models\GroceryDeal;
use App\Services\RecipeMatcherService;
use App\Services\ShoppingListService;
use Illuminate\Http\Request;

class ShoppingListController extends Controller
{
    public function __construct(
        protected ShoppingListService $shoppingListService,
        protected RecipeMatcherService $recipeMatcher
    ) {}

    public function index()
    {
        $deals = GroceryDeal::current()->with('store')->get();

        // Generate a meal plan to derive shopping list from
        $schedule = $this->recipeMatcher->generateMealPlan($deals, 7);

        // Collect all items from the meal plan
        $mealPlanItems = collect([]);
        foreach ($schedule as $meal) {
            foreach ($meal['matched_deals'] ?? [] as $deal) {
                $key = $deal->id ?? spl_object_id($deal);
                $mealPlanItems[$key] = $deal;
            }
        }

        $shoppingList = $this->shoppingListService->generateFromMealPlan($mealPlanItems);

        // Also get best current deals for quick-add suggestions
        $suggestedDeals = GroceryDeal::bestDeals(15)->with('store')->get();

        return view('grocery.shopping-list', compact('shoppingList', 'suggestedDeals', 'schedule'));
    }
}
