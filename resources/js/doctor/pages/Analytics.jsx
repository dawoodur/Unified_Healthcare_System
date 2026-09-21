import React, { useEffect, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

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

function SummaryCard({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-analytics-stat is-${tone}`}>
      <span className="doctor-analytics-stat-icon">
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

function BarSeries({ data, tone = 'blue', valuePrefix = '' }) {
  const values = (data || []).map((item) => Number(item.value || 0));
  const max = Math.max(...values, 1);

  return (
    <div className={`doctor-analytics-bars is-${tone}`}>
      {(data || []).map((item) => {
        const value = Number(item.value || 0);
        const height = value === 0 ? 4 : Math.max(12, Math.round((value / max) * 100));

        return (
          <div className="doctor-analytics-bar-column" key={item.label}>
            <div className="doctor-analytics-bar-value">
              {valuePrefix}{Number.isInteger(value) ? value.toLocaleString() : value.toFixed(0)}
            </div>
            <div className="doctor-analytics-bar-track">
              <span style={{ height: `${height}%` }}></span>
            </div>
            <small>{item.label}</small>
          </div>
        );
      })}
    </div>
  );
}

export default function Analytics() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/analytics')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your analytics right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  if (error) {
    return (
      <div className="doctor-page doctor-analytics-page">
        <QuickNav />
        <div className="doctor-analytics-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-circle" aria-hidden="true"></i></span>
          <div>
            <strong>Analytics unavailable</strong>
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
      <div className="doctor-page doctor-analytics-page">
        <QuickNav />
        <div className="doctor-analytics-state doctor-card">
          <span><i className="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
          <div>
            <strong>Loading analytics</strong>
            <p>Preparing your practice trends…</p>
          </div>
        </div>
        <DoctorFooter />
      </div>
    );
  }

  const earnings = data.earnings_by_month || [];
  const appointments = data.appointments_by_month || [];
  const statuses = data.status_counts || [];
  const stats = data.stats || {};

  const highestEarning = earnings.reduce(
    (best, item) => Number(item.value || 0) > Number(best?.value || -1) ? item : best,
    null
  );
  const busiest = appointments.reduce(
    (best, item) => Number(item.value || 0) > Number(best?.value || -1) ? item : best,
    null
  );
  const topStatus = statuses.reduce(
    (best, item) => Number(item.value || 0) > Number(best?.value || -1) ? item : best,
    null
  );

  const insights = { highestEarning, busiest, topStatus };

  const maxStatus = Math.max(...statuses.map((item) => Number(item.value || 0)), 1);

  return (
    <div className="doctor-page doctor-analytics-page">
      <QuickNav />

      <section className="doctor-analytics-hero">
        <div>
          <span className="doctor-analytics-eyebrow">PRACTICE ANALYTICS</span>
          <h1>Analytics</h1>
          <p>
            Review appointment volume, completed-payment earnings, patient reach,
            and status patterns across your practice.
          </p>
        </div>

        <div className="doctor-analytics-period">
          <span><i className="bi bi-calendar3" aria-hidden="true"></i></span>
          <div>
            <strong>Last 6 months</strong>
            <small>Monthly activity through the current month</small>
          </div>
        </div>
      </section>

      <section className="doctor-analytics-stats" aria-label="Analytics summary">
        <SummaryCard
          icon="bi-cash-stack"
          value={`৳${Number(stats.earnings_6m || 0).toLocaleString()}`}
          label="6-month earnings"
          hint="Completed payments"
          tone="blue"
        />
        <SummaryCard
          icon="bi-calendar2-check"
          value={Number(stats.appointments_6m || 0).toLocaleString()}
          label="6-month appointments"
          hint="Bookings in the period"
          tone="teal"
        />
        <SummaryCard
          icon="bi-people"
          value={Number(stats.total_patients || 0).toLocaleString()}
          label="Unique patients"
          hint="Across your appointment history"
          tone="violet"
        />
        <SummaryCard
          icon="bi-check2-circle"
          value={Number(stats.completed_visits || 0).toLocaleString()}
          label="Completed visits"
          hint="All-time completed appointments"
          tone="amber"
        />
      </section>

      <section className="doctor-analytics-layout">
        <div className="doctor-analytics-main">
          <section className="doctor-analytics-chart-card is-blue">
            <header className="doctor-analytics-card-header">
              <div>
                <span className="doctor-analytics-card-icon is-blue">
                  <i className="bi bi-cash-coin" aria-hidden="true"></i>
                </span>
                <div>
                  <h2>Earnings trend</h2>
                  <p>Completed-payment earnings by month.</p>
                </div>
              </div>
              <span className="doctor-analytics-card-total">
                ৳{Number(stats.earnings_6m || 0).toLocaleString()}
              </span>
            </header>

            <BarSeries data={earnings} tone="blue" valuePrefix="৳" />
          </section>

          <section className="doctor-analytics-chart-card is-teal">
            <header className="doctor-analytics-card-header">
              <div>
                <span className="doctor-analytics-card-icon is-teal">
                  <i className="bi bi-calendar-week" aria-hidden="true"></i>
                </span>
                <div>
                  <h2>Appointment volume</h2>
                  <p>Appointments booked in each of the last six months.</p>
                </div>
              </div>
              <span className="doctor-analytics-card-total">
                {Number(stats.appointments_6m || 0).toLocaleString()} total
              </span>
            </header>

            <BarSeries data={appointments} tone="teal" />
          </section>

          <section className="doctor-analytics-status-card">
            <header className="doctor-analytics-card-header">
              <div>
                <span className="doctor-analytics-card-icon is-violet">
                  <i className="bi bi-pie-chart" aria-hidden="true"></i>
                </span>
                <div>
                  <h2>Appointments by status</h2>
                  <p>Your all-time appointment status breakdown.</p>
                </div>
              </div>
            </header>

            <div className="doctor-analytics-status-list">
              {statuses.length === 0 ? (
                <div className="doctor-analytics-empty">
                  <i className="bi bi-pie-chart" aria-hidden="true"></i>
                  <strong>No appointment status data yet</strong>
                </div>
              ) : (
                statuses.map((item, index) => {
                  const value = Number(item.value || 0);
                  const width = Math.max(4, Math.round((value / maxStatus) * 100));
                  return (
                    <div className={`doctor-analytics-status-row tone-${index % 4}`} key={item.label}>
                      <span className="doctor-analytics-status-label">{item.label}</span>
                      <span className="doctor-analytics-status-track">
                        <span style={{ width: `${width}%` }}></span>
                      </span>
                      <strong>{value}</strong>
                    </div>
                  );
                })
              )}
            </div>
          </section>
        </div>

        <aside className="doctor-analytics-sidebar">
          <section className="doctor-analytics-insight-card is-violet">
            <span className="doctor-analytics-insight-icon">
              <i className="bi bi-lightning-charge" aria-hidden="true"></i>
            </span>
            <div>
              <h2>6-month highlights</h2>

              <dl>
                <div>
                  <dt>Highest earning month</dt>
                  <dd>
                    {insights.highestEarning
                      ? `${insights.highestEarning.label} · ৳${Number(insights.highestEarning.value || 0).toLocaleString()}`
                      : 'No data'}
                  </dd>
                </div>
                <div>
                  <dt>Busiest month</dt>
                  <dd>
                    {insights.busiest
                      ? `${insights.busiest.label} · ${Number(insights.busiest.value || 0).toLocaleString()} appointments`
                      : 'No data'}
                  </dd>
                </div>
                <div>
                  <dt>Most common status</dt>
                  <dd>
                    {insights.topStatus
                      ? `${insights.topStatus.label} · ${Number(insights.topStatus.value || 0).toLocaleString()}`
                      : 'No data'}
                  </dd>
                </div>
              </dl>
            </div>
          </section>

          <section className="doctor-analytics-insight-card is-amber">
            <span className="doctor-analytics-insight-icon">
              <i className="bi bi-info-circle" aria-hidden="true"></i>
            </span>
            <div>
              <h2>How these numbers are counted</h2>
              <p>
                Earnings include completed payments only. Monthly appointment
                charts cover the latest six calendar months. Unique patients
                are counted across your full appointment history.
              </p>
            </div>
          </section>
        </aside>
      </section>

      <DoctorFooter />
    </div>
  );
}
