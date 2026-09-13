<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Services\BloodDonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Patient-facing side of blood donation: check your own eligibility, log
 * a donation, see your history. The hospital-facing "send an emergency
 * request" side lives in BloodRequestController — both share the same
 * BloodDonationService for the actual eligibility rule (every 3 months).
 */
class BloodDonationController extends Controller
{
    public function __construct(private BloodDonationService $donations)
    {
    }

    /** Patient: eligibility status + donation history (GET /patient/blood-donations). */
    public function index()
    {
        $patient = Auth::user()->patient;

        $history = $patient->bloodDonations()
            ->with('hospital')
            ->orderByDesc('donated_at')
            ->get();

        return view('patient.blood-donations.index', [
            'isEligible' => $this->donations->isEligible($patient),
            'nextEligibleDate' => $this->donations->nextEligibleDate($patient),
            'history' => $history,
        ]);
    }

    /** Patient: shows the "log a donation" form (GET /patient/blood-donations/create). */
    public function create()
    {
        $patient = Auth::user()->patient;

        if (!$this->donations->isEligible($patient)) {
            return redirect()->route('patient.blood-donations')
                ->withErrors(['status' => 'You can donate again on ' . $this->donations->nextEligibleDate($patient)->format('M j, Y') . '.']);
        }

        $hospitals = Hospital::orderBy('hospital_name')->get();

        return view('patient.blood-donations.create', compact('hospitals'));
    }

    /** Patient: submits a logged donation (POST /patient/blood-donations). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'donated_at' => ['required', 'date_format:Y-m-d'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,hospital_id'],
        ]);

        $patient = Auth::user()->patient;
        $hospital = !empty($data['hospital_id']) ? Hospital::find($data['hospital_id']) : null;

        $result = $this->donations->recordDonation($patient, $data['donated_at'], $hospital);

        if (!$result['ok']) {
            return back()->withErrors(['status' => $result['message']]);
        }

        return redirect()->route('patient.blood-donations')->with('success', $result['message']);
    }
}
