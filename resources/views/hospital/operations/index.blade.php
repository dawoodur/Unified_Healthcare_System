@extends('layouts.app')
@section('title', 'Operation Requests')
@section('content')
<div class="card">
  <h1>Operation Requests</h1>
  <p class="muted">Patients requesting a major operation from your Surgery listings. Respond with a doctor, date, time, and serial number — the patient then accepts (and pays) or declines. While an offer is still awaiting the patient's response, you can bump its serial number for emergency triage; once accepted, the slot is locked in.</p>
  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
</div>

<div class="card">
  <h2>Pending</h2>
  @if ($pending->isEmpty())
    <p class="muted">Nothing pending right now.</p>
  @else
    <table>
      <thead><tr><th>Patient</th><th>Operation</th><th>Notes</th><th>Doctor</th><th>Date &amp; time</th><th>Serial</th><th>Price</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach ($pending as $req)
          @include('hospital.operations.partials.row', ['req' => $req])
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="card">
  <h2>Completed</h2>
  @if ($completed->isEmpty())
    <p class="muted">No one has requested an operation yet.</p>
  @else
    <table>
      <thead><tr><th>Patient</th><th>Operation</th><th>Notes</th><th>Doctor</th><th>Date &amp; time</th><th>Serial</th><th>Price</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach ($completed as $req)
          @include('hospital.operations.partials.row', ['req' => $req])
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
