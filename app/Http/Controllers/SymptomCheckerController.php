<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use App\Models\DiseaseKeyword;
use App\Models\Doctor;
use App\Models\SymptomSpecialtyMap;
use Illuminate\Http\Request;

/**
 * A rule-based (not AI) symptom -> specialty suggester: a patient
 * describes what's wrong in plain text, this looks for every keyword
 * from symptom_specialty_map that appears anywhere in it, sums each
 * matched keyword's weight per specialty, and ranks specialties by that
 * total. Purely string matching — no external API, no model.
 *
 * The same input also runs against disease_keywords (see
 * predictConditions() below) to suggest possible conditions —
 * deliberately labeled "not a diagnosis" everywhere it's shown, since
 * this is the same plain string matching, not a medical model.
 */
class SymptomCheckerController extends Controller
{
    /** Shows the blank symptom-checker form (GET /patient/symptom-checker). */
    public function index()
    {
        return view('patient.symptom-checker', ['symptoms' => null, 'results' => null, 'doctors' => null, 'conditions' => null]);
    }

    /** Runs the keyword match and shows ranked specialties + matching doctors (POST /patient/symptom-checker). */
    public function search(Request $request)
    {
        $data = $request->validate([
            'symptoms' => ['required', 'string', 'max:500'],
        ]);

        $input = mb_strtolower($data['symptoms']);

        // Every keyword row whose phrase appears ANYWHERE in what the
        // patient typed — e.g. "chest pain" matches "I've had chest pain
        // since yesterday". Loaded in full since this table is small
        // (a few dozen rows) — a per-row LIKE query would be slower, not
        // faster, for a table this size.
        $matchedRows = SymptomSpecialtyMap::with('specialty')
            ->get()
            ->filter(fn (SymptomSpecialtyMap $row) => str_contains($input, mb_strtolower($row->keyword)));

        $results = $matchedRows
            ->groupBy('specialty_id')
            ->map(fn ($rows) => [
                'specialty' => $rows->first()->specialty,
                'score' => $rows->sum('weight'),
                'matchedKeywords' => $rows->pluck('keyword')->unique()->values(),
            ])
            ->sortByDesc('score')
            ->values();

        // Doctors from the top 3 matched specialties, best-rated first.
        $topSpecialtyIds = $results->take(3)->pluck('specialty.specialty_id');

        $doctors = collect();
        if ($topSpecialtyIds->isNotEmpty()) {
            $doctors = Doctor::where('verification_status', 'approved')
                ->whereHas('specialties', fn ($q) => $q->whereIn('specialties.specialty_id', $topSpecialtyIds))
                ->with(['specialties', 'account'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('reviews_avg_rating')
                ->get();
        }

        return view('patient.symptom-checker', [
            'symptoms' => $data['symptoms'],
            'results' => $results,
            'doctors' => $doctors,
            'conditions' => $this->predictConditions($input),
        ]);
    }

    /**
     * Same keyword-matching idea as the specialty match above, pointed at
     * disease_keywords instead: every disease with at least one matching
     * keyword, ranked by match count. Never returns a single "answer" —
     * always a ranked list, so the view can't accidentally read as "you
     * have X."
     */
    private function predictConditions(string $input)
    {
        $matchedRows = DiseaseKeyword::with('disease')
            ->get()
            ->filter(fn (DiseaseKeyword $row) => str_contains($input, mb_strtolower($row->keyword)));

        return $matchedRows
            ->groupBy('disease_id')
            ->map(fn ($rows) => [
                'disease' => $rows->first()->disease,
                'matchedKeywords' => $rows->pluck('keyword')->unique()->values(),
            ])
            ->sortByDesc(fn ($row) => $row['matchedKeywords']->count())
            ->take(5)
            ->values();
    }
}
