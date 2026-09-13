<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a doctor log a patient's vital signs (blood pressure, heart rate,
 * temperature, height/weight) right after a visit — tied to the specific
 * appointment, same "only your own completed visits" rule as issuing a
 * prescription (see PrescriptionController). One vitals reading per
 * appointment; editing isn't supported here, only adding.
 */
class VitalController extends Controller
{
    // Which vitals fields get their own trend chart on the patient page
    // below, and how each one is labeled/units — one place to add a new
    // trackable vital later instead of repeating this in the method body.
    private const TREND_FIELDS = [
        'weight_kg' => ['label' => 'Weight', 'unit' => 'kg'],
        'blood_pressure_systolic' => ['label' => 'Blood Pressure (Systolic)', 'unit' => 'mmHg'],
        'blood_pressure_diastolic' => ['label' => 'Blood Pressure (Diastolic)', 'unit' => 'mmHg'],
        'heart_rate' => ['label' => 'Heart Rate', 'unit' => 'bpm'],
        'temperature_celsius' => ['label' => 'Temperature', 'unit' => '°C'],
    ];

    /** Patient: trend charts of their own vitals over time (GET /patient/vitals). */
    public function index()
    {
        $vitals = Auth::user()->patient
            ->vitals()
            ->whereNotNull('recorded_at')
            ->orderBy('recorded_at')
            ->get();

        $trends = collect(self::TREND_FIELDS)->map(function ($meta, $field) use ($vitals) {
            $readings = $vitals->whereNotNull($field)->map(fn ($v) => [
                'label' => $v->recorded_at->format('M j'),
                'value' => (float) $v->$field,
                'formatted' => $v->$field . ' ' . $meta['unit'],
            ]);

            return ['label' => $meta['label'], 'readings' => $readings->values()];
        });

        return view('patient.vitals', compact('trends', 'vitals'));
    }

    /** Shows the "log vitals" form for one appointment (GET /doctor/appointments/{appointment}/vitals). */
    public function create(Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        if ($appointment->vital) {
            return redirect()->route('doctor.appointments')->withErrors(['vitals' => 'Vitals have already been logged for this appointment.']);
        }

        if ($appointment->status !== 'completed') {
            return redirect()->route('doctor.appointments')->withErrors(['vitals' => 'Mark this appointment as visited before logging vitals.']);
        }

        return view('doctor.vitals-create', compact('appointment'));
    }

    /** Saves the vitals reading (POST /doctor/appointments/{appointment}/vitals). */
    public function store(Request $request, Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        if ($appointment->vital) {
            return back()->withErrors(['vitals' => 'Vitals have already been logged for this appointment.']);
        }

        $data = $request->validate([
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:0', 'max:200'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'temperature_celsius' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:999.9'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:999.9'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment->patient->vitals()->create(array_merge($data, [
            'appointment_id' => $appointment->appointment_id,
            'recorded_by_doctor_id' => $appointment->doctor_id,
        ]));

        return redirect()->route('doctor.appointments')->with('success', 'Vitals logged.');
    }

    private function authorizeDoctor(Appointment $appointment): void
    {
        if ($appointment->doctor_id !== Auth::user()->doctor->doctor_id) {
            abort(403);
        }
    }
}
