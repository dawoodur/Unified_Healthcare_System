<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row = one pharmacy's profile details (name, ETIN number, address).
 * Linked to an Account row (login/email/password) by account_id.
 * Same shape as Patient.php / Doctor.php — see those files for a fuller
 * explanation of each piece if this one looks unfamiliar.
 */
class Pharmacy extends Model
{
    use HasFactory;

    public $timestamps = false;           // no created_at/updated_at columns on this table
    protected $primaryKey = 'pharmacy_id'; // our ID column's real name

    protected $fillable = ['account_id', 'pharmacy_name', 'etin_number', 'address'];

    /** $pharmacy->account gives back the linked Account (email, password, etc). */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    /** Every medicine batch this pharmacy has ever stocked — see PharmacyMedicineStock.php. */
    public function medicineStock(): HasMany
    {
        return $this->hasMany(PharmacyMedicineStock::class, 'pharmacy_id', 'pharmacy_id');
    }

    /** Every order a patient has placed with this pharmacy, any status. */
    public function orders(): HasMany
    {
        return $this->hasMany(MedicineOrder::class, 'pharmacy_id', 'pharmacy_id');
    }

    /** Every rating/comment a patient has ever left for this pharmacy — see Review.php. */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'pharmacy_id', 'pharmacy_id');
    }
}
