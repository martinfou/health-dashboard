<?php

namespace App\Http\Controllers;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use Illuminate\Http\Request;

class GroceryController extends Controller
{
    public function index()
    {
        $stores = GroceryStore::with(['currentDeals' => function ($q) {
            $q->orderBy('category')->orderByRaw('(regular_price - price) DESC');
        }])->get();

        $bestDeals = GroceryDeal::bestDeals(10)->with('store')->get();

        $byCategory = GroceryDeal::current()
            ->with('store')
            ->get()
            ->groupBy('category');

        return view('grocery.index', compact('stores', 'bestDeals', 'byCategory'));
    }
}
