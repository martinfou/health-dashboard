<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\WeightReading;
use App\Models\BodyMeasurement;
use App\Models\NutritionLog;
use App\Models\ActivityLog;

class ImportController extends Controller
{
    public function index()
    {
        return Inertia::render('Import');
    }

    public function uploadCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'type' => 'required|in:fatsecret,arboleaf_weight,arboleaf_measurements,garmin',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        
        // Remove BOM and header lines (FatSecret headers start with #)
        $dataLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;
            if (str_starts_with($line, 'Date') || str_starts_with($line, '"Date')) continue;
            if (str_starts_with($line, 'Daily Average') || str_starts_with($line, 'Total')) continue;
            if (str_starts_with($line, 'Period Summary') || str_starts_with($line, 'Report Details')) continue;
            $dataLines[] = $line;
        }

        $user = $request->user();
        $imported = 0;

        switch ($request->type) {
            case 'fatsecret':
                $imported = $this->importFatSecret($user, $dataLines);
                break;
            case 'garmin':
                $imported = $this->importGarmin($user, $content);
                break;
        }

        return back()->with('success', "{$imported} entrées importées avec succès !");
    }

    private function importFatSecret($user, array $lines)
    {
        $count = 0;
        foreach ($lines as $line) {
            // FatSecret CSV: "Friday, February 20, 2026",1744,62.7,12.519,210.26,25.4,76.23,87.78,2794.96,528.96,1406.9
            $parts = str_getcsv($line);
            if (count($parts) < 7) continue;

            $dateStr = trim($parts[0], '"');
            $date = date('Y-m-d', strtotime($dateStr));
            if (!$date || $date < '2020-01-01') continue;

            NutritionLog::updateOrCreate(
                ['user_id' => $user->id, 'logged_at' => $date],
                [
                    'calories' => (int) round((float) ($parts[1] ?? 0)),
                    'fat_g' => round((float) ($parts[2] ?? 0), 1),
                    'carbs_g' => round((float) ($parts[4] ?? 0), 1),
                    'protein_g' => round((float) ($parts[7] ?? 0), 1),
                    'fiber_g' => round((float) ($parts[5] ?? 0), 1),
                    'sugar_g' => round((float) ($parts[6] ?? 0), 1),
                    'source' => 'FatSecret',
                ]
            );
            $count++;
        }
        return $count;
    }

    private function importGarmin($user, string $content)
    {
        $count = 0;
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, ',')) continue;
            
            $parts = str_getcsv($line);
            if (count($parts) < 2) continue;

            // Detect Garmin data type from content
            $first = strtolower($parts[0] ?? '');

            if (str_contains($content, 'Activity Type') && count($parts) >= 3) {
                // Monthly activity summary: "Jan 2026,Gym & Fitness Equipment,6"
                $month = date('Y-m-d', strtotime('first day of ' . $parts[0]));
                $sessions = (int) ($parts[2] ?? 0);
                
                $activity = ActivityLog::firstOrNew(['user_id' => $user->id, 'activity_date' => $month]);
                $activity->gym_sessions = ($activity->gym_sessions ?? 0) + $sessions;
                $activity->source = 'Garmin Connect';
                $activity->save();
                $count++;
            }
        }
        return $count;
    }
}
