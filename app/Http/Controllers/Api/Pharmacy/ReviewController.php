<?php

namespace App\Http\Controllers\Api\Pharmacy;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of ReviewController::forPharmacy().
 *
 * The anonymity guarantee is the same one the Blade page relies on and it
 * lives in the get() below: only rating/comment/created_at are selected, so
 * patient_id never leaves the database.
 */
class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Auth::user()->pharmacy->reviews()
            ->orderByDesc('review_id')
            ->get(['rating', 'comment', 'created_at']);

        return response()->json([
            'reviews' => $reviews->map(fn ($review) => [
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'date_label' => $review->created_at?->format('M j, Y'),
            ]),
            'average' => $reviews->isEmpty() ? null : round($reviews->avg('rating'), 1),
            'count' => $reviews->count(),
            'breakdown' => collect(range(5, 1))
                ->map(fn ($star) => [
                    'star' => $star,
                    'count' => $reviews->where('rating', $star)->count(),
                ])->values(),
        ]);
    }
}
