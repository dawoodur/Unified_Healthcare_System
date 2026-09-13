<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one delivery agent's profile details (name, age, blood group).
 * Linked to an Account row (login/email/password) by account_id.
 * Same shape as Patient.php / Doctor.php — see those files for a fuller
 * explanation of each piece if this one looks unfamiliar.
 */
class DeliveryAgent extends Model
{
    use HasFactory;

    public $timestamps = false;                 // no created_at/updated_at columns on this table
    protected $primaryKey = 'delivery_agent_id'; // our ID column's real name

    protected $fillable = ['account_id', 'full_name', 'age', 'blood_group', 'gender'];

    /** $agent->account gives back the linked Account (email, password, etc). */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    /** Every order ever assigned to this agent, any status. */
    public function deliveries(): HasMany
    {
        return $this->hasMany(MedicineOrder::class, 'delivery_agent_id', 'delivery_agent_id');
    }

    /** Every rating/comment a patient has ever left for this delivery agent — see Review.php. */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'delivery_agent_id', 'delivery_agent_id');
    }
}
