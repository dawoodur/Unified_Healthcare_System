<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * "Forgot password" — OTP-based, reusing the exact same machinery as
 * registration/login: OtpService issues the code and emails it, and the
 * SAME /verify-otp page (OtpController) checks it. The only new thing
 * this controller adds is what happens on either side of that shared
 * step: asking for the account's email first, and — once OtpController
 * confirms the code was correct for purpose 'password_reset' — showing
 * the "choose a new password" form.
 */
class ForgotPasswordController extends Controller
{
    public function __construct(private OtpService $otp)
    {
    }

    /** Shows the "enter your email" form (GET /forgot-password). */
    public function show()
    {
        return view('auth.forgot-password');
    }

    /** Handles that form being submitted (POST /forgot-password). */
    public function send(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $account = Account::where('email', $data['email'])->first();

        if (!$account) {
            // Matches how login/registration already handle an unknown
            // identifier elsewhere in this app (see LoginController) —
            // consistently direct rather than a vaguer "check your email
            // if that account exists" message.
            return back()->withErrors(['email' => 'No account found with that email.'])->withInput();
        }

        $result = $this->otp->issue($account, 'password_reset');

        // Reuses the exact same session keys OtpController already reads
        // for registration/login — that's what makes GET/POST /verify-otp
        // work here with zero changes to that controller's show()/resend().
        session(['pending_account_id' => $account->account_id, 'pending_otp_purpose' => 'password_reset']);

        if (!$result['sent']) {
            session()->flash('error', 'Could not send the verification email. Please try again shortly.');
        }

        return redirect()->route('otp.show');
    }

    /**
     * Shows the "choose a new password" form (GET /reset-password) — only
     * reachable after OtpController::verify() has confirmed the emailed
     * code was correct for purpose 'password_reset' (see the branch added
     * there), which is what sets password_reset_account_id in the session.
     */
    public function showReset()
    {
        if (!session('password_reset_account_id')) {
            return redirect()->route('login');
        }

        return view('auth.reset-password');
    }

    /** Handles the new password being submitted (POST /reset-password). */
    public function reset(Request $request)
    {
        $accountId = session('password_reset_account_id');
        if (!$accountId) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $account = Account::findOrFail($accountId);
        $account->update([
            'password_hash' => Hash::make($data['password']),
            // A forgotten password is a good reason to also clear any
            // stale lockout state — the account owner has just proven
            // (via emailed OTP) that this really is them.
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        session()->forget('password_reset_account_id');
        session()->flash('success', 'Password reset successfully. You can now log in.');

        return redirect()->route('login');
    }
}
