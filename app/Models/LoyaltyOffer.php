<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyalty_program_id',
        'description',
        'points_value',
        'required_spend',
        'valid_from',
        'valid_until',
        'product',
        'category',
    ];

    protected $casts = [
        'points_value' => 'decimal:0',
        'required_spend' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }
}
