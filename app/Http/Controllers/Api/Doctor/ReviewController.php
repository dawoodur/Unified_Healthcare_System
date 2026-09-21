<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/** JSON twin of ReviewController::forDoctor() for the React Reviews page. */
class ReviewController extends Controller
{
    public function index()
    {
        $doctor = Auth::user()->doctor;

        $reviews = $doctor->reviews()
            ->orderByDesc('review_id')
            ->get(['rating', 'comment', 'created_at']);

        $average = $reviews->isEmpty() ? null : round($reviews->avg('rating'), 1);

        return response()->json([
            'average' => $average,
            'count' => $reviews->count(),
            'reviews' => $reviews->map(fn ($r) => [
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->format('D, M j Y'),
            ])->values(),
        ]);
    }
}
