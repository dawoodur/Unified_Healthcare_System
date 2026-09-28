@extends('layouts.app')
@section('title', 'Delivery Dashboard')
@section('content')
<div class="card">
  <h1>{{ __('dashboard.delivery.welcome', ['name' => $agent->full_name]) }}</h1>
  <p class="muted">{{ auth()->user()->uidTag() }} &middot; {{ __('dashboard.delivery.age') }} {{ $agent->age }} &middot; {{ __('dashboard.' . $agent->gender) }} &middot; {{ __('dashboard.delivery.blood_group') }} {{ $agent->blood_group }}</p>
</div>
<div class="grid grid-2">
  <div class="card dashboard-card">
    <h2><i class="bi bi-truck card-icon"></i>{{ __('dashboard.delivery.available_title') }}</h2>
    <p class="muted">{{ __('dashboard.delivery.available_desc') }}</p>
    <p><a href="{{ route('delivery.available') }}">{{ __('dashboard.delivery.see_available') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-box-seam card-icon"></i>{{ __('dashboard.delivery.deliveries_title') }}</h2>
    <p class="muted">{{ __('dashboard.delivery.deliveries_desc') }}</p>
    <p><a href="{{ route('delivery.my-deliveries') }}">{{ __('dashboard.delivery.my_deliveries') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-chat-dots card-icon"></i>{{ __('dashboard.delivery.inbox_title') }}</h2>
    <p class="muted">{{ __('dashboard.delivery.inbox_desc') }}</p>
    <p><a href="{{ route('inbox.index') }}">{{ __('dashboard.delivery.open_inbox') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-star card-icon"></i>{{ __('dashboard.delivery.reviews_title') }}</h2>
    <p class="muted">{{ __('dashboard.delivery.reviews_desc') }}</p>
    <p><a href="{{ route('delivery.reviews') }}">{{ __('dashboard.delivery.my_reviews') }}</a></p>
  </div>
</div>
@endsection
