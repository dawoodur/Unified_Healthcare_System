<?php

namespace App\Http\Controllers\Api\Hospital;

use App\Http\Controllers\Controller;
use App\Models\FacilityBooking;
use App\Models\FacilityCategory;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of HospitalFacilityController's read screens (the facility
 * catalogue and the booking queue) for the React hospital section.
 *
 * Writes that change money or free a bed — setting a price, removing an
 * offering, marking a booking complete — deliberately stay on the existing
 * web routes, which already carry their reward-point and discharge side
 * effects; the React pages link out to them rather than re-implementing
 * that logic against a second code path.
 */
class FacilityController extends Controller
{
    public function index()
    {
        $hospital = Auth::user()->hospital;

        $offerings = $hospital->facilities()->with('facilityType.category')->get()->keyBy('facility_type_id');

        // Grouped by category so the list shows facilities organised the
        // same way patients will browse them.
        $categories = FacilityCategory::with('facilityTypes')->orderBy('category_name')->get();

        return response()->json([
            'categories' => $categories->map(fn ($category) => [
                'category_id' => (int) $category->category_id,
                'category_name' => $category->category_name,
                'types' => $category->facilityTypes->map(function ($type) use ($offerings) {
                    $offering = $offerings->get($type->facility_type_id);

                    return [
                        'facility_type_id' => (int) $type->facility_type_id,
                        'name' => $type->name,
                        'is_occupancy' => (bool) $type->is_occupancy,
                        'offered' => (bool) $offering,
                        'price' => $offering?->price,
                        'daily_capacity' => $offering?->daily_capacity,
                        'offering_id' => $offering?->facility_offering_id,
                    ];
                })->values(),
            ])->values(),
            'offered_count' => $offerings->count(),
        ]);
    }

    public function bookings()
    {
        $hospitalId = Auth::user()->hospital->hospital_id;
        $with = ['patient', 'facilityType'];

        $pending = FacilityBooking::where('hospital_id', $hospitalId)
            ->where('status', 'booked')
            ->with($with)->orderBy('booking_date')->orderBy('serial_number')->get();

        $completed = FacilityBooking::where('hospital_id', $hospitalId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with($with)->orderBy('booking_date')->orderBy('serial_number')->get();

        return response()->json([
            'pending' => $pending->map(fn (FacilityBooking $b) => $this->shape($b)),
            'completed' => $completed->map(fn (FacilityBooking $b) => $this->shape($b)),
        ]);
    }

    private function shape(FacilityBooking $booking): array
    {
        return [
            'booking_id' => (int) $booking->facility_booking_id,
            'serial_number' => $booking->serial_number,
            'patient_name' => $booking->patient->full_name,
            'facility_type' => $booking->facilityType->name,
            'is_occupancy' => (bool) $booking->facilityType->is_occupancy,
            'booking_date' => $booking->booking_date?->format('M j, Y'),
            'requested_days' => $booking->requested_days,
            'price' => $booking->price,
            'status' => $booking->status,
        ];
    }
}
