<div class="patient-facility-booking-list">
  @foreach ($bookings as $b)
    @php
      $isOccupancy = (bool) ($b->facilityType->is_occupancy ?? false);
      $categoryName = $b->facilityType->category->category_name ?? null;
    @endphp

    <article class="patient-facility-booking-card patient-facility-booking-card-{{ $b->status }}">
      <div class="patient-facility-booking-date" aria-label="{{ $b->booking_date->format('F j, Y') }}">
        <small>{{ strtoupper($b->booking_date->format('M')) }}</small>
        <strong>{{ $b->booking_date->format('d') }}</strong>
        <span>{{ $b->booking_date->format('D') }}</span>
      </div>

      <div class="patient-facility-booking-identity">
        <span class="patient-facility-booking-icon" aria-hidden="true">
          <i class="bi {{ $isOccupancy ? 'bi-hospital' : 'bi-clipboard2-pulse' }}"></i>
        </span>
        <div>
          <strong>{{ $b->facilityType->name }}</strong>
          @if ($categoryName)
            <span>{{ $categoryName }}</span>
          @endif
          <small><i class="bi bi-building" aria-hidden="true"></i>{{ $b->hospital->hospital_name }}</small>
          <small><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $b->hospital->fullAddress() }}</small>
        </div>
      </div>

      <div class="patient-facility-booking-details">
        @if ($isOccupancy)
          <div>
            <span><i class="bi bi-calendar3" aria-hidden="true"></i>Admission date</span>
            <strong>{{ $b->booking_date->format('M d, Y') }}</strong>
          </div>
          <div>
            <span><i class="bi bi-moon-stars" aria-hidden="true"></i>Requested stay</span>
            <strong>{{ $b->requested_days ? $b->requested_days . ' day(s)' : '—' }}</strong>
          </div>
          <div>
            <span><i class="bi bi-calendar-check" aria-hidden="true"></i>Expected discharge</span>
            <strong>{{ $b->expectedDischargeDate()?->format('M d, Y') ?? '—' }}</strong>
          </div>
        @else
          <div>
            <span><i class="bi bi-calendar3" aria-hidden="true"></i>Booked date</span>
            <strong>{{ $b->booking_date->format('M d, Y') }}</strong>
          </div>
          <div>
            <span><i class="bi bi-list-ol" aria-hidden="true"></i>Queue / Serial</span>
            <strong>{{ $b->serial_number ? '#' . $b->serial_number : '—' }}</strong>
          </div>
        @endif
        <div>
          <span><i class="bi bi-wallet2" aria-hidden="true"></i>Price</span>
          <strong>BDT {{ number_format($b->price, 2) }}</strong>
        </div>
      </div>

      <div class="patient-facility-booking-state">
        <span class="patient-facility-booking-status patient-facility-booking-status-{{ $b->status }}">
          @if ($b->status === 'completed')
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
          @elseif ($b->status === 'cancelled')
            <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
          @else
            <i class="bi bi-clock-fill" aria-hidden="true"></i>
          @endif
          {{ $b->statusLabel() }}
        </span>

        <a href="{{ route('patient.hospitals.show', $b->hospital) }}">
          View hospital <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </div>
    </article>
  @endforeach
</div>
