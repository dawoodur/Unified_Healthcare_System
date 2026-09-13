<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One row = one patient's medicine order from one pharmacy (an order can't
 * span multiple pharmacies — see medicine_order_items for the individual
 * medicines in it). Tracks its own lifecycle: placed -> accepted ->
 * out_for_delivery -> delivered, or cancelled at any point before delivered.
 */
class MedicineOrder extends Model
{
    // this table DOES have both created_at and updated_at, unlike most
    // other tables in this app — so we let Eloquent manage both normally.
    protected $table = 'medicine_orders';
    protected $primaryKey = 'order_id';

    protected $fillable = [
        'patient_id', 'pharmacy_id', 'prescription_id', 'delivery_agent_id',
        'delivery_address', 'status', 'reward_points_used',
        'subtotal', 'discount_amount', 'total_amount',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id', 'pharmacy_id');
    }

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id', 'delivery_agent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MedicineOrderItem::class, 'order_id', 'order_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'medicine_order_id', 'order_id');
    }

    // Same reasoning as Appointment::statusLabel() — one place to keep the
    // word shown to users consistent across the patient/pharmacy/delivery
    // views. Wording lives in lang/en|bn/statuses.php so it switches with locale.
    private const STATUS_BADGE_CLASSES = [
        'placed' => 'badge-warning',
        'accepted' => 'badge-warning',
        'out_for_delivery' => 'badge-warning',
        'delivered' => 'badge-success',
        'cancelled' => 'badge-danger',
    ];

    public function statusLabel(): string
    {
        $key = 'statuses.medicine_order.' . $this->status;
        return __($key) !== $key ? __($key) : ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge';
    }
}
