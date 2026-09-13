<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row = one general health tip shown on the patient dashboard. See DashboardController::patient(). */
class HealthTip extends Model
{
    public $timestamps = false;
    protected $table = 'health_tips';
    protected $primaryKey = 'tip_id';

    protected $fillable = ['tip_text', 'icon'];
}
