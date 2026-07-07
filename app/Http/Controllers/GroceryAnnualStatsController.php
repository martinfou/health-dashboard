<?php

namespace App\Http\Controllers;

use App\Models\GroceryStore;
use App\Services\GroceryAnnualStatsService;
use Illuminate\Http\Request;

class GroceryAnnualStatsController extends Controller
{
    public function __construct(protected GroceryAnnualStatsService $statsService) {}

    public function index()
    {
        $report = $this->statsService->fullReport();

        // Check if we have any data
        $hasData = collect($report['store_ranking'])->sum('total_deals') > 0;

        return view('grocery.annual-stats', compact('report', 'hasData'));
    }

    /**
     * Return monthly trend data as JSON for Chart.js.
     */
    public function trendsData()
    {
        $stores = GroceryStore::all();
        $startOfYear = now()->startOfYear();
        $trends = $this->statsService->monthlyTrends($stores, $startOfYear);

        return response()->json($trends);
    }
}
