<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Admin;
use App\Support\Phone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * A terminal command (not a web page) for creating an admin account. There's
 * no public "sign up as admin" form anywhere in the app on purpose — this
 * is the only way one gets created.
 *
 * Run it like:
 *   php artisan admin:seed "Full Name" email@example.com 01700000000 SomePassword123
 * ('signature' below defines the command's name and its 4 required arguments.)
 */
class SeedAdminCommand extends Command
{
    protected $signature = 'admin:seed {name} {email} {mobile} {password}';
    protected $description = 'Create an admin account (no public admin registration form exists by design). Pass "-" for mobile to leave it blank.';

    /**
     * This runs when someone types `php artisan admin:seed ...`.
     * Returns self::SUCCESS (0) or self::FAILURE (1) — this is the command's
     * "exit code," a standard way for scripts to report whether they worked.
     */
    public function handle(): int
    {
        // Only one admin is allowed to exist, ever. If one's already been
        // created, refuse outright — don't even look at the arguments given.
        if (Account::where('role', 'admin')->exists()) {
            $this->error('An admin account already exists. Only one admin is allowed — this command will not create a second one.');
            return self::FAILURE;
        }

        $name = $this->argument('name');
        $email = $this->argument('email');
        $mobile = $this->argument('mobile') === '-' ? null : Phone::normalize($this->argument('mobile'));
        $password = $this->argument('password');

        // Same idea as $request->validate() in a controller, but there's no
        // HTTP request here (we're in a terminal, not a browser) — so we
        // build a Validator by hand and check it ourselves instead.
        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', 'string', 'min:8']]
        );

        if ($validator->fails()) {
            // $this->error(...) prints red text to the terminal.
            $this->error(implode(' ', $validator->errors()->all()));
            return self::FAILURE;
        }

        $accountQuery = Account::where('email', $email);
        if ($mobile) {
            $accountQuery->orWhere('mobile', $mobile);
        }

        if ($accountQuery->exists()) {
            $this->error('An account with that email/mobile already exists.');
            return self::FAILURE;
        }

        // Same "all-or-nothing" transaction pattern used in RegistrationController.
        $account = DB::transaction(function () use ($name, $email, $mobile, $password) {
            $account = Account::create([
                'role' => 'admin',
                'email' => $email,
                'mobile' => $mobile,
                'password_hash' => Hash::make($password),
                'is_verified' => true, // skip the usual email-verification step for this one
            ]);

            Admin::create(['account_id' => $account->account_id, 'full_name' => $name]);

            return $account;
        });

        // $this->info(...) prints green/success text to the terminal.
        $this->info("Admin account created (account_id={$account->account_id}, is_verified=1 — no email OTP needed for this seeded account, but login will still send a login OTP).");
        return self::SUCCESS;
    }
}
