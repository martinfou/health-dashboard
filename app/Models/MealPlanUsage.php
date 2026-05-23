<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MealPlanUsage extends Model
{
    protected $fillable = [
        'recipe_name',
        'used_on',
        'week_label',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'used_on' => 'date',
            'context' => 'array',
        ];
    }

    /**
     * Get recipes used in the last N weeks.
     */
    public static function recentlyUsed(int $weeksBack = 4): array
    {
        $cutoff = Carbon::now()->subWeeks($weeksBack)->startOfWeek();

        return static::where('used_on', '>=', $cutoff)
            ->pluck('recipe_name')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get the complete usage history with counts.
     */
    public static function usageHistory(int $limit = 20): array
    {
        return static::selectRaw('recipe_name, COUNT(*) as times_used, MAX(used_on) as last_used')
            ->groupBy('recipe_name')
            ->orderByDesc('times_used')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Record multiple recipes from a meal plan.
     */
    public static function recordUsage(array $schedule, string $weekLabel): void
    {
        $now = Carbon::now()->startOfWeek();

        foreach ($schedule as $day => $meal) {
            static::updateOrCreate(
                [
                    'recipe_name' => $meal['name'],
                    'used_on' => $now->copy()->addDays(array_search($day, ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche']) ?: 0),
                ],
                [
                    'week_label' => $weekLabel,
                    'context' => [
                        'day' => $day,
                        'estimate_cost' => $meal['estimated_cost'] ?? 0,
                        'match_pct' => $meal['match_pct'] ?? 0,
                        'deal_count' => $meal['matched_deals']->count() ?? 0,
                    ],
                ]
            );
        }
    }
}
