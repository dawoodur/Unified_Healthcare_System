<?php

namespace App\Http\Controllers\Api\Hospital;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OperationRequestController;
use App\Models\OperationRequest;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of OperationRequestController::hospitalIndex().
 *
 * Read-only: making an offer schedules a real operation (date, serial,
 * assigned surgeon, price) and stays on the existing web route, so that
 * scheduling logic keeps living in exactly one place.
 */
class OperationController extends Controller
{
    public function index()
    {
        $hospitalId = Auth::user()->hospital->hospital_id;
        $with = ['patient', 'facilityType', 'assignedDoctor'];
        $pendingStatuses = OperationRequestController::PENDING_STATUSES;

        $pending = OperationRequest::where('hospital_id', $hospitalId)
            ->whereIn('status', $pendingStatuses)
            ->with($with)->orderBy('scheduled_date')->orderBy('scheduled_time')->get();

        $completed = OperationRequest::where('hospital_id', $hospitalId)
            ->whereNotIn('status', $pendingStatuses)
            ->with($with)->orderBy('scheduled_date')->orderBy('scheduled_time')->get();

        return response()->json([
            'pending' => $pending->map(fn (OperationRequest $o) => $this->shape($o)),
            'completed' => $completed->map(fn (OperationRequest $o) => $this->shape($o)),
        ]);
    }

    private function shape(OperationRequest $operation): array
    {
        return [
            'operation_request_id' => (int) $operation->operation_request_id,
            'patient_name' => $operation->patient?->full_name,
            'facility_type' => $operation->facilityType?->name,
            'assigned_doctor' => $operation->assignedDoctor?->full_name,
            'scheduled_date' => $operation->scheduled_date?->format('M j, Y'),
            'scheduled_time' => $operation->scheduled_time,
            'serial_number' => $operation->serial_number,
            'price' => $operation->price,
            'status' => $operation->status,
            'patient_notes' => $operation->patient_notes,
        ];
    }
}
