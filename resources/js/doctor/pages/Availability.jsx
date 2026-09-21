import React, { useEffect, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function ext(path) {
  return `${window.DOCTOR_APP_BASE}${path}`;
}

const emptyForm = {
  day_of_week: '0',
  start_time: '10:00',
  end_time: '12:00',
  max_patients: 10,
  mode: 'online',
  hospital_id: '',
};

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

function StatCard({ icon, value, label, hint }) {
  return (
    <article className="doctor-availability-stat">
      <span className="doctor-availability-stat-icon">
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

export default function Availability() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [formErrors, setFormErrors] = useState({});
  const [saving, setSaving] = useState(false);

  function load() {
    setError(null);
    return client
      .get('/availability')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your availability right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  function submitAdd(e) {
    e.preventDefault();

    const doAdd = () => {
      setSaving(true);
      setFormErrors({});

      client
        .post('/availability', form)
        .then(() => {
          setForm(emptyForm);
          setShowForm(false);
          return load();
        })
        .catch((err) => {
          if (err.response?.status === 422) {
            setFormErrors(err.response.data.errors || {});
          } else {
            setError('Could not add that visiting window.');
          }
        })
        .finally(() => setSaving(false));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal('Add this visiting window?', doAdd);
    } else {
      doAdd();
    }
  }

  function remove(templateId) {
    const doRemove = () => {
      client
        .delete(`/availability/${templateId}`)
        .then(() => load())
        .catch(() => setError('Could not remove that visiting window.'));
    };

    if (window.showConfirmModal) {
      window.showConfirmModal('Remove this visiting window?', doRemove);
    } else {
      doRemove();
    }
  }

  if (error) {
    return (
      <div className="doctor-page doctor-availability-page">
        <QuickNav />
        <div className="doctor-availability-state doctor-card" role="alert">
          <i className="bi bi-exclamation-circle" aria-hidden="true"></i>
          <div>
            <strong>Availability unavailable</strong>
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
      <div className="doctor-page doctor-availability-page">
        <QuickNav />
        <div className="doctor-availability-state doctor-card">
          <i className="bi bi-calendar-week" aria-hidden="true"></i>
          <div>
            <strong>Loading availability</strong>
            <p>Preparing your weekly visiting hours…</p>
          </div>
        </div>
        <DoctorFooter />
      </div>
    );
  }

  const { templates, hospitals, day_names: dayNames } = data;

  const onlineWindows = templates.filter((item) => item.mode === 'online').length;
  const onsiteWindows = templates.filter((item) => item.mode === 'onsite').length;
  const weeklyCapacity = templates.reduce(
    (sum, item) => sum + Number(item.max_patients || 0),
    0
  );
  const daysCovered = new Set(
    templates.map((item) => item.day_name || String(item.day_of_week))
  ).size;

  const stats = {
    online: onlineWindows,
    onsite: onsiteWindows,
    weeklyCapacity,
    daysCovered,
  };

  const windowsByDay = new Map(dayNames.map((name) => [name, []]));

  templates.forEach((item) => {
    const name = item.day_name || dayNames[Number(item.day_of_week)] || 'Other';
    if (!windowsByDay.has(name)) windowsByDay.set(name, []);
    windowsByDay.get(name).push(item);
  });

  windowsByDay.forEach((items) => {
    items.sort((a, b) => String(a.start_time).localeCompare(String(b.start_time)));
  });

  function setMode(mode) {
    setForm((current) => ({
      ...current,
      mode,
      hospital_id: mode === 'online' ? '' : current.hospital_id,
    }));
  }

  return (
    <div className="doctor-page doctor-availability-page">
      <QuickNav />

      <section className="doctor-availability-hero">
        <div>
          <span className="doctor-availability-eyebrow">DOCTOR AVAILABILITY</span>
          <h1>Availability</h1>
          <p>
            Set recurring weekly consultation windows, control patient capacity,
            and manage when patients can book you.
          </p>
        </div>

        <div className="doctor-availability-hero-actions">
          <button
            type="button"
            className="doctor-availability-primary-action"
            onClick={() => setShowForm((value) => !value)}
          >
            <i className={`bi ${showForm ? 'bi-x-lg' : 'bi-plus-lg'}`} aria-hidden="true"></i>
            {showForm ? 'Close form' : 'Add visiting window'}
          </button>

          <a href={ext('/leave')} className="doctor-availability-secondary-action">
            <i className="bi bi-calendar2-x" aria-hidden="true"></i>
            Leave / unavailable dates
          </a>
        </div>
      </section>

      <section className="doctor-availability-stats" aria-label="Availability summary">
        <StatCard
          icon="bi-calendar2-week"
          value={templates.length}
          label="Active windows"
          hint="Recurring each week"
        />
        <StatCard
          icon="bi-calendar-check"
          value={stats.daysCovered}
          label="Days covered"
          hint={`Out of ${dayNames.length} days`}
        />
        <StatCard
          icon="bi-people"
          value={stats.weeklyCapacity}
          label="Weekly capacity"
          hint="Maximum patient queue"
        />
        <StatCard
          icon="bi-building"
          value={stats.onsite}
          label="Onsite windows"
          hint={`${stats.online} online window${stats.online === 1 ? '' : 's'}`}
        />
      </section>

      <section className="doctor-availability-layout">
        <div className="doctor-availability-schedule-card">
          <header className="doctor-availability-card-header">
            <div>
              <span className="doctor-availability-card-icon">
                <i className="bi bi-calendar-week" aria-hidden="true"></i>
              </span>
              <div>
                <h2>Weekly schedule</h2>
                <p>Your recurring consultation windows for a normal week.</p>
              </div>
            </div>
            <span className="doctor-availability-window-count">
              {templates.length} window{templates.length === 1 ? '' : 's'}
            </span>
          </header>

          <div className="doctor-availability-week">
            {dayNames.map((dayName) => {
              const dayWindows = windowsByDay.get(dayName) || [];

              return (
                <section
                  className={`doctor-availability-day ${dayWindows.length ? 'has-windows' : ''}`}
                  key={dayName}
                >
                  <div className="doctor-availability-day-label">
                    <span>{dayName.slice(0, 3)}</span>
                    <strong>{dayName}</strong>
                    <small>
                      {dayWindows.length
                        ? `${dayWindows.length} window${dayWindows.length === 1 ? '' : 's'}`
                        : 'No hours'}
                    </small>
                  </div>

                  <div className="doctor-availability-day-windows">
                    {dayWindows.length === 0 ? (
                      <div className="doctor-availability-empty-day">
                        <span>No visiting hours set</span>
                      </div>
                    ) : (
                      dayWindows.map((item) => (
                        <article className="doctor-availability-window" key={item.template_id}>
                          <div className="doctor-availability-window-time">
                            <i className="bi bi-clock" aria-hidden="true"></i>
                            <div>
                              <strong>{item.start_time} – {item.end_time}</strong>
                              <small>Recurring every {dayName}</small>
                            </div>
                          </div>

                          <div className="doctor-availability-window-meta">
                            <span className={`doctor-availability-mode is-${item.mode}`}>
                              <i
                                className={`bi ${item.mode === 'online' ? 'bi-camera-video' : 'bi-hospital'}`}
                                aria-hidden="true"
                              ></i>
                              {item.mode === 'online' ? 'Online' : 'Onsite'}
                            </span>
                            <span>
                              <i className="bi bi-people" aria-hidden="true"></i>
                              {item.max_patients} patients
                            </span>
                            {item.hospital_name && (
                              <span>
                                <i className="bi bi-geo-alt" aria-hidden="true"></i>
                                {item.hospital_name}
                              </span>
                            )}
                          </div>

                          <button
                            type="button"
                            className="doctor-availability-remove"
                            onClick={() => remove(item.template_id)}
                            aria-label={`Remove ${dayName} ${item.start_time} visiting window`}
                          >
                            <i className="bi bi-trash3" aria-hidden="true"></i>
                            Remove
                          </button>
                        </article>
                      ))
                    )}
                  </div>
                </section>
              );
            })}
          </div>
        </div>

        <aside className="doctor-availability-sidebar">
          {showForm ? (
            <section className="doctor-availability-form-card">
              <header className="doctor-availability-card-header">
                <div>
                  <span className="doctor-availability-card-icon">
                    <i className="bi bi-plus-circle" aria-hidden="true"></i>
                  </span>
                  <div>
                    <h2>Add visiting window</h2>
                    <p>Create a recurring weekly consultation period.</p>
                  </div>
                </div>
              </header>

              <form onSubmit={submitAdd} className="doctor-availability-form">
                {hospitals.length === 0 && (
                  <div className="doctor-availability-info">
                    <i className="bi bi-info-circle" aria-hidden="true"></i>
                    <p>
                      No hospital has assigned you yet, so you can currently add
                      <strong> online </strong>
                      windows only.
                    </p>
                  </div>
                )}

                <div className="doctor-availability-field">
                  <label htmlFor="day_of_week">Day of week</label>
                  <select
                    id="day_of_week"
                    value={form.day_of_week}
                    onChange={(e) => setForm({ ...form, day_of_week: e.target.value })}
                    required
                  >
                    {dayNames.map((name, index) => (
                      <option key={name} value={index}>{name}</option>
                    ))}
                  </select>
                </div>

                <div className="doctor-availability-form-grid">
                  <div className="doctor-availability-field">
                    <label htmlFor="start_time">Start time</label>
                    <input
                      type="time"
                      id="start_time"
                      value={form.start_time}
                      onChange={(e) => setForm({ ...form, start_time: e.target.value })}
                      required
                    />
                  </div>

                  <div className="doctor-availability-field">
                    <label htmlFor="end_time">End time</label>
                    <input
                      type="time"
                      id="end_time"
                      value={form.end_time}
                      onChange={(e) => setForm({ ...form, end_time: e.target.value })}
                      required
                    />
                  </div>
                </div>

                {formErrors.end_time && (
                  <p className="doctor-availability-field-error">{formErrors.end_time[0]}</p>
                )}

                <div className="doctor-availability-field">
                  <label htmlFor="max_patients">Patient capacity</label>
                  <input
                    type="number"
                    id="max_patients"
                    min="1"
                    max="200"
                    value={form.max_patients}
                    onChange={(e) => setForm({ ...form, max_patients: e.target.value })}
                    required
                  />
                  <small>
                    Bookings receive queue serial numbers from 1 up to this limit.
                  </small>
                </div>

                <fieldset className="doctor-availability-mode-field">
                  <legend>Consultation mode</legend>
                  <div>
                    <button
                      type="button"
                      className={form.mode === 'online' ? 'is-active' : ''}
                      onClick={() => setMode('online')}
                    >
                      <i className="bi bi-camera-video" aria-hidden="true"></i>
                      <span>
                        <strong>Online</strong>
                        <small>Video consultation</small>
                      </span>
                    </button>

                    <button
                      type="button"
                      className={form.mode === 'onsite' ? 'is-active' : ''}
                      onClick={() => setMode('onsite')}
                      disabled={hospitals.length === 0}
                    >
                      <i className="bi bi-hospital" aria-hidden="true"></i>
                      <span>
                        <strong>Onsite</strong>
                        <small>Hospital visit</small>
                      </span>
                    </button>
                  </div>
                  <input type="hidden" name="mode" value={form.mode} />
                </fieldset>

                {form.mode === 'onsite' && (
                  <div className="doctor-availability-field">
                    <label htmlFor="hospital_id">Hospital</label>
                    <select
                      id="hospital_id"
                      value={form.hospital_id}
                      onChange={(e) => setForm({ ...form, hospital_id: e.target.value })}
                      disabled={hospitals.length === 0}
                      required
                    >
                      <option value="">Choose a hospital</option>
                      {hospitals.map((hospital) => (
                        <option
                          key={hospital.hospital_id}
                          value={hospital.hospital_id}
                        >
                          {hospital.hospital_name}
                        </option>
                      ))}
                    </select>
                    {formErrors.hospital_id && (
                      <p className="doctor-availability-field-error">
                        {formErrors.hospital_id[0]}
                      </p>
                    )}
                  </div>
                )}

                <div className="doctor-availability-form-actions">
                  <button
                    type="button"
                    className="doctor-availability-cancel"
                    onClick={() => {
                      setShowForm(false);
                      setFormErrors({});
                    }}
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="doctor-availability-save"
                    disabled={saving}
                  >
                    <i className="bi bi-check2" aria-hidden="true"></i>
                    {saving ? 'Saving…' : 'Add window'}
                  </button>
                </div>
              </form>
            </section>
          ) : (
            <>
              <section className="doctor-availability-guide-card">
                <span className="doctor-availability-guide-icon">
                  <i className="bi bi-lightbulb" aria-hidden="true"></i>
                </span>
                <div>
                  <h2>How availability works</h2>
                  <p>
                    Each window repeats every week. Patients booking within a
                    window receive a queue serial number rather than an individual
                    appointment time.
                  </p>
                </div>

                <div className="doctor-availability-guide-points">
                  <span><i className="bi bi-arrow-repeat"></i> Repeats weekly</span>
                  <span><i className="bi bi-list-ol"></i> Queue-based booking</span>
                  <span><i className="bi bi-people"></i> Capacity controlled by you</span>
                </div>

                <button
                  type="button"
                  className="doctor-availability-guide-action"
                  onClick={() => setShowForm(true)}
                >
                  <i className="bi bi-plus-lg" aria-hidden="true"></i>
                  Add a visiting window
                </button>
              </section>

              <a href={ext('/leave')} className="doctor-availability-leave-card">
                <span>
                  <i className="bi bi-calendar2-x" aria-hidden="true"></i>
                </span>
                <div>
                  <strong>Leave / unavailable dates</strong>
                  <small>Block specific dates without changing your weekly schedule.</small>
                </div>
                <i className="bi bi-arrow-right" aria-hidden="true"></i>
              </a>

              <section className="doctor-availability-hospital-card">
                <header>
                  <div>
                    <i className="bi bi-building" aria-hidden="true"></i>
                    <strong>Assigned hospitals</strong>
                  </div>
                  <span>{hospitals.length}</span>
                </header>

                {hospitals.length === 0 ? (
                  <p>No hospitals have assigned you yet.</p>
                ) : (
                  <div>
                    {hospitals.map((hospital) => (
                      <span key={hospital.hospital_id}>
                        <i className="bi bi-hospital" aria-hidden="true"></i>
                        {hospital.hospital_name}
                      </span>
                    ))}
                  </div>
                )}
              </section>
            </>
          )}
        </aside>
      </section>

      <DoctorFooter />
    </div>
  );
}
