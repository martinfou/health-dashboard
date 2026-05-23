<?php

namespace App\Http\Controllers;

use App\Models\GroceryStore;
use App\Models\PriceHistory;
use App\Services\PricePredictorService;
use Illuminate\Http\Request;

class PricePredictorController extends Controller
{
    public function __construct(protected PricePredictorService $predictorService) {}

    public function index()
    {
        $products = PriceHistory::select('product')
            ->distinct()
            ->orderBy('product')
            ->pluck('product');

        $stores = GroceryStore::orderBy('name')->get();

        return view('grocery.predictions', compact('products', 'stores'));
    }

    public function data(Request $request)
    {
        $request->validate([
            'product' => 'nullable|string|max:255',
            'store_id' => 'nullable|integer|exists:grocery_stores,id',
        ]);

        $prediction = $this->predictorService->predict(
            $request->get('product'),
            $request->integer('store_id') ?: null
        );

        return response()->json($prediction);
    }
}
