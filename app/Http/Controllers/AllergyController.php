<?php

namespace App\Http\Controllers;

use App\Models\Allergy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a patient manage their own known-allergy list. Shown prominently
 * wherever their records are viewed (their own Medical Records page, and
 * a doctor's view of their records once granted access) — including as a
 * safety warning on the e-prescription form, since that's the whole point
 * of tracking this.
 */
class AllergyController extends Controller
{
    /** Shows the "add an allergy" form (GET /patient/allergies/create). */
    public function create()
    {
        $patient = Auth::user()->patient;
        $allergies = $patient->allergies()
            ->with('recordedBy')
            ->orderByDesc('created_at')
            ->get();

        return view('patient.allergies-create', compact('allergies'));
    }

    /** Adds one allergy to the patient's own list (POST /patient/allergies). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'allergen' => ['required', 'string', 'max:150'],
            'reaction' => ['nullable', 'string', 'max:255'],
        ]);

        Auth::user()->patient->allergies()->create([
            'allergen' => $data['allergen'],
            'reaction' => $data['reaction'] ?? null,
            'recorded_by_account_id' => Auth::id(),
        ]);

        return redirect()->route('patient.records')->with('success', 'Allergy added.');
    }

    /** Removes one allergy from the patient's own list (POST /patient/allergies/{allergy}/remove). */
    public function destroy(Allergy $allergy)
    {
        if ($allergy->patient_id !== Auth::user()->patient->patient_id) {
            abort(403);
        }

        $allergy->delete();

        return back()->with('success', 'Allergy removed.');
    }
}
