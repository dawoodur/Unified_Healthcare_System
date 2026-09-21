<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorCalendarNote extends Model
{
    protected $fillable = [
        'doctor_id',
        'note_date',
        'note',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];
}
