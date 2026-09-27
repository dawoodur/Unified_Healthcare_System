import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function DoctorIdentity({ doctor, sublabel }) {
  return (
    <>
      <span className="hospital-avatar">
        {doctor.full_name.split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('')}
      </span>
      <div>
        <strong>Dr. {doctor.full_name}</strong>
        <small>
          {doctor.specialties.length > 0 ? doctor.specialties.join(' · ') : 'General care'}
          {doctor.consultation_fee ? ` · ৳${doctor.consultation_fee}` : ''}
          {sublabel ? ` · ${sublabel}` : ''}
        </small>
      </div>
    </>
  );
}

function DoctorRow({ doctor, actionLabel, actionTone, onAction, busy }) {
  return (
    <div className="hospital-person-row">
      <DoctorIdentity doctor={doctor} />
      <button type="button" className={`hospital-btn is-${actionTone}`} onClick={onAction} disabled={busy}>
        {busy ? 'Saving…' : actionLabel}
      </button>
    </div>
  );
}

/** A pending application: one row, two competing actions — Accept and Reject. */
function ApplicationRow({ application, onAccept, onReject, busy }) {
  return (
    <div className="hospital-person-row">
      <DoctorIdentity doctor={application.doctor} sublabel={`Applied ${application.submitted_label}`} />
      <div className="hospital-row-actions">
        <button type="button" className="hospital-btn is-primary" onClick={onAccept} disabled={busy}>
          {busy === 'accept' ? 'Saving…' : 'Accept'}
        </button>
        <button type="button" className="hospital-btn is-danger" onClick={onReject} disabled={busy}>
          {busy === 'reject' ? 'Saving…' : 'Reject'}
        </button>
      </div>
    </div>
  );
}

export default function Doctors() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState('');
  const [busyId, setBusyId] = useState(null);
  const [busyApplication, setBusyApplication] = useState(null); // { id, action } — a row has two competing buttons
  const [notice, setNotice] = useState(null);

  function load(term = '') {
    setError(null);
    return client
      .get('/doctors', { params: term ? { search: term } : {} })
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your hospital’s doctors right now.'));
  }

  useEffect(() => { load(); }, []);

  function act(doctor, action) {
    setBusyId(doctor.doctor_id);
    setNotice(null);

    client
      .post(`/doctors/${doctor.doctor_id}/${action}`)
      .then((res) => {
        setNotice({ tone: 'ok', text: res.data.message });
        return load(search);
      })
      .catch((err) => setNotice({
        tone: 'error',
        text: err.response?.data?.message || 'That did not go through. Please try again.',
      }))
      .finally(() => setBusyId(null));
  }

  function actOnApplication(application, action) {
    setBusyApplication({ id: application.application_id, action });
    setNotice(null);

    client
      .post(`/career-applications/${application.application_id}/${action}`)
      .then((res) => {
        setNotice({ tone: 'ok', text: res.data.message });
        return load(search);
      })
      .catch((err) => setNotice({
        tone: 'error',
        text: err.response?.data?.message || 'That did not go through. Please try again.',
      }))
      .finally(() => setBusyApplication(null));
  }

  return (
    <PageShell
      pageClass="hospital-doctors-page"
      icon="bi-person-badge"
      title="Doctors"
      subtitle="Assign approved doctors to your hospital, or remove them from your active staff."
      loading={!data}
      error={error}
      onRetry={() => load(search)}
      loadingTitle="Loading doctors"
      loadingMessage="Fetching your assigned staff…"
    >
      {data && (
        <>
          {notice && (
            <p className={`hospital-notice is-${notice.tone}`}>
              <i className={`bi ${notice.tone === 'ok' ? 'bi-check2-circle' : 'bi-exclamation-triangle'}`} aria-hidden="true"></i>
              {notice.text}
            </p>
          )}

          {data.applications.length > 0 && (
            <section className="hospital-card">
              <header className="hospital-card-header">
                <div>
                  <span className="hospital-card-icon is-amber"><i className="bi bi-person-vcard" aria-hidden="true"></i></span>
                  <div>
                    <h2>Applications</h2>
                    <p>{data.applications.length} doctor{data.applications.length === 1 ? '' : 's'} asking to join your staff.</p>
                  </div>
                </div>
              </header>

              <div className="hospital-people-list">
                {data.applications.map((application) => (
                  <ApplicationRow
                    key={application.application_id}
                    application={application}
                    busy={busyApplication?.id === application.application_id ? busyApplication.action : null}
                    onAccept={() => actOnApplication(application, 'accept')}
                    onReject={() => actOnApplication(application, 'reject')}
                  />
                ))}
              </div>
            </section>
          )}

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-blue"><i className="bi bi-search" aria-hidden="true"></i></span>
                <div>
                  <h2>Add a doctor</h2>
                  <p>Only approved doctors who are not already on your staff can be added.</p>
                </div>
              </div>
            </header>

            <form
              className="hospital-search-form"
              onSubmit={(e) => { e.preventDefault(); load(search); }}
            >
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search approved doctors by name…"
                aria-label="Search doctors by name"
              />
              <button type="submit" className="hospital-btn is-primary">Search</button>
            </form>

            {data.search_term && (
              data.search_results.length === 0 ? (
                <EmptyState
                  icon="bi-person-x"
                  title="No matching doctors"
                  message={`Nobody approved and unassigned matches “${data.search_term}”.`}
                />
              ) : (
                <div className="hospital-people-list">
                  {data.search_results.map((doctor) => (
                    <DoctorRow
                      key={doctor.doctor_id}
                      doctor={doctor}
                      actionLabel="Assign"
                      actionTone="primary"
                      busy={busyId === doctor.doctor_id}
                      onAction={() => act(doctor, 'assign')}
                    />
                  ))}
                </div>
              )
            )}
          </section>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-indigo"><i className="bi bi-people" aria-hidden="true"></i></span>
                <div>
                  <h2>Assigned doctors</h2>
                  <p>{data.assigned.length} currently working at your hospital.</p>
                </div>
              </div>
            </header>

            {data.assigned.length === 0 ? (
              <EmptyState
                icon="bi-person-plus"
                title="No doctors assigned yet"
                message="Search above to add your first doctor."
              />
            ) : (
              <div className="hospital-people-list">
                {data.assigned.map((doctor) => (
                  <DoctorRow
                    key={doctor.doctor_id}
                    doctor={doctor}
                    actionLabel="Remove"
                    actionTone="danger"
                    busy={busyId === doctor.doctor_id}
                    onAction={() => act(doctor, 'revoke')}
                  />
                ))}
              </div>
            )}
          </section>
        </>
      )}
    </PageShell>
  );
}
