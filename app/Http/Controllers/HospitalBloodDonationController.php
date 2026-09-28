<?php

namespace App\Http\Controllers;

use App\Models\BloodDonation;
use App\Services\BloodDonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Hospital confirms the donations patients logged against it. Confirmation is
 * what starts the donor's 3-day countdown and credits their reward points —
 * see BloodDonationService::confirm().
 */
class HospitalBloodDonationController extends Controller
{
    public function __construct(private BloodDonationService $donations)
    {
    }

    /** Hospital: donations waiting on them, plus what they already reviewed (GET /hospital/blood-donations). */
    public function index()
    {
        $hospital = Auth::user()->hospital;

        $pending = BloodDonation::where('hospital_id', $hospital->hospital_id)
            ->where('status', 'pending')
            ->with('patient')
            ->orderBy('donated_at')
            ->get();

        $reviewed = BloodDonation::where('hospital_id', $hospital->hospital_id)
            ->whereIn('status', ['confirmed', 'rejected'])
            ->with('patient')
            ->orderByDesc('confirmed_at')
            ->orderByDesc('donation_id')
            ->limit(25)
            ->get();

        return view('hospital.blood-donations.index', [
            'pending' => $pending,
            'reviewed' => $reviewed,
            'pointsPerDonation' => app(\App\Services\RewardPointService::class)->pointsFor('blood_donation'),
        ]);
    }

    public function confirm(BloodDonation $donation)
    {
        $result = $this->donations->confirm($donation, Auth::user()->hospital);

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->withErrors(['status' => $result['message']]);
    }

    public function reject(Request $request, BloodDonation $donation)
    {
        $data = $request->validate(['reject_reason' => ['nullable', 'string', 'max:255']]);

        $result = $this->donations->reject($donation, Auth::user()->hospital, $data['reject_reason'] ?? null);

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->withErrors(['status' => $result['message']]);
    }
}
