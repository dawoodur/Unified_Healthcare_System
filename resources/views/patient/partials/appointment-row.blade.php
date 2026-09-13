<tr>
  <td>#{{ $a->serial_number }}</td>
  <td>Dr. {{ $a->doctor->full_name }}</td>
  <td>{{ $a->appointment_date->format('D, M j Y') }}</td>
  <td>
    {{ $a->timeRangeLabel() }}
    @isset($queueStatusByDoctor)
      @if ($a->appointment_date->isToday() && $queueStatusByDoctor->has($a->doctor_id))
        <div class="muted" style="font-size:0.8rem;">{{ __('patient.appointments.now_serving', ['number' => $queueStatusByDoctor[$a->doctor_id]->current_serial]) }}</div>
      @endif
    @endisset
  </td>
  <td>
    <span class="badge">{{ $a->appointment_type === 'online' ? __('patient.book.online') : __('patient.book.onsite') }}</span>
    @if ($a->appointment_type === 'onsite' && $a->hospital)
      <div class="muted" style="font-size:0.8rem;">{{ $a->hospital->hospital_name }}</div>
    @endif
  </td>
  <td>
    <span class="badge {{ $a->statusBadgeClass() }}">{{ $a->statusLabel() }}</span>
    @if ($a->status === 'no_show')
      <div class="muted" style="font-size:0.8rem;">{{ __('patient.appointments.not_marked_visited') }}</div>
    @endif
  </td>
  <td>
    @if ($a->payment)
      BDT {{ number_format($a->payment->amount, 2) }}
      <span class="badge {{ $a->payment->status === 'refunded' ? 'text-bg-warning' : 'text-bg-success' }}">{{ __('statuses.payment.' . $a->payment->status) }}</span>
    @else
      <span class="muted">&mdash;</span>
    @endif
  </td>
  <td>
    @if ($a->appointment_type === 'online' && !in_array($a->status, ['cancelled', 'completed', 'no_show']))
      @if ($a->isJoinableNow())
        <a href="{{ route('consultation.show', $a) }}" class="btn btn-secondary" style="padding:0.3rem 0.7rem;">{{ __('patient.appointments.join_video') }}</a>
      @else
        <button type="button" class="btn btn-secondary" disabled style="padding:0.3rem 0.7rem;">{{ __('patient.appointments.join_video') }}</button>
        <div class="muted" style="font-size:0.8rem;">{{ __('patient.appointments.opens_at', ['time' => $a->timeRangeLabel()]) }}</div>
      @endif
    @endif
    @if (in_array($a->status, ['booked', 'confirmed']))
      <form method="POST" action="{{ route('patient.appointments.cancel', $a) }}" style="display:inline;" data-confirm="Cancel this appointment with Dr. {{ $a->doctor->full_name }}? {{ $a->payment && $a->payment->status === 'completed' ? 'Your payment will be refunded.' : '' }}">
        @csrf
        <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.7rem;">{{ __('patient.appointments.cancel') }}</button>
      </form>
    @endif
  </td>
</tr>
