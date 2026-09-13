<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\SymptomSpecialtyMap;
use Illuminate\Http\Request;

/**
 * The rule-based "which kind of doctor do I need" + FAQ search assistant —
 * no external API or ML, just keyword matching against
 * symptom_specialty_map and faq_keywords (both seeded in
 * DatabaseSeeder). A specialty's score is the sum of the weight of every
 * one of its mapped keywords found inside the patient's free-text
 * description — simple enough to compute in PHP over a small table,
 * same "fetch then filter in PHP" style as AdminController::searchUser().
 */
class AssistantController extends Controller
{
    /** GET /patient/assistant?q=... */
    public function index(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $inputLower = mb_strtolower($query);

        $specialtySuggestions = collect();
        $faqMatches = collect();

        if ($query !== '') {
            $specialtySuggestions = SymptomSpecialtyMap::with('specialty')
                ->get()
                ->filter(fn (SymptomSpecialtyMap $row) => str_contains($inputLower, mb_strtolower($row->keyword)))
                ->groupBy('specialty_id')
                ->map(fn ($rows) => (object) [
                    'specialty' => $rows->first()->specialty,
                    'score' => $rows->sum('weight'),
                    'matchedKeywords' => $rows->pluck('keyword')->unique()->values(),
                ])
                ->sortByDesc('score')
                ->values();

            $faqMatches = Faq::with('keywords')
                ->get()
                ->filter(fn (Faq $faq) => $faq->keywords->contains(
                    fn ($kw) => str_contains($inputLower, mb_strtolower($kw->keyword))
                ))
                ->values();
        }

        $faqsByCategory = Faq::orderBy('category')->get()->groupBy(fn (Faq $f) => $f->category ?? 'General');

        return view('patient.assistant', compact('query', 'specialtySuggestions', 'faqMatches', 'faqsByCategory'));
    }
}
