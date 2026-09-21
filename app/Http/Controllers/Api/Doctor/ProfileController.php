<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    private const GENDERS = ['male', 'female'];

    public function show()
    {
        $account = Auth::user();
        $doctor = $account->doctor;

        $doctor->load('specialties');

        return response()->json([
            'account' => [
                'account_id' => (int) $account->account_id,
                'uid' => $account->uidTag(),
                'email' => $account->email,
                'mobile' => $account->mobile,
                'photo_url' => $account->photoUrl(),
            ],
            'doctor' => [
                'doctor_id' => (int) $doctor->doctor_id,
                'full_name' => $doctor->full_name,
                'age' => $doctor->age,
                'blood_group' => $doctor->blood_group,
                'gender' => $doctor->gender,
                'bio' => $doctor->bio,
                'consultation_fee' => $doctor->consultation_fee,
                'verification_status' => $doctor->verification_status,
                'specialty_ids' => $doctor->specialties->pluck('specialty_id')->map(fn ($id) => (int) $id)->values(),
                'specialties' => $doctor->specialties
                    ->map(fn ($specialty) => [
                        'specialty_id' => (int) $specialty->specialty_id,
                        'specialty_name' => $specialty->specialty_name,
                    ])
                    ->values(),
            ],
            'specialties' => Specialty::orderBy('specialty_name')
                ->get()
                ->map(fn ($specialty) => [
                    'specialty_id' => (int) $specialty->specialty_id,
                    'specialty_name' => $specialty->specialty_name,
                ])
                ->values(),
            'stats' => [
                'active_hospitals' => $doctor->activeHospitals()->count(),
                'total_appointments' => $doctor->appointments()->count(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $account = Auth::user();
        $doctor = $account->doctor;

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'age' => ['required', 'integer', 'between:21,100'],
            'blood_group' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'gender' => ['required', Rule::in(self::GENDERS)],
            'mobile' => [
                'required',
                'regex:' . Phone::BD_MOBILE_REGEX,
                Rule::unique('accounts', 'mobile')->ignore($account->account_id, 'account_id'),
            ],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('accounts', 'email')->ignore($account->account_id, 'account_id'),
            ],
            'bio' => ['nullable', 'string'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'specialty_ids' => ['required', 'array', 'min:1'],
            'specialty_ids.*' => ['integer', 'exists:specialties,specialty_id'],
        ]);

        $account->update([
            'email' => $data['email'],
            'mobile' => Phone::normalize($data['mobile']),
        ]);

        $doctor->update([
            'full_name' => $data['full_name'],
            'age' => $data['age'],
            'blood_group' => $data['blood_group'],
            'gender' => $data['gender'],
            'bio' => $data['bio'] ?? null,
            'consultation_fee' => $data['consultation_fee'] ?? 0,
        ]);

        $doctor->specialties()->sync(array_unique($data['specialty_ids']));

        return response()->json([
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $account = Auth::user();

        if ($account->photo_path) {
            Storage::disk('public')->delete($account->photo_path);
        }

        $path = $request->file('photo')->store('photos', 'public');
        $account->update(['photo_path' => $path]);

        return response()->json([
            'message' => 'Profile photo updated.',
            'photo_url' => $account->fresh()->photoUrl(),
        ]);
    }

    public function destroyPhoto()
    {
        $account = Auth::user();

        if ($account->photo_path) {
            Storage::disk('public')->delete($account->photo_path);
            $account->update(['photo_path' => null]);
        }

        return response()->json([
            'message' => 'Profile photo removed.',
            'photo_url' => null,
        ]);
    }
}
