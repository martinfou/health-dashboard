<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthInsightController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
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
    Route::get('/trading', [App\Http\Controllers\TradingController::class, 'index'])->name('trading');

    // Grocery & Price Intelligence
    Route::get('/grocery', [App\Http\Controllers\GroceryController::class, 'index'])->name('grocery');
    Route::get('/grocery/price-intel', [App\Http\Controllers\PriceIntelController::class, 'index'])->name('grocery.price-intel');
    Route::get('/grocery/history', [App\Http\Controllers\PriceIntelController::class, 'history'])->name('grocery.history');
    Route::get('/grocery/meal-plan', [App\Http\Controllers\PriceIntelController::class, 'mealPlan'])->name('grocery.meal-plan');
});
