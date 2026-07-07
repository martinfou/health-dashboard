<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MealPlanTracking extends Model
{
    protected $table = 'meal_plan_tracking';

    protected $fillable = [
        'user_id', 'recipe_name', 'planned_date', 'meal_slot',
        'calories', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'sugar_g',
        'icon', 'eaten', 'eaten_at', 'nutrition_log_id', 'context',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'eaten' => 'boolean',
            'eaten_at' => 'datetime',
            'context' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function nutritionLog()
    {
        return $this->belongsTo(NutritionLog::class);
    }

    // ── Scopes ──

    public function scopeForDate($query, $date)
    {
        return $query->where('planned_date', $date);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->where('planned_date', Carbon::today());
    }

    // ── Helpers ──

    public function markAsEaten(): void
    {
        $this->update(['eaten' => true, 'eaten_at' => now()]);
    }

    public static function dailySummary(int $userId, $date = null): array
    {
        $date = $date ?: Carbon::today();
        $meals = static::forUser($userId)->forDate($date)->get();

        $totalCalories = $meals->where('eaten', true)->sum('calories');
        $totalProtein = $meals->where('eaten', true)->sum('protein_g');
        $totalCarbs = $meals->where('eaten', true)->sum('carbs_g');
        $totalFat = $meals->where('eaten', true)->sum('fat_g');
        $totalFiber = $meals->where('eaten', true)->sum('fiber_g');
        $totalSugar = $meals->where('eaten', true)->sum('sugar_g');
        $plannedCalories = $meals->sum('calories');
        $eatenCount = $meals->where('eaten', true)->count();
        $totalCount = $meals->count();

        return [
            'date' => $date,
            'meals' => $meals,
            'total_calories' => $totalCalories,
            'planned_calories' => $plannedCalories,
            'remaining_calories' => max(0, 1900 - $totalCalories),
            'calorie_progress' => min(100, round(($totalCalories / 1900) * 100)),
            'total_protein' => $totalProtein,
            'total_carbs' => $totalCarbs,
            'total_fat' => $totalFat,
            'total_fiber' => $totalFiber,
            'total_sugar' => $totalSugar,
            'eaten_count' => $eatenCount,
            'total_count' => $totalCount,
        ];
    }
}
