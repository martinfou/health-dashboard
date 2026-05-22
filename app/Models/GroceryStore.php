<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroceryStore extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'flyer_url'];

    public function deals()
    {
        return $this->hasMany(GroceryDeal::class);
    }

    public function currentDeals()
    {
        return $this->deals()->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }
}
