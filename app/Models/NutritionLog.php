<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class NutritionLog extends Model
{
    protected $fillable = ['user_id'];
    protected function casts(): array { return ['raw_data' => 'array']; }
    public function user() { return $this->belongsTo(User::class); }
}
