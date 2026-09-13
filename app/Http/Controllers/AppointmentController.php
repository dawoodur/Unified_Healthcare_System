<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentWaitlist;
use App\Models\Doctor;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\PaymentMethod;
use App\Models\QueueStatus;
use App\Models\Specialty;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The patient-facing side of booking (search doctors, view a doctor's open
 * slots, book one, see your own appointment list) plus the doctor-facing
 * appointment list. All the actual slot math lives in AppointmentService —
 * this controller just calls it and picks a view.
 */
class AppointmentController extends Controller
{
    public function __construct(private AppointmentService $appointments)
    {
    }

    /** Patient: search/browse doctors, by specialty, name, u_id, availability, gender, and consultation type. */
    public function searchDoctors(Request $request)
    {
        $this->appointments->expireNoShows();

        $query = Doctor::where('verification_status', 'approved')
            ->with([
                'specialties',
                'account',
                'availabilityTemplates.hospital',
                'leaveDates',
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        $selectedSpecialtyId = $request->integer('specialty_id') ?: null;
        if ($selectedSpecialtyId) {
            $query->whereHas('specialties', function ($q) use ($selectedSpecialtyId) {
                $q->where('specialties.specialty_id', $selectedSpecialtyId);
            });
        }

        $name = trim((string) $request->get('name'));
        if ($name !== '') {
            $query->where('full_name', 'like', '%' . $name . '%');
        }

        // Accepts a plain number or the displayed tag itself ("doc15",
        // "#doc15") — strip everything but digits so either form works.
        $uidInput = trim((string) $request->get('u_id'));
        $uid = preg_replace('/\D/', '', $uidInput);
        if ($uid !== '') {
            $query->where('account_id', $uid);
        }

        $gender = in_array($request->get('gender'), ['male', 'female'], true)
            ? $request->get('gender')
            : null;
        if ($gender) {
            $query->where('gender', $gender);
        }

        $consultationType = in_array($request->get('consultation_type'), ['online', 'onsite'], true)
            ? $request->get('consultation_type')
            : null;
        if ($consultationType) {
            $query->whereHas('availabilityTemplates', function ($q) use ($consultationType) {
                $q->where('is_active', true)->where('mode', $consultationType);
            });
        }

        $availability = in_array($request->get('availability'), ['today', 'tomorrow', 'scheduled'], true)
            ? $request->get('availability')
            : null;
        if ($availability === 'today' || $availability === 'tomorrow') {
            $date = $availability === 'today' ? today() : today()->copy()->addDay();
            $query->whereHas('availabilityTemplates', function ($q) use ($date) {
                $q->where('is_active', true)->where('day_of_week', $date->dayOfWeek);
            })->whereDoesntHave('leaveDates', function ($q) use ($date) {
                $q->whereDate('leave_date', $date->toDateString());
            });
        } elseif ($availability === 'scheduled') {
            $query->whereHas('availabilityTemplates', fn ($q) => $q->where('is_active', true));
        }

        $sort = in_array($request->get('sort'), ['recommended', 'rating', 'fee_low', 'fee_high', 'name'], true)
            ? $request->get('sort')
            : 'recommended';

        match ($sort) {
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count')->orderBy('full_name'),
            'fee_low' => $query->orderBy('consultation_fee')->orderBy('full_name'),
            'fee_high' => $query->orderByDesc('consultation_fee')->orderBy('full_name'),
            'name' => $query->orderBy('full_name'),
            default => $query->orderByDesc('reviews_count')->orderByDesc('reviews_avg_rating')->orderBy('full_name'),
        };

        $doctors = $query->get();
        $specialties = Specialty::orderBy('specialty_name')->get();
        $patient = Auth::user()->patient;
        $favoriteDoctorIds = $patient->favoriteDoctors()->pluck('doctors.doctor_id');

        $appointmentWith = ['doctor.specialties', 'hospital', 'template'];
        $upcomingAppointment = Appointment::where('patient_id', $patient->patient_id)
            ->whereIn('status', self::PENDING_STATUSES)
            ->whereDate('appointment_date', '>=', today()->toDateString())
            ->with($appointmentWith)
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->first();

        $pastAppointments = Appointment::where('patient_id', $patient->patient_id)
            ->whereNotIn('status', self::PENDING_STATUSES)
            ->with($appointmentWith)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(3)
            ->get();

        return view('patient.search-doctors', [
            'doctors' => $doctors,
            'specialties' => $specialties,
            'selectedSpecialtyId' => $selectedSpecialtyId,
            'name' => $name,
            'uidInput' => $uidInput,
            'favoriteDoctorIds' => $favoriteDoctorIds,
            'availability' => $availability,
            'gender' => $gender,
            'consultationType' => $consultationType,
            'sort' => $sort,
            'upcomingAppointment' => $upcomingAppointment,
            'pastAppointments' => $pastAppointments,
        ]);
    }

    /** Patient: their bookmarked doctors (GET /patient/favorites). */
    public function favorites()
    {
        $doctors = Auth::user()->patient
            ->favoriteDoctors()
            ->with(['specialties', 'account'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('full_name')
            ->get();

        return view('patient.favorites', compact('doctors'));
    }

    /** Patient: bookmarks or un-bookmarks a doctor (POST /patient/doctors/{doctor}/favorite). */
    public function toggleFavorite(Doctor $doctor)
    {
        $patient = Auth::user()->patient;

        if ($patient->favoriteDoctors()->where('doctors.doctor_id', $doctor->doctor_id)->exists()) {
            $patient->favoriteDoctors()->detach($doctor->doctor_id);
            $message = 'Removed from favorites.';
        } else {
            $patient->favoriteDoctors()->attach($doctor->doctor_id);
            $message = 'Added to favorites.';
        }

        return back()->with('success', $message);
    }

    /** Patient: view one doctor's profile + their open visiting windows for the next 2 weeks (GET /patient/doctors/{doctor}). */
    public function showDoctor(Doctor $doctor)
    {
        if ($doctor->verification_status !== 'approved') {
            abort(404);
        }

        $doctor->load(['specialties', 'account']);
        $doctor->loadAvg('reviews', 'rating');
        $doctor->loadCount('reviews');
        $windowsByDate = $this->appointments->upcomingWindows($doctor, Auth::user()->patient);

        // For the "join a waitlist" form below the calendar — every
        // active recurring window this doctor holds, regardless of
        // whether it currently has open spots (that's checked
        // server-side when the form is actually submitted).
        $activeTemplates = $doctor->availabilityTemplates()->where('is_active', true)->get();

        $isFavorite = Auth::user()->patient->favoriteDoctors()->where('doctors.doctor_id', $doctor->doctor_id)->exists();

        return view('patient.book-appointment', compact('doctor', 'windowsByDate', 'activeTemplates', 'isFavorite'));
    }

    /** Patient: submit a booking for one visiting window (POST /patient/doctors/{doctor}/book). */
    public function book(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:doctor_availability_templates,template_id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $template = DoctorAvailabilityTemplate::findOrFail($data['template_id']);
        if ($template->doctor_id !== $doctor->doctor_id) {
            abort(404);
        }

        // Only Cash is offered for real right now — same reason as medicine
        // checkout: bKash's sandbox credentials in .env are still
        // placeholders, so there's no working payment gateway to redirect to.
        $paymentMethod = PaymentMethod::where('method_name', 'Cash')->firstOrFail();

        $patient = Auth::user()->patient;
        $result = $this->appointments->book($patient, $template, $data['date'], $paymentMethod);

        if (!$result['ok']) {
            return back()->withErrors(['slot' => $result['message']]);
        }

        return redirect()
            ->route('patient.appointments')
            ->with('success', "Appointment booked! Your serial number is #{$result['appointment']->serial_number}. BDT " . number_format($template->doctor->consultation_fee, 2) . ' charged (Cash).');
    }

    // Shared by myAppointments()/doctorAppointments() below: which
    // statuses count as "still pending" vs "done one way or another."
    private const PENDING_STATUSES = ['booked', 'confirmed'];

    /** Patient: view their own list of appointments, split into Pending/Completed and sorted earliest-first (GET /patient/appointments). */
    public function myAppointments()
    {
        $this->appointments->expireNoShows();

        $patientId = Auth::user()->patient->patient_id;
        $with = ['doctor', 'hospital', 'template', 'payment'];

        // Two separate queries (not one query cloned) — Eloquent
        // relation objects don't reliably deep-clone their underlying
        // query, so reusing one via clone() here could silently leak
        // the first query's where() calls into the second.
        $pending = Appointment::where('patient_id', $patientId)
            ->whereIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('appointment_date')->orderBy('appointment_time')->get();

        $completed = Appointment::where('patient_id', $patientId)
            ->whereNotIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('appointment_date')->orderBy('appointment_time')->get();

        // "Now serving #X" for today's doctors only — a queue status from
        // a past or future date isn't meaningful, so this is keyed by
        // doctor_id and only ever holds today's row per doctor.
        // ->filter(isToday()) rather than ->where('appointment_date', ...)
        // — appointment_date is cast to a Carbon instance, which
        // stringifies as "Y-m-d H:i:s" for a loose == comparison, so a
        // plain "Y-m-d" string would never actually match it.
        $todaysDoctorIds = $pending->filter(fn (Appointment $a) => $a->appointment_date->isToday())->pluck('doctor_id')->unique();
        $queueStatusByDoctor = QueueStatus::whereIn('doctor_id', $todaysDoctorIds)
            ->where('queue_date', today()->toDateString())
            ->get()
            ->keyBy('doctor_id');

        $waitlist = AppointmentWaitlist::where('patient_id', $patientId)
            ->where('status', 'waiting')
            ->with(['doctor', 'template'])
            ->orderBy('requested_date')
            ->get();

        return view('patient.my-appointments', compact('pending', 'completed', 'queueStatusByDoctor', 'waitlist'));
    }

    /** Patient: cancels their own upcoming appointment (POST /patient/appointments/{appointment}/cancel). */
    public function cancel(Appointment $appointment)
    {
        if ($appointment->patient_id !== Auth::user()->patient->patient_id) {
            abort(403);
        }

        $result = $this->appointments->cancel($appointment);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Patient: joins the waitlist for a full window (POST /patient/doctors/{doctor}/waitlist). */
    public function joinWaitlist(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:doctor_availability_templates,template_id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $template = DoctorAvailabilityTemplate::findOrFail($data['template_id']);
        if ($template->doctor_id !== $doctor->doctor_id) {
            abort(404);
        }

        $result = $this->appointments->joinWaitlist(Auth::user()->patient, $template, $data['date']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Patient: leaves a waitlist they joined (POST /patient/waitlist/{waitlist}/leave). */
    public function leaveWaitlist(AppointmentWaitlist $waitlist)
    {
        if ($waitlist->patient_id !== Auth::user()->patient->patient_id) {
            abort(403);
        }

        $waitlist->update(['status' => 'cancelled']);

        return back()->with('success', 'Left the waitlist.');
    }

    /** Doctor: view everyone booked in with them, split into Pending/Completed and sorted earliest-first (GET /doctor/appointments). */
    public function doctorAppointments()
    {
        $this->appointments->expireNoShows();

        $doctorId = Auth::user()->doctor->doctor_id;
        $with = ['patient', 'hospital', 'template', 'prescription', 'payment', 'vital'];

        $pending = Appointment::where('doctor_id', $doctorId)
            ->whereIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('appointment_date')->orderBy('appointment_time')->get();

        $completed = Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('appointment_date')->orderBy('appointment_time')->get();

        // See the same fix + explanation in myAppointments() above — this
        // had the identical bug (a Carbon object never loosely equals a
        // plain date string), so $todayCount was silently always 0.
        $todayCount = $pending->filter(fn (Appointment $a) => $a->appointment_date->isToday())->count();

        $todaysQueueStatus = QueueStatus::where('doctor_id', $doctorId)
            ->where('queue_date', today()->toDateString())
            ->first();

        return view('doctor.appointments', compact('pending', 'completed', 'todayCount', 'todaysQueueStatus'));
    }

    /** Doctor: updates "now serving #X" for today (POST /doctor/queue-status). */
    public function updateQueueStatus(Request $request)
    {
        $data = $request->validate([
            'current_serial' => ['required', 'integer', 'min:0'],
        ]);

        QueueStatus::updateOrCreate(
            ['doctor_id' => Auth::user()->doctor->doctor_id, 'queue_date' => today()->toDateString()],
            ['current_serial' => $data['current_serial']]
        );

        return back()->with('success', 'Queue status updated.');
    }

    /**
     * Doctor: mark an appointment as visited/complete — works the same way
     * whether the appointment was online or onsite (POST /doctor/appointments/{appointment}/visited).
     * For onsite visits this is the only signal the system has that the
     * consultation actually happened; for online ones, the doctor clicks
     * it after finishing the video call (see ConsultationController).
     */
    public function markVisited(Appointment $appointment)
    {
        if ($appointment->doctor_id !== Auth::user()->doctor->doctor_id) {
            abort(403);
        }

        if (in_array($appointment->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors(['status' => 'This appointment is already ' . $appointment->status . '.']);
        }

        $appointment->update(['status' => 'completed']);

        return back()->with('success', 'Marked as visited.');
    }
}
