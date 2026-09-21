import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import MiniCalendar from '../components/MiniCalendar';
import DonutChart from '../components/DonutChart';

function ext(path) {
  return `${window.DOCTOR_APP_BASE}${path}`;
}

const STATUS_LABEL = {
  completed: 'Completed',
  no_show: 'No show',
  in_progress: 'In progress',
  upcoming: 'Upcoming',
};

function initials(name) {
  return String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('');
}

function formatCalendarDate(dateString) {
  if (!dateString) return '';

  const [year, month, day] = dateString.split('-').map(Number);
  const date = new Date(year, month - 1, day);

  return new Intl.DateTimeFormat('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  }).format(date);
}

function WorkspaceNav() {
  const items = [
    { type: 'link', to: '/dashboard', icon: 'bi-house-door', label: 'Dashboard', active: true },
    { type: 'link', to: '/appointments', icon: 'bi-calendar2-check', label: 'Appointments' },
    { type: 'link', to: '/availability', icon: 'bi-calendar-week', label: 'Availability' },
    { type: 'anchor', href: ext('/records'), icon: 'bi-folder2-open', label: 'Records' },
    { type: 'link', to: '/inbox', icon: 'bi-chat-dots', label: 'Inbox' },
    { type: 'link', to: '/reviews', icon: 'bi-star', label: 'Reviews' },
    { type: 'anchor', href: ext('/analytics'), icon: 'bi-graph-up-arrow', label: 'Analytics' },
  ];

  return (
    <nav className="doctor-dashboard-workspace-nav" aria-label="Doctor workspace navigation">
      {items.map((item) => {
        const content = (
          <>
            <i className={`bi ${item.icon}`} aria-hidden="true"></i>
            <span>{item.label}</span>
          </>
        );

        return item.type === 'link' ? (
          <Link key={item.label} to={item.to} className={item.active ? 'is-active' : ''}>{content}</Link>
        ) : (
          <a key={item.label} href={item.href}>{content}</a>
        );
      })}
    </nav>
  );
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

export default function Dashboard() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [selectedCalendarDate, setSelectedCalendarDate] = useState(null);
  const [calendarDay, setCalendarDay] = useState(null);
  const [calendarDayLoading, setCalendarDayLoading] = useState(false);
  const [calendarDayError, setCalendarDayError] = useState(null);
  const [showCalendarNoteEditor, setShowCalendarNoteEditor] = useState(false);
  const [calendarNoteDraft, setCalendarNoteDraft] = useState('');
  const [calendarNoteSaving, setCalendarNoteSaving] = useState(false);

  useEffect(() => {
    client
      .get('/dashboard')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your dashboard right now.'));
  }, []);

  useEffect(() => {
    if (!data || !selectedCalendarDate) return;

    setCalendarDayLoading(true);
    setCalendarDayError(null);

    client
      .get(`/dashboard/calendar/${selectedCalendarDate}`)
      .then((res) => {
        setCalendarDay(res.data);
        setCalendarNoteDraft(res.data.note || '');
      })
      .catch(() => {
        setCalendarDay(null);
        setCalendarDayError('Could not load the selected date.');
      })
      .finally(() => setCalendarDayLoading(false));
  }, [data, selectedCalendarDate]);

  const formattedDate = useMemo(() => (
    new Intl.DateTimeFormat('en-US', {
      weekday: 'long',
      month: 'short',
      day: '2-digit',
      year: 'numeric',
    }).format(new Date())
  ), []);

  useEffect(() => {
    if (!data || selectedCalendarDate) return;

    const today = new Date();
    const dateString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    setSelectedCalendarDate(dateString);
  }, [data, selectedCalendarDate]);

  if (error) {
    return (
      <div className="doctor-page doctor-dashboard-page">
        <WorkspaceNav />
        <div className="doctor-dashboard-load-state">{error}</div>
        <DoctorFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-dashboard-page">
        <WorkspaceNav />
        <div className="doctor-dashboard-load-state">Loading…</div>
        <DoctorFooter />
      </div>
    );
  }

  const {
    doctor,
    stats,
    today_appointments: todayAppointments,
    calendar_appointment_dates: calendarDates,
    patient_overview: overview,
  } = data;

  const verificationStatus = doctor.verification_status || 'pending';
  const verificationLabel = verificationStatus.charAt(0).toUpperCase() + verificationStatus.slice(1);
  const patientTotal = overview.new + overview.follow_up + overview.returning;
  const specialties = doctor.specialties?.length ? doctor.specialties.join(', ') : 'No specialty selected';

  const now = new Date();
  const calendarMonthPrefix = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  const todayDateString = `${calendarMonthPrefix}-${String(now.getDate()).padStart(2, '0')}`;

  const bookedDaysThisMonth = [...new Set(calendarDates || [])]
    .filter((date) => date.startsWith(calendarMonthPrefix))
    .sort();
  const nextBookedDay = bookedDaysThisMonth.find((date) => date >= todayDateString);

  function selectCalendarDate(date) {
    setSelectedCalendarDate(date);
    setShowCalendarNoteEditor(false);
  }

  function openCalendarNoteEditor() {
    if (!selectedCalendarDate) return;
    setCalendarNoteDraft(calendarDay?.note || '');
    setShowCalendarNoteEditor(true);
  }

  function saveCalendarNote(event) {
    event.preventDefault();
    if (!selectedCalendarDate) return;

    setCalendarNoteSaving(true);
    setCalendarDayError(null);

    client
      .put(`/dashboard/calendar/${selectedCalendarDate}/note`, {
        note: calendarNoteDraft,
      })
      .then((res) => {
        setCalendarDay((current) => ({
          ...(current || {}),
          date: selectedCalendarDate,
          appointments: current?.appointments || [],
          note: res.data.note || '',
        }));
        setCalendarNoteDraft(res.data.note || '');
        setShowCalendarNoteEditor(false);
      })
      .catch(() => setCalendarDayError('Could not save that calendar note.'))
      .finally(() => setCalendarNoteSaving(false));
  }

  return (
    <div className="doctor-page doctor-dashboard-page">
      <WorkspaceNav />

      <section className="doctor-dashboard-hero" aria-labelledby="doctor-dashboard-title">
        <div className="doctor-dashboard-hero-copy">
          <div className="doctor-dashboard-eyebrow">
            <span aria-hidden="true"></span>
            Doctor Workspace
          </div>
          <h1 id="doctor-dashboard-title">Welcome, Dr. {doctor.full_name}</h1>
          <p>Here&apos;s what&apos;s happening with your patients today.</p>

          <div className="doctor-dashboard-hero-meta">
            <span><i className="bi bi-patch-check" aria-hidden="true"></i> Verification status: {verificationLabel}</span>
            <span><i className="bi bi-heart-pulse" aria-hidden="true"></i> {specialties}</span>
            <span><i className="bi bi-cash-coin" aria-hidden="true"></i> Consultation fee: BDT {Number(doctor.consultation_fee).toFixed(2)}</span>
          </div>
        </div>

        <div className="doctor-dashboard-hero-side">
          <div className="doctor-dashboard-date-block">
            <i className="bi bi-calendar3" aria-hidden="true"></i>
            <span>{formattedDate}</span>
          </div>
          <div className="doctor-dashboard-quote-card">
            <div>
              <strong>“Small steps in care<br />make a big difference.”</strong>
            </div>
            <i className="bi bi-flower1" aria-hidden="true"></i>
          </div>
        </div>
      </section>

      <section className="doctor-dashboard-stat-grid" aria-label="Today at a glance">
        <article className="doctor-dashboard-stat-card">
          <span className="doctor-dashboard-stat-icon"><i className="bi bi-calendar2-check" aria-hidden="true"></i></span>
          <div>
            <strong>{stats.today_appointments}</strong>
            <span>Today&apos;s Appointments</span>
          </div>
          <Link to="/appointments" aria-label="View appointments"><i className="bi bi-arrow-right" aria-hidden="true"></i></Link>
        </article>

        <article className="doctor-dashboard-stat-card">
          <span className="doctor-dashboard-stat-icon"><i className="bi bi-hourglass-split" aria-hidden="true"></i></span>
          <div>
            <strong>{stats.pending_appointments}</strong>
            <span>Pending Appointments</span>
          </div>
          <Link to="/appointments" aria-label="View pending appointments"><i className="bi bi-arrow-right" aria-hidden="true"></i></Link>
        </article>

        <article className="doctor-dashboard-stat-card">
          <span className="doctor-dashboard-stat-icon"><i className="bi bi-camera-video" aria-hidden="true"></i></span>
          <div>
            <strong>{stats.today_video_count}</strong>
            <span>Video Consultations</span>
          </div>
          <Link to="/appointments" aria-label="View video consultations"><i className="bi bi-arrow-right" aria-hidden="true"></i></Link>
        </article>

        <article className="doctor-dashboard-stat-card">
          <span className="doctor-dashboard-stat-icon"><i className="bi bi-cash-coin" aria-hidden="true"></i></span>
          <div>
            <strong>৳{Number(stats.today_earnings).toLocaleString()}</strong>
            <span>Today&apos;s Earnings</span>
          </div>
          <a href={ext('/analytics')} aria-label="View analytics"><i className="bi bi-arrow-right" aria-hidden="true"></i></a>
        </article>
      </section>

      <section className="doctor-dashboard-primary-grid">
        <article className="doctor-dashboard-panel doctor-dashboard-appointments-panel">
          <header className="doctor-dashboard-panel-header">
            <div>
              <span className="doctor-dashboard-panel-icon"><i className="bi bi-calendar2-check" aria-hidden="true"></i></span>
              <h2>Today&apos;s Appointments</h2>
            </div>
            <Link to="/appointments">View all <i className="bi bi-arrow-right" aria-hidden="true"></i></Link>
          </header>

          {todayAppointments.length === 0 ? (
            <div className="doctor-dashboard-empty">
              <i className="bi bi-calendar2" aria-hidden="true"></i>
              <strong>Nothing on your schedule today.</strong>
              <span>Your next booked consultation will appear here.</span>
            </div>
          ) : (
            <ol className="doctor-dashboard-timeline">
              {todayAppointments.map((appointment) => (
                <li key={appointment.appointment_id}>
                  <div className="doctor-dashboard-time">
                    <strong>{appointment.time_label}</strong>
                    <span>{appointment.appointment_type === 'online' ? 'Online consultation' : 'Onsite consultation'}</span>
                  </div>

                  <div className="doctor-dashboard-patient-avatar">
                    {appointment.patient_photo_url ? (
                      <img src={appointment.patient_photo_url} alt="" />
                    ) : (
                      <span>{initials(appointment.patient_name)}</span>
                    )}
                  </div>

                  <div className="doctor-dashboard-patient-copy">
                    <strong>{appointment.patient_name}</strong>
                    <span>{appointment.appointment_type === 'online' ? 'Video consultation' : 'In-person consultation'}</span>
                  </div>

                  <span className={`doctor-dashboard-status is-${appointment.status_key}`}>
                    {STATUS_LABEL[appointment.status_key] || 'Upcoming'}
                  </span>
                </li>
              ))}
            </ol>
          )}
        </article>

        <article className="doctor-dashboard-panel doctor-dashboard-overview-panel">
          <header className="doctor-dashboard-panel-header">
            <div>
              <span className="doctor-dashboard-panel-icon"><i className="bi bi-people" aria-hidden="true"></i></span>
              <h2>Patient Overview</h2>
            </div>
            <a href={ext('/records')}>Records <i className="bi bi-arrow-right" aria-hidden="true"></i></a>
          </header>

          {patientTotal === 0 ? (
            <div className="doctor-dashboard-empty doctor-dashboard-empty-compact">
              <i className="bi bi-people" aria-hidden="true"></i>
              <strong>No patient history yet.</strong>
              <span>Patient activity will appear here after consultations.</span>
            </div>
          ) : (
            <div className="doctor-dashboard-overview-content">
              <DonutChart
                totalLabel="Patients"
                segments={[
                  { label: 'New', value: overview.new, color: '#0a9d9a' },
                  { label: 'Follow-up', value: overview.follow_up, color: '#63b8d4' },
                  { label: 'Returning', value: overview.returning, color: '#8b7cdf' },
                ]}
              />
            </div>
          )}
        </article>
      </section>

      <section className="doctor-dashboard-secondary-grid">
        <article className="doctor-dashboard-panel doctor-dashboard-calendar-panel">
          <header className="doctor-dashboard-panel-header">
            <div>
              <span className="doctor-dashboard-panel-icon"><i className="bi bi-calendar3" aria-hidden="true"></i></span>
              <h2>Calendar</h2>
            </div>

            <div className="doctor-dashboard-calendar-header-actions">
              <button type="button" onClick={openCalendarNoteEditor} disabled={!selectedCalendarDate}>
                <i className="bi bi-sticky" aria-hidden="true"></i>
                {calendarDay?.note ? 'Edit note' : 'Add note'}
              </button>
              <Link to="/availability">Manage availability <i className="bi bi-arrow-right" aria-hidden="true"></i></Link>
            </div>
          </header>

          <div className="doctor-dashboard-calendar-summary">
            <div>
              <strong>{bookedDaysThisMonth.length}</strong>
              <span>Booked days this month</span>
            </div>
            <div>
              <small>Next booked day</small>
              <strong>{nextBookedDay ? formatCalendarDate(nextBookedDay) : 'No upcoming bookings'}</strong>
            </div>
          </div>

          <MiniCalendar
            busyDates={calendarDates}
            selectedDate={selectedCalendarDate}
            onSelectDate={selectCalendarDate}
          />

          <div className="doctor-dashboard-calendar-legend" aria-label="Calendar legend">
            <span><i className="is-today" aria-hidden="true"></i>Today</span>
            <span><i className="is-booked" aria-hidden="true"></i>Appointment day</span>
            <span>Click a date to inspect it</span>
          </div>

          <section className="doctor-dashboard-calendar-day" aria-live="polite">
            <header>
              <div>
                <small>Selected date</small>
                <strong>{selectedCalendarDate ? formatCalendarDate(selectedCalendarDate) : 'Choose a date'}</strong>
              </div>
              {calendarDay?.appointments?.length > 0 && (
                <span>{calendarDay.appointments.length} booking{calendarDay.appointments.length === 1 ? '' : 's'}</span>
              )}
            </header>

            {calendarDay?.note && !showCalendarNoteEditor && (
              <div className="doctor-dashboard-calendar-note">
                <i className="bi bi-sticky" aria-hidden="true"></i>
                <div>
                  <small>Doctor note</small>
                  <p>{calendarDay.note}</p>
                </div>
              </div>
            )}

            {showCalendarNoteEditor && (
              <form className="doctor-dashboard-calendar-note-editor" onSubmit={saveCalendarNote}>
                <label htmlFor="doctor-calendar-note">Note for {formatCalendarDate(selectedCalendarDate)}</label>
                <textarea
                  id="doctor-calendar-note"
                  value={calendarNoteDraft}
                  onChange={(event) => setCalendarNoteDraft(event.target.value)}
                  maxLength={1000}
                  rows={3}
                  placeholder="Add a private reminder, preparation note, or follow-up note for this date…"
                />
                <div>
                  <small>{calendarNoteDraft.length}/1000</small>
                  <button type="button" onClick={() => setShowCalendarNoteEditor(false)}>Cancel</button>
                  <button type="submit" disabled={calendarNoteSaving}>
                    {calendarNoteSaving ? 'Saving…' : 'Save note'}
                  </button>
                </div>
              </form>
            )}

            {!showCalendarNoteEditor && (
              calendarDayLoading ? (
                <div className="doctor-dashboard-calendar-day-state">Loading date details…</div>
              ) : calendarDayError ? (
                <div className="doctor-dashboard-calendar-day-state is-error">{calendarDayError}</div>
              ) : calendarDay?.appointments?.length > 0 ? (
                <div className="doctor-dashboard-calendar-bookings">
                  {calendarDay.appointments.map((appointment) => (
                    <article key={appointment.appointment_id}>
                      <div className="doctor-dashboard-calendar-booking-time">
                        <strong>{appointment.time_range_label}</strong>
                        <span>Queue #{appointment.serial_number}</span>
                      </div>

                      <div className="doctor-dashboard-calendar-booking-patient">
                        <strong>{appointment.patient_name}</strong>
                        <span>
                          {appointment.appointment_type === 'online' ? 'Online consultation' : 'Onsite consultation'}
                          {appointment.hospital_name ? ` · ${appointment.hospital_name}` : ''}
                        </span>
                      </div>

                      <div className="doctor-dashboard-calendar-booking-extra">
                        <span className={`doctor-dashboard-status is-${appointment.status_key}`}>
                          {appointment.status_label}
                        </span>
                        {appointment.has_prescription && <span><i className="bi bi-file-earmark-medical" aria-hidden="true"></i> Prescription</span>}
                        {appointment.has_vitals && <span><i className="bi bi-heart-pulse" aria-hidden="true"></i> Vitals</span>}
                      </div>
                    </article>
                  ))}
                </div>
              ) : (
                <div className="doctor-dashboard-calendar-day-state">
                  No bookings for this date.
                </div>
              )
            )}
          </section>
        </article>

        <article className="doctor-dashboard-panel doctor-dashboard-actions-panel">
          <header className="doctor-dashboard-panel-header">
            <div>
              <span className="doctor-dashboard-panel-icon"><i className="bi bi-lightning-charge" aria-hidden="true"></i></span>
              <h2>Quick Actions</h2>
            </div>
          </header>

          <div className="doctor-dashboard-actions-grid">
            <Link to="/appointments"><i className="bi bi-calendar2-check" aria-hidden="true"></i><span>View Appointments</span></Link>
            <Link to="/availability"><i className="bi bi-calendar-week" aria-hidden="true"></i><span>Manage Availability</span></Link>
            <a href={ext('/records')}><i className="bi bi-folder2-open" aria-hidden="true"></i><span>Patient Records</span></a>
            <Link to="/inbox"><i className="bi bi-chat-dots" aria-hidden="true"></i><span>Open Inbox</span></Link>
          </div>
        </article>
      </section>

      <section className="doctor-dashboard-insights-panel" aria-labelledby="doctor-dashboard-insights-title">
        <header>
          <div>
            <i className="bi bi-bar-chart-line" aria-hidden="true"></i>
            <h2 id="doctor-dashboard-insights-title">Practice Insights</h2>
          </div>
          <a href={ext('/analytics')}>View analytics <i className="bi bi-arrow-right" aria-hidden="true"></i></a>
        </header>

        <div className="doctor-dashboard-insight-grid">
          <div><span>New Patients</span><strong>{overview.new}</strong><small>Current patient mix</small></div>
          <div><span>Follow-up Patients</span><strong>{overview.follow_up}</strong><small>Current patient mix</small></div>
          <div><span>Returning Patients</span><strong>{overview.returning}</strong><small>Current patient mix</small></div>
          <div><span>Today&apos;s Earnings</span><strong>৳{Number(stats.today_earnings).toLocaleString()}</strong><small>Completed payments today</small></div>
        </div>
      </section>

      <DoctorFooter />
    </div>
  );
}
