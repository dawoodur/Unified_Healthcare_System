<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One row = one medical specialty (e.g. "Cardiology", "Dermatology") from
 * the fixed list seeded in database/seeders/DatabaseSeeder.php. This is
 * the "many" side of Doctor's specialties() relationship — see Doctor.php.
 */
class Specialty extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'specialty_id';

    protected $fillable = ['specialty_name'];

    /** $specialty->doctors gives back every Doctor who has this specialty. */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_specialties', 'specialty_id', 'doctor_id');
    }
}
