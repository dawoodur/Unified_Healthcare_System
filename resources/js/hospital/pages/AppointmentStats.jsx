import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function StatTile({ icon, value, label, hint, tone }) {
  return (
    <article className={`hospital-stat is-${tone}`}>
      <span className="hospital-stat-icon"><i className={`bi ${icon}`} aria-hidden="true"></i></span>
      <div>
        <strong>{value}</strong>
        <span>{label}</span>
        <small>{hint}</small>
      </div>
    </article>
  );
}

export default function AppointmentStats() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/appointment-stats')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load appointment statistics right now.'));
  }

  useEffect(load, []);

  const totals = data?.totals;
  const completionRate = totals && totals.total > 0
    ? Math.round((totals.completed / totals.total) * 100)
    : 0;

  return (
    <PageShell
      pageClass="hospital-stats-page"
      icon="bi-graph-up-arrow"
      title="Appointment statistics"
      subtitle="How each doctor at your hospital is performing across their appointments."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading statistics"
      loadingMessage="Crunching your appointment history…"
    >
      {data && (
        <>
          <section className="hospital-stats" aria-label="Appointment totals">
            <StatTile icon="bi-calendar2-check" value={totals.total.toLocaleString()} label="Total appointments" hint="All time" tone="violet" />
            <StatTile icon="bi-check2-circle" value={totals.completed.toLocaleString()} label="Completed" hint={`${completionRate}% completion rate`} tone="teal" />
            <StatTile icon="bi-hourglass-split" value={totals.booked.toLocaleString()} label="Upcoming" hint="Booked or confirmed" tone="indigo" />
            <StatTile icon="bi-x-circle" value={totals.cancelled.toLocaleString()} label="Cancelled / no-show" hint="Did not go ahead" tone="rose" />
          </section>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-indigo"><i className="bi bi-person-badge" aria-hidden="true"></i></span>
                <div>
                  <h2>By doctor</h2>
                  <p>Busiest first. Every appointment booked at this hospital counts once.</p>
                </div>
              </div>
            </header>

            {data.by_doctor.length === 0 ? (
              <EmptyState
                icon="bi-bar-chart"
                title="No appointments yet"
                message="Statistics appear once patients start booking at your hospital."
              />
            ) : (
              <div className="hospital-table-wrap">
                <table className="hospital-table">
                  <thead>
                    <tr>
                      <th>Doctor</th>
                      <th>Total</th>
                      <th>Completed</th>
                      <th>Upcoming</th>
                      <th>Cancelled</th>
                      <th>Completion</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.by_doctor.map((row) => {
                      const rate = row.total > 0 ? Math.round((row.completed / row.total) * 100) : 0;
                      return (
                        <tr key={row.doctor_id}>
                          <td>Dr. {row.doctor_name}</td>
                          <td>{row.total}</td>
                          <td>{row.completed}</td>
                          <td>{row.booked}</td>
                          <td>{row.cancelled}</td>
                          <td>
                            <span className="hospital-rank-track hospital-rank-track-inline">
                              <span style={{ width: `${Math.max(4, rate)}%` }}></span>
                            </span>
                            <small>{rate}%</small>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </section>
        </>
      )}
    </PageShell>
  );
}
