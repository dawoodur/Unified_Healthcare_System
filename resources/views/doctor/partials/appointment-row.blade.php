<tr>
  <td>#{{ $a->serial_number }}</td>
  <td>{{ $a->patient->full_name }}</td>
  <td>{{ $a->appointment_date->format('D, M j Y') }}</td>
  <td>{{ $a->timeRangeLabel() }}</td>
  <td>
    <span class="badge">{{ ucfirst($a->appointment_type) }}</span>
    @if ($a->appointment_type === 'onsite' && $a->hospital)
      <div class="muted" style="font-size:0.8rem;">{{ $a->hospital->hospital_name }}</div>
    @endif
  </td>
  <td>
    <span class="badge {{ $a->statusBadgeClass() }}">{{ $a->statusLabel() }}</span>
    @if ($a->status === 'no_show')
      <div class="muted" style="font-size:0.8rem;">Refunded &mdash; you didn't mark them visited in time</div>
    @endif
  </td>
  <td>
    @if ($a->payment)
      BDT {{ number_format($a->payment->amount, 2) }}
      <span class="badge {{ $a->payment->status === 'refunded' ? 'text-bg-warning' : 'text-bg-success' }}">{{ ucfirst($a->payment->status) }}</span>
    @else
      <span class="muted">&mdash;</span>
    @endif
  </td>
  <td>
    @if (!in_array($a->status, ['cancelled', 'completed', 'no_show']))
      @if ($a->appointment_type === 'online')
        @if ($a->isJoinableNow())
          <a href="{{ route('consultation.show', $a) }}" class="btn btn-secondary" style="padding:0.3rem 0.7rem;">Join Video Call</a>
        @else
          <button type="button" class="btn btn-secondary" disabled style="padding:0.3rem 0.7rem;">Join Video Call</button>
          <div class="muted" style="font-size:0.8rem;">Opens at {{ $a->timeRangeLabel() }}</div>
        @endif
      @endif
      <form method="POST" action="{{ route('doctor.appointments.visited', $a) }}" style="display:inline;" data-confirm="Mark {{ $a->patient->full_name }} as visited?">
        @csrf
        <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Mark Visited</button>
      </form>
    @elseif ($a->status === 'completed')
      @if ($a->prescription)
        <a href="{{ route('doctor.appointments.prescription.create', $a) }}" class="badge text-bg-success">View Prescription</a>
      @else
        <a href="{{ route('doctor.appointments.prescription.create', $a) }}" class="btn" style="padding:0.3rem 0.7rem;">Issue Prescription</a>
      @endif
      @if ($a->vital)
        <span class="badge text-bg-success">Vitals logged</span>
      @else
        <a href="{{ route('doctor.appointments.vitals.create', $a) }}" class="btn btn-secondary" style="padding:0.3rem 0.7rem;">Log Vitals</a>
      @endif
    @endif
  </td>
</tr>
