<?php

namespace App\Http\Controllers;

use App\Models\GroceryDeal;
use App\Models\LoyaltyProgram;
use App\Services\LoyaltyMatcherService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(protected LoyaltyMatcherService $loyaltyService) {}

    public function index()
    {
        $programs = $this->loyaltyService->getProgramsSummary();
        $deals = GroceryDeal::current()->with('store')->get();
        $matches = $this->loyaltyService->matchDeals($deals);
        $programsList = LoyaltyProgram::all();

        return view('grocery.loyalty', compact('programs', 'matches', 'deals', 'programsList'));
    }
}
