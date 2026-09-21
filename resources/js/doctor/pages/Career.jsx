import React, { useEffect, useMemo, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function DoctorCareerFooter() {
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

function DoctorCareerStat({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-career-stat is-${tone}`}>
      <span className="doctor-career-stat-icon">
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

function careerStatusLabel(status) {
  return String(status || 'pending')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

export default function Career() {
  const [data, setData] = useState(null);
  const [search, setSearch] = useState('');
  const [busyHospitalId, setBusyHospitalId] = useState(null);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  function load() {
    setError(null);

    return client
      .get('/career')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load hospital opportunities right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  const hospitals = useMemo(() => {
    if (!data) return [];
    const query = search.trim().toLowerCase();
    if (!query) return data.hospitals || [];

    return (data.hospitals || []).filter((hospital) =>
      [
        hospital.hospital_name,
        hospital.city,
        hospital.address,
        hospital.registration_number,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
        .includes(query)
    );
  }, [data, search]);

  function apply(hospitalId) {
    if (busyHospitalId) return;

    const run = () => {
      setBusyHospitalId(Number(hospitalId));
      setSuccess(null);
      setError(null);

      client
        .post(`/career/hospitals/${hospitalId}/apply`)
        .then((res) => {
          setSuccess(res.data.message || 'Application submitted.');
          return load();
        })
        .catch((err) => {
          setError(err.response?.data?.message || 'Could not submit this application.');
        })
        .finally(() => setBusyHospitalId(null));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal('Apply for a chamber at this hospital?', run);
    } else {
      run();
    }
  }

  function withdraw(applicationId, hospitalId) {
    if (busyHospitalId) return;

    const run = () => {
      setBusyHospitalId(Number(hospitalId));
      setSuccess(null);
      setError(null);

      client
        .delete(`/career/applications/${applicationId}`)
        .then((res) => {
          setSuccess(res.data.message || 'Application withdrawn.');
          return load();
        })
        .catch((err) => {
          setError(err.response?.data?.message || 'Could not withdraw this application.');
        })
        .finally(() => setBusyHospitalId(null));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal('Withdraw this chamber application?', run);
    } else {
      run();
    }
  }

  if (error && !data) {
    return (
      <div className="doctor-page doctor-career-page">
        <QuickNav />
        <div className="doctor-career-state doctor-card" role="alert">
          <span><i className="bi bi-briefcase" aria-hidden="true"></i></span>
          <div>
            <strong>Career opportunities unavailable</strong>
            <p>{error}</p>
            <button type="button" className="btn" onClick={load}>Try again</button>
          </div>
        </div>
        <DoctorCareerFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-career-page">
        <QuickNav />
        <div className="doctor-career-state doctor-card">
          <span><i className="bi bi-briefcase" aria-hidden="true"></i></span>
          <div>
            <strong>Loading career opportunities</strong>
            <p>Preparing available hospitals…</p>
          </div>
        </div>
        <DoctorCareerFooter />
      </div>
    );
  }

  const stats = data.stats || {};

  return (
    <div className="doctor-page doctor-career-page">
      <QuickNav />

      <section className="doctor-career-hero">
        <div>
          <span className="doctor-career-eyebrow">DOCTOR CAREER</span>
          <h1>Career & Chambers</h1>
          <p>
            Browse registered hospitals, apply for chamber opportunities, and
            track the status of your hospital applications.
          </p>
        </div>

        <div className="doctor-career-hero-note">
          <span><i className="bi bi-building-check" aria-hidden="true"></i></span>
          <div>
            <strong>Hospital approval required</strong>
            <small>Submitting an application does not automatically assign a chamber.</small>
          </div>
        </div>
      </section>

      <section className="doctor-career-stats" aria-label="Career summary">
        <DoctorCareerStat
          icon="bi-hospital"
          value={stats.active_hospitals || 0}
          label="Active hospitals"
          hint="Current chamber assignments"
          tone="teal"
        />
        <DoctorCareerStat
          icon="bi-hourglass-split"
          value={stats.pending_applications || 0}
          label="Pending"
          hint="Applications awaiting review"
          tone="amber"
        />
        <DoctorCareerStat
          icon="bi-buildings"
          value={stats.available_hospitals || 0}
          label="Available hospitals"
          hint="Not currently assigned"
          tone="blue"
        />
        <DoctorCareerStat
          icon="bi-file-earmark-check"
          value={stats.total_applications || 0}
          label="Applications"
          hint="Your application history"
          tone="violet"
        />
      </section>

      {success && (
        <div className="doctor-career-flash is-success">
          <i className="bi bi-check-circle" aria-hidden="true"></i>
          {success}
        </div>
      )}

      {error && (
        <div className="doctor-career-flash is-error">
          <i className="bi bi-exclamation-triangle" aria-hidden="true"></i>
          {error}
        </div>
      )}

      <section className="doctor-career-panel">
        <header>
          <div>
            <span><i className="bi bi-buildings" aria-hidden="true"></i></span>
            <div>
              <h2>Hospital opportunities</h2>
              <p>Apply to hospitals where you do not currently have an active assignment.</p>
            </div>
          </div>

          <label className="doctor-career-search">
            <i className="bi bi-search" aria-hidden="true"></i>
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search hospital, city, address…"
              aria-label="Search hospitals"
            />
          </label>
        </header>

        {hospitals.length === 0 ? (
          <div className="doctor-career-empty">
            <i className="bi bi-building-slash" aria-hidden="true"></i>
            <strong>No hospitals found</strong>
            <span>Try a different search term.</span>
          </div>
        ) : (
          <div className="doctor-career-hospitals">
            {hospitals.map((hospital) => {
              const application = hospital.application;
              const isBusy = Number(busyHospitalId) === Number(hospital.hospital_id);

              return (
                <article key={hospital.hospital_id} className="doctor-career-hospital-card">
                  <div className="doctor-career-hospital-icon">
                    <i className="bi bi-hospital" aria-hidden="true"></i>
                  </div>

                  <div className="doctor-career-hospital-copy">
                    <div className="doctor-career-hospital-title">
                      <div>
                        <h3>{hospital.hospital_name}</h3>
                        <span>{hospital.registration_number}</span>
                      </div>

                      {hospital.is_assigned ? (
                        <b className="is-active">Active chamber</b>
                      ) : application ? (
                        <b className={`is-${application.status}`}>
                          {careerStatusLabel(application.status)}
                        </b>
                      ) : (
                        <b className="is-open">Open to apply</b>
                      )}
                    </div>

                    <div className="doctor-career-hospital-meta">
                      <span>
                        <i className="bi bi-geo-alt" aria-hidden="true"></i>
                        {hospital.full_address}
                      </span>
                      <span>
                        <i className="bi bi-people" aria-hidden="true"></i>
                        {hospital.active_doctors_count} active doctors
                      </span>
                    </div>
                  </div>

                  <div className="doctor-career-hospital-action">
                    {hospital.is_assigned ? (
                      <span className="doctor-career-assigned">
                        <i className="bi bi-check2-circle" aria-hidden="true"></i>
                        Assigned
                      </span>
                    ) : application?.status === 'pending' ? (
                      <button
                        type="button"
                        className="is-withdraw"
                        disabled={isBusy}
                        onClick={() => withdraw(application.application_id, hospital.hospital_id)}
                      >
                        <i className="bi bi-x-circle" aria-hidden="true"></i>
                        {isBusy ? 'Working…' : 'Withdraw'}
                      </button>
                    ) : application?.status === 'accepted' ? (
                      <span className="doctor-career-assigned">
                        <i className="bi bi-check2-circle" aria-hidden="true"></i>
                        Accepted
                      </span>
                    ) : (
                      <button
                        type="button"
                        className="is-apply"
                        disabled={isBusy}
                        onClick={() => apply(hospital.hospital_id)}
                      >
                        <i className="bi bi-send" aria-hidden="true"></i>
                        {isBusy ? 'Applying…' : application ? 'Apply again' : 'Apply for chamber'}
                      </button>
                    )}
                  </div>
                </article>
              );
            })}
          </div>
        )}
      </section>

      <DoctorCareerFooter />
    </div>
  );
}
