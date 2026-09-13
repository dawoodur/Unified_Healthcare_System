<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row = one patient bookmarking one doctor. See Patient::favoriteDoctors() for the convenient many-to-many view of this. */
class FavoriteDoctor extends Model
{
    public $timestamps = false;
    protected $table = 'favorite_doctors';
    protected $primaryKey = 'favorite_id';

    protected $fillable = ['patient_id', 'doctor_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}
