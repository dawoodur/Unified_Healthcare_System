<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * "My Profile" — lets a logged-in account of ANY role edit exactly the
 * same information RegistrationController collected when they first
 * signed up (name/age/blood group/etc., email, mobile), plus upload a
 * photo. Deliberately NOT here: changing your password (a separate,
 * more sensitive flow this doesn't touch) and a doctor's certificate
 * re-upload (that resets admin verification — too big a side effect to
 * fold into a plain "edit my info" form).
 */
class ProfileController extends Controller
{
    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    private const GENDERS = ['male', 'female'];

    /** Shows the current info (role-specific fields) and the edit form (GET /profile). */
    public function edit()
    {
        $account = Auth::user();

        if ($account->role === 'doctor') {
            $account->doctor->load('specialties');
        }

        return view('profile.edit', [
            'account' => $account,
            'specialties' => $account->role === 'doctor' ? Specialty::orderBy('specialty_name')->get() : null,
        ]);
    }

    /** Handles the info form being submitted — different fields per role (POST /profile). */
    public function update(Request $request)
    {
        $account = Auth::user();

        match ($account->role) {
            'patient' => $this->updatePerson($request, $account, ageRange: [1, 120], withAddress: true),
            'doctor' => $this->updateDoctor($request, $account),
            'hospital' => $this->updateHospital($request, $account),
            'pharmacy' => $this->updatePharmacy($request, $account),
            'delivery' => $this->updatePerson($request, $account, ageRange: [18, 70], withAddress: false),
            'admin' => $this->updateAdmin($request, $account),
        };

        return redirect()->route('profile.edit')->with('success', 'Profile updated.');
    }

    /** Shared by patient and delivery — same field shape (full_name/age/blood_group/gender/mobile/email, patient also gets address). */
    private function updatePerson(Request $request, $account, array $ageRange, bool $withAddress): void
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'age' => ['required', 'integer', 'between:' . $ageRange[0] . ',' . $ageRange[1]],
            'blood_group' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'gender' => ['required', Rule::in(self::GENDERS)],
            'mobile' => ['required', 'regex:' . Phone::BD_MOBILE_REGEX, Rule::unique('accounts', 'mobile')->ignore($account->account_id, 'account_id')],
            'email' => ['required', 'email', 'max:190', Rule::unique('accounts', 'email')->ignore($account->account_id, 'account_id')],
            'address' => $withAddress ? ['nullable', 'string', 'max:255'] : ['prohibited'],
        ]);

        $account->update(['email' => $data['email'], 'mobile' => Phone::normalize($data['mobile'])]);

        $profileData = [
            'full_name' => $data['full_name'],
            'age' => $data['age'],
            'blood_group' => $data['blood_group'],
            'gender' => $data['gender'],
        ];
        if ($withAddress) {
            $profileData['address'] = $data['address'] ?? null;
        }

        $account->profile()->update($profileData);
    }

    private function updateDoctor(Request $request, $account): void
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'age' => ['required', 'integer', 'between:21,100'],
            'blood_group' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'gender' => ['required', Rule::in(self::GENDERS)],
            'mobile' => ['required', 'regex:' . Phone::BD_MOBILE_REGEX, Rule::unique('accounts', 'mobile')->ignore($account->account_id, 'account_id')],
            'email' => ['required', 'email', 'max:190', Rule::unique('accounts', 'email')->ignore($account->account_id, 'account_id')],
            'bio' => ['nullable', 'string'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'specialty_ids' => ['required', 'array', 'min:1'],
            'specialty_ids.*' => ['integer', 'exists:specialties,specialty_id'],
        ]);

        $account->update(['email' => $data['email'], 'mobile' => Phone::normalize($data['mobile'])]);

        $doctor = $account->doctor;
        $doctor->update([
            'full_name' => $data['full_name'],
            'age' => $data['age'],
            'blood_group' => $data['blood_group'],
            'gender' => $data['gender'],
            'bio' => $data['bio'] ?? null,
            'consultation_fee' => $data['consultation_fee'] ?? 0,
        ]);
        $doctor->specialties()->sync(array_unique($data['specialty_ids']));
    }

    private function updateHospital(Request $request, $account): void
    {
        $hospital = $account->hospital;

        $data = $request->validate([
            'hospital_name' => ['required', 'string', 'max:190'],
            'registration_number' => ['required', 'string', 'max:100', Rule::unique('hospitals', 'registration_number')->ignore($hospital->hospital_id, 'hospital_id')],
            'email' => ['required', 'email', 'max:190', Rule::unique('accounts', 'email')->ignore($account->account_id, 'account_id')],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $account->update(['email' => $data['email']]);
        $hospital->update([
            'hospital_name' => $data['hospital_name'],
            'registration_number' => $data['registration_number'],
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }

    private function updatePharmacy(Request $request, $account): void
    {
        $pharmacy = $account->pharmacy;

        $data = $request->validate([
            'pharmacy_name' => ['required', 'string', 'max:190'],
            'etin_number' => ['required', 'string', 'max:100', Rule::unique('pharmacies', 'etin_number')->ignore($pharmacy->pharmacy_id, 'pharmacy_id')],
            'email' => ['required', 'email', 'max:190', Rule::unique('accounts', 'email')->ignore($account->account_id, 'account_id')],
            'mobile' => ['nullable', 'regex:' . Phone::BD_MOBILE_REGEX, Rule::unique('accounts', 'mobile')->ignore($account->account_id, 'account_id')],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $account->update([
            'email' => $data['email'],
            'mobile' => !empty($data['mobile']) ? Phone::normalize($data['mobile']) : null,
        ]);
        $pharmacy->update([
            'pharmacy_name' => $data['pharmacy_name'],
            'etin_number' => $data['etin_number'],
            'address' => $data['address'] ?? null,
        ]);
    }

    private function updateAdmin(Request $request, $account): void
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('accounts', 'email')->ignore($account->account_id, 'account_id')],
        ]);

        $account->update(['email' => $data['email']]);
        $account->admin->update(['full_name' => $data['full_name']]);
    }

    /** Handles a new photo being uploaded (POST /profile/photo). */
    public function updatePhoto(Request $request)
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $account = Auth::user();

        // Replacing an existing photo — delete the old file so uploads
        // don't just pile up forever on disk.
        if ($account->photo_path) {
            Storage::disk('public')->delete($account->photo_path);
        }

        $path = $request->file('photo')->store('photos', 'public');
        $account->update(['photo_path' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }

    /** Removes the current photo, falling back to the initials avatar (POST /profile/photo/remove). */
    public function destroyPhoto()
    {
        $account = Auth::user();

        if ($account->photo_path) {
            Storage::disk('public')->delete($account->photo_path);
            $account->update(['photo_path' => null]);
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
