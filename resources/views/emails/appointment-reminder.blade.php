<p>Hello {{ $appointment->patient->full_name }},</p>
<p>This is a reminder that you have an appointment tomorrow:</p>
<ul>
  <li><strong>Doctor:</strong> Dr. {{ $appointment->doctor->full_name }}</li>
  <li><strong>Date:</strong> {{ $appointment->appointment_date->format('D, M j Y') }}</li>
  <li><strong>Time:</strong> {{ $appointment->timeRangeLabel() }}</li>
  <li><strong>Type:</strong> {{ ucfirst($appointment->appointment_type) }}</li>
  @if ($appointment->appointment_type === 'onsite' && $appointment->hospital)
    <li><strong>Location:</strong> {{ $appointment->hospital->hospital_name }}</li>
  @endif
  <li><strong>Serial number:</strong> #{{ $appointment->serial_number }}</li>
</ul>
@if ($appointment->appointment_type === 'online')
  <p>You'll be able to join the video call from your appointments page once it's time.</p>
@endif
