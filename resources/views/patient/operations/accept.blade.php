@extends('layouts.app')
@section('title', 'Accept Operation Offer')
@section('content')
<div class="card" style="max-width:560px;margin:0 auto;">
  <h1>{{ $operationRequest->facilityType->name }}</h1>
  <p class="muted">{{ $operationRequest->hospital->hospital_name }}</p>

  <table style="margin-top:0.75rem;">
    <tbody>
      <tr><th>{{ __('patient.operations.doctor_row') }}</th><td>Dr. {{ $operationRequest->assignedDoctor->full_name }}</td></tr>
      <tr><th>{{ __('patient.operations.date_row') }}</th><td>{{ $operationRequest->scheduled_date->format('D, M j Y') }}</td></tr>
      <tr><th>{{ __('patient.operations.time_row') }}</th><td>{{ \Illuminate\Support\Carbon::parse($operationRequest->scheduled_time)->format('g:i A') }}</td></tr>
      <tr><th>{{ __('patient.operations.serial_row') }}</th><td>#{{ $operationRequest->serial_number }}</td></tr>
      <tr><th>{{ __('patient.operations.price_row') }}</th><td>BDT {{ number_format($operationRequest->price, 2) }}</td></tr>
    </tbody>
  </table>

  @if ($paymentMethods->isEmpty())
    <p class="alert alert-danger" style="margin-top:1rem;">{{ __('patient.operations.no_payment_method') }}</p>
  @else
    <form method="POST" action="{{ route('patient.operations.accept', $operationRequest) }}" style="margin-top:1rem;" data-confirm="Accept this offer and pay BDT {{ number_format($operationRequest->price, 2) }}?">
      @csrf
      <div class="field">
        <label>{{ __('patient.operations.payment_method') }}</label>
        @foreach ($paymentMethods as $method)
          <label style="font-weight:normal;display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem;">
            <input type="radio" name="payment_method_id" value="{{ $method->payment_method_id }}" @checked($loop->first) required style="width:auto;min-height:auto;">
            {{ $method->method_name }}{{ __('patient.operations.pay_at_hospital') }}
          </label>
        @endforeach
        <p class="muted">{{ __('patient.operations.bkash_note') }}</p>
      </div>
      <button type="submit" class="btn btn-block">{{ __('patient.operations.accept_confirm') }}</button>
    </form>
  @endif
  <p class="muted"><a href="{{ route('patient.operations') }}">{{ __('patient.operations.back_to_requests') }}</a></p>
</div>
@endsection
