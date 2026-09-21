<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/** Bootstrap info the React app needs on load — who's logged in, basically. */
class MeController extends Controller
{
    public function show()
    {
        $doctor = Auth::user()->doctor;

        return response()->json([
            'doctor_id' => $doctor->doctor_id,
            'full_name' => $doctor->full_name,
            'email' => Auth::user()->email,
            'photo_url' => Auth::user()->photoUrl(),
            'verification_status' => $doctor->verification_status,
        ]);
    }
}
