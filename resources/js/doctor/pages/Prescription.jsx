import React, { useEffect, useMemo, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function appointmentIdFromPath() {
  const parts = window.location.pathname.split('/').filter(Boolean);
  const index = parts.lastIndexOf('appointments');
  return index >= 0 ? parts[index + 1] : null;
}

function DoctorPrescriptionFooter() {
  return (
    <footer className="patient-dashboard-footer">
      <div className="patient-dashboard-footer-brand">
        <span className="patient-dashboard-footer-dot" aria-hidden="true"></span>
        <div>
          <strong>Telemedicine Platform</strong>
          <small>Connected Healthcare</small>
        </div>
      </div>
      <p>Secure, connected care across your healthcare journey.</p>
    </footer>
  );
}

function PrescriptionStat({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-prescription-stat is-${tone}`}>
      <span className="doctor-prescription-stat-icon">
        <i className={`bi ${icon}`} aria-hidden="true"></i>
      </span>
      <div>
        <strong>{value}</strong>
        <span>{label}</span>
        <small>{hint}</small>
      </div>
    </article>
  );
}

function apiErrorMessage(error, fallback) {
  const errors = error.response?.data?.errors;
  if (errors) {
    const first = Object.values(errors).flat()[0];
    if (first) return first;
  }
  return error.response?.data?.message || fallback;
}

export default function Prescription() {
  const appointmentId = appointmentIdFromPath();
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [actionError, setActionError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [busy, setBusy] = useState(false);
  const [diagnosisNotes, setDiagnosisNotes] = useState('');
  const [medicineForm, setMedicineForm] = useState({
    medicine_master_id: '',
    for_illness: '',
    dosage: '',
    frequency: '',
    duration_days: '',
    notes: '',
  });
  const [facilityForm, setFacilityForm] = useState({
    facility_type_id: '',
    notes: '',
  });

  function load() {
    if (!appointmentId) {
      setError('Invalid appointment.');
      return Promise.resolve();
    }

    setError(null);
    return client
      .get(`/appointments/${appointmentId}/prescription`)
      .then((res) => {
        setData(res.data);
        if (res.data.mode === 'issued') {
          setDiagnosisNotes(res.data.prescription?.diagnosis_notes || '');
        }
      })
      .catch((err) => setError(apiErrorMessage(err, 'Could not load this prescription.')));
  }

  useEffect(() => {
    load();
  }, [appointmentId]);

  const draftMedicineCount = data?.draft?.medicines?.length || 0;
  const draftFacilityCount = data?.draft?.facilities?.length || 0;
  const issuedMedicineCount = data?.prescription?.medicines?.length || 0;
  const issuedFacilityCount = data?.prescription?.facilities?.length || 0;
  const allergyCount = data?.allergies?.length || 0;

  const selectedMedicine = useMemo(
    () => data?.medicines?.find(
      (medicine) => String(medicine.medicine_master_id) === String(medicineForm.medicine_master_id)
    ),
    [data?.medicines, medicineForm.medicine_master_id]
  );

  function addMedicine(event) {
    event.preventDefault();
    if (!medicineForm.medicine_master_id || busy) return;

    setBusy(true);
    setActionError(null);
    setSuccess(null);

    client
      .post(`/appointments/${appointmentId}/prescription/medicines`, {
        ...medicineForm,
        duration_days: medicineForm.duration_days || null,
      })
      .then(() => {
        setMedicineForm({
          medicine_master_id: '',
          for_illness: '',
          dosage: '',
          frequency: '',
          duration_days: '',
          notes: '',
        });
        setSuccess('Medicine added to the prescription draft.');
        return load();
      })
      .catch((err) => setActionError(apiErrorMessage(err, 'Could not add that medicine.')))
      .finally(() => setBusy(false));
  }

  function removeMedicine(index) {
    const run = () => {
      setBusy(true);
      setActionError(null);
      client
        .delete(`/appointments/${appointmentId}/prescription/medicines/${index}`)
        .then(() => load())
        .catch((err) => setActionError(apiErrorMessage(err, 'Could not remove that medicine.')))
        .finally(() => setBusy(false));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal('Remove this medicine from the prescription draft?', run);
    } else {
      run();
    }
  }

  function addFacility(event) {
    event.preventDefault();
    if (!facilityForm.facility_type_id || busy) return;

    setBusy(true);
    setActionError(null);
    setSuccess(null);

    client
      .post(`/appointments/${appointmentId}/prescription/facilities`, facilityForm)
      .then(() => {
        setFacilityForm({ facility_type_id: '', notes: '' });
        setSuccess('Test or procedure added to the prescription draft.');
        return load();
      })
      .catch((err) => setActionError(apiErrorMessage(err, 'Could not add that test or procedure.')))
      .finally(() => setBusy(false));
  }

  function removeFacility(index) {
    const run = () => {
      setBusy(true);
      setActionError(null);
      client
        .delete(`/appointments/${appointmentId}/prescription/facilities/${index}`)
        .then(() => load())
        .catch((err) => setActionError(apiErrorMessage(err, 'Could not remove that test or procedure.')))
        .finally(() => setBusy(false));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal('Remove this test or procedure from the prescription draft?', run);
    } else {
      run();
    }
  }

  function issuePrescription() {
    const run = () => {
      setBusy(true);
      setActionError(null);
      setSuccess(null);

      client
        .post(`/appointments/${appointmentId}/prescription/issue`, {
          diagnosis_notes: diagnosisNotes,
        })
        .then(() => {
          setSuccess('Prescription issued successfully. It is now read-only.');
          return load();
        })
        .catch((err) => setActionError(apiErrorMessage(err, 'Could not issue this prescription.')))
        .finally(() => setBusy(false));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal("Issue this prescription? It can't be edited afterward.", run);
    } else {
      run();
    }
  }

  if (error && !data) {
    return (
      <div className="doctor-page doctor-prescription-page">
        <QuickNav />
        <div className="doctor-prescription-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-circle" aria-hidden="true"></i></span>
          <div>
            <strong>Prescription unavailable</strong>
            <p>{error}</p>
            <a href={`${window.DOCTOR_APP_BASE}/appointments`}>Back to appointments</a>
          </div>
        </div>
        <DoctorPrescriptionFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-prescription-page">
        <QuickNav />
        <div className="doctor-prescription-state doctor-card">
          <span><i className="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
          <div>
            <strong>Loading prescription</strong>
            <p>Preparing the patient and visit details…</p>
          </div>
        </div>
        <DoctorPrescriptionFooter />
      </div>
    );
  }

  const isIssued = data.mode === 'issued';
  const medicineCount = isIssued ? issuedMedicineCount : draftMedicineCount;
  const facilityCount = isIssued ? issuedFacilityCount : draftFacilityCount;

  return (
    <div className="doctor-page doctor-prescription-page">
      <QuickNav />

      <section className="doctor-prescription-hero">
        <div>
          <span className="doctor-prescription-eyebrow">DOCTOR PRESCRIPTION</span>
          <h1>{isIssued ? 'Prescription Details' : 'Issue Prescription'}</h1>
          <p>
            {isIssued
              ? `Read-only prescription issued for ${data.appointment.patient.full_name}.`
              : `Build and review the prescription for ${data.appointment.patient.full_name} before issuing it.`}
          </p>
        </div>
        <div className="doctor-prescription-hero-actions">
          {isIssued && (
            <span className="doctor-prescription-issued-badge">
              <i className="bi bi-patch-check" aria-hidden="true"></i>
              Issued {data.prescription.issued_at_label}
            </span>
          )}
          <a className="doctor-prescription-back" href={`${window.DOCTOR_APP_BASE}/appointments`}>
            <i className="bi bi-arrow-left" aria-hidden="true"></i>
            Back to appointments
          </a>
        </div>
      </section>

      <section className="doctor-prescription-stats" aria-label="Prescription summary">
        <PrescriptionStat
          icon="bi-person-heart"
          value={data.appointment.patient.age ? `${data.appointment.patient.age}` : '—'}
          label="Patient age"
          hint={data.appointment.patient.blood_group ? `Blood group ${data.appointment.patient.blood_group}` : 'Blood group not recorded'}
          tone="blue"
        />
        <PrescriptionStat
          icon="bi-capsule-pill"
          value={medicineCount}
          label="Medicines"
          hint={isIssued ? 'Prescribed items' : 'Currently in draft'}
          tone="teal"
        />
        <PrescriptionStat
          icon="bi-clipboard2-pulse"
          value={facilityCount}
          label="Tests / procedures"
          hint={isIssued ? 'Recommended services' : 'Currently in draft'}
          tone="violet"
        />
        <PrescriptionStat
          icon="bi-exclamation-triangle"
          value={allergyCount}
          label="Known allergies"
          hint={allergyCount ? 'Review before prescribing' : 'None currently recorded'}
          tone="amber"
        />
      </section>

      {(actionError || success) && (
        <div className={`doctor-prescription-feedback ${actionError ? 'is-error' : 'is-success'}`}>
          <i className={`bi ${actionError ? 'bi-exclamation-circle' : 'bi-check2-circle'}`} aria-hidden="true"></i>
          {actionError || success}
        </div>
      )}

      <section className="doctor-prescription-patient-card">
        <div className="doctor-prescription-patient-icon">
          <i className="bi bi-person-vcard" aria-hidden="true"></i>
        </div>
        <div className="doctor-prescription-patient-copy">
          <span>Patient</span>
          <strong>{data.appointment.patient.full_name}</strong>
          <small>
            {data.appointment.patient.gender || 'Gender not recorded'}
            {data.appointment.patient.blood_group ? ` · ${data.appointment.patient.blood_group}` : ''}
          </small>
        </div>
        <div className="doctor-prescription-patient-meta">
          <span><i className="bi bi-calendar3" aria-hidden="true"></i>{data.appointment.date_label}</span>
          <span><i className="bi bi-clock" aria-hidden="true"></i>{data.appointment.time_range_label}</span>
          <span><i className={`bi ${data.appointment.appointment_type === 'online' ? 'bi-camera-video' : 'bi-hospital'}`} aria-hidden="true"></i>{data.appointment.appointment_type === 'online' ? 'Online consultation' : 'Onsite consultation'}</span>
        </div>
      </section>

      {allergyCount > 0 && (
        <section className="doctor-prescription-allergy-card">
          <span className="doctor-prescription-allergy-icon">
            <i className="bi bi-exclamation-triangle" aria-hidden="true"></i>
          </span>
          <div>
            <h2>Known allergies</h2>
            <p>Review these before adding medicines to the prescription.</p>
            <div className="doctor-prescription-allergy-list">
              {data.allergies.map((allergy) => (
                <span key={allergy.allergy_id || allergy.allergen}>
                  <strong>{allergy.allergen}</strong>
                  {allergy.reaction && <small>{allergy.reaction}</small>}
                </span>
              ))}
            </div>
          </div>
        </section>
      )}

      {isIssued ? (
        <div className="doctor-prescription-issued-layout">
          <section className="doctor-prescription-card is-blue">
            <header className="doctor-prescription-card-header">
              <div>
                <span className="doctor-prescription-card-icon is-blue"><i className="bi bi-capsule-pill" aria-hidden="true"></i></span>
                <div><h2>Prescribed medicines</h2><p>Medication instructions issued for this visit.</p></div>
              </div>
              <span className="doctor-prescription-count">{issuedMedicineCount}</span>
            </header>
            {issuedMedicineCount === 0 ? (
              <div className="doctor-prescription-empty">No medicines were prescribed.</div>
            ) : (
              <div className="doctor-prescription-issued-list">
                {data.prescription.medicines.map((item) => (
                  <article key={item.prescription_item_id}>
                    <span className="doctor-prescription-item-icon is-teal"><i className="bi bi-capsule" aria-hidden="true"></i></span>
                    <div>
                      <strong>{item.medicine_name}</strong>
                      <span>{[item.dosage, item.frequency, item.duration_label].filter(Boolean).join(' · ') || 'Instructions not specified'}</span>
                      {item.for_illness && <small>For: {item.for_illness}</small>}
                      {item.notes && <small>{item.notes}</small>}
                    </div>
                  </article>
                ))}
              </div>
            )}
          </section>

          <section className="doctor-prescription-card is-violet">
            <header className="doctor-prescription-card-header">
              <div>
                <span className="doctor-prescription-card-icon is-violet"><i className="bi bi-clipboard2-pulse" aria-hidden="true"></i></span>
                <div><h2>Tests & procedures</h2><p>Recommended diagnostic or hospital services.</p></div>
              </div>
              <span className="doctor-prescription-count">{issuedFacilityCount}</span>
            </header>
            {issuedFacilityCount === 0 ? (
              <div className="doctor-prescription-empty">No tests or procedures were recommended.</div>
            ) : (
              <div className="doctor-prescription-issued-list">
                {data.prescription.facilities.map((item) => (
                  <article key={item.prescription_facility_item_id}>
                    <span className="doctor-prescription-item-icon is-violet"><i className="bi bi-hospital" aria-hidden="true"></i></span>
                    <div>
                      <strong>{item.name}</strong>
                      <span>{item.category || 'Hospital service'}</span>
                      {item.notes && <small>{item.notes}</small>}
                    </div>
                  </article>
                ))}
              </div>
            )}
          </section>

          <section className="doctor-prescription-diagnosis-card">
            <span className="doctor-prescription-card-icon is-amber"><i className="bi bi-journal-medical" aria-hidden="true"></i></span>
            <div>
              <h2>Diagnosis notes</h2>
              <p>{data.prescription.diagnosis_notes || 'No diagnosis notes were added.'}</p>
            </div>
          </section>
        </div>
      ) : (
        <div className="doctor-prescription-builder">
          <main className="doctor-prescription-main">
            <section className="doctor-prescription-card is-blue">
              <header className="doctor-prescription-card-header">
                <div>
                  <span className="doctor-prescription-card-icon is-blue"><i className="bi bi-capsule-pill" aria-hidden="true"></i></span>
                  <div><h2>Medicines</h2><p>Add medication and dosing instructions one item at a time.</p></div>
                </div>
                <span className="doctor-prescription-count">{draftMedicineCount}</span>
              </header>

              <form className="doctor-prescription-form" onSubmit={addMedicine}>
                <div className="doctor-prescription-field is-full">
                  <label htmlFor="prescription_medicine">Medicine</label>
                  <select
                    id="prescription_medicine"
                    value={medicineForm.medicine_master_id}
                    onChange={(event) => setMedicineForm({ ...medicineForm, medicine_master_id: event.target.value })}
                    required
                  >
                    <option value="">Select a medicine…</option>
                    {data.medicines.map((medicine) => (
                      <option key={medicine.medicine_master_id} value={medicine.medicine_master_id}>
                        {medicine.generic_name}{medicine.brand_name ? ` (${medicine.brand_name})` : ''}{medicine.strength ? ` · ${medicine.strength}` : ''}
                      </option>
                    ))}
                  </select>
                  {selectedMedicine?.form && <small>{selectedMedicine.form}{selectedMedicine.strength ? ` · ${selectedMedicine.strength}` : ''}</small>}
                </div>

                <div className="doctor-prescription-field is-full">
                  <label htmlFor="prescription_condition">For illness / condition</label>
                  <input
                    id="prescription_condition"
                    type="text"
                    maxLength={150}
                    value={medicineForm.for_illness}
                    onChange={(event) => setMedicineForm({ ...medicineForm, for_illness: event.target.value })}
                    placeholder="e.g. Fever, throat infection"
                  />
                </div>

                <div className="doctor-prescription-field">
                  <label htmlFor="prescription_dosage">Dosage</label>
                  <input
                    id="prescription_dosage"
                    type="text"
                    maxLength={100}
                    value={medicineForm.dosage}
                    onChange={(event) => setMedicineForm({ ...medicineForm, dosage: event.target.value })}
                    placeholder="e.g. 1 tablet"
                  />
                </div>

                <div className="doctor-prescription-field">
                  <label htmlFor="prescription_frequency">Frequency</label>
                  <input
                    id="prescription_frequency"
                    type="text"
                    maxLength={100}
                    value={medicineForm.frequency}
                    onChange={(event) => setMedicineForm({ ...medicineForm, frequency: event.target.value })}
                    placeholder="e.g. Twice daily"
                  />
                </div>

                <div className="doctor-prescription-field">
                  <label htmlFor="prescription_duration">Duration (days)</label>
                  <input
                    id="prescription_duration"
                    type="number"
                    min="1"
                    max="365"
                    value={medicineForm.duration_days}
                    onChange={(event) => setMedicineForm({ ...medicineForm, duration_days: event.target.value })}
                    placeholder="e.g. 7"
                  />
                </div>

                <div className="doctor-prescription-field">
                  <label htmlFor="prescription_notes">Medicine notes</label>
                  <input
                    id="prescription_notes"
                    type="text"
                    maxLength={255}
                    value={medicineForm.notes}
                    onChange={(event) => setMedicineForm({ ...medicineForm, notes: event.target.value })}
                    placeholder="e.g. Take after meals"
                  />
                </div>

                <div className="doctor-prescription-form-action is-full">
                  <button type="submit" disabled={busy || !medicineForm.medicine_master_id}>
                    <i className="bi bi-plus-lg" aria-hidden="true"></i>
                    Add medicine
                  </button>
                </div>
              </form>

              <div className="doctor-prescription-draft-list">
                {draftMedicineCount === 0 ? (
                  <div className="doctor-prescription-empty">No medicines added yet.</div>
                ) : (
                  data.draft.medicines.map((item) => (
                    <article key={`medicine-${item.index}`}>
                      <span className="doctor-prescription-item-icon is-teal"><i className="bi bi-capsule" aria-hidden="true"></i></span>
                      <div>
                        <strong>{item.medicine_name}</strong>
                        <span>{[item.dosage, item.frequency, item.duration_label].filter(Boolean).join(' · ') || 'Instructions not specified'}</span>
                        {item.for_illness && <small>For: {item.for_illness}</small>}
                        {item.notes && <small>{item.notes}</small>}
                      </div>
                      <button type="button" onClick={() => removeMedicine(item.index)} disabled={busy}>
                        <i className="bi bi-trash3" aria-hidden="true"></i>Remove
                      </button>
                    </article>
                  ))
                )}
              </div>
            </section>

            <section className="doctor-prescription-card is-violet">
              <header className="doctor-prescription-card-header">
                <div>
                  <span className="doctor-prescription-card-icon is-violet"><i className="bi bi-clipboard2-pulse" aria-hidden="true"></i></span>
                  <div><h2>Tests & procedures</h2><p>Recommend diagnostic tests, procedures, or hospital services.</p></div>
                </div>
                <span className="doctor-prescription-count">{draftFacilityCount}</span>
              </header>

              <form className="doctor-prescription-form doctor-prescription-facility-form" onSubmit={addFacility}>
                <div className="doctor-prescription-field">
                  <label htmlFor="prescription_facility">Test / procedure</label>
                  <select
                    id="prescription_facility"
                    value={facilityForm.facility_type_id}
                    onChange={(event) => setFacilityForm({ ...facilityForm, facility_type_id: event.target.value })}
                    required
                  >
                    <option value="">Select a test or procedure…</option>
                    {data.facility_categories.map((category) => (
                      <optgroup key={category.category_id} label={category.category_name}>
                        {category.types.map((type) => (
                          <option key={type.facility_type_id} value={type.facility_type_id}>{type.name}</option>
                        ))}
                      </optgroup>
                    ))}
                  </select>
                </div>
                <div className="doctor-prescription-field">
                  <label htmlFor="prescription_facility_notes">Notes</label>
                  <input
                    id="prescription_facility_notes"
                    type="text"
                    maxLength={255}
                    value={facilityForm.notes}
                    onChange={(event) => setFacilityForm({ ...facilityForm, notes: event.target.value })}
                    placeholder="e.g. Fasting required"
                  />
                </div>
                <div className="doctor-prescription-form-action">
                  <button type="submit" disabled={busy || !facilityForm.facility_type_id}>
                    <i className="bi bi-plus-lg" aria-hidden="true"></i>Add
                  </button>
                </div>
              </form>

              <div className="doctor-prescription-draft-list">
                {draftFacilityCount === 0 ? (
                  <div className="doctor-prescription-empty">No tests or procedures added yet.</div>
                ) : (
                  data.draft.facilities.map((item) => (
                    <article key={`facility-${item.index}`}>
                      <span className="doctor-prescription-item-icon is-violet"><i className="bi bi-hospital" aria-hidden="true"></i></span>
                      <div>
                        <strong>{item.name}</strong>
                        <span>{item.category || 'Hospital service'}</span>
                        {item.notes && <small>{item.notes}</small>}
                      </div>
                      <button type="button" onClick={() => removeFacility(item.index)} disabled={busy}>
                        <i className="bi bi-trash3" aria-hidden="true"></i>Remove
                      </button>
                    </article>
                  ))
                )}
              </div>
            </section>
          </main>

          <aside className="doctor-prescription-sidebar">
            <section className="doctor-prescription-issue-card">
              <header>
                <span className="doctor-prescription-card-icon is-amber"><i className="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
                <div><h2>Review & issue</h2><p>Finalize the visit prescription.</p></div>
              </header>

              <div className="doctor-prescription-review-counts">
                <span><i className="bi bi-capsule-pill" aria-hidden="true"></i><strong>{draftMedicineCount}</strong> medicines</span>
                <span><i className="bi bi-clipboard2-pulse" aria-hidden="true"></i><strong>{draftFacilityCount}</strong> tests / procedures</span>
              </div>

              <label className="doctor-prescription-diagnosis-field" htmlFor="prescription_diagnosis">
                <span>Diagnosis notes <small>Optional</small></span>
                <textarea
                  id="prescription_diagnosis"
                  rows={5}
                  maxLength={2000}
                  value={diagnosisNotes}
                  onChange={(event) => setDiagnosisNotes(event.target.value)}
                  placeholder="Summarize the diagnosis or clinical assessment…"
                />
                <small>{diagnosisNotes.length}/2000</small>
              </label>

              <div className="doctor-prescription-final-warning">
                <i className="bi bi-shield-exclamation" aria-hidden="true"></i>
                <p>After issuing, this prescription becomes read-only and is added to the patient’s medical history.</p>
              </div>

              <button
                type="button"
                className="doctor-prescription-issue-button"
                disabled={busy || (draftMedicineCount === 0 && draftFacilityCount === 0)}
                onClick={issuePrescription}
              >
                <i className="bi bi-patch-check" aria-hidden="true"></i>
                {busy ? 'Processing…' : 'Issue prescription'}
              </button>
            </section>

            <section className="doctor-prescription-safety-card">
              <span className="doctor-prescription-card-icon is-blue"><i className="bi bi-shield-check" aria-hidden="true"></i></span>
              <div>
                <h2>Safe prescribing checklist</h2>
                <ul>
                  <li>Review recorded allergies</li>
                  <li>Confirm medicine, dosage, and frequency</li>
                  <li>Review test or procedure notes</li>
                  <li>Check the draft before issuing</li>
                </ul>
              </div>
            </section>
          </aside>
        </div>
      )}

      <DoctorPrescriptionFooter />
    </div>
  );
}
