<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TradeSignal extends Model
{
    protected $fillable = ['user_id'];
    protected function casts(): array { return []; }
    public function user() { return $this->belongsTo(User::class); }
}
