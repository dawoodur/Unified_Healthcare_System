import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function ext(path) {
  return `${window.HOSPITAL_APP_BASE}${path}`;
}

function BookingTable({ bookings, showComplete, csrf }) {
  if (bookings.length === 0) {
    return <EmptyState icon="bi-calendar2-x" title="Nothing here yet" />;
  }

  return (
    <div className="hospital-table-wrap">
      <table className="hospital-table">
        <thead>
          <tr>
            <th>Serial</th>
            <th>Patient</th>
            <th>Facility</th>
            <th>Date</th>
            <th>Days</th>
            <th>Price</th>
            <th>Status</th>
            {showComplete && <th aria-label="Actions"></th>}
          </tr>
        </thead>
        <tbody>
          {bookings.map((booking) => (
            <tr key={booking.booking_id}>
              <td>#{booking.serial_number}</td>
              <td>{booking.patient_name}</td>
              <td>
                {booking.facility_type}
                {booking.is_occupancy && <span className="hospital-chip">Bed</span>}
              </td>
              <td>{booking.booking_date}</td>
              <td>{booking.requested_days}</td>
              <td>৳{Number(booking.price).toLocaleString()}</td>
              <td><span className={`hospital-badge is-${booking.status}`}>{booking.status}</span></td>
              {showComplete && (
                <td>
                  {/*
                    Completing a booking is what discharges a bed and pays the
                    patient's reward points, so it posts to the existing web
                    route that already owns those side effects rather than a
                    second copy of that logic behind the API.
                  */}
                  <form method="POST" action={ext(`/facility-bookings/${booking.booking_id}/complete`)}>
                    <input type="hidden" name="_token" value={csrf} />
                    <button type="submit" className="hospital-btn is-primary">
                      {booking.is_occupancy ? 'Discharge' : 'Mark done'}
                    </button>
                  </form>
                </td>
              )}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default function FacilityBookings() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  // The complete/discharge action posts a normal form to a web route, so it
  // needs the session CSRF token rather than the axios XSRF header.
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    client
      .get('/facility-bookings')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your facility bookings right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="hospital-bookings-page"
      icon="bi-calendar2-check"
      title="Facility bookings"
      subtitle="Patients booked into your facilities, and the ones already settled."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading bookings"
      loadingMessage="Fetching your booking queue…"
    >
      {data && (
        <>
          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-amber"><i className="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <div>
                  <h2>Active bookings</h2>
                  <p>{data.pending.length} waiting to be completed or discharged.</p>
                </div>
              </div>
            </header>
            <BookingTable bookings={data.pending} showComplete csrf={csrf} />
          </section>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-teal"><i className="bi bi-check2-all" aria-hidden="true"></i></span>
                <div>
                  <h2>Settled</h2>
                  <p>{data.completed.length} completed or cancelled.</p>
                </div>
              </div>
            </header>
            <BookingTable bookings={data.completed} showComplete={false} csrf={csrf} />
          </section>
        </>
      )}
    </PageShell>
  );
}
