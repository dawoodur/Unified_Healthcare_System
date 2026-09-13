<?php

namespace App\Services;

use App\Models\MedicineMaster;
use App\Models\MedicineOrder;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Pharmacy;
use App\Models\PharmacyMedicineStock;
use App\Services\RewardPointService;
use Illuminate\Support\Facades\DB;

/**
 * Handles placing a medicine order safely: checking real stock is
 * available, decrementing it batch-by-batch (oldest expiry first — see
 * decrementStock()), and creating the order + its line items + a Payment
 * record as one atomic unit. Mirrors AppointmentService/FacilityBookingService:
 * a DB transaction with row locking so two patients buying the last few
 * units of the same medicine at the same instant can't both succeed.
 */
class MedicineOrderService
{
    public function __construct(private RewardPointService $rewardPoints)
    {
    }

    /**
     * Places an order for one pharmacy's medicines.
     * $items is [medicine_master_id => quantity, ...] — everything in the
     * patient's cart, which is always for a single pharmacy (see
     * CartController — a cart can't mix pharmacies, matching
     * medicine_orders having one pharmacy_id per order).
     * $pointsToRedeem is how many reward points the patient asked to
     * spend on this order (0 if none) — re-clamped against their ACTUAL
     * current balance inside this same transaction (see the
     * lockForUpdate() below), never trusted as-is from the caller.
     * Returns ['ok' => bool, 'message' => string, 'order' => ?MedicineOrder].
     */
    public function placeOrder(Patient $patient, Pharmacy $pharmacy, array $items, string $deliveryAddress, PaymentMethod $paymentMethod, int $pointsToRedeem = 0): array
    {
        if (empty($items)) {
            return ['ok' => false, 'message' => 'Your cart is empty.', 'order' => null];
        }

        try {
            return DB::transaction(function () use ($patient, $pharmacy, $items, $deliveryAddress, $paymentMethod, $pointsToRedeem) {
                $lineItems = [];
                $subtotal = 0;

                foreach ($items as $medicineMasterId => $quantity) {
                    $result = $this->decrementStock($pharmacy, (int) $medicineMasterId, (int) $quantity);

                    if (!$result['ok']) {
                        // Throwing inside DB::transaction()'s closure automatically
                        // undoes every stock decrement already made earlier in
                        // this same loop — caught just below, converted back
                        // into the ['ok' => false, ...] shape callers expect.
                        throw new \RuntimeException($result['message']);
                    }

                    $lineTotal = $result['unit_price'] * $quantity;
                    $subtotal += $lineTotal;

                    $lineItems[] = [
                        'medicine_master_id' => $medicineMasterId,
                        'quantity' => $quantity,
                        'unit_price_snapshot' => $result['unit_price'],
                    ];
                }

                // lockForUpdate() + a fresh read of the patient's balance
                // here (not whatever value the caller's $patient object
                // happened to be holding) — same reasoning as
                // decrementStock() above: without this, two checkouts
                // submitted at nearly the same instant could each see the
                // same starting balance and both "successfully" spend
                // points that, together, exceed what the patient actually has.
                $lockedPatient = Patient::where('patient_id', $patient->patient_id)->lockForUpdate()->first();
                $redemption = $this->rewardPoints->previewRedemption($lockedPatient, $pointsToRedeem, $subtotal);
                $totalAmount = $subtotal - $redemption['discount'];

                $order = MedicineOrder::create([
                    'patient_id' => $patient->patient_id,
                    'pharmacy_id' => $pharmacy->pharmacy_id,
                    'delivery_address' => $deliveryAddress,
                    'status' => 'placed',
                    'reward_points_used' => $redemption['points'],
                    'subtotal' => $subtotal,
                    'discount_amount' => $redemption['discount'],
                    'total_amount' => $totalAmount,
                ]);

                foreach ($lineItems as $lineItem) {
                    $order->items()->create($lineItem);
                }

                $this->rewardPoints->commitRedemption($lockedPatient, $redemption['points'], $order->order_id);

                Payment::create([
                    'account_id' => $patient->account_id,
                    'medicine_order_id' => $order->order_id,
                    'amount' => $totalAmount,
                    'payment_method_id' => $paymentMethod->payment_method_id,
                    'status' => 'pending', // flips to 'completed' once delivery is confirmed — see DeliveryOrderController::confirm()
                ]);

                return ['ok' => true, 'message' => 'Order placed.', 'order' => $order->fresh()];
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'order' => null];
        }
    }

    /**
     * Decrements stock for one medicine at one pharmacy, oldest-expiry-first
     * (FEFO — first-expiry-first-out), the same principle a real pharmacy
     * uses so older stock sells before it expires. If the requested
     * quantity spans more than one batch, the FIRST (soonest-expiring)
     * batch used sets the price for the whole line — a reasonable
     * simplification since medicine_order_items only stores one price per
     * line, and in practice a pharmacy's batches of the same medicine are
     * priced the same anyway.
     * Returns ['ok' => bool, 'message' => string, 'unit_price' => ?float].
     */
    private function decrementStock(Pharmacy $pharmacy, int $medicineMasterId, int $quantity): array
    {
        $batches = PharmacyMedicineStock::where('pharmacy_id', $pharmacy->pharmacy_id)
            ->where('medicine_master_id', $medicineMasterId)
            ->where('quantity_available', '>', 0)
            ->where('expiry_date', '>', now()->toDateString()) // never sell expired stock
            ->orderBy('expiry_date')
            ->lockForUpdate()
            ->get();

        $totalAvailable = $batches->sum('quantity_available');
        if ($totalAvailable < $quantity) {
            $medicineName = $batches->first()?->medicine?->generic_name ?? 'this medicine';
            return ['ok' => false, 'message' => "Sorry, {$pharmacy->pharmacy_name} only has {$totalAvailable} unit(s) of {$medicineName} left.", 'unit_price' => null];
        }

        $remaining = $quantity;
        $unitPrice = null;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $unitPrice ??= (float) $batch->unit_price; // the first (soonest-expiring) batch used sets the line's price

            $take = min($remaining, $batch->quantity_available);
            $batch->decrement('quantity_available', $take);
            $remaining -= $take;
        }

        return ['ok' => true, 'message' => 'ok', 'unit_price' => $unitPrice];
    }

    /**
     * Cancels an order and restocks whatever it had reserved — used by a
     * pharmacy rejecting a just-placed order, or a patient cancelling
     * before it's accepted. Which exact batch each unit originally came
     * from isn't tracked per line item, so the restock goes onto whichever
     * batch of that medicine currently expires soonest instead (keeping
     * the FEFO ordering sensible for the next order).
     */
    public function cancelOrder(MedicineOrder $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $batch = PharmacyMedicineStock::where('pharmacy_id', $order->pharmacy_id)
                    ->where('medicine_master_id', $item->medicine_master_id)
                    ->orderBy('expiry_date')
                    ->lockForUpdate()
                    ->first();

                if ($batch) {
                    $batch->increment('quantity_available', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);
            $order->payment?->update(['status' => 'failed']);
        });
    }

    /**
     * Works out what a cart is currently worth, for both the cart page and
     * the checkout page — priced using each medicine's cheapest
     * currently-in-stock, non-expired batch at that pharmacy (the same
     * price a patient would actually be charged if they ordered right now).
     * $items is [medicine_master_id => quantity, ...]. Returns
     * ['lines' => Collection of ['medicine' => MedicineMaster, 'quantity' => int, 'price' => ?float, 'line_total' => float], 'subtotal' => float].
     */
    public function previewCart(Pharmacy $pharmacy, array $items): array
    {
        $lines = collect();
        $subtotal = 0;

        foreach ($items as $medicineMasterId => $quantity) {
            $medicine = MedicineMaster::find($medicineMasterId);
            if (!$medicine) {
                continue;
            }

            $price = PharmacyMedicineStock::where('pharmacy_id', $pharmacy->pharmacy_id)
                ->where('medicine_master_id', $medicineMasterId)
                ->where('quantity_available', '>', 0)
                ->where('expiry_date', '>', now()->toDateString())
                ->min('unit_price');

            $lineTotal = ($price ?? 0) * $quantity;
            $subtotal += $lineTotal;

            $lines->push([
                'medicine' => $medicine,
                'quantity' => $quantity,
                'price' => $price,
                'line_total' => $lineTotal,
            ]);
        }

        return ['lines' => $lines, 'subtotal' => $subtotal];
    }
}
