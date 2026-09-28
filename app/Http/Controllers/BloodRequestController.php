<?php

namespace App\Http\Controllers;

use App\Services\BloodDonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Hospital-facing side of blood donation: send an emergency email for a
 * specific blood type, see the history of past requests. See
 * BloodDonationService::sendEmergencyRequest() for exactly who gets
 * emailed and BloodDonationController for the patient-facing side.
 */
class BloodRequestController extends Controller
{
    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

    public function __construct(private BloodDonationService $donations)
    {
    }

    /** Hospital: history of emergency requests they've sent (GET /hospital/blood-requests). */
    public function index()
    {
        $bloodRequests = Auth::user()->hospital
            ->bloodRequests()
            ->orderByDesc('blood_request_id')
            ->get();

        return view('hospital.blood-requests.index', compact('bloodRequests'));
    }

    /** Hospital: shows the "send an emergency request" form, with a live eligible-donor count per blood type (GET /hospital/blood-requests/create). */
    public function create()
    {
        // Shown next to each option in the <select> so a hospital can see
        // roughly how many people would actually be emailed before
        // picking a type — cheap to compute, there are only 8 types.
        $eligibleCounts = collect(self::BLOOD_GROUPS)
            ->mapWithKeys(fn ($group) => [$group => $this->donations->eligibleDonors($group)->count()]);

        return view('hospital.blood-requests.create', compact('eligibleCounts'));
    }

    /** Hospital: sends the emergency request (POST /hospital/blood-requests). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'blood_group' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $hospital = Auth::user()->hospital;
        $result = $this->donations->sendEmergencyRequest($hospital, $data['blood_group'], $data['message'] ?? null);

        return redirect()->route('hospital.blood-requests')
            ->with('success', "Emergency request sent — emailed {$result['sent']} of {$result['total']} eligible {$data['blood_group']} donor(s).");
    }
}
