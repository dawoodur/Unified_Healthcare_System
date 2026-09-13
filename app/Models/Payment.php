<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row = one payment, for either an appointment OR a medicine order —
 * never both (the database itself enforces that with a CHECK constraint,
 * see the payments migration). bKash-specific columns
 * (bkash_payment_id/bkash_trx_id) sit unused for Cash payments; they only
 * get filled in once a real bKash sandbox integration is wired up.
 */
class Payment extends Model
{
    public $timestamps = false; // this table only has created_at, not updated_at
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'account_id', 'appointment_id', 'medicine_order_id', 'operation_request_id', 'amount',
        'payment_method_id', 'bkash_payment_id', 'bkash_trx_id', 'status', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function medicineOrder(): BelongsTo
    {
        return $this->belongsTo(MedicineOrder::class, 'medicine_order_id', 'order_id');
    }

    public function operationRequest(): BelongsTo
    {
        return $this->belongsTo(OperationRequest::class, 'operation_request_id', 'operation_request_id');
    }
}
