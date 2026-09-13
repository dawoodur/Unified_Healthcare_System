@php
  $avatarName = $account?->displayName() ?? 'User';
  $avatarInitials = collect(preg_split('/\s+/', trim($avatarName)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
  $avatarPhoto = $account?->photoUrl();
@endphp
@if ($avatarPhoto)
  <img class="avatar-circle avatar-photo {{ $size ?? '' }}" src="{{ $avatarPhoto }}" alt="{{ $avatarName }}">
@else
  <span class="avatar-circle {{ $size ?? '' }}" role="img" aria-label="{{ $avatarName }}">{{ $avatarInitials ?: 'U' }}</span>
@endif
