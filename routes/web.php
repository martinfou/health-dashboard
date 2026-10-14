<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlippController;
use App\Http\Controllers\GroceryAnnualStatsController;
use App\Http\Controllers\GroceryController;
use App\Http\Controllers\HealthInsightController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\PriceHeatmapController;
use App\Http\Controllers\PriceIntelController;
use App\Http\Controllers\PricePredictorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\StockUpAlertController;
use App\Http\Controllers\TradingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/insights/refresh', [HealthInsightController::class, 'refresh'])->name('insights.refresh');
    Route::get('/import', [ImportController::class, 'index'])->name('import');
    Route::post('/import/csv', [ImportController::class, 'uploadCsv'])->name('import.csv');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/{report}/pdf', [ReportController::class, 'downloadPdf'])->name('reports.downloadPdf');
    Route::get('/journal', [JournalController::class, 'index'])->name('journal');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');
    Route::delete('/journal/{entry}', [JournalController::class, 'destroy'])->name('journal.destroy');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trading', [TradingController::class, 'index'])->name('trading');

    // Grocery & Price Intelligence
    Route::get('/grocery', [GroceryController::class, 'index'])->name('grocery');
    Route::get('/grocery/price-intel', [PriceIntelController::class, 'index'])->name('grocery.price-intel');
    Route::get('/grocery/history', [PriceIntelController::class, 'history'])->name('grocery.history');
    Route::get('/grocery/meal-plan', [MealPlanController::class, 'index'])->name('grocery.meal-plan');
    Route::get('/grocery/meal-plan/history', [MealPlanController::class, 'history'])->name('grocery.meal-plan.history');
    Route::get('/grocery/meal-plan/tracking', [MealPlanController::class, 'tracking'])->name('grocery.meal-plan.tracking');
    Route::post('/grocery/meal-plan/eat/{trackingId}', [MealPlanController::class, 'eatMeal'])->name('grocery.meal-plan.eat');
    Route::post('/grocery/meal-plan/uneat/{trackingId}', [MealPlanController::class, 'uneatMeal'])->name('grocery.meal-plan.uneat');
    Route::post('/grocery/meal-plan/regenerate', [MealPlanController::class, 'regenerate'])->name('grocery.meal-plan.regenerate');

    // Feature 1: Stock Up Alert
    Route::get('/grocery/stock-up', [StockUpAlertController::class, 'index'])->name('grocery.stock-up');
    Route::get('/grocery/stock-up/trigger', [StockUpAlertController::class, 'trigger'])->name('grocery.stock-up.trigger');

    // Feature 2: Price Heatmap
    Route::get('/grocery/heatmap', [PriceHeatmapController::class, 'index'])->name('grocery.heatmap');
    Route::get('/grocery/heatmap/data', [PriceHeatmapController::class, 'data'])->name('grocery.heatmap.data');

    // Feature 4: Shopping List
    Route::get('/grocery/shopping-list', [ShoppingListController::class, 'index'])->name('grocery.shopping-list');

    // Feature 5: Price Predictor
    Route::get('/grocery/predictions', [PricePredictorController::class, 'index'])->name('grocery.predictions');
    Route::get('/grocery/predictions/data', [PricePredictorController::class, 'data'])->name('grocery.predictions.data');

    // Feature 6: Flipp Comparateur
    Route::get('/grocery/flipp', [FlippController::class, 'index'])->name('grocery.flipp');
    Route::get('/grocery/flipp/search', [FlippController::class, 'search'])->name('grocery.flipp.search');

    // Feature 7: Loyalty Points
    Route::get('/grocery/loyalty', [LoyaltyController::class, 'index'])->name('grocery.loyalty');

    // Annual Store Stats
    Route::get('/grocery/annual-stats', [GroceryAnnualStatsController::class, 'index'])->name('grocery.annual-stats');
    Route::get('/grocery/annual-stats/trends', [GroceryAnnualStatsController::class, 'trendsData'])->name('grocery.annual-stats.trends');
});
