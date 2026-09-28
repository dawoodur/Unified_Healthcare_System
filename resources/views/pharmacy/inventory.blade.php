@extends('layouts.app')
@section('title', 'Medicine Inventory')
@section('content')
<div class="card">
  <h1>Medicine Inventory</h1>
  <p class="muted">List which medicines you stock, in which batches, at what price, expiring when. Patients compare these prices across pharmacies — see <a href="{{ route('pharmacy.orders') }}">your orders</a>.</p>
  <p style="display:flex;gap:0.6rem;">
    <a href="{{ route('pharmacy.inventory.create') }}" class="btn">+ Add or update a batch</a>
    <a href="{{ route('pharmacy.inventory.medicines.create') }}" class="btn btn-secondary">+ Add a new medicine to the catalog</a>
  </p>
</div>

<div class="card">
  <h2>Your current stock</h2>
  <p class="muted">A batch marked <span class="badge text-bg-danger">Expired</span> won't be sold to patients, but it stays listed here — pull it off your shelf in real life first, then click <strong>Done</strong> to take it off this list.</p>
  @if ($stockByMedicine->isEmpty())
    <p class="muted">You haven't added any stock yet.</p>
  @else
    <table>
      <thead><tr><th>Medicine</th><th>Batch</th><th>Expiry</th><th>Price</th><th>Quantity</th><th></th></tr></thead>
      <tbody>
        @foreach ($stockByMedicine as $medicineMasterId => $batches)
          @foreach ($batches as $batch)
            <tr>
              <td>{{ $batch->medicine->generic_name }} @if($batch->medicine->brand_name)<span class="muted">({{ $batch->medicine->brand_name }})</span>@endif <span class="muted">#m{{ $batch->medicine_master_id }}</span></td>
              <td>{{ $batch->batch_no }}</td>
              <td class="muted">
                {{ $batch->expiry_date->format('M j, Y') }}
                @if ($batch->isExpired())
                  <span class="badge text-bg-danger">Expired</span>
                @endif
              </td>
              <td>BDT {{ number_format($batch->unit_price, 2) }}</td>
              <td>{{ $batch->quantity_available }}</td>
              <td style="display:flex;gap:0.4rem;">
                @unless ($batch->isExpired())
                  <a href="{{ route('pharmacy.inventory.create', ['edit' => $batch->stock_id]) }}" class="btn btn-secondary" style="padding:0.3rem 0.7rem;">Edit</a>
                @endunless
                @if ($batch->isExpired())
                  <form method="POST" action="{{ route('pharmacy.inventory.destroy', $batch) }}" data-confirm="Confirm you've physically removed this expired batch of {{ $batch->medicine->generic_name }} from your shelf?">
                    @csrf
                    <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Done</button>
                  </form>
                @else
                  <form method="POST" action="{{ route('pharmacy.inventory.destroy', $batch) }}" data-confirm="Remove this batch of {{ $batch->medicine->generic_name }}?">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.7rem;">Remove</button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
