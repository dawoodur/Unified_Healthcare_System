<tr>
  <td>{{ $req->patient->full_name }} <span class="muted">{{ $req->patient->account?->uidTag() }}</span></td>
  <td>{{ $req->facilityType->name }}</td>
  <td class="muted">{{ $req->patient_notes ?? '—' }}</td>
  <td class="muted">{{ $req->assignedDoctor ? 'Dr. ' . $req->assignedDoctor->full_name : '—' }}</td>
  <td class="muted">
    @if ($req->scheduled_date)
      {{ $req->scheduled_date->format('D, M j Y') }} at {{ \Illuminate\Support\Carbon::parse($req->scheduled_time)->format('g:i A') }}
    @else
      —
    @endif
  </td>
  <td class="muted">{{ $req->serial_number ? '#' . $req->serial_number : '—' }}</td>
  <td>BDT {{ number_format($req->price, 2) }}</td>
  <td><span class="badge {{ $req->statusBadgeClass() }}">{{ $req->statusLabel() }}</span></td>
  <td>
    @if (in_array($req->status, ['requested', 'declined']))
      <a href="{{ route('hospital.operations.offer.show', $req) }}" class="btn" style="padding:0.3rem 0.7rem;">{{ $req->status === 'declined' ? 'New Offer' : 'Make Offer' }}</a>
    @elseif ($req->status === 'offered')
      <form method="POST" action="{{ route('hospital.operations.reprioritize', $req) }}" style="display:flex;gap:0.3rem;align-items:center;">
        @csrf
        <input type="number" name="serial_number" value="{{ $req->serial_number }}" min="1" style="width:70px;padding:0.25rem 0.4rem;">
        <button type="submit" class="btn btn-secondary" style="padding:0.3rem 0.6rem;">Update Serial</button>
      </form>
    @elseif ($req->status === 'accepted')
      <form method="POST" action="{{ route('hospital.operations.complete', $req) }}" enctype="multipart/form-data" data-confirm="Mark {{ $req->patient->full_name }}'s {{ $req->facilityType->name }} as completed? This also confirms cash payment was collected." style="display:flex;flex-direction:column;gap:0.3rem;min-width:220px;">
        @csrf
        <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png" style="font-size:0.75rem;">
        <input type="text" name="report_notes" placeholder="Report notes (optional)" style="font-size:0.8rem;padding:0.25rem 0.4rem;">
        <button type="submit" class="btn" style="padding:0.3rem 0.7rem;align-self:flex-start;">Mark Completed</button>
      </form>
    @endif
  </td>
</tr>
