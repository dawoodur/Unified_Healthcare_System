<?php

namespace App\Http\Controllers\Api\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BloodDonation;
use App\Services\RewardPointService;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of the hospital's two blood screens — the emergency requests
 * it has sent, and the donation claims waiting for someone at the desk to
 * confirm.
 *
 * Confirming/rejecting a donation awards reward points and is left on the
 * existing web routes (HospitalBloodDonationController) so that side
 * effect keeps running through one code path only; this controller is
 * read-only.
 */
class BloodController extends Controller
{
    public function requests()
    {
        $bloodRequests = Auth::user()->hospital
            ->bloodRequests()
            ->orderByDesc('blood_request_id')
            ->get();

        return response()->json([
            'requests' => $bloodRequests->map(fn ($request) => [
                'blood_request_id' => (int) $request->blood_request_id,
                'blood_group' => $request->blood_group,
                'message' => $request->message,
                // How many eligible donors were actually emailed when this
                // request went out — the only "did it reach anyone" signal.
                'recipient_count' => (int) $request->recipient_count,
                'created_label' => $request->created_at?->format('M j, Y'),
            ]),
        ]);
    }

    public function donations(RewardPointService $rewardPoints)
    {
        $hospitalId = Auth::user()->hospital->hospital_id;

        $pending = BloodDonation::where('hospital_id', $hospitalId)
            ->where('status', 'pending')
            ->with('patient')
            ->orderBy('donated_at')
            ->get();

        $reviewed = BloodDonation::where('hospital_id', $hospitalId)
            ->whereIn('status', ['confirmed', 'rejected'])
            ->with('patient')
            ->orderByDesc('confirmed_at')
            ->orderByDesc('donation_id')
            ->limit(25)
            ->get();

        return response()->json([
            'pending' => $pending->map(fn (BloodDonation $d) => $this->shape($d)),
            'reviewed' => $reviewed->map(fn (BloodDonation $d) => $this->shape($d)),
            'points_per_donation' => $rewardPoints->pointsFor('blood_donation'),
        ]);
    }

    private function shape(BloodDonation $donation): array
    {
        return [
            'donation_id' => (int) $donation->donation_id,
            'patient_name' => $donation->patient?->full_name,
            'blood_group' => $donation->patient?->blood_group,
            'donated_label' => $donation->donated_at?->format('M j, Y'),
            'status' => $donation->status,
            'reject_reason' => $donation->reject_reason,
        ];
    }
}
