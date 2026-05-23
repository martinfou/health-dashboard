<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    protected $table = 'price_history';
    protected $fillable = [
        'grocery_store_id', 'product', 'category', 'sale_price', 'regular_price',
        'unit', 'valid_from', 'valid_until', 'scraped_at', 'store_brand', 'is_bio'
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'scraped_at' => 'date',
        'is_bio' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(GroceryStore::class, 'grocery_store_id');
    }

    public function scopeRecent($query, $months = 12)
    {
        return $query->where('scraped_at', '>=', now()->subMonths($months));
    }
}
