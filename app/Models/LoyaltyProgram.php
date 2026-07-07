<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_number',
        'points_balance',
        'last_synced_at',
    ];

    protected $casts = [
        'points_balance' => 'decimal:0',
        'last_synced_at' => 'datetime',
    ];

    public function offers()
    {
        return $this->hasMany(LoyaltyOffer::class);
    }

    public function activeOffers()
    {
        return $this->offers()
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }
}
