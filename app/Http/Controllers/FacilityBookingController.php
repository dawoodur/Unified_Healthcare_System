<?php

namespace App\Http\Controllers;

use App\Models\FacilityBooking;
use App\Models\HospitalFacility;
use App\Services\FacilityBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The patient-facing side of facility booking (view one hospital's open
 * dates for a facility, book one, see your own booking list). Mirrors
 * AppointmentController, but for hospital facilities instead of doctors —
 * all the actual date/capacity math lives in FacilityBookingService.
 */
class FacilityBookingController extends Controller
{
    public function __construct(private FacilityBookingService $bookings)
    {
    }

    /**
     * Patient: view one hospital's booking options for one facility
     * (GET /patient/facilities/{offering}/book). Bed-type facilities (ICU,
     * Cabin, etc.) show "is one free right now?"; everything else shows a
     * 14-day date list — see FacilityType::is_occupancy.
     */
    public function show(HospitalFacility $offering)
    {
        $offering->load('hospital', 'facilityType.category');
        $patient = Auth::user()->patient;

        if ($offering->facilityType->is_occupancy) {
            $availability = $this->bookings->currentAvailability($offering, $patient);
            return view('patient.book-facility', compact('offering', 'availability'));
        }

        $dates = $this->bookings->upcomingDates($offering, $patient);
        return view('patient.book-facility', compact('offering', 'dates'));
    }

    /** Patient: submit a booking (POST /patient/facilities/{offering}/book). */
    public function book(Request $request, HospitalFacility $offering)
    {
        $patient = Auth::user()->patient;

        if ($offering->facilityType->is_occupancy) {
            $data = $request->validate([
                'days' => ['required', 'integer', 'min:1', 'max:90'],
            ]);
            $result = $this->bookings->bookOccupancy($patient, $offering, $data['days']);
        } else {
            $data = $request->validate([
                'date' => ['required', 'date_format:Y-m-d'],
            ]);
            $result = $this->bookings->book($patient, $offering, $data['date']);
        }

        if (!$result['ok']) {
            return back()->withErrors(['slot' => $result['message']]);
        }

        $message = $offering->facilityType->is_occupancy
            ? $result['message']
            : "Facility booked! Your serial number is #{$result['booking']->serial_number}.";

        return redirect()->route('patient.facility-bookings')->with('success', $message);
    }

    /** Patient: view their own facility bookings, grouped by current status (GET /patient/facility-bookings). */
    public function myBookings()
    {
        $patientId = Auth::user()->patient->patient_id;
        $with = ['hospital', 'facilityType.category'];

        $pending = FacilityBooking::where('patient_id', $patientId)
            ->where('status', 'booked')
            ->with($with)
            ->orderBy('booking_date')
            ->orderBy('serial_number')
            ->get();

        $completed = FacilityBooking::where('patient_id', $patientId)
            ->where('status', 'completed')
            ->with($with)
            ->orderByDesc('booking_date')
            ->orderByDesc('serial_number')
            ->get();

        $cancelled = FacilityBooking::where('patient_id', $patientId)
            ->where('status', 'cancelled')
            ->with($with)
            ->orderByDesc('booking_date')
            ->orderByDesc('serial_number')
            ->get();

        return view('patient.my-facility-bookings', compact('pending', 'completed', 'cancelled'));
    }
}
