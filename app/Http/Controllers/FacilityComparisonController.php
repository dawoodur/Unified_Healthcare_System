<?php

namespace App\Http\Controllers;

use App\Models\FacilityBooking;
use App\Models\FacilityCategory;
use App\Models\FacilityType;
use App\Models\Hospital;
use App\Models\HospitalFacility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a patient compare hospital prices for a facility (a blood test,
 * surgery, an ICU bed, etc.) — this is the "affordable medical facilities"
 * feature from the spec. HospitalFacility rows for one facility_type_id are
 * sorted cheapest first, while the same page also provides a searchable
 * hospital directory.
 */
class FacilityComparisonController extends Controller
{
    /** Shows hospital discovery + (once one is chosen) a price-sorted facility comparison. */
    public function index(Request $request)
    {
        $categories = FacilityCategory::with(['facilityTypes' => fn ($q) => $q->orderBy('name')])
            ->orderBy('category_name')
            ->get();

        // Keep the original individual filters compatible with old/bookmarked URLs.
        $uidInput = trim((string) $request->get('u_id'));
        $hospitalName = trim((string) $request->get('hospital_name'));
        $facilitySearch = trim((string) $request->get('facility'));

        // The redesigned page uses one calm search field. It searches only data
        // the Hospital model actually stores: name/address/city/u_id/facility name.
        $searchQuery = trim((string) $request->get('q'));

        $hospitalsQuery = Hospital::withCount('facilities')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with('account')
            ->orderBy('hospital_name');

        $uid = preg_replace('/\D/', '', $uidInput);
        if ($uid !== '') {
            $hospitalsQuery->whereHas('account', fn ($q) => $q->where('accounts.account_id', $uid));
        }

        if ($hospitalName !== '') {
            $hospitalsQuery->where('hospital_name', 'like', '%' . $hospitalName . '%');
        }

        if ($facilitySearch !== '') {
            $hospitalsQuery->whereHas(
                'facilities.facilityType',
                fn ($q) => $q->where('facility_types.name', 'like', '%' . $facilitySearch . '%')
            );
        }

        if ($searchQuery !== '') {
            $searchDigits = preg_replace('/\D/', '', $searchQuery);
            $hospitalsQuery->where(function ($query) use ($searchQuery, $searchDigits) {
                $query->where('hospital_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('address', 'like', '%' . $searchQuery . '%')
                    ->orWhere('city', 'like', '%' . $searchQuery . '%')
                    ->orWhereHas('facilities.facilityType', fn ($q) => $q->where('facility_types.name', 'like', '%' . $searchQuery . '%'));

                if ($searchDigits !== '') {
                    $query->orWhereHas('account', fn ($q) => $q->where('accounts.account_id', $searchDigits));
                }
            });
        }

        $hospitals = $hospitalsQuery->get();

        $selectedTypeId = $request->integer('facility_type_id') ?: null;
        $selectedType = null;
        $offerings = collect();

        if ($selectedTypeId) {
            $selectedType = FacilityType::with('category')->findOrFail($selectedTypeId);

            $offerings = HospitalFacility::where('facility_type_id', $selectedTypeId)
                ->with([
                    'hospital' => fn ($q) => $q
                        ->with('account')
                        ->withAvg('reviews', 'rating')
                        ->withCount('reviews'),
                ])
                ->orderBy('price')
                ->get();
        }

        // Small sidebar reminder using the existing facility-booking data.
        $patientId = Auth::user()?->patient?->patient_id;
        $recentBookings = $patientId
            ? FacilityBooking::where('patient_id', $patientId)
                ->with(['hospital', 'facilityType'])
                ->latest('created_at')
                ->take(3)
                ->get()
            : collect();

        return view('patient.compare-facilities', [
            'categories' => $categories,
            'hospitals' => $hospitals,
            'selectedType' => $selectedType,
            'offerings' => $offerings,
            'uidInput' => $uidInput,
            'hospitalName' => $hospitalName,
            'facilitySearch' => $facilitySearch,
            'searchQuery' => $searchQuery,
            'recentBookings' => $recentBookings,
        ]);
    }

    /**
     * Patient diagnostic-test workspace. This is a focused view over the same
     * facility catalogue/booking system used by Hospital Services; it does not
     * create a second lab backend or duplicate booking rules.
     */
    public function labTests(Request $request)
    {
        $diagnosticCategory = FacilityCategory::where('category_name', 'Diagnostic Test')
            ->with(['facilityTypes' => fn ($q) => $q->orderBy('facility_type_id')])
            ->first();

        $testTypes = $diagnosticCategory?->facilityTypes ?? collect();
        $searchQuery = trim((string) $request->get('q'));
        $showAll = $request->get('view') === 'all';
        $sortOrder = $request->get('sort') === 'highest' ? 'highest' : 'lowest';
        $selectedType = null;
        $searchMatchedType = false;

        $requestedTypeId = $request->integer('facility_type_id') ?: null;
        if ($requestedTypeId) {
            $selectedType = $testTypes->firstWhere('facility_type_id', $requestedTypeId);
        }

        if (! $selectedType && $searchQuery !== '') {
            $selectedType = $testTypes->first(
                fn (FacilityType $type) => stripos($type->name, $searchQuery) !== false
                    || stripos($searchQuery, $type->name) !== false
            );
            $searchMatchedType = (bool) $selectedType;
            if ($selectedType) {
                $showAll = false;
            }
        }

        if (! $selectedType && ! $showAll) {
            $selectedType = $testTypes->first();
        }

        $offerings = collect();
        if ($selectedType) {
            $offeringsQuery = HospitalFacility::where('facility_type_id', $selectedType->facility_type_id)
                ->with([
                    'hospital' => fn ($q) => $q
                        ->with('account')
                        ->withAvg('reviews', 'rating')
                        ->withCount('reviews'),
                ]);

            // If the search selected a test by name, show every hospital for it.
            // Otherwise the same field can narrow the selected test by hospital,
            // city/address or numeric account UID.
            if ($searchQuery !== '' && ! $searchMatchedType) {
                $digits = preg_replace('/\D/', '', $searchQuery);
                $offeringsQuery->whereHas('hospital', function ($query) use ($searchQuery, $digits) {
                    $query->where(function ($hospitalQuery) use ($searchQuery, $digits) {
                        $hospitalQuery->where('hospital_name', 'like', '%' . $searchQuery . '%')
                            ->orWhere('address', 'like', '%' . $searchQuery . '%')
                            ->orWhere('city', 'like', '%' . $searchQuery . '%');

                        if ($digits !== '') {
                            $hospitalQuery->orWhereHas('account', fn ($q) => $q->where('accounts.account_id', $digits));
                        }
                    });
                });
            }

            $offerings = $offeringsQuery
                ->orderBy('price', $sortOrder === 'highest' ? 'desc' : 'asc')
                ->get();
        }

        $summaryRows = $testTypes->isEmpty()
            ? collect()
            : HospitalFacility::whereIn('facility_type_id', $testTypes->pluck('facility_type_id'))
                ->selectRaw('facility_type_id, MIN(price) as min_price, COUNT(*) as hospital_count')
                ->groupBy('facility_type_id')
                ->get()
                ->keyBy('facility_type_id');

        $testSummaries = $testTypes->map(function (FacilityType $type) use ($summaryRows) {
            $summary = $summaryRows->get($type->facility_type_id);
            return [
                'type' => $type,
                'min_price' => $summary?->min_price,
                'hospital_count' => (int) ($summary?->hospital_count ?? 0),
            ];
        });

        if ($showAll && $searchQuery !== '') {
            $testSummaries = $testSummaries->filter(
                fn (array $summary) => stripos($summary['type']->name, $searchQuery) !== false
            )->values();
        }

        $patientId = Auth::user()?->patient?->patient_id;
        $recentBookings = $patientId
            ? FacilityBooking::where('patient_id', $patientId)
                ->whereHas('facilityType.category', fn ($q) => $q->where('category_name', 'Diagnostic Test'))
                ->with(['hospital', 'facilityType'])
                ->latest('created_at')
                ->take(3)
                ->get()
            : collect();

        return view('patient.lab-tests', compact(
            'testTypes',
            'selectedType',
            'offerings',
            'testSummaries',
            'searchQuery',
            'showAll',
            'sortOrder',
            'recentBookings'
        ));
    }

    /** Shows one hospital's full priced facility list, grouped by category. */
    public function showHospital(Hospital $hospital)
    {
        $hospital->loadAvg('reviews', 'rating');
        $hospital->loadCount('reviews');

        $offeringsByCategory = $hospital->facilities()
            ->with('facilityType.category')
            ->get()
            ->groupBy(fn (HospitalFacility $offering) => $offering->facilityType->category->category_name);

        $activeDoctors = $hospital->activeDoctors()
            ->where('doctors.verification_status', 'approved')
            ->with('specialties')
            ->orderBy('full_name')
            ->get();

        // Reviews remain anonymous here: only rating/comment/date are loaded.
        $hospitalReviews = $hospital->reviews()
            ->orderByDesc('review_id')
            ->limit(10)
            ->get(['review_id', 'rating', 'comment', 'created_at']);

        return view('patient.hospital-facilities', compact(
            'hospital',
            'offeringsByCategory',
            'activeDoctors',
            'hospitalReviews'
        ));
    }
}
