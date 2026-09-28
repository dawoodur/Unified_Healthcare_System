<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Illuminate\Support\Facades\Auth;

/**
 * Admin review of a patient-scanned prescription (PrescriptionScanController)
 * before its medicines can actually be ordered — see
 * Patient::unverifiedScannedMedicineIds() and MedicineOrderService::placeOrder()
 * for the gate this approve/reject unlocks or keeps shut. Same shape as
 * AdminMedicineDraftController: the React ScannedPrescriptions page fetches
 * the queue via the JSON API and posts here (a plain form, same convention
 * as every other admin approve/reject action) to actually change anything.
 */
class AdminScannedPrescriptionController extends Controller
{
    public function approve(Prescription $prescription)
    {
        $this->authorizeScanned($prescription);

        $prescription->update([
            'verification_status' => 'verified',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Verified — the patient can now order these medicines.');
    }

    public function reject(Prescription $prescription)
    {
        $this->authorizeScanned($prescription);

        $prescription->update([
            'verification_status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Rejected — nothing from this scan can be ordered.');
    }

    private function authorizeScanned(Prescription $prescription): void
    {
        if (!$prescription->isScanned()) {
            abort(404);
        }
    }
}
