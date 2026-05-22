<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroceryDeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'grocery_store_id', 'product', 'category', 'price', 'unit',
        'regular_price', 'valid_from', 'valid_until', 'flyer_page',
        'image_url', 'is_bio', 'store_brand'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_bio' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(GroceryStore::class, 'grocery_store_id');
    }

    public function savings()
    {
        if ($this->regular_price && $this->regular_price > $this->price) {
            return round($this->regular_price - $this->price, 2);
        }
        return 0;
    }

    public function savingsPercent()
    {
        if ($this->regular_price && $this->regular_price > 0) {
            return round(($this->regular_price - $this->price) / $this->regular_price * 100);
        }
        return 0;
    }

    public function scopeCurrent($query)
    {
        return $query->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBestDeals($query, $limit = 20)
    {
        return $query->current()
            ->whereNotNull('regular_price')
            ->whereColumn('regular_price', '>', 'price')
            ->orderByRaw('(regular_price - price) DESC')
            ->limit($limit);
    }
}
