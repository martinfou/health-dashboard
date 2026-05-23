<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockUpAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'grocery_deal_id',
        'product',
        'grocery_store_id',
        'price',
        'historical_low_price',
        'savings_pct',
        'triggered_at',
        'notified_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'historical_low_price' => 'decimal:2',
        'savings_pct' => 'decimal:1',
        'triggered_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function deal()
    {
        return $this->belongsTo(GroceryDeal::class, 'grocery_deal_id');
    }

    public function store()
    {
        return $this->belongsTo(GroceryStore::class, 'grocery_store_id');
    }

    public function scopeNotNotified($query)
    {
        return $query->whereNull('notified_at');
    }
}
