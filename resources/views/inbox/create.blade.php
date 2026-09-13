@extends('layouts.app')
@section('title', 'New Conversation')
@section('content')
<div class="card">
  <h1>New Conversation</h1>
  <p><a href="{{ route('inbox.index') }}">&larr; Back to inbox</a></p>

  @if (empty($allowedRoles))
    <p class="muted">Your role can't start a conversation with anyone right now.</p>
  @else
    <p class="muted">Pick who you'd like to message:</p>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">
      @foreach ($allowedRoles as $role)
        <a href="{{ route('inbox.create', ['role' => $role]) }}" class="btn {{ $selectedRole === $role ? '' : 'btn-secondary' }}">{{ ucfirst($role) }}</a>
      @endforeach
    </div>

    @if ($selectedRole)
      <form method="GET" action="{{ route('inbox.create') }}" style="display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="role" value="{{ $selectedRole }}">
        <div style="flex:1;min-width:200px;">
          <label for="search">Search by name</label>
          <input type="text" id="search" name="search" value="{{ $search }}" placeholder="e.g. Dhaka Central">
        </div>
        <button type="submit" class="btn">Search</button>
      </form>
    @endif
  @endif
</div>

@if ($selectedRole)
  <div class="card">
    <h2>{{ ucfirst($selectedRole) }} accounts</h2>
    @if ($results->isEmpty())
      <p class="muted">No matching {{ $selectedRole }} accounts found.</p>
    @else
      <table>
        <thead><tr><th>u_id</th><th>Name</th><th></th></tr></thead>
        <tbody>
          @foreach ($results as $account)
            <tr>
              <td class="muted">{{ $account->uidTag() }}</td>
              <td>{{ $account->displayName() }}</td>
              <td>
                <form method="POST" action="{{ route('inbox.start') }}" data-confirm="Start a conversation with {{ $account->displayName() }}?">
                  @csrf
                  <input type="hidden" name="account_id" value="{{ $account->account_id }}">
                  <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Message</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endif
@endsection
