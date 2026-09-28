<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one admin's profile details (just a name — admins are simple).
 * Linked to an Account row (login/email/password) by account_id.
 * There's no public sign-up form for this one — see
 * app/Console/Commands/SeedAdminCommand.php for how admin accounts get created.
 */
class Admin extends Model
{
    use HasFactory;

    public $timestamps = false;        // no created_at/updated_at columns on this table
    protected $primaryKey = 'admin_id'; // our ID column's real name

    protected $fillable = ['account_id', 'full_name'];

    /** $admin->account gives back the linked Account (email, password, etc). */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }
}
