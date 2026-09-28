@extends('layouts.app')
@section('title', 'Facilities & Pricing')
@section('content')
<div class="card">
  <h1>Facilities &amp; Pricing</h1>
  <p class="muted">Set your price and daily booking quota for each facility you offer. Patients compare these prices across hospitals, and can book a date directly — see <a href="{{ route('hospital.facility-bookings') }}">everyone who's booked</a>.</p>
  <p><a href="{{ route('hospital.facilities.types.create') }}" class="btn btn-secondary">+ Add a new facility type to the catalog</a></p>
</div>

@foreach ($categories as $category)
  @if ($category->facilityTypes->isNotEmpty())
    <div class="card">
      <h2>{{ $category->category_name }}</h2>
      <table>
        <thead><tr><th>Facility</th><th>Your price</th><th>{{ $category->facilityTypes->first()->is_occupancy ? 'Total beds' : 'Daily quota' }}</th><th></th></tr></thead>
        <tbody>
          @foreach ($category->facilityTypes as $type)
            @php $offering = $offerings->get($type->facility_type_id); @endphp
            <tr>
              <td>{{ $type->name }} <span class="muted">({{ $type->unit_label }})</span></td>
              <td>
                @if ($offering)
                  BDT {{ number_format($offering->price, 2) }}
                @else
                  <span class="muted">Not listed</span>
                @endif
              </td>
              <td>
                @if ($offering)
                  {{ $offering->daily_capacity }}{{ $type->is_occupancy ? ' beds' : ' / day' }}
                @else
                  <span class="muted">—</span>
                @endif
              </td>
              <td style="display:flex;gap:0.4rem;">
                <a href="{{ route('hospital.facilities.create', $type) }}" class="btn" style="padding:0.35rem 0.7rem;">{{ $offering ? 'Update' : '+ Add' }}</a>
                @if ($offering)
                  <form method="POST" action="{{ route('hospital.facilities.destroy', $offering) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="padding:0.35rem 0.7rem;" data-confirm="Remove this listing?">Remove</button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
@endforeach
@endsection
