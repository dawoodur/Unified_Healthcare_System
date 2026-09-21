import React, { useEffect, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function DoctorReportFooter() {
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

function DoctorReportStat({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-report-stat is-${tone}`}>
      <span className="doctor-report-stat-icon">
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

function reportTone(status) {
  if (status === 'resolved') return 'green';
  if (status === 'in_review') return 'violet';
  if (status === 'dismissed') return 'slate';
  return 'amber';
}

export default function ReportIssue() {
  const [data, setData] = useState(null);
  const [subject, setSubject] = useState('');
  const [description, setDescription] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [formErrors, setFormErrors] = useState({});
  const [success, setSuccess] = useState(null);

  function load() {
    setError(null);

    return client
      .get('/report-issue')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your reports right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  function submitReport(event) {
    event.preventDefault();
    if (saving) return;

    setSaving(true);
    setFormErrors({});
    setSuccess(null);

    client
      .post('/report-issue', { subject, description })
      .then((res) => {
        setSubject('');
        setDescription('');
        setSuccess(res.data.message || 'Your report has been submitted.');
        return load();
      })
      .catch((err) => {
        if (err.response?.status === 422) {
          setFormErrors(err.response.data.errors || {});
        } else {
          setError(err.response?.data?.message || 'Could not submit your report.');
        }
      })
      .finally(() => setSaving(false));
  }

  if (error && !data) {
    return (
      <div className="doctor-page doctor-report-page">
        <QuickNav />
        <div className="doctor-report-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-square" aria-hidden="true"></i></span>
          <div>
            <strong>Reports unavailable</strong>
            <p>{error}</p>
            <button type="button" className="btn" onClick={load}>Try again</button>
          </div>
        </div>
        <DoctorReportFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-report-page">
        <QuickNav />
        <div className="doctor-report-state doctor-card">
          <span><i className="bi bi-exclamation-square" aria-hidden="true"></i></span>
          <div>
            <strong>Loading reports</strong>
            <p>Preparing your issue history…</p>
          </div>
        </div>
        <DoctorReportFooter />
      </div>
    );
  }

  const reports = data.reports || [];
  const stats = data.stats || {};

  return (
    <div className="doctor-page doctor-report-page">
      <QuickNav />

      <section className="doctor-report-hero">
        <div>
          <span className="doctor-report-eyebrow">REPORT AN ISSUE</span>
          <h1>Report an issue</h1>
          <p>
            Send a platform problem directly to the admin team and track the
            progress of reports you have already submitted.
          </p>
        </div>

        <div className="doctor-report-hero-note">
          <span><i className="bi bi-shield-check" aria-hidden="true"></i></span>
          <div>
            <strong>Private support channel</strong>
            <small>Only you and platform administrators can review these reports.</small>
          </div>
        </div>
      </section>

      <section className="doctor-report-stats" aria-label="Report summary">
        <DoctorReportStat
          icon="bi-exclamation-circle"
          value={stats.open || 0}
          label="Open"
          hint="Waiting for review"
          tone="amber"
        />
        <DoctorReportStat
          icon="bi-search"
          value={stats.in_review || 0}
          label="In review"
          hint="Admin is checking"
          tone="violet"
        />
        <DoctorReportStat
          icon="bi-check2-circle"
          value={stats.resolved || 0}
          label="Resolved"
          hint="Completed reports"
          tone="green"
        />
        <DoctorReportStat
          icon="bi-list-check"
          value={stats.total || 0}
          label="Total reports"
          hint="Your complete history"
          tone="blue"
        />
      </section>

      {success && (
        <div className="doctor-report-flash is-success">
          <i className="bi bi-check-circle" aria-hidden="true"></i>
          {success}
        </div>
      )}

      {error && (
        <div className="doctor-report-flash is-error">
          <i className="bi bi-exclamation-triangle" aria-hidden="true"></i>
          {error}
        </div>
      )}

      <section className="doctor-report-layout">
        <form className="doctor-report-form-card" onSubmit={submitReport}>
          <header>
            <span><i className="bi bi-pencil-square" aria-hidden="true"></i></span>
            <div>
              <h2>Submit a new report</h2>
              <p>Describe the issue clearly so the admin team can reproduce it.</p>
            </div>
          </header>

          <div className="doctor-report-form-body">
            <label>
              <span>Subject</span>
              <input
                value={subject}
                onChange={(event) => setSubject(event.target.value)}
                maxLength={190}
                placeholder="e.g. Appointment page button is not responding"
                required
              />
              {formErrors.subject && <small className="is-error">{formErrors.subject[0]}</small>}
            </label>

            <label>
              <span>Description</span>
              <textarea
                value={description}
                onChange={(event) => setDescription(event.target.value)}
                maxLength={2000}
                rows={7}
                placeholder="Explain what happened, what you expected, and any steps that reproduce the issue…"
                required
              />
              <small className={formErrors.description ? 'is-error' : ''}>
                {formErrors.description?.[0] || `${description.length}/2000`}
              </small>
            </label>

            <div className="doctor-report-guidance">
              <i className="bi bi-lightbulb" aria-hidden="true"></i>
              <span>
                Do not include passwords, OTP codes, or unnecessary patient-sensitive information.
              </span>
            </div>

            <button type="submit" disabled={saving}>
              <i className="bi bi-send" aria-hidden="true"></i>
              {saving ? 'Submitting…' : 'Submit report'}
            </button>
          </div>
        </form>

        <section className="doctor-report-history-card">
          <header>
            <div>
              <span><i className="bi bi-clock-history" aria-hidden="true"></i></span>
              <div>
                <h2>Your report history</h2>
                <p>Admin responses appear here when available.</p>
              </div>
            </div>
            <b>{reports.length}</b>
          </header>

          {reports.length === 0 ? (
            <div className="doctor-report-empty">
              <i className="bi bi-inbox" aria-hidden="true"></i>
              <strong>No reports submitted</strong>
              <span>Your reports will appear here after submission.</span>
            </div>
          ) : (
            <div className="doctor-report-history">
              {reports.map((report) => (
                <article key={report.report_id}>
                  <div className="doctor-report-history-top">
                    <div>
                      <strong>{report.subject}</strong>
                      <small>{report.created_label}</small>
                    </div>
                    <span className={`is-${reportTone(report.status)}`}>
                      {report.status_label}
                    </span>
                  </div>

                  <p>{report.description}</p>

                  {report.admin_response && (
                    <div className="doctor-report-admin-response">
                      <i className="bi bi-reply" aria-hidden="true"></i>
                      <div>
                        <small>Admin response</small>
                        <p>{report.admin_response}</p>
                      </div>
                    </div>
                  )}
                </article>
              ))}
            </div>
          )}
        </section>
      </section>

      <DoctorReportFooter />
    </div>
  );
}
