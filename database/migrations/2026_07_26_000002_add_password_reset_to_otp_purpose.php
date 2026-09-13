<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds 'password_reset' to otp_verifications.purpose — the "forgot
 * password" flow reuses the exact same OTP machinery (OtpService, the
 * shared /verify-otp page) as registration and login, it just needed this
 * purpose value to exist. See ForgotPasswordController.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE otp_verifications MODIFY purpose ENUM('registration_verify', 'login', 'record_access', 'delivery_confirmation', 'password_reset') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE otp_verifications MODIFY purpose ENUM('registration_verify', 'login', 'record_access', 'delivery_confirmation') NOT NULL");
    }
};
