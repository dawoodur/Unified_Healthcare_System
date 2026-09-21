import React, { useEffect, useMemo, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function DoctorProfileFooter() {
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

function ProfileStat({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-profile-stat is-${tone}`}>
      <span className="doctor-profile-stat-icon">
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

function initials(name) {
  return String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('');
}

function apiError(error, fallback) {
  const errors = error.response?.data?.errors;
  if (errors) {
    const first = Object.values(errors).flat()[0];
    if (first) return first;
  }
  return error.response?.data?.message || fallback;
}

export default function Profile() {
  const [data, setData] = useState(null);
  const [form, setForm] = useState(null);
  const [error, setError] = useState(null);
  const [formErrors, setFormErrors] = useState({});
  const [saving, setSaving] = useState(false);
  const [photoBusy, setPhotoBusy] = useState(false);
  const [success, setSuccess] = useState(null);

  function load() {
    setError(null);
    return client
      .get('/profile')
      .then((res) => {
        setData(res.data);
        setForm({
          full_name: res.data.doctor.full_name || '',
          age: res.data.doctor.age || '',
          blood_group: res.data.doctor.blood_group || '',
          gender: res.data.doctor.gender || '',
          email: res.data.account.email || '',
          mobile: res.data.account.mobile || '',
          bio: res.data.doctor.bio || '',
          consultation_fee: res.data.doctor.consultation_fee ?? '',
          specialty_ids: res.data.doctor.specialty_ids || [],
        });
      })
      .catch(() => setError('Could not load your profile right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  const selectedSpecialties = useMemo(
    () => new Set((form?.specialty_ids || []).map(Number)),
    [form?.specialty_ids]
  );

  function toggleSpecialty(id) {
    const specialtyId = Number(id);
    setForm((current) => {
      const next = new Set((current.specialty_ids || []).map(Number));
      if (next.has(specialtyId)) next.delete(specialtyId);
      else next.add(specialtyId);

      return {
        ...current,
        specialty_ids: Array.from(next),
      };
    });
  }

  function saveProfile(event) {
    event.preventDefault();
    if (!form || saving) return;

    setSaving(true);
    setFormErrors({});
    setSuccess(null);

    client
      .put('/profile', form)
      .then((res) => {
        setSuccess(res.data.message || 'Profile updated successfully.');
        return load();
      })
      .catch((err) => {
        if (err.response?.status === 422) {
          setFormErrors(err.response.data.errors || {});
        } else {
          setError(apiError(err, 'Could not update your profile.'));
        }
      })
      .finally(() => setSaving(false));
  }

  function uploadPhoto(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file || photoBusy) return;

    const body = new FormData();
    body.append('photo', file);

    setPhotoBusy(true);
    setSuccess(null);
    setError(null);

    client
      .post('/profile/photo', body)
      .then((res) => {
        setSuccess(res.data.message || 'Profile photo updated.');
        return load();
      })
      .catch((err) => setError(apiError(err, 'Could not update your profile photo.')))
      .finally(() => setPhotoBusy(false));
  }

  function removePhoto() {
    if (photoBusy || !data?.account?.photo_url) return;

    const run = () => {
      setPhotoBusy(true);
      setSuccess(null);
      client
        .delete('/profile/photo')
        .then((res) => {
          setSuccess(res.data.message || 'Profile photo removed.');
          return load();
        })
        .catch((err) => setError(apiError(err, 'Could not remove your profile photo.')))
        .finally(() => setPhotoBusy(false));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal('Remove your current profile photo?', run);
    } else {
      run();
    }
  }

  if (error && !data) {
    return (
      <div className="doctor-page doctor-profile-page">
        <QuickNav />
        <div className="doctor-profile-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-circle" aria-hidden="true"></i></span>
          <div>
            <strong>Profile unavailable</strong>
            <p>{error}</p>
            <button type="button" className="btn" onClick={load}>Try again</button>
          </div>
        </div>
        <DoctorProfileFooter />
      </div>
    );
  }

  if (!data || !form) {
    return (
      <div className="doctor-page doctor-profile-page">
        <QuickNav />
        <div className="doctor-profile-state doctor-card">
          <span><i className="bi bi-person-badge" aria-hidden="true"></i></span>
          <div>
            <strong>Loading profile</strong>
            <p>Preparing your professional information…</p>
          </div>
        </div>
        <DoctorProfileFooter />
      </div>
    );
  }

  const verificationLabel = data.doctor.verification_status
    ? String(data.doctor.verification_status).replaceAll('_', ' ')
    : 'Pending';

  return (
    <div className="doctor-page doctor-profile-page">
      <QuickNav />

      <section className="doctor-profile-hero">
        <div>
          <span className="doctor-profile-eyebrow">DOCTOR PROFILE</span>
          <h1>My Profile</h1>
          <p>
            Keep your professional, contact, specialty, and consultation details
            accurate for patients and hospitals.
          </p>
        </div>
        <div className="doctor-profile-hero-badge">
          <i className="bi bi-shield-check" aria-hidden="true"></i>
          <div>
            <strong>{verificationLabel}</strong>
            <small>Verification status</small>
          </div>
        </div>
      </section>

      <section className="doctor-profile-stats" aria-label="Profile summary">
        <ProfileStat
          icon="bi-patch-check"
          value={verificationLabel}
          label="Verification"
          hint="Doctor account status"
          tone="blue"
        />
        <ProfileStat
          icon="bi-heart-pulse"
          value={data.doctor.specialty_ids.length}
          label="Specialties"
          hint="Areas shown to patients"
          tone="teal"
        />
        <ProfileStat
          icon="bi-cash-coin"
          value={`BDT ${Number(data.doctor.consultation_fee || 0).toLocaleString()}`}
          label="Consultation fee"
          hint="Current standard fee"
          tone="amber"
        />
        <ProfileStat
          icon="bi-hospital"
          value={data.stats.active_hospitals}
          label="Hospitals"
          hint="Active assignments"
          tone="violet"
        />
      </section>

      {success && (
        <div className="doctor-profile-flash is-success">
          <i className="bi bi-check-circle" aria-hidden="true"></i>
          {success}
        </div>
      )}

      {error && (
        <div className="doctor-profile-flash is-error">
          <i className="bi bi-exclamation-triangle" aria-hidden="true"></i>
          {error}
        </div>
      )}

      <section className="doctor-profile-layout">
        <aside className="doctor-profile-sidebar">
          <section className="doctor-profile-identity-card">
            <div className="doctor-profile-photo-wrap">
              {data.account.photo_url ? (
                <img src={data.account.photo_url} alt="" />
              ) : (
                <span>{initials(data.doctor.full_name) || 'DR'}</span>
              )}
            </div>

            <h2>{data.doctor.full_name}</h2>
            <p>{data.account.uid}</p>

            <div className="doctor-profile-specialty-tags">
              {data.doctor.specialties.map((specialty) => (
                <span key={specialty.specialty_id}>{specialty.specialty_name}</span>
              ))}
            </div>

            <label className="doctor-profile-photo-upload">
              <i className="bi bi-camera" aria-hidden="true"></i>
              {photoBusy ? 'Updating…' : 'Change photo'}
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={uploadPhoto}
                disabled={photoBusy}
                hidden
              />
            </label>

            {data.account.photo_url && (
              <button
                type="button"
                className="doctor-profile-photo-remove"
                onClick={removePhoto}
                disabled={photoBusy}
              >
                <i className="bi bi-trash3" aria-hidden="true"></i>
                Remove photo
              </button>
            )}
          </section>

          <section className="doctor-profile-account-card">
            <header>
              <span><i className="bi bi-person-vcard" aria-hidden="true"></i></span>
              <div>
                <strong>Account details</strong>
                <small>Login and professional account</small>
              </div>
            </header>
            <dl>
              <div><dt>Email</dt><dd>{data.account.email}</dd></div>
              <div><dt>Mobile</dt><dd>{data.account.mobile || '—'}</dd></div>
              <div><dt>Appointments</dt><dd>{data.stats.total_appointments}</dd></div>
            </dl>
          </section>
        </aside>

        <main className="doctor-profile-main">
          <form className="doctor-profile-form-card" onSubmit={saveProfile}>
            <header className="doctor-profile-section-header">
              <span className="is-blue">
                <i className="bi bi-person-lines-fill" aria-hidden="true"></i>
              </span>
              <div>
                <h2>Professional information</h2>
                <p>These details are used across your doctor profile and booking experience.</p>
              </div>
            </header>

            <div className="doctor-profile-form-section">
              <h3>Basic information</h3>
              <div className="doctor-profile-form-grid">
                <label className="is-wide">
                  <span>Full name</span>
                  <input
                    value={form.full_name}
                    onChange={(e) => setForm({ ...form, full_name: e.target.value })}
                    maxLength={150}
                    required
                  />
                  {formErrors.full_name && <small className="is-error">{formErrors.full_name[0]}</small>}
                </label>

                <label>
                  <span>Age</span>
                  <input
                    type="number"
                    min="21"
                    max="100"
                    value={form.age}
                    onChange={(e) => setForm({ ...form, age: e.target.value })}
                    required
                  />
                </label>

                <label>
                  <span>Gender</span>
                  <select
                    value={form.gender}
                    onChange={(e) => setForm({ ...form, gender: e.target.value })}
                    required
                  >
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select>
                </label>

                <label>
                  <span>Blood group</span>
                  <select
                    value={form.blood_group}
                    onChange={(e) => setForm({ ...form, blood_group: e.target.value })}
                    required
                  >
                    {['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'].map((group) => (
                      <option key={group} value={group}>{group}</option>
                    ))}
                  </select>
                </label>

                <label>
                  <span>Consultation fee (BDT)</span>
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.consultation_fee}
                    onChange={(e) => setForm({ ...form, consultation_fee: e.target.value })}
                  />
                </label>

                <label className="is-wide">
                  <span>Professional bio</span>
                  <textarea
                    rows={4}
                    value={form.bio}
                    onChange={(e) => setForm({ ...form, bio: e.target.value })}
                    placeholder="Write a short professional introduction for patients…"
                  />
                </label>
              </div>
            </div>

            <div className="doctor-profile-form-section">
              <h3>Contact information</h3>
              <div className="doctor-profile-form-grid">
                <label>
                  <span>Email</span>
                  <input
                    type="email"
                    value={form.email}
                    onChange={(e) => setForm({ ...form, email: e.target.value })}
                    required
                  />
                  {formErrors.email && <small className="is-error">{formErrors.email[0]}</small>}
                </label>

                <label>
                  <span>Mobile</span>
                  <input
                    value={form.mobile}
                    onChange={(e) => setForm({ ...form, mobile: e.target.value })}
                    required
                  />
                  {formErrors.mobile && <small className="is-error">{formErrors.mobile[0]}</small>}
                </label>
              </div>
            </div>

            <div className="doctor-profile-form-section">
              <h3>Specialties</h3>
              <div className="doctor-profile-specialty-grid">
                {data.specialties.map((specialty) => (
                  <label
                    key={specialty.specialty_id}
                    className={selectedSpecialties.has(Number(specialty.specialty_id)) ? 'is-selected' : ''}
                  >
                    <input
                      type="checkbox"
                      checked={selectedSpecialties.has(Number(specialty.specialty_id))}
                      onChange={() => toggleSpecialty(specialty.specialty_id)}
                    />
                    <span>
                      <i className="bi bi-heart-pulse" aria-hidden="true"></i>
                      {specialty.specialty_name}
                    </span>
                  </label>
                ))}
              </div>
              {formErrors.specialty_ids && (
                <small className="doctor-profile-specialty-error">{formErrors.specialty_ids[0]}</small>
              )}
            </div>

            <div className="doctor-profile-form-actions">
              <span>
                <i className="bi bi-info-circle" aria-hidden="true"></i>
                Certificate re-upload and password changes are intentionally separate from profile editing.
              </span>
              <button type="submit" disabled={saving}>
                <i className="bi bi-check2" aria-hidden="true"></i>
                {saving ? 'Saving…' : 'Save changes'}
              </button>
            </div>
          </form>
        </main>
      </section>

      <DoctorProfileFooter />
    </div>
  );
}
