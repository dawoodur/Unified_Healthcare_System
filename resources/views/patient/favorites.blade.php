@extends('layouts.app')
@section('title', 'My Favorite Doctors')
@section('content')
<div class="card">
  <h1>{{ __('patient.favorites.title') }}</h1>
  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  <p class="muted"><a href="{{ route('patient.doctors') }}">{{ __('patient.favorites.find_more') }}</a></p>
</div>

<div class="grid grid-2">
  @forelse ($doctors as $doctor)
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
          @include('partials.avatar', ['account' => $doctor->account, 'size' => 'avatar-circle-lg'])
          <h2 style="margin:0;"><a href="{{ route('patient.doctors.show', $doctor) }}">Dr. {{ $doctor->full_name }}</a> <span class="muted" style="font-weight:normal;">{{ $doctor->account?->uidTag() }}</span></h2>
        </div>
        <form method="POST" action="{{ route('patient.doctors.favorite', $doctor) }}" data-confirm="Remove Dr. {{ $doctor->full_name }} from favorites?">
          @csrf
          <button type="submit" class="btn btn-secondary" style="padding:0.2rem 0.5rem;" title="{{ __('patient.search.remove_favorite') }}">★</button>
        </form>
      </div>
      <p class="muted">{{ $doctor->specialties->pluck('specialty_name')->implode(', ') }}</p>
      <p>{{ __('patient.search.consultation_fee', ['amount' => number_format($doctor->consultation_fee, 2)]) }}</p>
      <p>
        @if ($doctor->reviews_count > 0)
          @include('partials.star-rating', ['rating' => $doctor->reviews_avg_rating, 'label' => number_format($doctor->reviews_avg_rating, 1) . " ({$doctor->reviews_count})"])
        @else
          <span class="muted">{{ __('patient.search.no_ratings') }}</span>
        @endif
      </p>
    </div>
  @empty
    <div class="card"><p class="muted">{{ __('patient.favorites.empty') }}</p></div>
  @endforelse
</div>
@endsection
