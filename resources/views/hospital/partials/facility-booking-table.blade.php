<table>
  <thead>
    <tr><th>Date</th><th>Serial</th><th>Facility</th><th>Patient</th><th>Requested stay</th><th>Price</th><th>Status</th><th></th></tr>
  </thead>
  <tbody>
    @foreach ($bookings as $b)
      <tr>
        <td>{{ $b->booking_date->format('D, M j Y') }}</td>
        <td>#{{ $b->serial_number }}</td>
        <td>{{ $b->facilityType->name }}</td>
        <td>{{ $b->patient->full_name }}</td>
        <td class="muted">
          @if ($b->requested_days)
            {{ $b->requested_days }} day(s), until {{ $b->expectedDischargeDate()->format('M j') }}
          @else
            &mdash;
          @endif
        </td>
        <td>BDT {{ number_format($b->price, 2) }}</td>
        <td><span class="badge {{ $b->statusBadgeClass() }}">{{ $b->statusLabel() }}</span></td>
        <td>
          @if ($b->status === 'booked')
            @php
              $confirmMsg = $b->facilityType->is_occupancy
                  ? "Discharge {$b->patient->full_name} from {$b->facilityType->name}? This frees the bed up for the next patient."
                  : "Mark {$b->patient->full_name}'s {$b->facilityType->name} as completed?";
            @endphp
            <form method="POST" action="{{ route('hospital.facility-bookings.complete', $b) }}" enctype="multipart/form-data" data-confirm="{{ $confirmMsg }}" style="display:flex;flex-direction:column;gap:0.3rem;min-width:220px;">
              @csrf
              @unless ($b->facilityType->is_occupancy)
                <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png" style="font-size:0.75rem;">
                <input type="text" name="report_notes" placeholder="Report notes (optional)" style="font-size:0.8rem;padding:0.25rem 0.4rem;">
              @endunless
              <button type="submit" class="btn" style="padding:0.3rem 0.7rem;align-self:flex-start;">
                {{ $b->facilityType->is_occupancy ? 'Discharge' : 'Mark Completed' }}
              </button>
            </form>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
