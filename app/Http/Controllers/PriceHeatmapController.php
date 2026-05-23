<?php

namespace App\Http\Controllers;

use App\Models\GroceryStore;
use App\Models\PriceHistory;
use App\Services\PriceHeatmapService;
use Illuminate\Http\Request;

class PriceHeatmapController extends Controller
{
    public function __construct(protected PriceHeatmapService $heatmapService) {}

    public function index()
    {
        $products = PriceHistory::select('product')
            ->distinct()
            ->orderBy('product')
            ->pluck('product');

        $stores = GroceryStore::orderBy('name')->get();

        return view('grocery.heatmap', compact('products', 'stores'));
    }

    public function data(Request $request)
    {
        $product = $request->get('product');
        $storeId = $request->get('store_id');
        $months = $request->integer('months', 12);

        $heatmap = $this->heatmapService->generate($product, $storeId ? (int) $storeId : null, $months);

        return response()->json($heatmap);
    }
}
