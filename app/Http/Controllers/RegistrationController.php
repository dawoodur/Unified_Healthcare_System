<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Admin;
use App\Models\DeliveryAgent;
use App\Models\Doctor;
use App\Models\DoctorCertificate;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Pharmacy;
use App\Models\Specialty;
use App\Services\OtpService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * One pair of methods per role (patient/doctor/hospital/pharmacy/delivery):
 * a show*() that displays the empty form, and a store*() that handles it
 * being submitted. All five store*() methods follow the exact same shape:
 *   1. Validate the submitted form data
 *   2. Check the email/mobile isn't already taken
 *   3. Insert an Account row + that role's profile row (as ONE atomic unit)
 *   4. Send a verification email and redirect to the "enter your code" page
 *
 * If storePatient() below makes sense to you, the other four are the same
 * pattern with different fields — you don't need to re-learn it each time.
 */
class RegistrationController extends Controller
{
    // A class constant — a fixed list that never changes while the app runs,
    // shared by every store*() method below that needs to validate a blood
    // group. self::BLOOD_GROUPS refers to this from inside the class.
    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

    // Same idea, for the three roles below that collect gender (patient,
    // doctor, delivery man — the individual-person roles; hospitals and
    // pharmacies are business accounts with no such field).
    private const GENDERS = ['male', 'female'];

    // See LoginController.php for an explanation of this constructor pattern
    // (Laravel automatically creates and hands in an OtpService for us).
    public function __construct(private OtpService $otp)
    {
    }

    /** The "choose your role" landing page (GET /register). */
    public function choose()
    {
        return view('auth.register-choose');
    }

    /** Shows the empty patient registration form (GET /register/patient). */
    public function showPatient()
    {
        return view('auth.register-patient');
    }

    /** Handles the patient registration form being submitted (POST /register/patient). */
    public function storePatient(Request $request)
    {
        // $request->validate([...]) checks every field against these rules.
        // If ANY rule fails, Laravel automatically stops here and sends the
        // user back to the form with error messages — none of the code
        // below runs in that case, so everything after this line can safely
        // assume the data is valid.
        //   'required'        -> can't be empty
        //   'string'/'integer' -> must be that type
        //   'max:150'          -> at most 150 characters
        //   'between:1,120'    -> a number from 1 to 120
        //   Rule::in([...])    -> must be one of these exact values
        //   'regex:...'        -> must match a pattern (see Phone.php for the mobile number pattern)
        //   'confirmed'        -> a matching "password_confirmation" field must also be present
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'age' => ['required', 'integer', 'between:1,120'],
            'blood_group' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'gender' => ['required', Rule::in(self::GENDERS)],
            'mobile' => ['required', 'regex:' . Phone::BD_MOBILE_REGEX],
            'email' => ['required', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Turn "+8801712345678" or "01712345678" etc. into one consistent
        // format before saving (see Phone::normalize()).
        $mobile = Phone::normalize($data['mobile']);

        // Stop here (with a clear error) if this email/mobile is already registered.
        $this->assertIdentifierFree($data['email'], $mobile);

        // This registration touches TWO tables (accounts + patients) — we
        // want either BOTH inserts to succeed, or NEITHER (never a half-done
        // registration). DB::transaction(function () {...}) guarantees that:
        // if anything inside the closure throws an error, every change made
        // so far inside it is automatically undone.
        //
        // `function () use ($data, $mobile) { ... }` is a closure (a small
        // nameless function) — see AppSetting.php for a fuller explanation
        // of what `use (...)` does. Whatever this closure `return`s becomes
        // the value DB::transaction(...) itself returns, which is why
        // `$account = DB::transaction(...)` works.
        $account = DB::transaction(function () use ($data, $mobile) {
            $account = Account::create([
                'role' => 'patient',
                'email' => $data['email'],
                'mobile' => $mobile,
                'password_hash' => Hash::make($data['password']), // never store the real password
                'is_verified' => false,
            ]);

            Patient::create([
                'account_id' => $account->account_id,
                'full_name' => $data['full_name'],
                'age' => $data['age'],
                'blood_group' => $data['blood_group'],
                'gender' => $data['gender'],
                'address' => $data['address'] ?? null, // ?? = "use this if address wasn't submitted"
            ]);

            return $account;
        });

        return $this->afterRegistration($account);
    }

    /**
     * Shows the empty doctor registration form (GET /register/doctor).
     * Also passes the full list of specialties to the view, so it can
     * render them as checkboxes/options for the doctor to pick from.
     */
    public function showDoctor()
    {
        return view('auth.register-doctor', ['specialties' => Specialty::orderBy('specialty_name')->get()]);
    }

    /**
     * Handles the doctor registration form (POST /register/doctor).
     * Same shape as storePatient() above, with two differences worth
     * calling out: a file upload (the certificate) and a many-to-many
     * relationship (specialties) to save.
     */
    public function storeDoctor(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'age' => ['required', 'integer', 'between:21,100'],
            'blood_group' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'gender' => ['required', Rule::in(self::GENDERS)],
            'mobile' => ['required', 'regex:' . Phone::BD_MOBILE_REGEX],
            'email' => ['required', 'email', 'max:190'],
            'bio' => ['nullable', 'string'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'specialty_ids' => ['required', 'array', 'min:1'],
            // 'specialty_ids.*' validates EVERY item inside the specialty_ids
            // array individually — each one must be a real specialty_id that
            // actually exists in the specialties table.
            'specialty_ids.*' => ['integer', 'exists:specialties,specialty_id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // 'mimes:pdf,jpg,jpeg,png' + 'max:5120' = only those file types,
            // at most 5120 KB (5 MB).
            'certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $mobile = Phone::normalize($data['mobile']);
        $this->assertIdentifierFree($data['email'], $mobile);

        // Save the uploaded file to storage/app/certificates (NOT inside
        // public/, so it can't be opened by just guessing/typing a URL —
        // there's no public download route for it yet). $certificatePath
        // ends up being something like "certificates/randomfilename.pdf".
        $certificatePath = $request->file('certificate')->store('certificates', 'local');

        $account = DB::transaction(function () use ($data, $mobile, $certificatePath) {
            $account = Account::create([
                'role' => 'doctor',
                'email' => $data['email'],
                'mobile' => $mobile,
                'password_hash' => Hash::make($data['password']),
                'is_verified' => false,
            ]);

            $doctor = Doctor::create([
                'account_id' => $account->account_id,
                'full_name' => $data['full_name'],
                'age' => $data['age'],
                'blood_group' => $data['blood_group'],
                'gender' => $data['gender'],
                'consultation_fee' => $data['consultation_fee'] ?? 0,
                'bio' => $data['bio'] ?? null,
                'verification_status' => 'pending', // an admin approves this later
            ]);

            // The doctor picked possibly several specialties. sync(...)
            // writes one row per chosen specialty into the doctor_specialties
            // table (see Doctor::specialties() in Doctor.php for why that
            // table exists). array_unique(...) just guards against the same
            // ID being submitted twice by accident.
            $doctor->specialties()->sync(array_unique($data['specialty_ids']));

            DoctorCertificate::create([
                'doctor_id' => $doctor->doctor_id,
                'file_path' => $certificatePath,
                'verification_status' => 'pending',
            ]);

            return $account;
        });

        return $this->afterRegistration($account);
    }

    /** Shows the empty hospital registration form (GET /register/hospital). */
    public function showHospital()
    {
        return view('auth.register-hospital');
    }

    /** Handles the hospital registration form (POST /register/hospital). */
    public function storeHospital(Request $request)
    {
        $data = $request->validate([
            'hospital_name' => ['required', 'string', 'max:190'],
            // 'unique:hospitals,registration_number' checks the hospitals
            // table itself — no two hospitals can share a registration number.
            'registration_number' => ['required', 'string', 'max:100', 'unique:hospitals,registration_number'],
            'email' => ['required', 'email', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Hospitals don't register with a mobile number (per the spec —
        // they log in with email only), so we pass null here.
        $this->assertIdentifierFree($data['email'], null);

        $account = DB::transaction(function () use ($data) {
            $account = Account::create([
                'role' => 'hospital',
                'email' => $data['email'],
                'mobile' => null,
                'password_hash' => Hash::make($data['password']),
                'is_verified' => false,
            ]);

            Hospital::create([
                'account_id' => $account->account_id,
                'hospital_name' => $data['hospital_name'],
                'registration_number' => $data['registration_number'],
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
            ]);

            return $account;
        });

        return $this->afterRegistration($account);
    }

    /** Shows the empty pharmacy registration form (GET /register/pharmacy). */
    public function showPharmacy()
    {
        return view('auth.register-pharmacy');
    }

    /** Handles the pharmacy registration form (POST /register/pharmacy). */
    public function storePharmacy(Request $request)
    {
        $data = $request->validate([
            'pharmacy_name' => ['required', 'string', 'max:190'],
            'etin_number' => ['required', 'string', 'max:100', 'unique:pharmacies,etin_number'],
            'email' => ['required', 'email', 'max:190'],
            // Unlike the other roles, a pharmacy's mobile number is optional
            // ('nullable' instead of 'required') — but IF one is given, it
            // still has to look like a real number.
            'mobile' => ['nullable', 'regex:' . Phone::BD_MOBILE_REGEX],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $mobile = !empty($data['mobile']) ? Phone::normalize($data['mobile']) : null;
        $this->assertIdentifierFree($data['email'], $mobile);

        $account = DB::transaction(function () use ($data, $mobile) {
            $account = Account::create([
                'role' => 'pharmacy',
                'email' => $data['email'],
                'mobile' => $mobile,
                'password_hash' => Hash::make($data['password']),
                'is_verified' => false,
            ]);

            Pharmacy::create([
                'account_id' => $account->account_id,
                'pharmacy_name' => $data['pharmacy_name'],
                'etin_number' => $data['etin_number'],
                'address' => $data['address'] ?? null,
            ]);

            return $account;
        });

        return $this->afterRegistration($account);
    }

    /** Shows the empty delivery agent registration form (GET /register/delivery). */
    public function showDelivery()
    {
        return view('auth.register-delivery');
    }

    /** Handles the delivery agent registration form (POST /register/delivery). */
    public function storeDelivery(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'age' => ['required', 'integer', 'between:18,70'],
            'blood_group' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'gender' => ['required', Rule::in(self::GENDERS)],
            'mobile' => ['required', 'regex:' . Phone::BD_MOBILE_REGEX],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $mobile = Phone::normalize($data['mobile']);
        $this->assertIdentifierFree($data['email'], $mobile);

        $account = DB::transaction(function () use ($data, $mobile) {
            $account = Account::create([
                'role' => 'delivery',
                'email' => $data['email'],
                'mobile' => $mobile,
                'password_hash' => Hash::make($data['password']),
                'is_verified' => false,
            ]);

            DeliveryAgent::create([
                'account_id' => $account->account_id,
                'full_name' => $data['full_name'],
                'age' => $data['age'],
                'blood_group' => $data['blood_group'],
                'gender' => $data['gender'],
            ]);

            return $account;
        });

        return $this->afterRegistration($account);
    }

    /**
     * Shared by every store*() method above: throws a validation error
     * (which Laravel turns into "go back to the form with this message")
     * if the email — or, when given, the mobile number — is already used
     * by some other account.
     */
    private function assertIdentifierFree(string $email, ?string $mobile): void
    {
        $query = Account::where('email', $email);
        if ($mobile) {
            $query->orWhere('mobile', $mobile);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with that email or mobile number already exists.',
            ]);
        }
    }

    /**
     * Shared by every store*() method above: once an Account row exists,
     * this sends the first verification email and remembers (in the
     * session) which account is mid-verification, then sends the browser
     * to the "enter your code" page.
     */
    private function afterRegistration(Account $account)
    {
        $result = $this->otp->issue($account, 'registration_verify');

        session([
            'pending_account_id' => $account->account_id,
            'pending_otp_purpose' => 'registration_verify',
        ]);

        if (!$result['sent']) {
            session()->flash('error', 'Account created, but the verification email could not be sent. Try resending the code.');
        }

        return redirect()->route('otp.show');
    }
}
