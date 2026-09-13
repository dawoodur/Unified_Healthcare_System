<?php

namespace App\Http\Controllers;

use App\Models\FacilityBooking;
use App\Models\MedicineOrder;
use App\Services\RewardPointService;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{
    public function __construct(private RewardPointService $rewardPoints)
    {
    }

    /** Patient: current reward balance, earning rules, redemption value, and immutable point ledger. */
    public function index()
    {
        $patient = Auth::user()->patient;

        $ledger = $patient->rewardPointLedger()
            ->orderByDesc('created_at')
            ->orderByDesc('ledger_id')
            ->get();

        $medicineOrderIds = $ledger
            ->whereIn('source_type', ['medicine_purchase', 'redemption'])
            ->pluck('source_id')
            ->filter()
            ->unique();

        $labBookingIds = $ledger
            ->where('source_type', 'lab_test_purchase')
            ->pluck('source_id')
            ->filter()
            ->unique();

        $medicineOrders = MedicineOrder::whereIn('order_id', $medicineOrderIds)
            ->with('pharmacy')
            ->get()
            ->keyBy('order_id');

        $labBookings = FacilityBooking::whereIn('facility_booking_id', $labBookingIds)
            ->with(['facilityType', 'hospital'])
            ->get()
            ->keyBy('facility_booking_id');

        $maxRedeemable = $this->rewardPoints->maxRedeemable($patient);
        $redeemPercent = $this->rewardPoints->percentFor($maxRedeemable);

        $earningPoints = [
            'review' => $this->rewardPoints->pointsFor('review'),
            'medicine_purchase' => $this->rewardPoints->pointsFor('medicine_purchase'),
            'lab_test_purchase' => $this->rewardPoints->pointsFor('lab_test_purchase'),
        ];

        $earnedTotal = (int) $ledger->where('points', '>', 0)->sum('points');
        $spentTotal = abs((int) $ledger->where('points', '<', 0)->sum('points'));

        return view('patient.rewards', compact(
            'patient',
            'ledger',
            'medicineOrders',
            'labBookings',
            'maxRedeemable',
            'redeemPercent',
            'earningPoints',
            'earnedTotal',
            'spentTotal'
        ));
    }
}
