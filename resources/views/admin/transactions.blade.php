@extends('layouts.app')
@section('title', 'Transactions')
@section('content')
<div class="card">
  <h1>Transactions</h1>
  <p class="muted">Every payment made on the platform — appointment fees and medicine orders alike.</p>

  <form method="GET" action="{{ route('admin.transactions') }}" style="display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
    <div>
      <label for="status">Filter by status</label>
      <select id="status" name="status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach (['initiated', 'pending', 'completed', 'failed', 'refunded'] as $option)
          <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
        @endforeach
      </select>
    </div>
  </form>

  <p style="margin-top:1rem;"><strong>{{ $payments->count() }}</strong> transaction(s) shown &middot; <strong>BDT {{ number_format($totalCompleted, 2) }}</strong> completed total</p>
</div>

<div class="card">
  @if ($payments->isEmpty())
    <p class="muted">No transactions found.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Payer</th>
          <th>For</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Status</th>
          <th>Paid at</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($payments as $payment)
          <tr>
            <td>{{ $payment->payment_id }}</td>
            <td>{{ $payment->account?->uidTag() ?? '#u' . $payment->account_id }}</td>
            <td>
              @if ($payment->appointment)
                Appointment #{{ $payment->appointment_id }}
                <span class="muted">({{ $payment->appointment->patient->full_name ?? '—' }} &rarr; Dr. {{ $payment->appointment->doctor->full_name ?? '—' }})</span>
              @elseif ($payment->medicineOrder)
                Order #{{ $payment->medicine_order_id }}
                <span class="muted">({{ $payment->medicineOrder->patient->full_name ?? '—' }} &rarr; {{ $payment->medicineOrder->pharmacy->pharmacy_name ?? '—' }})</span>
              @else
                <span class="muted">—</span>
              @endif
            </td>
            <td>BDT {{ number_format($payment->amount, 2) }}</td>
            <td>{{ $payment->paymentMethod->method_name ?? '—' }}</td>
            <td>
              <span class="badge {{ ['initiated' => 'text-bg-warning', 'pending' => 'text-bg-warning', 'completed' => 'text-bg-success', 'failed' => 'text-bg-danger', 'refunded' => 'text-bg-danger'][$payment->status] ?? 'badge' }}">
                {{ ucfirst($payment->status) }}
              </span>
            </td>
            <td>{{ $payment->paid_at?->format('D, M j Y g:i A') ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
