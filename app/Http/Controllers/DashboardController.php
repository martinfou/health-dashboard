<?php
namespace App\Http\Controllers;

use App\Models\BodyMeasurement;
use App\Models\NutritionLog;
use App\Models\WeightReading;
use App\Models\ActivityLog;
use App\Services\HealthInsights\HealthInsightService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private readonly HealthInsightService $insightService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        // Weight readings
        $weightReadings = WeightReading::where('user_id', $userId)
            ->orderBy('recorded_at')->get(['recorded_at', 'weight_lb', 'bmi', 'body_fat_pct']);

        // Body measurements
        $measurements = BodyMeasurement::where('user_id', $userId)
            ->orderBy('measured_at')->get(['measured_at', 'waist_cm', 'hips_cm', 'abdomen_cm', 'whr']);

        // Nutrition logs (last 30 days)
        $nutrition = NutritionLog::where('user_id', $userId)
            ->where('logged_at', '>=', now()->subDays(60))
            ->orderBy('logged_at')->get(['logged_at', 'calories', 'protein_g', 'fat_g', 'carbs_g']);

        // Monthly activity summary
        $activityMonthly = ActivityLog::where('user_id', $userId)
            ->select(
                DB::raw("strftime('%Y-%m', activity_date) as month"),
                DB::raw('SUM(steps) as total_steps'),
                DB::raw('SUM(gym_sessions) as total_gym'),
                DB::raw('SUM(calories_burned) as total_cals_burned'),
                DB::raw('AVG(heart_rate_avg) as avg_hr')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Monthly nutrition averages
        $nutritionMonthly = NutritionLog::where('user_id', $userId)
            ->select(
                DB::raw("strftime('%Y-%m', logged_at) as month"),
                DB::raw('AVG(calories) as avg_calories'),
                DB::raw('AVG(protein_g) as avg_protein'),
                DB::raw('AVG(fat_g) as avg_fat'),
                DB::raw('AVG(carbs_g) as avg_carbs')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // KPIs
        $firstW = WeightReading::where('user_id', $userId)->orderBy('recorded_at')->first();
        $lastW = WeightReading::where('user_id', $userId)->orderBy('recorded_at', 'desc')->first();
        $totalLoss = $firstW && $lastW ? round($firstW->weight_lb - $lastW->weight_lb, 1) : 0;

        $firstM = BodyMeasurement::where('user_id', $userId)->orderBy('measured_at')->first();
        $lastM = BodyMeasurement::where('user_id', $userId)->orderBy('measured_at', 'desc')->first();
        $waistLoss = $firstM && $lastM ? round($firstM->waist_cm - $lastM->waist_cm, 1) : 0;

        $totalGym = ActivityLog::where('user_id', $userId)->sum('gym_sessions');

        $insights = $this->insightService->ensureInsights($user);

        return Inertia::render('Dashboard', [
            'healthInsights' => $this->insightService->toArray($insights),
            'kpis' => [
                'current_weight' => $lastW?->weight_lb ?? 0,
                'total_weight_loss' => $totalLoss,
                'current_whr' => $lastM?->whr ?? 0,
                'waist_loss_cm' => $waistLoss,
                'total_gym_sessions' => $totalGym,
                'avg_calories' => round($nutrition->avg('calories') ?? 0),
            ],
            'weightReadings' => $weightReadings,
            'bodyMeasurements' => $measurements,
            'nutritionLogs' => $nutrition,
            'activityMonthly' => $activityMonthly,
            'nutritionMonthly' => $nutritionMonthly,
        ]);
    }
}
