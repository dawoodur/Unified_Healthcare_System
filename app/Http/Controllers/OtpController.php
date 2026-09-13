<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the "enter your 6-digit code" screen. This ONE controller is
 * shared by both registration verification AND login 2FA — it doesn't need
 * to know which one it's doing, because it just reads "pending_otp_purpose"
 * out of the session (set earlier by RegistrationController or
 * LoginController) and acts accordingly.
 */
class OtpController extends Controller
{
    // See LoginController.php for an explanation of this constructor pattern.
    public function __construct(private OtpService $otp)
    {
    }

    /**
     * A small private helper (only usable inside this class) that looks up
     * which Account is currently mid-verification, based on what
     * RegistrationController/LoginController stashed in the session a
     * moment ago. Returns null if nobody's mid-verification right now.
     */
    private function pendingAccount(): ?Account
    {
        $accountId = session('pending_account_id');
        return $accountId ? Account::find($accountId) : null;
    }

    /** Shows the "enter your code" page (GET /verify-otp). */
    public function show()
    {
        $account = $this->pendingAccount();
        if (!$account || !session('pending_otp_purpose')) {
            // Nobody's supposed to be here right now (e.g. they navigated
            // here directly without registering/logging in first) — bounce
            // them to login instead of showing a broken page.
            return redirect()->route('login');
        }

        return view('auth.verify-otp');
    }

    /** Handles the code being submitted (POST /verify-otp). */
    public function verify(Request $request)
    {
        $account = $this->pendingAccount();
        $purpose = session('pending_otp_purpose');

        if (!$account || !$purpose) {
            return redirect()->route('login');
        }

        // 'digits:6' = must be exactly 6 digits, nothing else.
        $data = $request->validate(['otp_code' => ['required', 'digits:6']]);

        // The actual checking logic (correct code? expired? too many wrong
        // attempts?) lives in OtpService — this controller just calls it
        // and reacts to the result.
        $result = $this->otp->verify($account, $purpose, $data['otp_code']);

        if (!$result['ok']) {
            return back()->withErrors(['otp_code' => $result['message']]);
        }

        // Correct code — now do something different depending on WHY they
        // were entering a code in the first place.
        if ($purpose === 'registration_verify') {
            $account->update(['is_verified' => true]);
            session()->forget(['pending_account_id', 'pending_otp_purpose']);
            session()->flash('success', 'Email verified successfully! You can now log in.');
            return redirect()->route('login');
        }

        if ($purpose === 'login') {
            // This is the moment the user actually becomes "logged in."
            $request->session()->regenerate(); // security: issue a fresh session ID
            Auth::login($account);             // Laravel now considers this browser authenticated
            $account->update(['last_login_at' => now()]);
            session()->forget(['pending_account_id', 'pending_otp_purpose']);
            return redirect()->route($account->role . '.dashboard');
        }

        if ($purpose === 'password_reset') {
            // Proven it's really them via the emailed code, but don't log
            // them in yet — ForgotPasswordController::showReset() checks
            // for this key to gate the "choose a new password" form.
            session(['password_reset_account_id' => $account->account_id]);
            session()->forget(['pending_account_id', 'pending_otp_purpose']);
            return redirect()->route('password.reset.show');
        }

        return redirect()->route('login');
    }

    /** Handles the "Resend code" button. */
    public function resend(Request $request)
    {
        $account = $this->pendingAccount();
        $purpose = session('pending_otp_purpose');

        if (!$account || !$purpose) {
            return redirect()->route('login');
        }

        // Don't let someone spam-click "resend" — OtpService enforces a
        // cooldown (see OTP_RESEND_COOLDOWN_SEC in app_settings).
        if (!$this->otp->canResend($account, $purpose)) {
            return back()->withErrors(['otp_code' => 'Please wait a bit before requesting another code.']);
        }

        $this->otp->issue($account, $purpose);

        return back()->with('resend_message', 'A new verification code has been sent to your email.');
    }
}
