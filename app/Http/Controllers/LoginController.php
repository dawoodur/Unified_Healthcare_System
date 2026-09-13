<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AppSetting;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the "enter email/mobile + password" step of logging in.
 * (The second step — entering the emailed code — is OtpController, not
 * this file. This one only checks the password and, if correct, triggers
 * an OTP email and sends the browser onward to the OTP page.)
 */
class LoginController extends Controller
{
    /**
     * This constructor doesn't get called by you writing `new LoginController(...)`
     * anywhere — Laravel itself creates this controller automatically for
     * every request, and it notices the constructor is asking for an
     * OtpService, so it creates one and hands it in for you. This pattern
     * is called "dependency injection" — you just declare what you need as
     * a parameter, and Laravel supplies it. `private OtpService $otp` both
     * declares the parameter AND saves it as $this->otp in one line (a PHP
     * shortcut called "constructor property promotion").
     */
    public function __construct(private OtpService $otp)
    {
    }

    /** Shows the login form (GET /login). */
    public function show()
    {
        // If we got here because a session timed out (see EnsureRole
        // middleware), show a one-time message explaining why.
        if (request()->boolean('timeout')) {
            session()->flash('info', 'You were signed out due to inactivity.');
        }

        return view('auth.login');
    }

    /** Handles the login form submission (POST /login). */
    public function store(Request $request)
    {
        // Check the submitted data isn't empty. If either field is missing,
        // Laravel automatically sends the user back to the form with an
        // error message — none of the code below even runs in that case.
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // The user can type either their email OR mobile number into one
        // box — this looks for an account matching either.
        $account = Account::where('email', $data['identifier'])
            ->orWhere('mobile', $data['identifier'])
            ->first();

        if (!$account) {
            // back() = "reload the form the user just submitted."
            // withErrors([...]) attaches a message field can show.
            // withInput() refills whatever they'd already typed.
            return back()->withErrors(['identifier' => 'No account found with that email/mobile.'])->withInput();
        }

        if (!$account->is_active) {
            return back()->withErrors(['identifier' => 'This account has been deactivated. Contact support.'])->withInput();
        }

        // If a previous lockout (see registerFailedLogin below) hasn't
        // expired yet, refuse to even check the password.
        if ($account->locked_until && $account->locked_until->isFuture()) {
            $minutesLeft = now()->diffInMinutes($account->locked_until) + 1;
            return back()->withErrors(['identifier' => "Account temporarily locked. Try again in {$minutesLeft} minute(s)."])->withInput();
        }

        // Hash::check compares the typed password against the stored hash
        // (we never store the real password anywhere, only this hash).
        if (!Hash::check($data['password'], $account->password_hash)) {
            $this->registerFailedLogin($account);
            return back()->withErrors(['password' => 'Incorrect password.'])->withInput();
        }

        // Password was correct, but if they never finished verifying their
        // email after registering, don't let them log in yet — instead,
        // resend the registration code and send them to the OTP page.
        if (!$account->is_verified) {
            $this->otp->issue($account, 'registration_verify');
            session(['pending_account_id' => $account->account_id, 'pending_otp_purpose' => 'registration_verify']);
            session()->flash('info', 'Your email is not verified yet. We just sent a fresh verification code.');
            return redirect()->route('otp.show');
        }

        // Correct password + verified account: clear any old lockout state.
        $account->update(['failed_login_attempts' => 0, 'locked_until' => null]);

        // This is the "2-step verification" from the spec: password alone
        // is NOT enough to log in. We generate + email a fresh code, remember
        // in the session "this browser is mid-login for this account," and
        // send them to the same OTP page used by registration.
        $result = $this->otp->issue($account, 'login');
        session(['pending_account_id' => $account->account_id, 'pending_otp_purpose' => 'login']);

        if (!$result['sent']) {
            session()->flash('error', 'Could not send the verification email. Please try again shortly.');
        }

        return redirect()->route('otp.show');
    }

    /** Logs the user out (POST /logout — the button in the site header). */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();     // throw away this browser's session data
        $request->session()->regenerateToken(); // issue a fresh CSRF token for security

        return redirect()->route('login');
    }

    /**
     * Bumps the failed-attempt counter, and locks the account for a while
     * if too many wrong passwords in a row (a basic brute-force defense).
     * AppSetting::get(...) reads the actual threshold/duration numbers from
     * the app_settings database table instead of hardcoding them here.
     */
    private function registerFailedLogin(Account $account): void
    {
        $threshold = (int) AppSetting::get('LOGIN_LOCK_THRESHOLD', '5');
        $lockMinutes = (int) AppSetting::get('LOGIN_LOCK_MINUTES', '15');

        $attempts = $account->failed_login_attempts + 1;

        $update = ['failed_login_attempts' => $attempts];
        if ($attempts >= $threshold) {
            $update['locked_until'] = now()->addMinutes($lockMinutes);
        }

        $account->update($update);
    }
}
