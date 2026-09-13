<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row = one way to pay ("bKash", "Cash", "Card", "Bank Transfer"),
 * seeded once in DatabaseSeeder. Referenced by payments.payment_method_id.
 */
class PaymentMethod extends Model
{
    public $timestamps = false;
    protected $table = 'payment_methods';
    protected $primaryKey = 'payment_method_id';

    protected $fillable = ['method_name'];
}
