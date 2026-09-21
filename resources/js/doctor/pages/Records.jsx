import React, { useEffect, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function ext(path) {
  return `${window.DOCTOR_APP_BASE}${path}`;
}

function DoctorFooter() {
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

function initials(name) {
  return String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('') || 'PT';
}

function statusMeta(patient) {
  if (patient.access_group === 'active') {
    return {
      label: patient.access_label || 'Access approved',
      className: 'is-active',
      icon: 'bi-shield-check',
    };
  }

  if (patient.access_group === 'pending') {
    return {
      label: patient.access_label || 'Awaiting approval',
      className: 'is-pending',
      icon: 'bi-hourglass-split',
    };
  }

  if (patient.grant_status === 'denied') {
    return {
      label: patient.access_label || 'Access denied',
      className: 'is-denied',
      icon: 'bi-shield-x',
    };
  }

  if (patient.grant_status === 'expired') {
    return {
      label: patient.access_label || 'Access expired',
      className: 'is-expired',
      icon: 'bi-clock-history',
    };
  }

  return {
    label: 'Access not requested',
    className: 'is-needed',
    icon: 'bi-lock',
  };
}

function SummaryCard({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-records-stat is-${tone}`}>
      <span className="doctor-records-stat-icon">
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

export default function Records() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [message, setMessage] = useState(null);
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState('all');
  const [requestingPatientId, setRequestingPatientId] = useState(null);

  function load() {
    setError(null);
    return client
      .get('/records')
      .then((response) => setData(response.data))
      .catch(() => setError('Could not load patient records right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  function requestAccess(patient) {
    const doRequest = () => {
      setRequestingPatientId(patient.patient_id);
      setMessage(null);
      setError(null);

      client
        .post(`/records/${patient.patient_id}/request`)
        .then((response) => {
          setMessage(response.data?.message || `Access requested for ${patient.full_name}.`);
          return load();
        })
        .catch((requestError) => {
          const serverMessage = requestError.response?.data?.message;
          setError(serverMessage || `Could not request access to ${patient.full_name}'s records.`);
        })
        .finally(() => setRequestingPatientId(null));
    };

    const prompt = `Request access to ${patient.full_name}'s medical records? The patient will need to approve the request.`;

    if (window.showConfirmModal) {
      window.showConfirmModal(prompt, doRequest);
    } else if (window.confirm(prompt)) {
      doRequest();
    }
  }

  if (error && !data) {
    return (
      <div className="doctor-page doctor-records-page">
        <QuickNav />
        <div className="doctor-records-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-circle" aria-hidden="true"></i></span>
          <div>
            <strong>Patient records unavailable</strong>
            <p>{error}</p>
            <button type="button" className="btn" onClick={load}>Try again</button>
          </div>
        </div>
        <DoctorFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-records-page">
        <QuickNav />
        <div className="doctor-records-state doctor-card">
          <span><i className="bi bi-folder2-open" aria-hidden="true"></i></span>
          <div>
            <strong>Loading patient records</strong>
            <p>Preparing your patient access list…</p>
          </div>
        </div>
        <DoctorFooter />
      </div>
    );
  }

  const patients = data.patients || [];
  const stats = data.stats || {
    total_patients: patients.length,
    active_access: 0,
    awaiting_approval: 0,
    access_needed: 0,
  };

  const normalizedSearch = search.trim().toLowerCase();
  const visiblePatients = patients.filter((patient) => {
    const matchesFilter = filter === 'all' || patient.access_group === filter;
    const haystack = [
      patient.full_name,
      patient.patient_id,
      patient.blood_group,
      patient.gender,
      patient.latest_appointment_label,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    return matchesFilter && (!normalizedSearch || haystack.includes(normalizedSearch));
  });

  const filters = [
    { key: 'all', label: 'All patients', count: stats.total_patients },
    { key: 'active', label: 'Active access', count: stats.active_access },
    { key: 'pending', label: 'Awaiting approval', count: stats.awaiting_approval },
    { key: 'needed', label: 'Access needed', count: stats.access_needed },
  ];

  return (
    <div className="doctor-page doctor-records-page">
      <QuickNav />

      <section className="doctor-records-hero">
        <div>
          <span className="doctor-records-eyebrow">DOCTOR RECORDS</span>
          <h1>Patient Records</h1>
          <p>
            Review patients you have treated, request time-limited record access,
            and open approved medical histories securely.
          </p>
        </div>

        <div className="doctor-records-privacy-card">
          <span><i className="bi bi-shield-lock" aria-hidden="true"></i></span>
          <div>
            <strong>Patient-approved access</strong>
            <small>Records remain locked until the patient approves your request.</small>
          </div>
        </div>
      </section>

      <section className="doctor-records-stats" aria-label="Patient record access summary">
        <SummaryCard
          icon="bi-people"
          value={stats.total_patients}
          label="Patients seen"
          hint="With appointment history"
          tone="blue"
        />
        <SummaryCard
          icon="bi-shield-check"
          value={stats.active_access}
          label="Active access"
          hint="Records available now"
          tone="green"
        />
        <SummaryCard
          icon="bi-hourglass-split"
          value={stats.awaiting_approval}
          label="Awaiting approval"
          hint="Patient action required"
          tone="amber"
        />
        <SummaryCard
          icon="bi-lock"
          value={stats.access_needed}
          label="Access needed"
          hint="Request or renew access"
          tone="violet"
        />
      </section>

      {(message || error) && (
        <div className={`doctor-records-notice ${error ? 'is-error' : 'is-success'}`} role="status">
          <i className={`bi ${error ? 'bi-exclamation-circle' : 'bi-check-circle'}`} aria-hidden="true"></i>
          <span>{error || message}</span>
        </div>
      )}

      <section className="doctor-records-layout">
        <div className="doctor-records-list-card">
          <header className="doctor-records-list-header">
            <div className="doctor-records-tabs" role="tablist" aria-label="Record access filters">
              {filters.map((item) => (
                <button
                  key={item.key}
                  type="button"
                  className={filter === item.key ? 'is-active' : ''}
                  onClick={() => setFilter(item.key)}
                >
                  {item.label}
                  <span>{item.count}</span>
                </button>
              ))}
            </div>

            <label className="doctor-records-search">
              <i className="bi bi-search" aria-hidden="true"></i>
              <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search patient, blood group…"
                aria-label="Search patients"
              />
            </label>
          </header>

          <div className="doctor-records-table-head" aria-hidden="true">
            <span>Patient</span>
            <span>Clinical context</span>
            <span>Access status</span>
            <span>Action</span>
          </div>

          {visiblePatients.length === 0 ? (
            <div className="doctor-records-empty">
              <span><i className="bi bi-folder2-open" aria-hidden="true"></i></span>
              <strong>No patients found</strong>
              <p>Try another filter or search term.</p>
            </div>
          ) : (
            <div className="doctor-records-patient-list">
              {visiblePatients.map((patient) => {
                const status = statusMeta(patient);
                const isRequesting = requestingPatientId === patient.patient_id;

                return (
                  <article className="doctor-records-patient-row" key={patient.patient_id}>
                    <div className="doctor-records-patient-cell">
                      <span className="doctor-records-avatar">{initials(patient.full_name)}</span>
                      <div>
                        <strong>{patient.full_name}</strong>
                        <small>Patient #{patient.patient_id}</small>
                        <span>
                          {patient.age ? `${patient.age} yrs` : 'Age not on file'}
                          {' · '}
                          {patient.gender ? patient.gender.charAt(0).toUpperCase() + patient.gender.slice(1) : 'Gender not on file'}
                          {' · '}
                          {patient.blood_group || 'Blood group not on file'}
                        </span>
                      </div>
                    </div>

                    <div className="doctor-records-context-cell">
                      <strong>{patient.appointment_count} appointment{patient.appointment_count === 1 ? '' : 's'}</strong>
                      <span>Last visit: {patient.latest_appointment_label || 'Not available'}</span>
                      <small>
                        {patient.online_appointments} online · {patient.onsite_appointments} onsite
                      </small>
                    </div>

                    <div className="doctor-records-access-cell">
                      <span className={`doctor-records-access-badge ${status.className}`}>
                        <i className={`bi ${status.icon}`} aria-hidden="true"></i>
                        {status.label}
                      </span>
                      {patient.access_expires_at && patient.access_group === 'active' && (
                        <small>Expires {patient.access_expires_at}</small>
                      )}
                      {patient.access_group === 'pending' && (
                        <small>Waiting for patient confirmation</small>
                      )}
                    </div>

                    <div className="doctor-records-action-cell">
                      {patient.access_group === 'active' ? (
                        <a href={patient.view_url || ext(`/records/${patient.patient_id}`)} className="doctor-records-view-button">
                          <i className="bi bi-folder2-open" aria-hidden="true"></i>
                          View records
                        </a>
                      ) : patient.access_group === 'pending' ? (
                        <span className="doctor-records-waiting-action">
                          <i className="bi bi-hourglass-split" aria-hidden="true"></i>
                          Waiting
                        </span>
                      ) : (
                        <button
                          type="button"
                          className="doctor-records-request-button"
                          onClick={() => requestAccess(patient)}
                          disabled={isRequesting}
                        >
                          <i className="bi bi-key" aria-hidden="true"></i>
                          {isRequesting ? 'Requesting…' : patient.grant_status === 'expired' ? 'Renew access' : 'Request access'}
                        </button>
                      )}
                    </div>
                  </article>
                );
              })}
            </div>
          )}

          <footer className="doctor-records-list-footer">
            <span>
              Showing {visiblePatients.length} of {patients.length} patient{patients.length === 1 ? '' : 's'}
            </span>
          </footer>
        </div>

        <aside className="doctor-records-sidebar">
          <section className="doctor-records-guide-card">
            <span className="doctor-records-guide-icon">
              <i className="bi bi-shield-lock" aria-hidden="true"></i>
            </span>
            <h2>How record access works</h2>
            <p>
              Access is tied to a patient you have previously treated and remains
              unavailable until the patient explicitly approves the request.
            </p>

            <div className="doctor-records-guide-steps">
              <div>
                <span>1</span>
                <div><strong>Request access</strong><small>Choose a patient from your history.</small></div>
              </div>
              <div>
                <span>2</span>
                <div><strong>Patient approves</strong><small>An approval code is sent to the patient.</small></div>
              </div>
              <div>
                <span>3</span>
                <div><strong>Review records</strong><small>Access remains available until the grant expires.</small></div>
              </div>
            </div>
          </section>

          <section className="doctor-records-security-card">
            <header>
              <i className="bi bi-lock" aria-hidden="true"></i>
              <strong>Privacy safeguards</strong>
            </header>
            <ul>
              <li><i className="bi bi-check2-circle"></i> Only previous patients appear here</li>
              <li><i className="bi bi-check2-circle"></i> Approval is controlled by the patient</li>
              <li><i className="bi bi-check2-circle"></i> Approved access automatically expires</li>
            </ul>
          </section>
        </aside>
      </section>

      <DoctorFooter />
    </div>
  );
}
