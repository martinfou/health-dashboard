<?php
namespace App\Http\Controllers;

use App\Models\DailyJournal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $entries = DailyJournal::where('user_id', $request->user()->id)
            ->orderBy('entry_date', 'desc')
            ->paginate(30);

        $today = DailyJournal::where('user_id', $request->user()->id)
            ->where('entry_date', today())
            ->first();

        return Inertia::render('Journal', [
            'entries' => $entries,
            'today' => $today,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'energy_level' => 'nullable|integer|min:1|max:10',
            'sleep_quality' => 'nullable|integer|min:1|max:10',
            'mood' => 'nullable|integer|min:1|max:10',
            'gratitude' => 'nullable|string|max:2000',
            'intention' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['entry_date'] = today();

        DailyJournal::updateOrCreate(
            ['user_id' => $request->user()->id, 'entry_date' => today()],
            $validated
        );

        return back()->with('success', 'Journal enregistré !');
    }

    public function destroy($id)
    {
        $entry = DailyJournal::findOrFail($id);
        $entry->delete();
        return back()->with('success', 'Entrée supprimée.');
    }
}
