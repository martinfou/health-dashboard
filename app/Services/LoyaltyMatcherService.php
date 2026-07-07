<?php

namespace App\Services;

use App\Models\GroceryDeal;
use App\Models\LoyaltyOffer;
use App\Models\LoyaltyProgram;
use Illuminate\Support\Collection;

class LoyaltyMatcherService
{
    /**
     * Match active loyalty offers against current grocery deals.
     *
     * Returns: [{deal, loyaltyOffer, potentialPoints, valueInDollars}]
     */
    public function matchDeals(?Collection $deals = null): array
    {
        if ($deals === null) {
            $deals = GroceryDeal::current()->with('store')->get();
        }

        $activeOffers = LoyaltyOffer::where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->with('program')
            ->get();

        if ($activeOffers->isEmpty()) {
            return [];
        }

        $matches = [];

        foreach ($deals as $deal) {
            foreach ($activeOffers as $offer) {
                // First check: category match
                $categoryMatch = $offer->category && $deal->category === $offer->category;

                // Second: product name match (fuzzy)
                $productMatch = $offer->product && (
                    str_contains(mb_strtolower($deal->product), mb_strtolower($offer->product)) ||
                    str_contains(mb_strtolower($offer->product), mb_strtolower($deal->product))
                );

                // Third: required spend match
                $spendMatch = $offer->required_spend && $deal->price >= $offer->required_spend;

                // Fourth: no specific product/category = general offer
                $generalMatch = !$offer->product && !$offer->category && !$offer->required_spend;

                if ($categoryMatch || $productMatch || $spendMatch || $generalMatch) {
                    // Calculate points value in dollars (1000 pts ≈ 1$)
                    $valueInDollars = round($offer->points_value / 1000, 2);

                    $matches[] = [
                        'deal' => $deal,
                        'loyalty_offer' => $offer,
                        'program' => $offer->program,
                        'potential_points' => (int) $offer->points_value,
                        'value_in_dollars' => $valueInDollars,
                        'match_type' => $productMatch ? 'product' : ($categoryMatch ? 'category' : ($spendMatch ? 'spend' : 'general')),
                    ];
                }
            }
        }

        // Sort by points value descending, then unique by deal+offer
        usort($matches, fn ($a, $b) => $b['potential_points'] <=> $a['potential_points']);

        // Deduplicate
        $seen = [];
        $uniqueMatches = [];
        foreach ($matches as $m) {
            $key = $m['deal']->id . '|' . $m['loyalty_offer']->id;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueMatches[] = $m;
            }
        }

        return $uniqueMatches;
    }

    /**
     * Calculate total value from all active loyalty programs.
     */
    public function getProgramsSummary(): array
    {
        $programs = LoyaltyProgram::with('activeOffers')->get();

        return $programs->map(function ($program) {
            $activeOffers = $program->activeOffers;
            $totalPotential = $activeOffers->sum('points_value');
            $valueInDollars = round($totalPotential / 1000, 2);

            return [
                'program' => $program,
                'active_offers' => $activeOffers,
                'total_points_balance' => (int) $program->points_balance,
                'active_offers_value' => $valueInDollars,
                'last_synced' => $program->last_synced_at,
            ];
        });
    }
}
