{{-- Shared by the standalone prescription page (doctor/prescription-form.blade.php)
     and the consultation room (consultation/room.blade.php) — "issued from
     the consultation room" only actually means something if writing the
     prescription doesn't force the doctor to leave the call to do it.
     Expects: $appointment, $draft, $medicines, $draftMedicines, $allergies,
     $facilityDraft, $facilityCategories, $draftFacilityTypes. --}}
@if ($allergies->isNotEmpty())
  <div class="card" style="border-color:var(--bs-danger);">
    <h2 style="color:var(--bs-danger);">Known Allergies</h2>
    <ul style="margin:0;padding-left:1.2rem;">
      @foreach ($allergies as $allergy)
        <li>{{ $allergy->allergen }}@if($allergy->reaction) <span class="muted">({{ $allergy->reaction }})</span>@endif</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <h2>Medicines added so far</h2>
  @if (empty($draft))
    <p class="muted">No medicines added yet — use the form below.</p>
  @else
    <table>
      <thead><tr><th>Medicine</th><th>For Illness</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Notes</th><th></th></tr></thead>
      <tbody>
        @foreach ($draft as $index => $item)
          <tr>
            <td>{{ $draftMedicines[$item['medicine_master_id']]->generic_name ?? '—' }} <span class="muted">#m{{ $item['medicine_master_id'] }}</span></td>
            <td class="muted">{{ $item['for_illness'] ?? '—' }}</td>
            <td class="muted">{{ $item['dosage'] ?? '—' }}</td>
            <td class="muted">{{ $item['frequency'] ?? '—' }}</td>
            <td class="muted">{{ $item['duration_days'] ?? '—' }}</td>
            <td class="muted">{{ $item['notes'] ?? '—' }}</td>
            <td>
              <form method="POST" action="{{ route('doctor.appointments.prescription.remove-item', $appointment) }}" data-confirm="Remove this medicine from the draft?">
                @csrf
                <input type="hidden" name="index" value="{{ $index }}">
                <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.7rem;">Remove</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="card" style="max-width:560px;">
  <h2>Add a medicine</h2>
  <form method="POST" action="{{ route('doctor.appointments.prescription.add-item', $appointment) }}" data-confirm="Add this medicine to the prescription?">
    @csrf
    <div class="field">
      <label for="medicine_master_id">Medicine</label>
      <select id="medicine_master_id" name="medicine_master_id" required>
        <option value="">Select a medicine...</option>
        @foreach ($medicines as $medicine)
          <option value="{{ $medicine->medicine_master_id }}">#m{{ $medicine->medicine_master_id }} &middot; {{ $medicine->generic_name }}@if($medicine->brand_name) ({{ $medicine->brand_name }})@endif</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label for="for_illness">For illness / condition</label>
      <input type="text" id="for_illness" name="for_illness" maxlength="150" placeholder="e.g. Fever, Throat infection">
    </div>
    <div class="grid grid-2">
      <div class="field">
        <label for="dosage">Dosage</label>
        <input type="text" id="dosage" name="dosage" maxlength="100" placeholder="e.g. 1 tablet">
      </div>
      <div class="field">
        <label for="frequency">Frequency</label>
        <input type="text" id="frequency" name="frequency" maxlength="100" placeholder="e.g. Twice daily">
      </div>
    </div>
    <div class="field">
      <label for="duration_days">Duration (days)</label>
      <input type="number" id="duration_days" name="duration_days" min="1" max="365" placeholder="e.g. 7">
    </div>
    <div class="field">
      <label for="notes">Notes</label>
      <input type="text" id="notes" name="notes" maxlength="255" placeholder="e.g. Take after meals">
    </div>
    <button type="submit" class="btn">Add Medicine</button>
  </form>
</div>

<div class="card">
  <h2>Tests/operations added so far</h2>
  @if (empty($facilityDraft))
    <p class="muted">No tests or operations added yet — use the form below.</p>
  @else
    <table>
      <thead><tr><th>Test/Operation</th><th>Notes</th><th></th></tr></thead>
      <tbody>
        @foreach ($facilityDraft as $index => $item)
          <tr>
            <td>{{ $draftFacilityTypes[$item['facility_type_id']]->name ?? '—' }}</td>
            <td class="muted">{{ $item['notes'] ?? '—' }}</td>
            <td>
              <form method="POST" action="{{ route('doctor.appointments.prescription.remove-facility-item', $appointment) }}" data-confirm="Remove this test/operation from the draft?">
                @csrf
                <input type="hidden" name="index" value="{{ $index }}">
                <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.7rem;">Remove</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="card" style="max-width:560px;">
  <h2>Add a test or operation</h2>
  <p class="muted">A patient can compare hospital prices and book any of these themselves, the same way they order prescribed medicine.</p>
  <form method="POST" action="{{ route('doctor.appointments.prescription.add-facility-item', $appointment) }}" data-confirm="Add this test/operation to the prescription?">
    @csrf
    <div class="field">
      <label for="facility_type_id">Test / Operation</label>
      <select id="facility_type_id" name="facility_type_id" required>
        <option value="">Select...</option>
        @foreach ($facilityCategories as $category)
          <optgroup label="{{ $category->category_name }}">
            @foreach ($category->facilityTypes as $type)
              <option value="{{ $type->facility_type_id }}">{{ $type->name }}</option>
            @endforeach
          </optgroup>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label for="facility_notes">Notes</label>
      <input type="text" id="facility_notes" name="notes" maxlength="255" placeholder="e.g. Fasting required before test">
    </div>
    <button type="submit" class="btn">Add Test/Operation</button>
  </form>
</div>

<div class="card" style="max-width:560px;">
  <h2>Issue prescription</h2>
  <form method="POST" action="{{ route('doctor.appointments.prescription.store', $appointment) }}" data-confirm="Issue this prescription? It can't be edited afterward.">
    @csrf
    <div class="field">
      <label for="diagnosis_notes">Diagnosis notes (optional)</label>
      <textarea id="diagnosis_notes" name="diagnosis_notes" rows="3"></textarea>
      <button type="button" class="voice-dictate-btn btn btn-secondary" data-target="diagnosis_notes" style="margin-top:0.4rem;padding:0.3rem 0.7rem;">🎤 Dictate</button>
      <p class="muted" style="font-size:0.8rem;margin-top:0.3rem;">Speaks into text as you talk — only works in Chrome/Edge. Review the text before issuing; it's not always accurate.</p>
    </div>
    <button type="submit" class="btn btn-block">Issue Prescription</button>
  </form>
</div>

@push('scripts')
  <script src="{{ asset('js/voice-dictation.js') }}?v={{ filemtime(public_path('js/voice-dictation.js')) }}"></script>
@endpush
