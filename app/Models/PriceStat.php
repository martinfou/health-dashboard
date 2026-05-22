<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceStat extends Model
{
    protected $fillable = [
        'grocery_store_id',
        'product',
        'category',
        'unit',
        'sample_count',
        'avg_sale_price',
        'min_sale_price',
        'max_sale_price',
        'avg_regular_price',
        'avg_savings_pct',
        'first_seen',
        'last_seen',
    ];

    protected $casts = [
        'avg_sale_price' => 'decimal:2',
        'min_sale_price' => 'decimal:2',
        'max_sale_price' => 'decimal:2',
        'avg_regular_price' => 'decimal:2',
        'avg_savings_pct' => 'decimal:1',
        'first_seen' => 'date',
        'last_seen' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(GroceryStore::class, 'grocery_store_id');
    }

    /**
     * Rate a given sale price against historical stats.
     *
     * Returns rating: excellent|good|average|weak|bad|no_data
     */
    public function rateDeal(float $salePrice): array
    {
        if ($this->sample_count === 0 || $this->avg_sale_price == 0) {
            return [
                'rating' => 'no_data',
                'label' => '⚠️ Pas assez de données',
                'score' => 0,
                'savings_vs_avg' => 0,
            ];
        }

        $diffPct = ($this->avg_sale_price - $salePrice) / $this->avg_sale_price;

        // Seuils qualitatifs
        return match (true) {
            $diffPct >= 0.30 => [
                'rating' => 'excellent',
                'label' => '🔥 EXCELLENT',
                'score' => 5,
                'savings_vs_avg' => round($diffPct * 100),
            ],
            $diffPct >= 0.15 => [
                'rating' => 'good',
                'label' => '👍 Bon deal',
                'score' => 4,
                'savings_vs_avg' => round($diffPct * 100),
            ],
            $diffPct >= 0.05 => [
                'rating' => 'average',
                'label' => '✅ Correct',
                'score' => 3,
                'savings_vs_avg' => round($diffPct * 100),
            ],
            $diffPct >= -0.05 => [
                'rating' => 'weak',
                'label' => '😐 Moyen',
                'score' => 2,
                'savings_vs_avg' => round($diffPct * 100),
            ],
            default => [
                'rating' => 'bad',
                'label' => '❌ Pas un deal',
                'score' => 1,
                'savings_vs_avg' => round($diffPct * 100),
            ],
        };
    }
}
