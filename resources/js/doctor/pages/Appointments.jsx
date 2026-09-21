import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import QuickNav from '../components/QuickNav';
import MiniCalendar from '../components/MiniCalendar';


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

function formatDate(dateString, options = {}) {
  if (!dateString) return '';
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    ...options,
  }).format(new Date(`${dateString}T00:00:00`));
}

function initials(name) {
  return String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('');
}

function AppointmentActions({ appointment, onMarkVisited }) {
  if (appointment.is_actionable) {
    return (
      <div className="doctor-appointments-row-actions">
        {appointment.appointment_type === 'online' && (
          appointment.is_joinable_now ? (
            <a href={appointment.consultation_url} className="is-primary">
              <i className="bi bi-camera-video" aria-hidden="true"></i>
              Join call
            </a>
          ) : (
            <button type="button" disabled title={`Opens at ${appointment.time_range_label}`}>
              <i className="bi bi-camera-video" aria-hidden="true"></i>
              Join call
            </button>
          )
        )}
        <button
          type="button"
          className="is-visit"
          onClick={() => {
            if (window.showConfirmModal) {
              window.showConfirmModal(`Mark ${appointment.patient_name} as visited?`, () => onMarkVisited(appointment));
            } else {
              onMarkVisited(appointment);
            }
          }}
        >
          <i className="bi bi-check2-circle" aria-hidden="true"></i>
          Mark visited
        </button>
      </div>
    );
  }

  if (appointment.is_completed) {
    return (
      <div className="doctor-appointments-row-actions">
        <a href={appointment.prescription_url} className={appointment.prescription_exists ? '' : 'is-primary'}>
          <i className="bi bi-file-earmark-medical" aria-hidden="true"></i>
          {appointment.prescription_exists ? 'View prescription' : 'Issue prescription'}
        </a>
        {appointment.vital_exists ? (
          <span className="doctor-appointments-action-state">
            <i className="bi bi-heart-pulse" aria-hidden="true"></i>
            Vitals logged
          </span>
        ) : (
          <a href={appointment.vitals_url}>
            <i className="bi bi-heart-pulse" aria-hidden="true"></i>
            Log vitals
          </a>
        )}
      </div>
    );
  }

  return <span className="doctor-appointments-no-action">—</span>;
}

function AppointmentRow({ appointment, onMarkVisited }) {
  return (
    <tr>
      <td className="doctor-appointments-time-cell">
        <strong>{appointment.time_range_label}</strong>
        <span>Queue #{appointment.serial_number}</span>
      </td>
      <td>
        <div className="doctor-appointments-patient">
          <span className="doctor-appointments-avatar" aria-hidden="true">{initials(appointment.patient_name)}</span>
          <div>
            <strong>{appointment.patient_name}</strong>
            <span>Appointment #{appointment.appointment_id}</span>
          </div>
        </div>
      </td>
      <td>
        <div className="doctor-appointments-visit-type">
          <span className="doctor-appointments-type-icon">
            <i className={`bi ${appointment.appointment_type === 'online' ? 'bi-camera-video' : 'bi-person-check'}`} aria-hidden="true"></i>
          </span>
          <div>
            <strong>{appointment.appointment_type === 'online' ? 'Online' : 'Onsite'}</strong>
            <span>{appointment.hospital_name || (appointment.appointment_type === 'online' ? 'Video consultation' : 'Clinic visit')}</span>
          </div>
        </div>
      </td>
      <td>
        <div className="doctor-appointments-date-cell">
          <strong>{formatDate(appointment.appointment_date, { weekday: 'short' })}</strong>
          <span>{formatDate(appointment.appointment_date)}</span>
        </div>
      </td>
      <td>
        <span className={`doctor-appointments-status is-${appointment.status}`}>{appointment.status_label}</span>
        {appointment.is_no_show && <small className="doctor-appointments-refund-note">Refunded</small>}
      </td>
      <td>
        {appointment.payment ? (
          <div className="doctor-appointments-payment">
            <strong>BDT {Number(appointment.payment.amount).toFixed(2)}</strong>
            <span className={`is-${appointment.payment.status}`}>{appointment.payment.status}</span>
          </div>
        ) : (
          <span className="doctor-appointments-muted">—</span>
        )}
      </td>
      <td>
        <AppointmentActions appointment={appointment} onMarkVisited={onMarkVisited} />
      </td>
    </tr>
  );
}

export default function Appointments() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [serial, setSerial] = useState(0);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(null);
  const [activeFilter, setActiveFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [selectedDate, setSelectedDate] = useState(null);
  const [page, setPage] = useState(1);

  const perPage = 8;

  function load() {
    setError(null);
    return client
      .get('/appointments')
      .then((res) => {
        setData(res.data);
        setSerial(res.data.current_serial);
      })
      .catch(() => setError('Could not load your appointments right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  useEffect(() => {
    setPage(1);
  }, [activeFilter, search, selectedDate]);

  function submitQueueStatus(event) {
    event.preventDefault();
    setSaving(true);
    setSuccess(null);

    client
      .post('/appointments/queue-status', { current_serial: serial })
      .then(() => {
        setSuccess('Queue status updated.');
        setTimeout(() => setSuccess(null), 3000);
      })
      .catch((err) => setError(err.response?.data?.message || 'Could not update the queue status.'))
      .finally(() => setSaving(false));
  }

  function markVisited(appointment) {
    client
      .post(`/appointments/${appointment.appointment_id}/visited`)
      .then(() => load())
      .catch((err) => setError(err.response?.data?.message || 'Could not update that appointment.'));
  }

  const allAppointments = useMemo(() => {
    if (!data) return [];
    return [...data.pending, ...data.completed].sort((a, b) => {
      const left = `${a.appointment_date} ${a.time_range_label}`;
      const right = `${b.appointment_date} ${b.time_range_label}`;
      return left.localeCompare(right);
    });
  }, [data]);

  const todayString = useMemo(() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }, []);

  const counts = useMemo(() => {
    const completedCount = allAppointments.filter((a) => a.status === 'completed').length;
    const cancelledCount = allAppointments.filter((a) => a.status === 'cancelled').length;
    const todayCount = allAppointments.filter((a) => a.appointment_date === todayString).length;

    return {
      all: allAppointments.length,
      today: todayCount,
      upcoming: data?.pending.length || 0,
      completed: completedCount,
      cancelled: cancelledCount,
    };
  }, [allAppointments, data, todayString]);

  const appointmentDates = useMemo(
    () => allAppointments.map((appointment) => appointment.appointment_date),
    [allAppointments]
  );

  const filteredAppointments = useMemo(() => {
    let rows = allAppointments;

    if (selectedDate) {
      rows = rows.filter((appointment) => appointment.appointment_date === selectedDate);
    } else if (activeFilter === 'today') {
      rows = rows.filter((appointment) => appointment.appointment_date === todayString);
    } else if (activeFilter === 'upcoming') {
      rows = rows.filter((appointment) => data?.pending.some((pending) => pending.appointment_id === appointment.appointment_id));
    } else if (activeFilter === 'completed') {
      rows = rows.filter((appointment) => appointment.status === 'completed');
    } else if (activeFilter === 'cancelled') {
      rows = rows.filter((appointment) => appointment.status === 'cancelled');
    }

    const query = search.trim().toLowerCase();
    if (query) {
      rows = rows.filter((appointment) => [
        appointment.patient_name,
        appointment.appointment_type,
        appointment.status_label,
        appointment.hospital_name,
        appointment.appointment_date,
      ].filter(Boolean).some((value) => String(value).toLowerCase().includes(query)));
    }

    return rows;
  }, [activeFilter, allAppointments, data, search, selectedDate, todayString]);

  const totalPages = Math.max(1, Math.ceil(filteredAppointments.length / perPage));
  const currentPage = Math.min(page, totalPages);
  const visibleAppointments = filteredAppointments.slice((currentPage - 1) * perPage, currentPage * perPage);

  const upcomingAppointments = useMemo(() => {
    if (!data) return [];
    return data.pending.slice(0, 5);
  }, [data]);

  function chooseFilter(filter) {
    setSelectedDate(null);
    setActiveFilter(filter);
  }

  function chooseDate(date) {
    setSelectedDate(date);
    setActiveFilter('all');
  }

  if (error && !data) {
    return (
      <div className="doctor-page doctor-appointments-page" style={{ flex: '1 1 auto' }}>
        <QuickNav />
        <div className="doctor-appointments-load-state is-error">{error}</div>
        <DoctorFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-appointments-page" style={{ flex: '1 1 auto' }}>
        <QuickNav />
        <div className="doctor-appointments-load-state">Loading appointments…</div>
        <DoctorFooter />
      </div>
    );
  }

  return (
    <div className="doctor-page doctor-appointments-page" style={{ flex: '1 1 auto' }}>
      <QuickNav />

      <section className="doctor-appointments-hero">
        <div>
          <span className="doctor-appointments-eyebrow">Doctor appointments</span>
          <h1>Appointments</h1>
          <p>Manage scheduled consultations, patient queues, visit completion, prescriptions, and vitals from one place.</p>
        </div>

        <form className="doctor-appointments-queue" onSubmit={submitQueueStatus}>
          <div>
            <small>Today’s queue</small>
            <strong>Now serving</strong>
          </div>
          <label htmlFor="current_serial">#</label>
          <input
            type="number"
            id="current_serial"
            min="0"
            value={serial}
            onChange={(event) => setSerial(event.target.value)}
          />
          <button type="submit" disabled={saving}>{saving ? 'Updating…' : 'Update queue'}</button>
          {success && <span className="doctor-appointments-queue-success"><i className="bi bi-check2" aria-hidden="true"></i>{success}</span>}
        </form>
      </section>

      {error && <div className="doctor-appointments-inline-error">{error}</div>}

      <section className="doctor-appointments-stats" aria-label="Appointment overview">
        <article>
          <span><i className="bi bi-calendar2-check" aria-hidden="true"></i></span>
          <div><strong>{counts.today}</strong><small>Today’s appointments</small></div>
        </article>
        <article>
          <span><i className="bi bi-hourglass-split" aria-hidden="true"></i></span>
          <div><strong>{counts.upcoming}</strong><small>Pending appointments</small></div>
        </article>
        <article>
          <span><i className="bi bi-calendar3" aria-hidden="true"></i></span>
          <div><strong>{counts.all}</strong><small>Total appointments</small></div>
        </article>
        <article>
          <span><i className="bi bi-check2-circle" aria-hidden="true"></i></span>
          <div><strong>{counts.completed}</strong><small>Completed visits</small></div>
        </article>
      </section>

      <div className="doctor-appointments-layout">
        <section className="doctor-appointments-table-card">
          <header className="doctor-appointments-toolbar">
            <div className="doctor-appointments-tabs" role="tablist" aria-label="Appointment filters">
              {[
                ['all', 'All appointments', counts.all],
                ['today', 'Today', counts.today],
                ['upcoming', 'Upcoming', counts.upcoming],
                ['completed', 'Completed', counts.completed],
                ['cancelled', 'Cancelled', counts.cancelled],
              ].map(([key, label, count]) => (
                <button
                  key={key}
                  type="button"
                  className={!selectedDate && activeFilter === key ? 'is-active' : ''}
                  onClick={() => chooseFilter(key)}
                >
                  {label}<span>{count}</span>
                </button>
              ))}
            </div>

            <label className="doctor-appointments-search">
              <i className="bi bi-search" aria-hidden="true"></i>
              <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search patient, type, status…"
                aria-label="Search appointments"
              />
            </label>
          </header>

          {selectedDate && (
            <div className="doctor-appointments-date-filter">
              <span><i className="bi bi-calendar-event" aria-hidden="true"></i>{formatDate(selectedDate, { weekday: 'long' })}</span>
              <button type="button" onClick={() => setSelectedDate(null)}>Clear date</button>
            </div>
          )}

          {visibleAppointments.length === 0 ? (
            <div className="doctor-appointments-empty">
              <span><i className="bi bi-calendar2" aria-hidden="true"></i></span>
              <strong>No appointments found</strong>
              <p>Try another filter, date, or search term.</p>
            </div>
          ) : (
            <div className="doctor-appointments-table-wrap">
              <table className="doctor-appointments-table">
                <thead>
                  <tr>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Visit type</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {visibleAppointments.map((appointment) => (
                    <AppointmentRow
                      key={appointment.appointment_id}
                      appointment={appointment}
                      onMarkVisited={markVisited}
                    />
                  ))}
                </tbody>
              </table>
            </div>
          )}

          <footer className="doctor-appointments-pagination">
            <span>
              {filteredAppointments.length === 0
                ? '0 appointments'
                : `Showing ${(currentPage - 1) * perPage + 1}–${Math.min(currentPage * perPage, filteredAppointments.length)} of ${filteredAppointments.length}`}
            </span>
            {totalPages > 1 && (
              <div>
                <button type="button" onClick={() => setPage((value) => Math.max(1, value - 1))} disabled={currentPage === 1} aria-label="Previous page">
                  <i className="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                {Array.from({ length: totalPages }, (_, index) => index + 1).map((pageNumber) => (
                  <button
                    key={pageNumber}
                    type="button"
                    className={pageNumber === currentPage ? 'is-active' : ''}
                    onClick={() => setPage(pageNumber)}
                  >
                    {pageNumber}
                  </button>
                ))}
                <button type="button" onClick={() => setPage((value) => Math.min(totalPages, value + 1))} disabled={currentPage === totalPages} aria-label="Next page">
                  <i className="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
              </div>
            )}
          </footer>
        </section>

        <aside className="doctor-appointments-sidebar">
          <section className="doctor-appointments-calendar-card">
            <header>
              <div><i className="bi bi-calendar3" aria-hidden="true"></i><strong>Calendar</strong></div>
              <button type="button" onClick={() => chooseDate(todayString)}>Today</button>
            </header>
            <MiniCalendar busyDates={appointmentDates} selectedDate={selectedDate} onSelectDate={chooseDate} />
            <div className="doctor-appointments-calendar-legend">
              <span><i className="is-today" aria-hidden="true"></i>Today</span>
              <span><i className="is-booked" aria-hidden="true"></i>Appointment day</span>
            </div>
          </section>

          <section className="doctor-appointments-upcoming-card">
            <header>
              <div><i className="bi bi-calendar2-week" aria-hidden="true"></i><strong>Upcoming appointments</strong></div>
              <button type="button" onClick={() => chooseFilter('upcoming')}>View all <i className="bi bi-arrow-right" aria-hidden="true"></i></button>
            </header>

            {upcomingAppointments.length === 0 ? (
              <div className="doctor-appointments-upcoming-empty">No upcoming appointments.</div>
            ) : (
              <div className="doctor-appointments-upcoming-list">
                {upcomingAppointments.map((appointment) => (
                  <button
                    key={appointment.appointment_id}
                    type="button"
                    onClick={() => chooseDate(appointment.appointment_date)}
                  >
                    <span className="doctor-appointments-upcoming-time">{appointment.time_range_label}</span>
                    <span className="doctor-appointments-avatar" aria-hidden="true">{initials(appointment.patient_name)}</span>
                    <span className="doctor-appointments-upcoming-copy">
                      <strong>{appointment.patient_name}</strong>
                      <small>{formatDate(appointment.appointment_date, { weekday: 'short' })} · {appointment.appointment_type === 'online' ? 'Online' : 'Onsite'}</small>
                    </span>
                    <i className="bi bi-chevron-right" aria-hidden="true"></i>
                  </button>
                ))}
              </div>
            )}
          </section>

          <Link className="doctor-appointments-availability-link" to="/availability">
            <span><i className="bi bi-calendar-week" aria-hidden="true"></i></span>
            <div><strong>Manage availability</strong><small>Update your consultation windows.</small></div>
            <i className="bi bi-arrow-right" aria-hidden="true"></i>
          </Link>
        </aside>
      </div>

      <DoctorFooter />
    </div>
  );
}
