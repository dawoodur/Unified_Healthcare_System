import React, { useEffect, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function DoctorLeaveFooter() {
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

function DoctorLeaveStatCard({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-leave-stat is-${tone}`}>
      <span className="doctor-leave-stat-icon">
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

export default function Leave() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [form, setForm] = useState({ leave_date: '', reason: '' });
  const [formErrors, setFormErrors] = useState({});
  const [saving, setSaving] = useState(false);
  const [removingId, setRemovingId] = useState(null);

  function load() {
    setError(null);
    return client
      .get('/leave')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your unavailable dates right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  function submitLeave(event) {
    event.preventDefault();
    setSaving(true);
    setFormErrors({});

    const save = () => {
      client
        .post('/leave', form)
        .then(() => {
          setForm({ leave_date: '', reason: '' });
          return load();
        })
        .catch((err) => {
          if (err.response?.status === 422) {
            setFormErrors(err.response.data.errors || {});
          } else {
            setError('Could not block that date.');
          }
        })
        .finally(() => setSaving(false));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal('Block this date from patient booking?', save);
    } else {
      save();
    }
  }

  function removeLeave(leaveId, dateLabel) {
    const remove = () => {
      setRemovingId(Number(leaveId));
      client
        .delete(`/leave/${leaveId}`)
        .then(() => load())
        .catch(() => setError('Could not unblock that date.'))
        .finally(() => setRemovingId(null));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal(`Make ${dateLabel} bookable again?`, remove);
    } else {
      remove();
    }
  }

  if (error) {
    return (
      <div className="doctor-page doctor-leave-page">
        <QuickNav />
        <div className="doctor-leave-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-circle" aria-hidden="true"></i></span>
          <div>
            <strong>Unavailable dates could not be loaded</strong>
            <p>{error}</p>
            <button type="button" className="btn" onClick={load}>Try again</button>
          </div>
        </div>
        <DoctorLeaveFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-leave-page">
        <QuickNav />
        <div className="doctor-leave-state doctor-card">
          <span><i className="bi bi-calendar2-x" aria-hidden="true"></i></span>
          <div>
            <strong>Loading unavailable dates</strong>
            <p>Preparing your blocked booking dates…</p>
          </div>
        </div>
        <DoctorLeaveFooter />
      </div>
    );
  }

  const leaveDates = data.leave_dates || [];
  const stats = data.stats || {};

  return (
    <div className="doctor-page doctor-leave-page">
      <QuickNav />

      <section className="doctor-leave-hero">
        <div>
          <span className="doctor-leave-eyebrow">AVAILABILITY CONTROL</span>
          <h1>Leave &amp; unavailable dates</h1>
          <p>
            Block specific dates from patient booking without changing your normal
            recurring weekly availability.
          </p>
        </div>

        <a
          href={`${window.DOCTOR_APP_BASE}/availability`}
          className="doctor-leave-back-action"
        >
          <i className="bi bi-arrow-left" aria-hidden="true"></i>
          Back to availability
        </a>
      </section>

      <section className="doctor-leave-stats" aria-label="Unavailable date summary">
        <DoctorLeaveStatCard
          icon="bi-calendar2-x"
          value={stats.total || 0}
          label="Blocked dates"
          hint="Upcoming unavailable dates"
          tone="blue"
        />
        <DoctorLeaveStatCard
          icon="bi-calendar-month"
          value={stats.this_month || 0}
          label="This month"
          hint="Blocked in the current month"
          tone="amber"
        />
        <DoctorLeaveStatCard
          icon="bi-card-text"
          value={stats.reasons_noted || 0}
          label="Reasons noted"
          hint="Dates with a private reason"
          tone="teal"
        />
        <DoctorLeaveStatCard
          icon="bi-clock-history"
          value={stats.next_leave_label || 'None'}
          label="Next unavailable"
          hint={stats.next_leave_hint || 'No upcoming blocked date'}
          tone="violet"
        />
      </section>

      <section className="doctor-leave-layout">
        <div className="doctor-leave-list-card">
          <header className="doctor-leave-card-header">
            <div>
              <span className="doctor-leave-card-icon is-blue">
                <i className="bi bi-calendar2-week" aria-hidden="true"></i>
              </span>
              <div>
                <h2>Upcoming unavailable dates</h2>
                <p>These dates are excluded from patient booking.</p>
              </div>
            </div>
            <span className="doctor-leave-count">
              {leaveDates.length} date{leaveDates.length === 1 ? '' : 's'}
            </span>
          </header>

          <div className="doctor-leave-list">
            {leaveDates.length === 0 ? (
              <div className="doctor-leave-empty">
                <span><i className="bi bi-calendar-check" aria-hidden="true"></i></span>
                <strong>No unavailable dates scheduled</strong>
                <p>Your normal weekly availability is currently bookable on every eligible date.</p>
              </div>
            ) : (
              leaveDates.map((leave) => (
                <article className="doctor-leave-row" key={leave.leave_id}>
                  <div className="doctor-leave-date-box">
                    <span>{leave.month_short}</span>
                    <strong>{leave.day_number}</strong>
                  </div>

                  <div className="doctor-leave-date-copy">
                    <strong>{leave.weekday}, {leave.date_label}</strong>
                    <span>
                      <i className="bi bi-calendar2-x" aria-hidden="true"></i>
                      Patient booking disabled for this date
                    </span>
                  </div>

                  <div className="doctor-leave-reason">
                    <small>Reason</small>
                    <span>{leave.reason || 'No reason added'}</span>
                  </div>

                  <button
                    type="button"
                    className="doctor-leave-unblock"
                    onClick={() => removeLeave(leave.leave_id, leave.date_label)}
                    disabled={removingId === Number(leave.leave_id)}
                  >
                    <i className="bi bi-unlock" aria-hidden="true"></i>
                    {removingId === Number(leave.leave_id) ? 'Unblocking…' : 'Unblock'}
                  </button>
                </article>
              ))
            )}
          </div>
        </div>

        <aside className="doctor-leave-sidebar">
          <section className="doctor-leave-form-card">
            <header className="doctor-leave-card-header">
              <div>
                <span className="doctor-leave-card-icon is-violet">
                  <i className="bi bi-calendar-plus" aria-hidden="true"></i>
                </span>
                <div>
                  <h2>Block a date</h2>
                  <p>Temporarily stop new patient bookings.</p>
                </div>
              </div>
            </header>

            <form className="doctor-leave-form" onSubmit={submitLeave}>
              <div className="doctor-leave-field">
                <label htmlFor="doctor-leave-date">Unavailable date</label>
                <input
                  id="doctor-leave-date"
                  type="date"
                  min={data.min_date}
                  value={form.leave_date}
                  onChange={(event) => setForm({ ...form, leave_date: event.target.value })}
                  required
                />
                {formErrors.leave_date && (
                  <p className="doctor-leave-field-error">{formErrors.leave_date[0]}</p>
                )}
              </div>

              <div className="doctor-leave-field">
                <label htmlFor="doctor-leave-reason">Reason <span>Optional</span></label>
                <textarea
                  id="doctor-leave-reason"
                  rows={3}
                  maxLength={255}
                  value={form.reason}
                  onChange={(event) => setForm({ ...form, reason: event.target.value })}
                  placeholder="Conference, personal leave, travel…"
                />
                <small>{form.reason.length}/255 · Visible only in your availability management.</small>
                {formErrors.reason && (
                  <p className="doctor-leave-field-error">{formErrors.reason[0]}</p>
                )}
              </div>

              <button
                type="submit"
                className="doctor-leave-save"
                disabled={saving || !form.leave_date}
              >
                <i className="bi bi-calendar2-x" aria-hidden="true"></i>
                {saving ? 'Blocking date…' : 'Block this date'}
              </button>
            </form>
          </section>

          <section className="doctor-leave-info-card">
            <span className="doctor-leave-info-icon">
              <i className="bi bi-shield-check" aria-hidden="true"></i>
            </span>
            <div>
              <h2>What happens when you block a date?</h2>
              <p>
                Your recurring weekly windows stay unchanged. The selected calendar
                date is simply excluded from new patient bookings.
              </p>
            </div>
            <div className="doctor-leave-info-points">
              <span><i className="bi bi-calendar2-x"></i> New bookings are disabled</span>
              <span><i className="bi bi-arrow-repeat"></i> Weekly schedule remains intact</span>
              <span><i className="bi bi-unlock"></i> You can unblock the date anytime</span>
            </div>
          </section>
        </aside>
      </section>

      <DoctorLeaveFooter />
    </div>
  );
}
