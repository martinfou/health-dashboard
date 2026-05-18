<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class HealthReport extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'period_start',
        'period_end',
        'summary_data',
        'notes',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'summary_data' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'is_shared' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
