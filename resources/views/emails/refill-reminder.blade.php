<p>Hello {{ $item->prescription->patient->full_name }},</p>
<p>Based on the prescription Dr. {{ $item->prescription->doctor->full_name }} gave you, your course of <strong>{{ $item->medicine->generic_name }}</strong>@if($item->medicine->brand_name) ({{ $item->medicine->brand_name }})@endif is about to run out (it was prescribed for {{ $item->duration_days }} day(s)).</p>
<p>If you still need it, you can reorder from your prescriptions page.</p>
@if ($item->dosage || $item->frequency)
  <p class="muted">Original instructions: {{ $item->dosage }} {{ $item->frequency }}</p>
@endif
