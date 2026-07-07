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
     *
     * @param array $schedule Format: ['Lundi' => [['name' => ..., ...], ...], ...]
     */
    public static function recordUsage(array $schedule, string $weekLabel): void
    {
        $now = Carbon::now()->startOfWeek();
        $dayNames = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];

        foreach ($schedule as $day => $meals) {
            // Support both old format (single meal) and new format (array of meals)
            if (is_array($meals) && isset($meals['name'])) {
                $meals = [$meals];
            }

            if (!is_array($meals)) continue;

            $dayIndex = array_search($day, $dayNames);
            if ($dayIndex === false) continue;

            foreach ($meals as $meal) {
                if (!isset($meal['name'])) continue;

                static::updateOrCreate(
                    [
                        'recipe_name' => $meal['name'],
                        'used_on' => $now->copy()->addDays($dayIndex),
                    ],
                    [
                        'week_label' => $weekLabel,
                        'context' => [
                            'day' => $day,
                            'assigned_slot' => $meal['assigned_slot'] ?? null,
                            'estimate_cost' => $meal['estimated_cost'] ?? 0,
                            'match_pct' => $meal['match_pct'] ?? 0,
                            'deal_count' => isset($meal['matched_deals']) ? $meal['matched_deals']->count() : 0,
                        ],
                    ]
                );
            }
        }
    }
}
