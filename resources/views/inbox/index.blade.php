@extends('layouts.app')
@section('title', 'Inbox')
@section('content')
<div class="card">
  <h1>Inbox</h1>
  <p class="muted">Direct messages with hospitals, pharmacies, delivery agents, patients, and doctors — separate from a specific appointment's consultation chat.</p>
  <p><a href="{{ route('inbox.create') }}" class="btn">+ New conversation</a></p>
</div>

<div class="card">
  @if ($conversations->isEmpty())
    <p class="muted">No conversations yet — start one above.</p>
  @else
    <table>
      <thead><tr><th></th><th>With</th><th>Last message</th><th></th></tr></thead>
      <tbody>
        @foreach ($conversations as $c)
          <tr>
            <td>
              @if ($c->unread_count > 0)
                <span class="badge text-bg-danger">{{ $c->unread_count }} new</span>
              @endif
            </td>
            <td>
              {{ $c->other->displayName() }}
              <span class="badge">{{ ucfirst($c->other->role) }}</span>
              <span class="muted">{{ $c->other->uidTag() }}</span>
            </td>
            <td class="muted">
              @if ($c->last_message)
                {{ \Illuminate\Support\Str::limit($c->last_message->message_text, 60) }}
              @else
                <em>No messages yet</em>
              @endif
            </td>
            <td><a href="{{ route('inbox.show', $c) }}" class="btn btn-secondary" style="padding:0.3rem 0.7rem;">Open</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
