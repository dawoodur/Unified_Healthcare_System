<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorHospitalApplication;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CareerController extends Controller
{
    public function index()
    {
        $doctor = Auth::user()->doctor;

        $activeHospitalIds = $doctor->activeHospitals()
            ->pluck('hospitals.hospital_id')
            ->map(fn ($id) => (int) $id);

        $applicationsReady = Schema::hasTable('doctor_hospital_applications');

        $applications = $applicationsReady
            ? DoctorHospitalApplication::where('doctor_id', $doctor->doctor_id)
                ->orderByDesc('updated_at')
                ->get()
                ->keyBy('hospital_id')
            : collect();

        $hospitals = Hospital::query()
            ->withCount('activeDoctors')
            ->orderBy('hospital_name')
            ->get()
            ->map(function (Hospital $hospital) use ($activeHospitalIds, $applications) {
                $application = $applications->get($hospital->hospital_id);

                return [
                    'hospital_id' => (int) $hospital->hospital_id,
                    'hospital_name' => $hospital->hospital_name,
                    'registration_number' => $hospital->registration_number,
                    'address' => $hospital->address,
                    'city' => $hospital->city,
                    'full_address' => $hospital->fullAddress(),
                    'active_doctors_count' => (int) $hospital->active_doctors_count,
                    'is_assigned' => $activeHospitalIds->contains((int) $hospital->hospital_id),
                    'application' => $application ? [
                        'application_id' => (int) $application->application_id,
                        'status' => $application->status,
                        'created_at' => $application->created_at?->toIso8601String(),
                        'updated_at' => $application->updated_at?->toIso8601String(),
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'applications_ready' => $applicationsReady,
            'stats' => [
                'active_hospitals' => $activeHospitalIds->count(),
                'pending_applications' => $applications->where('status', 'pending')->count(),
                'available_hospitals' => $hospitals->where('is_assigned', false)->count(),
                'total_applications' => $applications->count(),
            ],
            'hospitals' => $hospitals,
        ]);
    }

    public function apply(Request $request, Hospital $hospital)
    {
        if (! Schema::hasTable('doctor_hospital_applications')) {
            return response()->json([
                'message' => 'Career applications are not initialized yet. Run php artisan migrate once, then try again.',
            ], 503);
        }

        $doctor = Auth::user()->doctor;

        $alreadyAssigned = $doctor->activeHospitals()
            ->where('hospitals.hospital_id', $hospital->hospital_id)
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'message' => 'You already have an active chamber assignment at this hospital.',
            ], 422);
        }

        $existing = DoctorHospitalApplication::where('doctor_id', $doctor->doctor_id)
            ->where('hospital_id', $hospital->hospital_id)
            ->first();

        if ($existing && in_array($existing->status, ['pending', 'accepted'], true)) {
            return response()->json([
                'message' => $existing->status === 'pending'
                    ? 'You already have a pending application for this hospital.'
                    : 'This hospital has already accepted your application.',
            ], 422);
        }

        $application = DoctorHospitalApplication::updateOrCreate(
            [
                'doctor_id' => $doctor->doctor_id,
                'hospital_id' => $hospital->hospital_id,
            ],
            [
                'status' => 'pending',
            ]
        );

        return response()->json([
            'message' => "Application sent to {$hospital->hospital_name}.",
            'application_id' => (int) $application->application_id,
        ], 201);
    }

    public function withdraw(DoctorHospitalApplication $application)
    {
        if (! Schema::hasTable('doctor_hospital_applications')) {
            return response()->json([
                'message' => 'Career applications are not initialized yet. Run php artisan migrate once, then try again.',
            ], 503);
        }

        $doctor = Auth::user()->doctor;

        if ((int) $application->doctor_id !== (int) $doctor->doctor_id) {
            abort(403);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending applications can be withdrawn.',
            ], 422);
        }

        $application->update(['status' => 'withdrawn']);

        return response()->json([
            'message' => 'Application withdrawn.',
        ]);
    }
}
