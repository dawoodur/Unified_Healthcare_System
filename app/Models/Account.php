<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * This class represents one row in the `accounts` database table.
 *
 * EVERY login (patient, doctor, hospital, pharmacy, delivery agent, admin)
 * has exactly one row here — it's where the email, mobile, password, and
 * role live. The role-specific details (a patient's age, a doctor's fee,
 * etc.) do NOT live here — they live in separate tables/models (Patient,
 * Doctor, ...) that are linked to this one via account_id. Think of Account
 * as "the login card" and the others as "the profile behind it."
 *
 * `extends Authenticatable` is what lets Laravel treat this class as
 * "a thing that can log in" — it's what makes Auth::login($account) and
 * auth()->user() work.
 */
class Account extends Authenticatable
{
    // Adds some reusable framework features we don't currently use much,
    // but they come standard on any Laravel "user-like" model.
    use HasFactory, Notifiable;

    // Our accounts table's ID column is called "account_id", not Laravel's
    // usual default of just "id" — this line tells Eloquent that.
    protected $primaryKey = 'account_id';

    // Only these fields are allowed to be set through Account::create([...]).
    // This is a safety net: if a form somewhere accidentally submitted a
    // field like "role" it shouldn't be allowed to set, Eloquent silently
    // ignores anything not on this list.
    protected $fillable = [
        'role',
        'email',
        'mobile',
        'photo_path',
        'password_hash',
        'is_verified',
        'is_active',
    ];

    // These fields are automatically hidden if an Account is ever converted
    // to JSON (e.g. for an API response) — so a password hash can never
    // accidentally leak out. We don't build any JSON APIs yet, but it's a
    // free safety net to have from day one.
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    // By default, every column comes back from the database as a plain
    // string. This tells Eloquent "actually turn these into more useful PHP
    // types automatically" — so $account->is_verified is a real true/false
    // value instead of the string "1", and $account->last_login_at is a
    // proper date object you can call ->format('Y-m-d') on, etc.
    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * Laravel's login system expects a method called getAuthPassword() that
     * returns "the hashed password to check against." Normally it would just
     * look for a column literally named "password" — ours is named
     * "password_hash" instead, so we override this one method to point at
     * the right column. Everything else about login still works normally.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // ------------------------------------------------------------------
    // The methods below are "relationships." None of them run any database
    // query by themselves when the class loads — they're recipes that only
    // run when you actually write something like $account->patient in your
    // code. At that point, Eloquent runs a query like:
    //   SELECT * FROM patients WHERE account_id = <this account's id>
    // and hands you back a Patient object (or null if this account isn't a
    // patient). Every account only ever has ONE matching row across all six
    // of these (whichever one matches its role) — the rest will be null.
    // ------------------------------------------------------------------

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class, 'account_id', 'account_id');
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class, 'account_id', 'account_id');
    }

    public function hospital(): HasOne
    {
        return $this->hasOne(Hospital::class, 'account_id', 'account_id');
    }

    public function pharmacy(): HasOne
    {
        return $this->hasOne(Pharmacy::class, 'account_id', 'account_id');
    }

    public function deliveryAgent(): HasOne
    {
        return $this->hasOne(DeliveryAgent::class, 'account_id', 'account_id');
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class, 'account_id', 'account_id');
    }

    // One account can have MANY otp codes over time (a new one every time
    // you log in), so this one is hasMany instead of hasOne — it returns a
    // list, not a single object.
    public function otpVerifications(): HasMany
    {
        return $this->hasMany(OtpVerification::class, 'account_id', 'account_id');
    }

    /** Every in-app notification ever sent to this account, read or unread. */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'account_id', 'account_id');
    }

    /**
     * A shortcut so calling code doesn't need to know/check the role itself.
     * Instead of writing:
     *   $account->role === 'patient' ? $account->patient : ($account->role === 'doctor' ? ... )
     * anywhere we need "this account's profile details," we just write:
     *   $account->profile()
     * and this method looks at the role and fetches the right one for us.
     *
     * `match ($this->role) { 'patient' => ..., 'doctor' => ..., default => null }`
     * is PHP's "match" expression — read it like a cleaner switch statement:
     * "look at $this->role; if it equals 'patient', return $this->patient;
     * if it equals 'doctor', return $this->doctor; ...; if nothing matched,
     * return null."
     */
    public function profile(): ?Model
    {
        return match ($this->role) {
            'patient' => $this->patient,
            'doctor' => $this->doctor,
            'hospital' => $this->hospital,
            'pharmacy' => $this->pharmacy,
            'delivery' => $this->deliveryAgent,
            'admin' => $this->admin,
            default => null,
        };
    }

    // The short prefix shown in front of the account_id for each role, so a
    // u_id reads as "which kind of user" at a glance — e.g. "#doc15" for the
    // doctor whose account_id is 15. This is a DISPLAY label only; the real,
    // underlying ID is still just account_id (see uidTag() below).
    private const UID_PREFIXES = [
        'admin' => 'ad',
        'doctor' => 'doc',
        'hospital' => 'h',
        'pharmacy' => 'ph',
        'patient' => 'pa',
        'delivery' => 'dm',
    ];

    /** "#pa12", "#doc15", "#h3", ... — the role-prefixed u_id shown everywhere in the UI. */
    public function uidTag(): string
    {
        $prefix = self::UID_PREFIXES[$this->role] ?? 'u';

        return "#{$prefix}{$this->account_id}";
    }

    /**
     * The one human-readable name for this account, whatever role it is —
     * patients/doctors/delivery agents have "full_name", hospitals have
     * "hospital_name", pharmacies have "pharmacy_name". Used anywhere we
     * need to show/search "who is this" without the caller having to know
     * which column applies (e.g. the admin user list/search).
     */
    public function displayName(): string
    {
        $profile = $this->profile();

        if (! $profile) {
            return '—';
        }

        return $profile->full_name ?? $profile->hospital_name ?? $profile->pharmacy_name ?? '—';
    }

    /**
     * The public URL for this account's uploaded profile photo, or null if
     * they haven't set one — callers fall back to the initials-circle
     * avatar in that case (see partials/avatar.blade.php). Stored on the
     * PUBLIC disk deliberately (unlike medical record files) since a
     * profile photo is meant to be visible to other people, e.g. a
     * patient seeing a doctor's photo on their profile.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->photo_path) : null;
    }
}
