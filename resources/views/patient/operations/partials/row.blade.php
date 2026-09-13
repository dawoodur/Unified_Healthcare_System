@php
  $isScheduled = (bool) $req->scheduled_date;
  $requestDate = $req->created_at?->locale(app()->getLocale());
  $scheduledDate = $req->scheduled_date?->locale(app()->getLocale());
  $statusHintKey = 'patient.operations.status_hint_' . $req->status;
  $statusHint = __($statusHintKey);
@endphp

<article class="patient-surgery-request patient-surgery-request-{{ $req->status }}">
  <div class="patient-surgery-date" aria-label="{{ $requestDate?->translatedFormat('F j, Y') }}">
    <small>{{ $requestDate?->translatedFormat('M') ?? '—' }}</small>
    <strong>{{ $requestDate?->format('d') ?? '—' }}</strong>
    <span>{{ $requestDate?->translatedFormat('D') ?? '' }}</span>
  </div>

  <div class="patient-surgery-request-identity">
    <span class="patient-surgery-request-icon" aria-hidden="true"><i class="bi bi-scissors"></i></span>
    <div>
      <strong>{{ $req->facilityType->name }}</strong>
      <small><i class="bi bi-hospital" aria-hidden="true"></i>{{ $req->hospital->hospital_name }}</small>
      <small><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $req->hospital->fullAddress() }}</small>
      <small><i class="bi bi-file-earmark-text" aria-hidden="true"></i>{{ __('patient.operations.request_id', ['id' => $req->operation_request_id]) }}</small>
    </div>
  </div>

  <div class="patient-surgery-request-details">
    <div>
      <span><i class="bi bi-calendar3" aria-hidden="true"></i>{{ __('patient.operations.requested_on') }}</span>
      <strong>{{ $requestDate?->translatedFormat('d M Y') ?? '—' }}</strong>
    </div>

    <div>
      <span><i class="bi bi-calendar2-week" aria-hidden="true"></i>{{ __('patient.operations.scheduled_for') }}</span>
      <strong>
        @if ($isScheduled)
          {{ $scheduledDate->translatedFormat('d M Y') }} · {{ \Illuminate\Support\Carbon::parse($req->scheduled_time)->format('g:i A') }}
        @else
          {{ __('patient.operations.not_scheduled') }}
        @endif
      </strong>
    </div>

    <div>
      <span><i class="bi bi-person-badge" aria-hidden="true"></i>{{ __('patient.operations.assigned_doctor') }}</span>
      <strong>{{ $req->assignedDoctor ? __('patient.operations.doctor_name', ['name' => $req->assignedDoctor->full_name]) : __('patient.operations.not_assigned') }}</strong>
    </div>

    <div>
      <span><i class="bi bi-list-ol" aria-hidden="true"></i>{{ __('patient.operations.queue_serial') }}</span>
      <strong>{{ $req->serial_number ? '#' . $req->serial_number : '—' }}</strong>
    </div>

    <div>
      <span><i class="bi bi-wallet2" aria-hidden="true"></i>{{ __('patient.operations.price') }}</span>
      <strong>BDT {{ number_format($req->price, 2) }}</strong>
    </div>
  </div>

  <div class="patient-surgery-request-notes">
    <span>{{ __('patient.operations.patient_notes') }}</span>
    <p>{{ $req->patient_notes ?: __('patient.operations.no_notes') }}</p>
  </div>

  <div class="patient-surgery-request-actions">
    <span class="patient-surgery-status patient-surgery-status-{{ $req->status }}">
      @switch($req->status)
        @case('requested')
          <i class="bi bi-hourglass-split" aria-hidden="true"></i>
          @break
        @case('offered')
          <i class="bi bi-envelope-check-fill" aria-hidden="true"></i>
          @break
        @case('accepted')
          <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
          @break
        @case('completed')
          <i class="bi bi-check2-all" aria-hidden="true"></i>
          @break
        @case('declined')
          <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
          @break
        @default
          <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
      @endswitch
      {{ $req->statusLabel() }}
    </span>

    @if ($statusHint !== $statusHintKey)
      <p>{{ $statusHint }}</p>
    @endif

    @if ($req->status === 'offered')
      <a class="patient-surgery-action patient-surgery-action-primary" href="{{ route('patient.operations.accept.show', $req) }}">
        {{ __('patient.operations.review_offer') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
      </a>
      <form method="POST" action="{{ route('patient.operations.decline', $req) }}" data-confirm="{{ __('patient.operations.decline_confirm', ['hospital' => $req->hospital->hospital_name]) }}">
        @csrf
        <button type="submit" class="patient-surgery-action patient-surgery-action-danger">{{ __('patient.operations.decline_offer') }}</button>
      </form>
    @elseif ($req->status === 'requested')
      <form method="POST" action="{{ route('patient.operations.cancel', $req) }}" data-confirm="{{ __('patient.operations.cancel_confirm') }}">
        @csrf
        <button type="submit" class="patient-surgery-action patient-surgery-action-secondary">{{ __('patient.operations.withdraw_request') }}</button>
      </form>
    @else
      <a class="patient-surgery-action patient-surgery-action-secondary" href="{{ route('patient.hospitals.show', $req->hospital) }}">
        {{ __('patient.operations.view_hospital') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
      </a>
    @endif
  </div>
</article>
