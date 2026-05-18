<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DailyJournal extends Model
{
    protected $fillable = ['user_id', 'entry_date', 'energy_level', 'sleep_quality', 'mood', 'gratitude', 'intention', 'notes', 'stoic_reflection'];
    protected function casts(): array { return ['stoic_reflection' => 'array', 'entry_date' => 'date']; }
    public function user() { return $this->belongsTo(User::class); }
}
