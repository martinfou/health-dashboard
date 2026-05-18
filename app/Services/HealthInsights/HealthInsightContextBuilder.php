<?php

namespace App\Services\HealthInsights;

use App\Models\ActivityLog;
use App\Models\BodyMeasurement;
use App\Models\DailyJournal;
use App\Models\NutritionLog;
use App\Models\User;
use App\Models\WeightReading;
use Illuminate\Support\Facades\DB;

class HealthInsightContextBuilder
{
    public function build(User $user): array
    {
        $userId = $user->id;

        $weightReadings = WeightReading::where('user_id', $userId)
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'weight_lb', 'bmi', 'body_fat_pct']);

        $measurements = BodyMeasurement::where('user_id', $userId)
            ->orderBy('measured_at')
            ->get(['measured_at', 'waist_cm', 'hips_cm', 'abdomen_cm', 'whr']);

        $nutritionRecent = NutritionLog::where('user_id', $userId)
            ->where('logged_at', '>=', now()->subDays(60))
            ->orderBy('logged_at')
            ->get(['logged_at', 'calories', 'protein_g', 'fat_g', 'carbs_g']);

        $activityMonthly = ActivityLog::where('user_id', $userId)
            ->select(
                DB::raw("strftime('%Y-%m', activity_date) as month"),
                DB::raw('SUM(steps) as total_steps'),
                DB::raw('SUM(gym_sessions) as total_gym'),
                DB::raw('AVG(heart_rate_avg) as avg_hr'),
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $journals = DailyJournal::where('user_id', $userId)
            ->where('entry_date', '>=', now()->subDays(30))
            ->orderByDesc('entry_date')
            ->limit(14)
            ->get(['entry_date', 'energy_level', 'sleep_quality', 'mood', 'gratitude', 'intention']);

        $firstW = $weightReadings->first();
        $lastW = $weightReadings->last();
        $firstM = $measurements->first();
        $lastM = $measurements->last();

        return [
            'user' => ['name' => $user->name],
            'locale' => config('health-insights.locale', 'fr'),
            'generated_at' => now()->toIso8601String(),
            'kpis' => [
                'current_weight_lb' => $lastW?->weight_lb,
                'total_weight_loss_lb' => $firstW && $lastW
                    ? round($firstW->weight_lb - $lastW->weight_lb, 1)
                    : null,
                'current_whr' => $lastM?->whr,
                'waist_loss_cm' => $firstM && $lastM
                    ? round($firstM->waist_cm - $lastM->waist_cm, 1)
                    : null,
                'total_gym_sessions' => ActivityLog::where('user_id', $userId)->sum('gym_sessions'),
                'avg_calories_60d' => round($nutritionRecent->avg('calories') ?? 0),
                'weight_reading_count' => $weightReadings->count(),
                'measurement_count' => $measurements->count(),
                'nutrition_log_count' => $nutritionRecent->count(),
            ],
            'weight_trend' => $weightReadings->take(-8)->values()->map(fn ($r) => [
                'date' => $r->recorded_at,
                'weight_lb' => $r->weight_lb,
                'body_fat_pct' => $r->body_fat_pct,
            ])->all(),
            'body_measurements_latest' => $lastM ? [
                'date' => $lastM->measured_at,
                'waist_cm' => $lastM->waist_cm,
                'hips_cm' => $lastM->hips_cm,
                'whr' => $lastM->whr,
            ] : null,
            'nutrition_recent_avg' => [
                'calories' => round($nutritionRecent->avg('calories') ?? 0),
                'protein_g' => round($nutritionRecent->avg('protein_g') ?? 0, 1),
                'fat_g' => round($nutritionRecent->avg('fat_g') ?? 0, 1),
                'carbs_g' => round($nutritionRecent->avg('carbs_g') ?? 0, 1),
            ],
            'activity_monthly' => $activityMonthly->map(fn ($r) => [
                'month' => $r->month,
                'gym_sessions' => (int) $r->total_gym,
                'steps' => (int) $r->total_steps,
                'avg_heart_rate' => $r->avg_hr ? round($r->avg_hr, 0) : null,
            ])->all(),
            'journal_recent' => $journals->map(fn ($j) => [
                'date' => $j->entry_date->format('Y-m-d'),
                'energy' => $j->energy_level,
                'sleep' => $j->sleep_quality,
                'mood' => $j->mood,
                'has_notes' => filled($j->gratitude) || filled($j->intention),
            ])->all(),
        ];
    }
}
