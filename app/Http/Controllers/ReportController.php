<?php
namespace App\Http\Controllers;

use App\Models\BodyMeasurement;
use App\Models\NutritionLog;
use App\Models\WeightReading;
use App\Models\ActivityLog;
use App\Models\HealthReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $reports = HealthReport::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Reports', ['reports' => $reports]);
    }

    public function generate(Request $request)
    {
        $user = $request->user();

        // Compute summary data (same logic as dashboard but simplified for PDF)
        $firstW = WeightReading::where('user_id', $user->id)->orderBy('recorded_at')->first();
        $lastW = WeightReading::where('user_id', $user->id)->orderBy('recorded_at', 'desc')->first();
        $firstM = BodyMeasurement::where('user_id', $user->id)->orderBy('measured_at')->first();
        $lastM = BodyMeasurement::where('user_id', $user->id)->orderBy('measured_at', 'desc')->first();

        $weightReadings = WeightReading::where('user_id', $user->id)->orderBy('recorded_at')->get();
        $measurements = BodyMeasurement::where('user_id', $user->id)->orderBy('measured_at')->get();
        $nutritionMonthly = NutritionLog::where('user_id', $user->id)
            ->select(DB::raw("strftime('%Y-%m', logged_at) as month"),
                DB::raw('AVG(calories) as avg_cal'), DB::raw('AVG(protein_g) as avg_prot'),
                DB::raw('AVG(fat_g) as avg_fat'), DB::raw('AVG(carbs_g) as avg_carbs'))
            ->groupBy('month')->orderBy('month')->get();
        $activityMonthly = ActivityLog::where('user_id', $user->id)
            ->select(DB::raw("strftime('%Y-%m', activity_date) as month"),
                DB::raw('SUM(gym_sessions) as gym'), DB::raw('SUM(steps) as steps'), DB::raw('AVG(heart_rate_avg) as hr'))
            ->groupBy('month')->orderBy('month')->get();

        $summary = [
            'current_weight' => $lastW?->weight_lb ?? 0,
            'total_loss' => $firstW && $lastW ? round($firstW->weight_lb - $lastW->weight_lb, 1) : 0,
            'waist_loss' => $firstM && $lastM ? round($firstM->waist_cm - $lastM->waist_cm, 1) : 0,
            'whr' => $lastM?->whr ?? 0,
            'total_gym' => ActivityLog::where('user_id', $user->id)->sum('gym_sessions'),
            'nutrition_monthly' => $nutritionMonthly->map(fn ($row) => [
                'month' => $row->month,
                'avg_cal' => $row->avg_cal,
                'avg_prot' => $row->avg_prot,
                'avg_fat' => $row->avg_fat,
                'avg_carbs' => $row->avg_carbs,
            ])->values()->all(),
            'activity_monthly' => $activityMonthly->map(fn ($row) => [
                'month' => $row->month,
                'gym' => $row->gym,
                'steps' => $row->steps,
                'hr' => $row->hr,
            ])->values()->all(),
            'generated_at' => now()->format('Y-m-d H:i'),
            'period' => ($firstW?->recorded_at ?? 'N/A') . ' → ' . ($lastW?->recorded_at ?? 'N/A'),
        ];

        // Save report
        $report = HealthReport::create([
            'user_id' => $user->id,
            'title' => 'Rapport Santé - ' . now()->format('d/m/Y'),
            'period_start' => $firstW?->recorded_at ?? now(),
            'period_end' => $lastW?->recorded_at ?? now(),
            'summary_data' => $summary,
        ]);

        return redirect()->route('reports')->with('success', 'Rapport généré !');
    }

    public function downloadPdf($id)
    {
        $report = HealthReport::where('user_id', auth()->id())->findOrFail($id);

        $data = [
            'report' => $report,
            'summary' => $this->normalizeSummaryForPdf($report->summary_data ?? []),
            'user' => $report->user,
        ];

        $pdf = Pdf::loadView('pdfs.health-report', $data);

        return $pdf->download("rapport-sante-{$report->id}.pdf");
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function normalizeSummaryForPdf(array $summary): array
    {
        $summary['nutrition_monthly'] = collect($summary['nutrition_monthly'] ?? [])
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        $summary['activity_monthly'] = collect($summary['activity_monthly'] ?? [])
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        return $summary;
    }
}
