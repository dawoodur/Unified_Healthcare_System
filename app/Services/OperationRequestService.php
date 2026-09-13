<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\HospitalFacility;
use App\Models\MedicalRecord;
use App\Models\OperationRequest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

/**
 * The major-operation request lifecycle: requested -> offered -> accepted
 * -> completed, with declined/cancelled off-ramps along the way (see the
 * doc comment on the operation_requests migration for the full diagram).
 * Every method here re-checks the request's CURRENT status before acting
 * — never trusts that a controller already checked it — since two tabs,
 * a slow double-click, or a stale page could otherwise let an action fire
 * twice or out of order.
 */
class OperationRequestService
{
    /** Patient submits a new request for one hospital's Surgery-category facility type. */
    public function request(Patient $patient, Hospital $hospital, HospitalFacility $offering, ?string $notes): array
    {
        $operationRequest = OperationRequest::create([
            'patient_id' => $patient->patient_id,
            'hospital_id' => $hospital->hospital_id,
            'facility_type_id' => $offering->facility_type_id,
            'patient_notes' => $notes,
            'price' => $offering->price, // snapshotted now — this flow doesn't support a later custom quote
            'status' => 'requested',
        ]);

        return ['ok' => true, 'message' => 'Request submitted.', 'operationRequest' => $operationRequest];
    }

    /** The next unused serial number among this hospital's currently offered/accepted requests for that date — just a starting suggestion, the hospital can override it. */
    public function suggestSerial(Hospital $hospital, string $date): int
    {
        $max = OperationRequest::where('hospital_id', $hospital->hospital_id)
            ->where('scheduled_date', $date)
            ->whereIn('status', ['offered', 'accepted'])
            ->max('serial_number');

        return ((int) $max) + 1;
    }

    /**
     * Hospital assigns (or re-assigns, after a decline) a doctor/date/time/
     * serial number. Allowed from 'requested' or 'declined' — a decline
     * isn't a dead end, it's the hospital's cue to try a different slot.
     */
    public function offer(OperationRequest $operationRequest, Doctor $doctor, string $date, string $time, int $serial): array
    {
        if (!in_array($operationRequest->status, ['requested', 'declined'], true)) {
            return ['ok' => false, 'message' => 'This request already has an active offer.'];
        }

        $operationRequest->update([
            'assigned_doctor_id' => $doctor->doctor_id,
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'serial_number' => $serial,
            'status' => 'offered',
            'offered_at' => now(),
        ]);

        return ['ok' => true, 'message' => 'Offer sent to the patient.'];
    }

    /**
     * Hospital bumps/reorders the serial number for emergency triage —
     * only while still 'offered'. Once a patient has accepted (and paid),
     * their slot is locked in; reprioritizing only ever reshuffles
     * requests still awaiting a response.
     */
    public function reprioritize(OperationRequest $operationRequest, int $newSerial): array
    {
        if ($operationRequest->status !== 'offered') {
            return ['ok' => false, 'message' => 'Only an offer still awaiting the patient\'s response can be reprioritized.'];
        }

        $operationRequest->update(['serial_number' => $newSerial]);

        return ['ok' => true, 'message' => 'Serial number updated.'];
    }

    /**
     * Patient accepts an offer and pays for it in the same step (Cash on
     * Delivery-style, same as medicine checkout — no working payment
     * gateway is connected yet). Creates the Payment row right here so an
     * accepted request always has exactly one.
     */
    public function accept(OperationRequest $operationRequest, PaymentMethod $paymentMethod): array
    {
        if ($operationRequest->status !== 'offered') {
            return ['ok' => false, 'message' => 'This request no longer has an active offer to accept.'];
        }

        return DB::transaction(function () use ($operationRequest, $paymentMethod) {
            $operationRequest->update(['status' => 'accepted', 'responded_at' => now()]);

            Payment::create([
                'account_id' => $operationRequest->patient->account_id,
                'operation_request_id' => $operationRequest->operation_request_id,
                'amount' => $operationRequest->price,
                'payment_method_id' => $paymentMethod->payment_method_id,
                'status' => 'pending', // flips to 'completed' once the operation is marked done — see markCompleted()
            ]);

            return ['ok' => true, 'message' => 'Accepted — see you on the scheduled date.'];
        });
    }

    /** Patient declines an offer — the hospital can then make a new one (see offer() above). */
    public function decline(OperationRequest $operationRequest): array
    {
        if ($operationRequest->status !== 'offered') {
            return ['ok' => false, 'message' => 'This request no longer has an active offer to decline.'];
        }

        $operationRequest->update(['status' => 'declined', 'responded_at' => now()]);

        return ['ok' => true, 'message' => 'Offer declined.'];
    }

    /** Patient withdraws a request entirely — only possible before the hospital has made any offer. */
    public function cancel(OperationRequest $operationRequest): array
    {
        if ($operationRequest->status !== 'requested') {
            return ['ok' => false, 'message' => 'This request already has an offer — decline it instead, or message the hospital.'];
        }

        $operationRequest->update(['status' => 'cancelled']);

        return ['ok' => true, 'message' => 'Request cancelled.'];
    }

    /**
     * Hospital marks the operation as actually performed — flips the
     * linked Payment to 'completed' (cash collected in person, same
     * moment the procedure happened) and, same idea as
     * HospitalFacilityController::markCompleted() for other facility
     * types, optionally attaches a report that becomes part of the
     * patient's medical records.
     */
    public function markCompleted(OperationRequest $operationRequest, string $hospitalName, int $createdByAccountId, ?string $filePath, ?string $notes): array
    {
        if ($operationRequest->status !== 'accepted') {
            return ['ok' => false, 'message' => 'Only an accepted request can be marked completed.'];
        }

        DB::transaction(function () use ($operationRequest, $hospitalName, $createdByAccountId, $filePath, $notes) {
            $operationRequest->update(['status' => 'completed', 'completed_at' => now()]);
            $operationRequest->payment?->update(['status' => 'completed', 'paid_at' => now()]);

            if ($filePath || $notes) {
                MedicalRecord::create([
                    'patient_id' => $operationRequest->patient_id,
                    'record_type' => 'facility_report',
                    'reference_id' => $operationRequest->operation_request_id,
                    'file_path' => $filePath,
                    'description' => trim($operationRequest->facilityType->name . ' — ' . $hospitalName . ($notes ? ': ' . $notes : '')),
                    'created_by_account_id' => $createdByAccountId,
                ]);
            }
        });

        return ['ok' => true, 'message' => 'Marked as completed.'];
    }
}
