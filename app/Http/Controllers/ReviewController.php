<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAgent;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Pharmacy;
use App\Models\Review;
use App\Services\RewardPointService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Star ratings + comments — a patient rates a doctor/hospital/pharmacy/
 * delivery man they've ACTUALLY used (checked server-side every time, not
 * just trusted from what a list page happened to show), one rating per
 * pair (submitting again edits it instead of adding a second). The
 * provider side (forDoctor/forHospital/forPharmacy/forDelivery) shows the
 * average rating and every comment, but never who wrote them — see the
 * big comment on providerView() below for exactly how that's enforced.
 */
class ReviewController extends Controller
{
    // One entry per reviewable target type: which Eloquent model it is,
    // and which column on `reviews` / primary key ties a review to it.
    // Every method below that needs to know "what does 'doctor' mean"
    // reads from this one place instead of repeating a switch/match.
    private const TYPES = [
        'doctor' => ['model' => Doctor::class, 'column' => 'doctor_id', 'key' => 'doctor_id'],
        'hospital' => ['model' => Hospital::class, 'column' => 'hospital_id', 'key' => 'hospital_id'],
        'pharmacy' => ['model' => Pharmacy::class, 'column' => 'pharmacy_id', 'key' => 'pharmacy_id'],
        'delivery' => ['model' => DeliveryAgent::class, 'column' => 'delivery_agent_id', 'key' => 'delivery_agent_id'],
    ];

    public function __construct(private RewardPointService $rewardPoints)
    {
    }

    /** Patient's "My Reviews" page (GET /patient/reviews) — everything they're eligible to rate, grouped by type. */
    public function index()
    {
        $patient = Auth::user()->patient;
        $patientId = $patient->patient_id;

        $doctors = $this->eligibleDoctors($patientId);
        $hospitals = $this->eligibleHospitals($patientId);
        $pharmacies = $this->eligiblePharmacies($patientId);
        $deliveryAgents = $this->eligibleDeliveryAgents($patientId);

        // One query for every review this patient has ever left, then
        // split by which column is set — cheaper than a separate lookup
        // per row in the four lists above.
        $existing = Review::where('patient_id', $patientId)->get();
        $existingByType = [
            'doctor' => $existing->whereNotNull('doctor_id')->keyBy('doctor_id'),
            'hospital' => $existing->whereNotNull('hospital_id')->keyBy('hospital_id'),
            'pharmacy' => $existing->whereNotNull('pharmacy_id')->keyBy('pharmacy_id'),
            'delivery' => $existing->whereNotNull('delivery_agent_id')->keyBy('delivery_agent_id'),
        ];

        return view('patient.reviews.index', [
            'doctors' => $doctors,
            'hospitals' => $hospitals,
            'pharmacies' => $pharmacies,
            'deliveryAgents' => $deliveryAgents,
            'existingByType' => $existingByType,
            'pointsBalance' => $patient->reward_points_balance,
        ]);
    }

    /** Shows the rate/edit form for one specific target (GET /patient/reviews/rate?type=doctor&id=5). */
    public function create(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'id' => ['required', 'integer'],
        ]);

        $patientId = Auth::user()->patient->patient_id;
        $target = $this->authorizeEligible($patientId, $data['type'], (int) $data['id']);

        $existing = Review::where('patient_id', $patientId)
            ->where(self::TYPES[$data['type']]['column'], $target->getKey())
            ->first();

        return view('patient.reviews.rate', [
            'type' => $data['type'],
            'target' => $target,
            'existing' => $existing,
        ]);
    }

    /** Handles the rating form submission (POST /patient/reviews/rate) — creates or edits, never a duplicate. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $patient = Auth::user()->patient;
        $target = $this->authorizeEligible($patient->patient_id, $data['type'], (int) $data['id']);
        $column = self::TYPES[$data['type']]['column'];

        // updateOrCreate: if this patient already rated this exact target,
        // overwrite that row's rating/comment instead of inserting a
        // second one — the unique index on the table would reject a
        // duplicate anyway, this just makes "editing" the normal path
        // instead of an error.
        $review = Review::updateOrCreate(
            ['patient_id' => $patient->patient_id, $column => $target->getKey()],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null]
        );

        // wasRecentlyCreated is true only when updateOrCreate() just did an
        // INSERT (a brand-new review) — false when it found and updated an
        // existing row. Points are only earned once per review, the first
        // time it's written; editing an existing rating never earns another.
        if ($review->wasRecentlyCreated) {
            $this->rewardPoints->award($patient, 'review', $review->review_id);
        }

        session()->flash('success', 'Thanks — your rating has been saved.');

        return redirect()->route('patient.reviews');
    }

    /** Doctor's own "My Reviews" page (GET /doctor/reviews) — average rating + anonymous comments. */
    public function forDoctor()
    {
        return $this->providerView(Auth::user()->doctor);
    }

    /** Hospital's own "My Reviews" page (GET /hospital/reviews). */
    public function forHospital()
    {
        return $this->providerView(Auth::user()->hospital);
    }

    /** Pharmacy's own "My Reviews" page (GET /pharmacy/reviews). */
    public function forPharmacy()
    {
        return $this->providerView(Auth::user()->pharmacy);
    }

    /** Delivery agent's own "My Reviews" page (GET /delivery/reviews). */
    public function forDelivery()
    {
        return $this->providerView(Auth::user()->deliveryAgent);
    }

    /**
     * Shared by all four forX() methods above. The anonymity guarantee
     * lives entirely in this one line: get(['rating', 'comment',
     * 'created_at']) tells Eloquent to select ONLY those three columns —
     * patient_id never comes back from the database at all, so there's
     * nothing here that could leak who wrote a review, even by accident
     * later (e.g. someone adding a debug dump of $review to the view).
     */
    private function providerView(Model $profile)
    {
        $reviews = $profile->reviews()
            ->orderByDesc('review_id')
            ->get(['rating', 'comment', 'created_at']);

        $average = $reviews->isEmpty() ? null : round($reviews->avg('rating'), 1);

        return view('reviews.provider', [
            'reviews' => $reviews,
            'average' => $average,
            'count' => $reviews->count(),
        ]);
    }

    /** Doctors this patient has completed at least one appointment with. */
    private function eligibleDoctors(int $patientId): Collection
    {
        return Doctor::whereHas('appointments', fn ($q) => $q->where('patient_id', $patientId)->where('status', 'completed'))
            ->orderBy('full_name')
            ->get();
    }

    /** Hospitals this patient has completed an onsite appointment OR a facility booking at. */
    private function eligibleHospitals(int $patientId): Collection
    {
        return Hospital::where(function ($q) use ($patientId) {
            $q->whereHas('appointments', fn ($q2) => $q2->where('patient_id', $patientId)->where('status', 'completed'))
                ->orWhereHas('facilityBookings', fn ($q2) => $q2->where('patient_id', $patientId)->where('status', 'completed'));
        })
            ->orderBy('hospital_name')
            ->get();
    }

    /** Pharmacies this patient has had at least one order delivered from. */
    private function eligiblePharmacies(int $patientId): Collection
    {
        return Pharmacy::whereHas('orders', fn ($q) => $q->where('patient_id', $patientId)->where('status', 'delivered'))
            ->orderBy('pharmacy_name')
            ->get();
    }

    /** Delivery agents who have delivered at least one order for this patient. */
    private function eligibleDeliveryAgents(int $patientId): Collection
    {
        return DeliveryAgent::whereHas('deliveries', fn ($q) => $q->where('patient_id', $patientId)->where('status', 'delivered'))
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Re-checks eligibility server-side — create()/store() never trust
     * that a request only ever contains what index()'s lists showed —
     * and returns the target model, or aborts 403 if this patient hasn't
     * actually used it.
     */
    private function authorizeEligible(int $patientId, string $type, int $id): Model
    {
        $eligible = match ($type) {
            'doctor' => $this->eligibleDoctors($patientId),
            'hospital' => $this->eligibleHospitals($patientId),
            'pharmacy' => $this->eligiblePharmacies($patientId),
            'delivery' => $this->eligibleDeliveryAgents($patientId),
        };

        $target = $eligible->firstWhere(self::TYPES[$type]['key'], $id);

        if (!$target) {
            abort(403, "You can only rate a {$type} you've actually used.");
        }

        return $target;
    }
}
