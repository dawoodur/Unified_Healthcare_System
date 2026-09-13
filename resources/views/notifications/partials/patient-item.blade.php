@php
  $type = $notification->type;

  $notificationMeta = match ($type) {
    'appointment_reminder' => [
      'icon' => 'bi-calendar2-check',
      'label' => __('patient.notifications.type.appointment'),
      'class' => 'appointment',
    ],
    'waitlist_slot_open' => [
      'icon' => 'bi-calendar-plus',
      'label' => __('patient.notifications.type.appointment'),
      'class' => 'appointment',
    ],
    'medicine_reminder' => [
      'icon' => 'bi-capsule-pill',
      'label' => __('patient.notifications.type.medicine'),
      'class' => 'medicine',
    ],
    'refill_reminder' => [
      'icon' => 'bi-prescription2',
      'label' => __('patient.notifications.type.medicine'),
      'class' => 'medicine',
    ],
    'report_resolved' => [
      'icon' => 'bi-check2-circle',
      'label' => __('patient.notifications.type.report'),
      'class' => 'report',
    ],
    default => [
      'icon' => 'bi-bell',
      'label' => __('patient.notifications.type.update'),
      'class' => 'general',
    ],
  };
@endphp

<article class="patient-notification-item {{ $isNew ? 'patient-notification-item-new' : '' }}">
  <span class="patient-notification-type-icon patient-notification-type-{{ $notificationMeta['class'] }}" aria-hidden="true">
    <i class="bi {{ $notificationMeta['icon'] }}"></i>
  </span>

  <div class="patient-notification-copy">
    <div class="patient-notification-meta">
      <span>{{ $notificationMeta['label'] }}</span>
      @if ($isNew)
        <em>{{ __('patient.notifications.new_badge') }}</em>
      @endif
    </div>

    <p>{{ $notification->message }}</p>

    <small>
      <i class="bi bi-clock" aria-hidden="true"></i>
      {{ $notification->created_at->format('D, M j Y · g:i A') }}
    </small>
  </div>
</article>
