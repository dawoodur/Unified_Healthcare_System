@extends('layouts.app')
@section('title', 'Search Users')
@section('content')
<div class="card">
  <h1>Search Users</h1>
  <p class="muted">Every user — any of the 6 roles — has a permanent <strong>u_id</strong> assigned the moment their account is created. Look one up by u_id for their full record, or filter the list below by name.</p>
  <form method="GET" action="{{ route('admin.users') }}" class="field" style="display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
    <div>
      <label for="u_id">u_id</label>
      <input type="number" id="u_id" name="u_id" min="1" value="{{ $uid }}" placeholder="e.g. 12">
    </div>
    <div>
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="{{ $name }}" placeholder="e.g. Rahim">
    </div>
    <button type="submit" class="btn">Search</button>
    @if ($uid || $name)
      <a href="{{ route('admin.users') }}" class="btn btn-secondary">Clear</a>
    @endif
  </form>
</div>

@if ($notFound)
  <div class="card">
    <p class="alert alert-danger">No user found with u_id #{{ $uid }}.</p>
  </div>
@endif

@if ($account)
  <div class="card">
    <h2>{{ $account->uidTag() }} <span class="badge">{{ ucfirst($account->role) }}</span></h2>
    <table>
      <tbody>
        <tr><th>Name</th><td>{{ $profile->full_name ?? $profile->hospital_name ?? $profile->pharmacy_name ?? '—' }}</td></tr>
        <tr><th>Email</th><td>{{ $account->email }}</td></tr>
        <tr><th>Mobile</th><td>{{ $account->mobile ?? '—' }}</td></tr>
        <tr><th>Verified</th><td><span class="badge {{ $account->is_verified ? 'text-bg-success' : 'text-bg-warning' }}">{{ $account->is_verified ? 'Yes' : 'No' }}</span></td></tr>
        <tr><th>Active</th><td><span class="badge {{ $account->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $account->is_active ? 'Yes' : 'No' }}</span></td></tr>
        <tr><th>Joined</th><td>{{ $account->created_at->format('D, M j Y') }}</td></tr>
        <tr><th>Last login</th><td>{{ $account->last_login_at?->format('D, M j Y g:i A') ?? 'Never' }}</td></tr>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h2>Profile details</h2>
    @if (!$profile)
      <p class="muted">No profile row found for this account — this shouldn't normally happen.</p>
    @else
      @switch($account->role)
        @case('patient')
          <table>
            <tbody>
              <tr><th>Age</th><td>{{ $profile->age }}</td></tr>
              <tr><th>Gender</th><td>{{ ucfirst($profile->gender) }}</td></tr>
              <tr><th>Blood group</th><td>{{ $profile->blood_group }}</td></tr>
              <tr><th>Address</th><td>{{ $profile->address ?? '—' }}</td></tr>
              <tr><th>Reward points</th><td>{{ $profile->reward_points_balance }}</td></tr>
            </tbody>
          </table>
          <div class="grid grid-2" style="margin-top:1rem;">
            <p><strong>{{ $profile->appointments()->count() }}</strong> appointment(s)</p>
            <p><strong>{{ $profile->facilityBookings()->count() }}</strong> facility booking(s)</p>
            <p><strong>{{ $profile->medicineOrders()->count() }}</strong> medicine order(s)</p>
            <p><strong>{{ $profile->medicalRecords()->count() }}</strong> medical record(s)</p>
          </div>
          @break

        @case('doctor')
          <table>
            <tbody>
              <tr><th>Age</th><td>{{ $profile->age }}</td></tr>
              <tr><th>Gender</th><td>{{ ucfirst($profile->gender) }}</td></tr>
              <tr><th>Blood group</th><td>{{ $profile->blood_group }}</td></tr>
              <tr><th>Consultation fee</th><td>BDT {{ number_format($profile->consultation_fee, 2) }}</td></tr>
              <tr><th>Verification status</th><td><span class="badge {{ ['pending' => 'text-bg-warning', 'approved' => 'text-bg-success', 'rejected' => 'text-bg-danger'][$profile->verification_status] }}">{{ ucfirst($profile->verification_status) }}</span></td></tr>
              <tr><th>Specialties</th><td>{{ $profile->specialties->pluck('specialty_name')->implode(', ') ?: 'None' }}</td></tr>
            </tbody>
          </table>
          <div class="grid grid-2" style="margin-top:1rem;">
            <p><strong>{{ $profile->appointments()->count() }}</strong> appointment(s)</p>
            <p><strong>{{ $profile->prescriptions()->count() }}</strong> prescription(s) issued</p>
          </div>
          @break

        @case('hospital')
          <table>
            <tbody>
              <tr><th>Registration No.</th><td>{{ $profile->registration_number }}</td></tr>
              <tr><th>Address</th><td>{{ $profile->fullAddress() }}</td></tr>
            </tbody>
          </table>
          <div class="grid grid-2" style="margin-top:1rem;">
            <p><strong>{{ $profile->activeDoctors()->count() }}</strong> assigned doctor(s)</p>
            <p><strong>{{ $profile->facilities()->count() }}</strong> priced facilit(y/ies)</p>
            <p><strong>{{ $profile->appointments()->count() }}</strong> onsite appointment(s)</p>
            <p><strong>{{ $profile->facilityBookings()->count() }}</strong> facility booking(s)</p>
          </div>
          @break

        @case('pharmacy')
          <table>
            <tbody>
              <tr><th>ETIN</th><td>{{ $profile->etin_number }}</td></tr>
              <tr><th>Address</th><td>{{ $profile->address ?? '—' }}</td></tr>
            </tbody>
          </table>
          <div class="grid grid-2" style="margin-top:1rem;">
            <p><strong>{{ $profile->medicineStock()->count() }}</strong> stocked batch(es)</p>
            <p><strong>{{ $profile->orders()->count() }}</strong> order(s) received</p>
          </div>
          @break

        @case('delivery')
          <table>
            <tbody>
              <tr><th>Age</th><td>{{ $profile->age }}</td></tr>
              <tr><th>Gender</th><td>{{ ucfirst($profile->gender) }}</td></tr>
              <tr><th>Blood group</th><td>{{ $profile->blood_group }}</td></tr>
            </tbody>
          </table>
          <div class="grid grid-2" style="margin-top:1rem;">
            <p><strong>{{ $profile->deliveries()->count() }}</strong> deliver(y/ies)</p>
          </div>
          @break

        @case('admin')
          <p class="muted">No additional profile fields for an admin account.</p>
          @break
      @endswitch
    @endif
  </div>
@endif

<div class="card">
  <h2>All users @if ($name) matching "{{ $name }}" @endif ({{ $accounts->count() }})</h2>
  @if ($accounts->isEmpty())
    <p class="muted">No users match that name.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>u_id</th>
          <th>Name</th>
          <th>Role</th>
          <th>Email</th>
          <th>Verified</th>
          <th>Active</th>
          <th>Joined</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($accounts as $acc)
          <tr>
            <td><a href="{{ route('admin.users', ['u_id' => $acc->account_id, 'name' => $name]) }}">{{ $acc->uidTag() }}</a></td>
            <td>{{ $acc->displayName() }}</td>
            <td><span class="badge">{{ ucfirst($acc->role) }}</span></td>
            <td>{{ $acc->email }}</td>
            <td><span class="badge {{ $acc->is_verified ? 'text-bg-success' : 'text-bg-warning' }}">{{ $acc->is_verified ? 'Yes' : 'No' }}</span></td>
            <td><span class="badge {{ $acc->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $acc->is_active ? 'Yes' : 'No' }}</span></td>
            <td>{{ $acc->created_at->format('D, M j Y') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
