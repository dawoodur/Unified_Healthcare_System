<p>Hello {{ $entry->patient->full_name }},</p>
<p>Good news — a spot just opened up in <strong>Dr. {{ $entry->doctor->full_name }}</strong>'s window on <strong>{{ $entry->requested_date->format('D, M j Y') }}</strong> ({{ \Illuminate\Support\Carbon::parse($entry->template->start_time)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($entry->template->end_time)->format('g:i A') }}), which you were waitlisted for.</p>
<p>Spots can fill up again quickly — book it now from your account if you still want it.</p>
