<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorHospitalAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a hospital manage which doctors work there. This is the ONLY place
 * a doctor_hospital_assignments row gets created — a doctor has no way to
 * add themselves to a hospital's staff list, only a hospital can do that
 * (matching how it works in real life: a hospital hires/assigns a doctor,
 * not the other way around).
 */
class HospitalDoctorController extends Controller
{
    /** Shows currently-assigned doctors (GET /hospital/doctors). */
    public function index()
    {
        $hospital = Auth::user()->hospital;

        $assignedDoctors = $hospital->activeDoctors()->with('specialties')->orderBy('full_name')->get();

        return view('hospital.assign-doctor', compact('assignedDoctors'));
    }

    /** Shows the "search and assign a doctor" form (GET /hospital/doctors/create). */
    public function create(Request $request)
    {
        $hospital = Auth::user()->hospital;

        // Only approved doctors not already actively assigned here can be searched/added.
        // "doctors.doctor_id" (not just "doctor_id") because the pivot table
        // doctor_hospital_assignments ALSO has a doctor_id column — an
        // unqualified name is ambiguous between the two once this relation
        // joins them, and MySQL rejects the query outright.
        $searchResults = collect();
        if ($request->filled('search')) {
            $alreadyAssignedIds = $hospital->activeDoctors()->pluck('doctors.doctor_id');

            $searchResults = Doctor::where('verification_status', 'approved')
                ->where('full_name', 'like', '%' . $request->string('search') . '%')
                ->whereNotIn('doctor_id', $alreadyAssignedIds)
                ->with('specialties')
                ->orderBy('full_name')
                ->get();
        }

        return view('hospital.doctors-create', [
            'searchResults' => $searchResults,
            'searchTerm' => $request->string('search')->toString(),
        ]);
    }

    /** Assigns one doctor to this hospital (POST /hospital/doctors/{doctor}/assign). */
    public function assign(Doctor $doctor)
    {
        if ($doctor->verification_status !== 'approved') {
            return back()->withErrors(['doctor' => 'Only verified doctors can be assigned.']);
        }

        $hospital = Auth::user()->hospital;

        // A doctor might have been assigned here before and later revoked —
        // reuse that same row (reactivate it) instead of trying to insert a
        // second one, which the database's unique(doctor_id, hospital_id)
        // constraint wouldn't allow anyway.
        DoctorHospitalAssignment::updateOrCreate(
            ['doctor_id' => $doctor->doctor_id, 'hospital_id' => $hospital->hospital_id],
            ['status' => 'active']
        );

        return redirect()->route('hospital.doctors')->with('success', "Dr. {$doctor->full_name} is now assigned to your hospital.");
    }

    /** Removes a doctor from this hospital's active staff (POST /hospital/doctors/{doctor}/revoke). */
    public function revoke(Doctor $doctor)
    {
        $hospital = Auth::user()->hospital;

        DoctorHospitalAssignment::where('doctor_id', $doctor->doctor_id)
            ->where('hospital_id', $hospital->hospital_id)
            ->update(['status' => 'revoked']);

        return back()->with('success', "Dr. {$doctor->full_name} has been removed from your hospital.");
    }
}
