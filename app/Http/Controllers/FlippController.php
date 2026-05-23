<?php

namespace App\Http\Controllers;

use App\Services\UnifiedPriceService;
use Illuminate\Http\Request;

class FlippController extends Controller
{
    public function __construct(protected UnifiedPriceService $unifiedPriceService) {}

    public function index()
    {
        $categories = $this->unifiedPriceService->getCategories();
        return view('grocery.flipp', compact('categories'));
    }

    public function search(Request $request)
    {
        $product = $request->get('q');
        $category = $request->get('category');
        $sortBy = $request->get('sort', 'price');

        $results = $this->unifiedPriceService->search($product, $category, $sortBy);

        return response()->json($results);
    }
}
