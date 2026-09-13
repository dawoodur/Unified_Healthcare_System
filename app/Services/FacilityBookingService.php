<?php

namespace App\Services;

use App\Models\FacilityBooking;
use App\Models\HospitalFacility;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The facility equivalent of AppointmentService — turns a hospital's
 * daily_capacity for one facility (see HospitalFacility.php) into real,
 * bookable upcoming dates, and handles the actual booking safely. There's
 * no personal time slot, same as a doctor's visiting window: everyone
 * booked for the same hospital+facility+date just gets the next queue
 * serial number (see book()).
 */
class FacilityBookingService
{
    /**
     * Works out how many spots are left on each of the next $daysAhead
     * days for one hospital's offering of one facility. Returns something
     * shaped like:
     *   [
     *     ['date' => '2026-07-14', 'spots_left' => 6, 'already_booked' => false],
     *     ...
     *   ]
     * A date is left out only if it's fully booked AND this patient hasn't
     * already booked it themselves (so their own booking still shows up,
     * same rule as AppointmentService::upcomingWindows()).
     */
    public function upcomingDates(HospitalFacility $offering, Patient $patient, int $daysAhead = 14): array
    {
        $windowStart = Carbon::today();
        $windowEnd = Carbon::today()->addDays($daysAhead - 1);

        $existingBookings = FacilityBooking::where('hospital_id', $offering->hospital_id)
            ->where('facility_type_id', $offering->facility_type_id)
            ->whereBetween('booking_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $bookedCounts = $existingBookings->countBy(fn ($booking) => $booking->booking_date->toDateString());

        $patientBookedDates = $existingBookings
            ->where('patient_id', $patient->patient_id)
            ->map(fn ($booking) => $booking->booking_date->toDateString())
            ->flip(); // fast "is this date already booked by me?" lookup

        $dates = [];

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = Carbon::today()->addDays($i)->toDateString();

            $bookedCount = $bookedCounts[$date] ?? 0;
            $spotsLeft = $offering->daily_capacity - $bookedCount;
            $alreadyBooked = isset($patientBookedDates[$date]);

            if ($spotsLeft <= 0 && !$alreadyBooked) {
                continue; // fully booked, and not this patient's own booking — don't offer it
            }

            $dates[] = [
                'date' => $date,
                'spots_left' => $spotsLeft,
                'already_booked' => $alreadyBooked,
            ];
        }

        return $dates;
    }

    /**
     * Books one facility slot, re-checking (inside a database transaction)
     * that a spot is still actually free — someone else could have taken
     * the last one between the patient viewing the page and clicking
     * "Book." Assigns the next queue serial number for that hospital's
     * offering of that facility on that date. Returns
     * ['ok' => bool, 'message' => string, 'booking' => ?FacilityBooking].
     */
    public function book(Patient $patient, HospitalFacility $offering, string $date): array
    {
        if (Carbon::parse($date)->isPast() && !Carbon::parse($date)->isToday()) {
            return ['ok' => false, 'message' => 'That date has already passed.', 'booking' => null];
        }

        return DB::transaction(function () use ($patient, $offering, $date) {
            // Lock every existing booking for this hospital+facility+date
            // before reading them, so two patients booking at the same
            // instant can't both slip past the capacity check or grab the
            // same serial number.
            $existingForDate = FacilityBooking::where('hospital_id', $offering->hospital_id)
                ->where('facility_type_id', $offering->facility_type_id)
                ->where('booking_date', $date)
                ->lockForUpdate()
                ->get();

            $bookedCount = $existingForDate->whereNotIn('status', ['cancelled'])->count();

            if ($bookedCount >= $offering->daily_capacity) {
                return ['ok' => false, 'message' => 'Sorry, this facility just reached its daily limit for that date. Please pick another date.', 'booking' => null];
            }

            $alreadyBooked = $existingForDate
                ->whereNotIn('status', ['cancelled'])
                ->where('patient_id', $patient->patient_id)
                ->isNotEmpty();
            if ($alreadyBooked) {
                return ['ok' => false, 'message' => 'You already have a booking for this facility on that date.', 'booking' => null];
            }

            $nextSerial = ($existingForDate->max('serial_number') ?? 0) + 1;

            $booking = FacilityBooking::create([
                'patient_id' => $patient->patient_id,
                'hospital_id' => $offering->hospital_id,
                'facility_type_id' => $offering->facility_type_id,
                'booking_date' => $date,
                'serial_number' => $nextSerial,
                'price' => $offering->price, // snapshot — see the facility_bookings migration
                'status' => 'booked',
            ]);

            return ['ok' => true, 'message' => 'Facility booked.', 'booking' => $booking];
        });
    }

    /**
     * The bed-type equivalent of upcomingDates() — there's no list of future
     * dates to offer, because whether a bed is free next week depends on
     * when today's occupants get discharged, which nobody can predict.
     * Instead this just answers "is one free right now?". Returns
     * ['capacity' => int, 'occupied_count' => int, 'spots_left' => int, 'already_booked' => bool].
     */
    public function currentAvailability(HospitalFacility $offering, Patient $patient): array
    {
        $active = FacilityBooking::where('hospital_id', $offering->hospital_id)
            ->where('facility_type_id', $offering->facility_type_id)
            ->where('status', 'booked') // only still-occupied beds count — 'completed' means discharged, so it's free again
            ->get();

        $occupiedCount = $active->count();
        $alreadyBooked = $active->where('patient_id', $patient->patient_id)->isNotEmpty();

        return [
            'capacity' => $offering->daily_capacity,
            'occupied_count' => $occupiedCount,
            'spots_left' => max(0, $offering->daily_capacity - $occupiedCount),
            'already_booked' => $alreadyBooked,
        ];
    }

    /**
     * Books a bed-type facility (ICU Bed, Cabin, etc.) right now — admission
     * date is always today, since there's no future date list for these
     * (see currentAvailability()). The booking counts against capacity
     * every day after this until a hospital staff member discharges the
     * patient (status flips to 'completed') — it does NOT free up again
     * just because a new day started, unlike book() above. $days is just
     * the patient's own estimate of how long they expect to stay (used to
     * quote a total price up front); it doesn't limit or auto-end the
     * booking — the hospital still discharges manually whenever the
     * patient actually leaves, sooner or later than planned. Returns
     * ['ok' => bool, 'message' => string, 'booking' => ?FacilityBooking].
     */
    public function bookOccupancy(Patient $patient, HospitalFacility $offering, int $days): array
    {
        return DB::transaction(function () use ($patient, $offering, $days) {
            // Lock every currently-occupied booking for this hospital+facility
            // before reading them, so two patients requesting the last free
            // bed at the same instant can't both get admitted.
            $active = FacilityBooking::where('hospital_id', $offering->hospital_id)
                ->where('facility_type_id', $offering->facility_type_id)
                ->where('status', 'booked')
                ->lockForUpdate()
                ->get();

            if ($active->count() >= $offering->daily_capacity) {
                return ['ok' => false, 'message' => 'Sorry, there are no beds free right now. Please check back later.', 'booking' => null];
            }

            if ($active->where('patient_id', $patient->patient_id)->isNotEmpty()) {
                return ['ok' => false, 'message' => 'You already have this booked and haven\'t been discharged yet.', 'booking' => null];
            }

            $today = Carbon::today()->toDateString();

            // Serial number here is just a unique reference for the day a
            // bed was admitted on — not a queue position, since occupancy
            // bookings don't compete for "today's" spots the way a same-day
            // test does.
            $nextSerial = (FacilityBooking::where('hospital_id', $offering->hospital_id)
                ->where('facility_type_id', $offering->facility_type_id)
                ->where('booking_date', $today)
                ->lockForUpdate()
                ->max('serial_number') ?? 0) + 1;

            $booking = FacilityBooking::create([
                'patient_id' => $patient->patient_id,
                'hospital_id' => $offering->hospital_id,
                'facility_type_id' => $offering->facility_type_id,
                'booking_date' => $today,
                'requested_days' => $days,
                'serial_number' => $nextSerial,
                'price' => $offering->price * $days, // per-day rate x how many days the patient asked for
                'status' => 'booked',
            ]);

            return ['ok' => true, 'message' => "Bed reserved for you for {$days} day(s). Estimated total: BDT " . number_format($offering->price * $days, 2) . '.', 'booking' => $booking];
        });
    }
}
