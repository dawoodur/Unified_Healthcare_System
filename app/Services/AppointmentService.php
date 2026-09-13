<?php

namespace App\Services;

use App\Mail\WaitlistSlotOpenMail;
use App\Models\Appointment;
use App\Models\AppointmentWaitlist;
use App\Models\Doctor;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\DoctorLeaveDate;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Turns a doctor's weekly availability templates (see
 * DoctorAvailabilityTemplate.php) into real, bookable upcoming visiting
 * windows, and handles the actual booking safely. There's no personal time
 * slot per patient — everyone booked into the same window on the same date
 * just gets a queue serial number (see book()), like a real doctor's
 * chamber: patients don't pick a private time, they take a number.
 */
class AppointmentService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Works out every bookable visiting window for a doctor over the next
     * $daysAhead days, grouped by date. Returns something shaped like:
     *   [
     *     '2026-07-14' => [
     *        ['template' => DoctorAvailabilityTemplate, 'spots_left' => 6, 'already_booked' => false],
     *        ...
     *     ],
     *     ...
     *   ]
     * A doctor can hold more than one window on the same date (e.g. an
     * onsite morning session and a separate online evening one) — each
     * gets its own entry. Windows that are already full AND that this
     * patient hasn't already booked are left out; windows whose end time
     * has already passed today are always left out.
     */
    public function upcomingWindows(Doctor $doctor, Patient $patient, int $daysAhead = 14): array
    {
        $templates = DoctorAvailabilityTemplate::where('doctor_id', $doctor->doctor_id)
            ->where('is_active', true)
            ->with('hospital') // so the view can show a hospital name for onsite windows without extra queries
            ->get();

        if ($templates->isEmpty()) {
            return [];
        }

        // Pull every appointment already booked in this whole date window in
        // ONE query, then count them in memory per (date, template) below —
        // much faster than running a separate COUNT query per window.
        $windowStart = Carbon::today();
        $windowEnd = Carbon::today()->addDays($daysAhead);

        $existingAppointments = Appointment::where('doctor_id', $doctor->doctor_id)
            ->whereBetween('appointment_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $existingCounts = $existingAppointments
            ->countBy(fn ($appointment) => $appointment->appointment_date->toDateString() . ':' . $appointment->template_id);

        // Which (date, template) pairs THIS patient already has a booking
        // in, so the view can show "Booked" instead of a clickable button.
        $patientBookedKeys = $existingAppointments
            ->where('patient_id', $patient->patient_id)
            ->map(fn ($appointment) => $appointment->appointment_date->toDateString() . ':' . $appointment->template_id)
            ->flip(); // turns the list into a fast "is this key set?" lookup

        // Dates this doctor has explicitly blocked off (vacation, a
        // conference, ...) — see DoctorLeaveDate.php. Every window on
        // one of these dates is skipped below, same as a fully-booked one.
        $leaveDates = DoctorLeaveDate::where('doctor_id', $doctor->doctor_id)
            ->whereBetween('leave_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->pluck('leave_date')
            ->map(fn ($date) => $date->toDateString())
            ->flip();

        $windowsByDate = [];

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = Carbon::today()->addDays($i);
            $dayOfWeek = (int) $date->format('w'); // 0=Sunday .. 6=Saturday, same numbering as our column

            if (isset($leaveDates[$date->toDateString()])) {
                continue; // doctor is on leave this whole day — no windows offered at all
            }

            foreach ($templates->where('day_of_week', $dayOfWeek) as $template) {
                $windowEndDateTime = Carbon::parse($date->toDateString() . ' ' . $template->end_time);
                if ($windowEndDateTime->isPast()) {
                    continue; // this day's window has already closed
                }

                $key = $date->toDateString() . ':' . $template->template_id;
                $bookedCount = $existingCounts[$key] ?? 0;
                $spotsLeft = $template->max_patients - $bookedCount;
                $alreadyBooked = isset($patientBookedKeys[$key]);

                if ($spotsLeft <= 0 && !$alreadyBooked) {
                    continue; // fully booked, and not this patient's own booking — don't offer it
                }

                $windowsByDate[$date->toDateString()][] = [
                    'template' => $template,
                    'spots_left' => $spotsLeft,
                    'already_booked' => $alreadyBooked,
                ];
            }
        }

        return $windowsByDate;
    }

    /**
     * Books one appointment into a visiting window, re-checking (inside a
     * database transaction) that a spot is still actually free — someone
     * else could have taken the last one in the moment between the patient
     * viewing the page and clicking "Book." Assigns the next queue serial
     * number for that doctor on that date. Also charges the doctor's
     * consultation fee right away (see the Payment::create() call below) —
     * unlike a medicine order, there's no separate "delivery" event to wait
     * for, so the fee is simply paid in full at booking time. If the
     * appointment later goes unvisited past its date, expireNoShows()
     * refunds it automatically. Returns
     * ['ok' => bool, 'message' => string, 'appointment' => ?Appointment].
     */
    public function book(Patient $patient, DoctorAvailabilityTemplate $template, string $date, PaymentMethod $paymentMethod): array
    {
        $windowEndDateTime = Carbon::parse($date . ' ' . $template->end_time);
        if ($windowEndDateTime->isPast()) {
            return ['ok' => false, 'message' => 'That visiting window has already ended.', 'appointment' => null];
        }

        $dayOfWeek = (int) Carbon::parse($date)->format('w');
        if ($template->day_of_week !== $dayOfWeek || !$template->is_active) {
            return ['ok' => false, 'message' => 'That window is no longer available.', 'appointment' => null];
        }

        $onLeave = DoctorLeaveDate::where('doctor_id', $template->doctor_id)->where('leave_date', $date)->exists();
        if ($onLeave) {
            return ['ok' => false, 'message' => 'This doctor is unavailable on that date.', 'appointment' => null];
        }

        return DB::transaction(function () use ($patient, $template, $date, $paymentMethod) {
            // Lock every existing appointment for this doctor on this date
            // before reading them, so two patients booking at the same
            // instant can't both slip past the capacity check or grab the
            // same serial number — the second one waits for the first
            // transaction to finish, then sees its result.
            $existingForDate = Appointment::where('doctor_id', $template->doctor_id)
                ->where('appointment_date', $date)
                ->lockForUpdate()
                ->get();

            $bookedInWindow = $existingForDate
                ->where('template_id', $template->template_id)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            if ($bookedInWindow >= $template->max_patients) {
                return ['ok' => false, 'message' => 'Sorry, this window just reached its patient limit. Please pick another.', 'appointment' => null];
            }

            $alreadyBooked = $existingForDate
                ->where('template_id', $template->template_id)
                ->whereNotIn('status', ['cancelled'])
                ->where('patient_id', $patient->patient_id)
                ->isNotEmpty();
            if ($alreadyBooked) {
                return ['ok' => false, 'message' => 'You already have an appointment in this window.', 'appointment' => null];
            }

            // One shared queue per doctor per day — the serial number counts
            // up across ALL of that doctor's windows on this date, so "who
            // booked first today" always gets #1, regardless of which
            // window (morning/evening, online/onsite) they picked.
            $nextSerial = ($existingForDate->max('serial_number') ?? 0) + 1;

            $appointment = Appointment::create([
                'patient_id' => $patient->patient_id,
                'doctor_id' => $template->doctor_id,
                'hospital_id' => $template->hospital_id,
                'template_id' => $template->template_id,
                'appointment_type' => $template->mode,
                'appointment_date' => $date,
                'appointment_time' => $template->start_time, // the window's start — the serial number is what actually orders patients
                'serial_number' => $nextSerial,
                'status' => 'booked',
            ]);

            Payment::create([
                'account_id' => $patient->account_id,
                'appointment_id' => $appointment->appointment_id,
                'amount' => $template->doctor->consultation_fee,
                'payment_method_id' => $paymentMethod->payment_method_id,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            return ['ok' => true, 'message' => 'Appointment booked.', 'appointment' => $appointment];
        });
    }

    /**
     * Patient cancels their own upcoming appointment — refunds the
     * payment if it was already charged, then checks whether anyone's
     * waitlisted for the spot that just freed up (see
     * notifyNextWaitlisted()). Doesn't touch the serial numbers of other
     * patients in the same window — those stay exactly as assigned.
     */
    public function cancel(Appointment $appointment): array
    {
        if (!in_array($appointment->status, ['booked', 'confirmed'], true)) {
            return ['ok' => false, 'message' => 'This appointment can no longer be cancelled.'];
        }

        DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => 'cancelled']);

            if ($appointment->payment && $appointment->payment->status === 'completed') {
                $appointment->payment->update(['status' => 'refunded']);
            }
        });

        if ($appointment->template_id) {
            $this->notifyNextWaitlisted($appointment->doctor_id, $appointment->template_id, $appointment->appointment_date->toDateString());
        }

        return ['ok' => true, 'message' => 'Appointment cancelled.'];
    }

    /**
     * Patient asks to be told if a spot opens up in a window that's
     * currently full — re-checks it's ACTUALLY full server-side first
     * (a patient should just book directly if it isn't), and that they
     * aren't already waiting for the same window.
     */
    public function joinWaitlist(Patient $patient, DoctorAvailabilityTemplate $template, string $date): array
    {
        $bookedCount = Appointment::where('doctor_id', $template->doctor_id)
            ->where('appointment_date', $date)
            ->where('template_id', $template->template_id)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($bookedCount < $template->max_patients) {
            return ['ok' => false, 'message' => 'This window still has open spots — book it directly instead.'];
        }

        $alreadyWaiting = AppointmentWaitlist::where('patient_id', $patient->patient_id)
            ->where('template_id', $template->template_id)
            ->where('requested_date', $date)
            ->where('status', 'waiting')
            ->exists();

        if ($alreadyWaiting) {
            return ['ok' => false, 'message' => 'You\'re already on the waitlist for this window.'];
        }

        AppointmentWaitlist::create([
            'patient_id' => $patient->patient_id,
            'doctor_id' => $template->doctor_id,
            'template_id' => $template->template_id,
            'requested_date' => $date,
            'status' => 'waiting',
        ]);

        return ['ok' => true, 'message' => 'Added to the waitlist — we\'ll email you if a spot opens up.'];
    }

    /**
     * Notifies (email + in-app) whichever waitlisted patient has been
     * waiting longest for this exact doctor+window+date, and marks them
     * 'notified' so the next cancellation moves on to the next person in
     * line instead of re-notifying the same one. Doesn't auto-book for
     * them — they still go through the normal book() flow, so they're
     * never charged for a spot they didn't actively confirm.
     */
    private function notifyNextWaitlisted(int $doctorId, int $templateId, string $date): void
    {
        $next = AppointmentWaitlist::where('doctor_id', $doctorId)
            ->where('template_id', $templateId)
            ->where('requested_date', $date)
            ->where('status', 'waiting')
            ->orderBy('created_at')
            ->with(['patient.account', 'doctor', 'template'])
            ->first();

        if (!$next) {
            return;
        }

        $next->update(['status' => 'notified', 'notified_at' => now()]);

        try {
            Mail::to($next->patient->account->email)->send(new WaitlistSlotOpenMail($next));
        } catch (\Throwable $e) {
            report($e);
        }

        $this->notifications->notify(
            $next->patient->account,
            'waitlist_slot_open',
            "A slot opened up with Dr. {$next->doctor->full_name} on " . Carbon::parse($date)->format('M j, Y') . ' — book now before it\'s taken again.',
            $next->waitlist_id
        );
    }

    /**
     * Sweeps every appointment whose visiting window has ended while it was
     * still just "booked"/"confirmed" — i.e. the doctor never clicked "Mark
     * Visited" — and flips it to "no_show", refunding its payment (if any).
     * There's no cron/scheduled task in this app; instead this runs
     * cheaply every time an appointment list is actually viewed (patient's,
     * doctor's, or admin's), the same lazy-recompute approach used for
     * medical record access grants (see RecordAccessGrant::isActive()).
     * Returns how many appointments were just expired.
     */
    public function expireNoShows(): int
    {
        $stale = Appointment::whereIn('status', ['booked', 'confirmed'])
            ->where('appointment_date', '<=', Carbon::today()->toDateString())
            ->with(['template', 'payment'])
            ->get()
            ->filter(function (Appointment $appointment) {
                $endTime = $appointment->template->end_time ?? '23:59:59';
                $windowEnd = Carbon::parse($appointment->appointment_date->toDateString() . ' ' . $endTime);
                return $windowEnd->isPast();
            });

        foreach ($stale as $appointment) {
            $appointment->update(['status' => 'no_show']);

            if ($appointment->payment && $appointment->payment->status === 'completed') {
                $appointment->payment->update(['status' => 'refunded']);
            }
        }

        return $stale->count();
    }
}
