import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import QuickNav from '../components/QuickNav';
import { HospitalFooter, StateCard, EmptyState } from '../components/PageShell';
import { useT } from '../i18n';

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

function BarSeries({ data, tone = 'violet', valuePrefix = '' }) {
  const rows = data || [];
  const max = Math.max(...rows.map((item) => Number(item.value || 0)), 1);

  if (rows.length === 0) {
    return <EmptyState icon="bi-bar-chart" title="No data for this period yet" />;
  }

  return (
    <div className={`hospital-bars is-${tone}`}>
      {rows.map((item) => {
        const value = Number(item.value || 0);
        const height = value === 0 ? 4 : Math.max(12, Math.round((value / max) * 100));

        return (
          <div className="hospital-bar-column" key={item.label}>
            <div className="hospital-bar-value">{valuePrefix}{value.toLocaleString()}</div>
            <div className="hospital-bar-track"><span style={{ height: `${height}%` }}></span></div>
            <small>{item.label}</small>
          </div>
        );
      })}
    </div>
  );
}

// A trend over time (appointments booked per month) reads as one line
// moving up and down, not six unrelated per-month totals the way a bar
// chart implies — see doctor/admin's Analytics pages for the same fix.
function LineSeries({ data, tone = 'violet' }) {
  const rows = data || [];
  const values = rows.map((item) => Number(item.value || 0));
  const max = Math.max(...values, 0);

  if (rows.length === 0 || max === 0) {
    return <EmptyState icon="bi-graph-up" title="No data for this period yet" />;
  }

  const min = Math.min(...values, 0);
  const range = max - min || 1;
  const width = 600;
  const height = 130;
  const padTop = 12;
  const padBottom = 12;
  const plotHeight = height - padTop - padBottom;

  const points = values.map((value, index) => ({
    x: values.length > 1 ? (index / (values.length - 1)) * width : width / 2,
    y: padTop + plotHeight - ((value - min) / range) * plotHeight,
  }));
  const linePoints = points.map((p) => `${p.x},${p.y}`).join(' ');
  const areaPoints = `${points[0].x},${height} ${linePoints} ${points[points.length - 1].x},${height}`;

  return (
    <div className={`hospital-line is-${tone}`}>
      <div className="hospital-line-svg-wrap">
        <svg viewBox={`0 0 ${width} ${height}`} preserveAspectRatio="none" aria-hidden="true">
          <polygon points={areaPoints} className="hospital-line-area" />
          <polyline points={linePoints} className="hospital-line-path" />
          {points.map((p, i) => <circle key={rows[i].label} cx={p.x} cy={p.y} r="4.5" className="hospital-line-dot" />)}
        </svg>
      </div>
      <div className="hospital-line-labels">
        {rows.map((item) => {
          const value = Number(item.value || 0);
          return (
            <div className="hospital-line-label-col" key={item.label}>
              <div className="hospital-line-value">{value.toLocaleString()}</div>
              <small>{item.label}</small>
            </div>
          );
        })}
      </div>
    </div>
  );
}

function initials(name) {
  return String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('');
}

/**
 * Account::photoUrl() only checks that a photo_path column is set, not that
 * the file is still on disk — a stale path renders as the browser's broken
 * image glyph. Falling back to initials on error means a missing file looks
 * like "no photo" instead of a broken page.
 */
function Avatar({ name, photoUrl }) {
  const [failed, setFailed] = useState(false);

  if (!photoUrl || failed) {
    return <span className="hospital-avatar">{initials(name) || 'PT'}</span>;
  }

  return <img src={photoUrl} alt="" onError={() => setFailed(true)} />;
}

// One tone each, matching the colour that section's icon uses in QuickNav,
// so the same job is the same colour wherever you meet it.
const TOOLS = [
  { to: '/doctors', icon: 'bi-person-badge', titleKey: 'doctor_assignments_title', descKey: 'doctor_assignments_desc', linkKey: 'manage_doctors', tone: 'indigo' },
  { to: '/facilities', icon: 'bi-building', titleKey: 'facilities_title', descKey: 'facilities_desc', linkKey: 'manage_facilities', tone: 'teal' },
  { to: '/operations', icon: 'bi-heart-pulse', titleKey: 'operations_title', descKey: 'operations_desc', linkKey: 'view_operation_requests', tone: 'rose' },
  { to: '/payment-methods', icon: 'bi-credit-card', titleKey: 'payment_methods_title', descKey: 'payment_methods_desc', linkKey: 'manage_payment_methods', tone: 'amber' },
  { to: '/appointment-stats', icon: 'bi-graph-up-arrow', titleKey: 'stats_title', descKey: 'stats_desc', linkKey: 'view_stats', tone: 'blue' },
  { to: '/reviews', icon: 'bi-star', titleKey: 'reviews_title', descKey: 'reviews_desc', linkKey: 'my_reviews', tone: 'green' },
  { to: '/blood-requests', icon: 'bi-droplet-fill', titleKey: 'blood_title', descKey: 'blood_desc', linkKey: 'blood_action', tone: 'red' },
];

export default function Dashboard() {
  const t = useT('dashboard');
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/dashboard')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your hospital dashboard right now.'));
  }

  useEffect(load, []);

  if (error || !data) {
    return (
      <div className="hospital-page hospital-dashboard-page">
        <QuickNav />
        {error ? (
          <StateCard
            icon="bi-exclamation-circle"
            title="Dashboard unavailable"
            message={error}
            onRetry={load}
            tone="is-error"
          />
        ) : (
          <StateCard icon="bi-hospital" title="Loading dashboard" message="Gathering today's hospital activity…" />
        )}
        <HospitalFooter />
      </div>
    );
  }

  const { hospital, stats, bed_availability: beds } = data;
  const occupancyPct = beds.total > 0 ? Math.round((beds.occupied / beds.total) * 100) : 0;

  return (
    <div className="hospital-page hospital-dashboard-page">
      <QuickNav />

      <section className="hospital-hero">
        <div>
          <span className="hospital-eyebrow">HOSPITAL WORKSPACE</span>
          <h1>{hospital.hospital_name}</h1>
          <p>
            {hospital.uid_tag} · {t('registration_no')} {hospital.registration_number}
            {hospital.city ? ` · ${hospital.city}` : ''}
          </p>
        </div>

        <div className="hospital-hero-occupancy">
          <span><i className="bi bi-hospital" aria-hidden="true"></i></span>
          <div>
            <strong>{occupancyPct}% occupied</strong>
            <small>{beds.occupied} of {beds.total} beds in use right now</small>
          </div>
        </div>
      </section>

      <section className="hospital-stats" aria-label="Hospital summary">
        <StatTile icon="bi-person-badge" value={stats.total_doctors.toLocaleString()} label={t('stat_total_doctors')} hint="Currently assigned" tone="indigo" />
        <StatTile icon="bi-people" value={stats.total_patients.toLocaleString()} label={t('stat_total_patients')} hint="Seen at this hospital" tone="blue" />
        <StatTile icon="bi-calendar2-check" value={stats.total_appointments.toLocaleString()} label={t('stat_total_appointments')} hint="All time" tone="teal" />
        <StatTile icon="bi-cash-coin" value={`৳${Number(stats.total_revenue).toLocaleString()}`} label={t('stat_total_revenue')} hint="Consultations + facilities" tone="green" />
      </section>

      <section className="hospital-layout">
        <div className="hospital-main">
          <section className="hospital-card hospital-chart-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-violet"><i className="bi bi-graph-up" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('overview_chart_title')}</h2>
                  <p>Appointments booked each month over the last six months.</p>
                </div>
              </div>
            </header>
            <LineSeries data={data.appointments_by_month} tone="violet" />
          </section>

          <section className="hospital-card hospital-chart-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-indigo"><i className="bi bi-people" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('overview_patients_series')}</h2>
                  <p>Distinct patients treated each month.</p>
                </div>
              </div>
            </header>
            <BarSeries data={data.patients_by_month} tone="indigo" />
          </section>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-teal"><i className="bi bi-diagram-3" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('department_overview_title')}</h2>
                  <p>Distinct patients per specialty at this hospital.</p>
                </div>
              </div>
            </header>

            {data.department_overview.length === 0 ? (
              <EmptyState icon="bi-diagram-3" title={t('no_department_data')} />
            ) : (
              <div className="hospital-rank-list">
                {data.department_overview.map((row, index) => {
                  const max = Math.max(...data.department_overview.map((d) => d.value), 1);
                  return (
                    <div className={`hospital-rank-row tone-${index % 4}`} key={row.label}>
                      <span className="hospital-rank-label">{row.label}</span>
                      <span className="hospital-rank-track">
                        <span style={{ width: `${Math.max(4, Math.round((row.value / max) * 100))}%` }}></span>
                      </span>
                      <strong>{row.formatted}</strong>
                    </div>
                  );
                })}
              </div>
            )}
          </section>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-blue"><i className="bi bi-calendar3" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('recent_appointments_title')}</h2>
                  <p>The latest bookings across your hospital.</p>
                </div>
              </div>
            </header>

            {data.recent_appointments.length === 0 ? (
              <EmptyState icon="bi-calendar3" title={t('no_recent_appointments')} />
            ) : (
              <div className="hospital-people-list">
                {data.recent_appointments.map((a) => (
                  <div className="hospital-person-row" key={a.appointment_id}>
                    <Avatar name={a.patient_name} photoUrl={a.patient_photo_url} />
                    <div>
                      <strong>{a.patient_name}</strong>
                      <small>Dr. {a.doctor_name} · {a.date_label}</small>
                    </div>
                    <span className={`hospital-badge is-${a.status}`}>{a.status_label}</span>
                  </div>
                ))}
              </div>
            )}
          </section>
        </div>

        <aside className="hospital-sidebar">
          <section className="hospital-card hospital-bed-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-rose"><i className="bi bi-hospital" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('bed_availability_title')}</h2>
                  <p>Live occupancy across your bed facilities.</p>
                </div>
              </div>
            </header>

            <div className="hospital-bed-meter" role="img" aria-label={`${occupancyPct}% of beds occupied`}>
              <span style={{ width: `${occupancyPct}%` }}></span>
            </div>

            <div className="hospital-bed-grid">
              <div><strong>{beds.total}</strong><small>{t('bed_total')}</small></div>
              <div><strong>{beds.occupied}</strong><small>{t('bed_occupied')}</small></div>
              <div><strong>{beds.available}</strong><small>{t('bed_available')}</small></div>
              <div><strong>{beds.icu}</strong><small>{t('bed_icu')}</small></div>
            </div>
          </section>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-amber"><i className="bi bi-activity" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('recent_activity_title')}</h2>
                </div>
              </div>
            </header>

            {data.recent_activity.length === 0 ? (
              <EmptyState icon="bi-activity" title={t('no_recent_activity')} />
            ) : (
              <ul className="hospital-activity-list">
                {data.recent_activity.map((item, index) => (
                  <li key={index}>
                    <i className={`bi ${item.icon}`} aria-hidden="true"></i>
                    <span>{item.message}</span>
                    <small>{item.time_label}</small>
                  </li>
                ))}
              </ul>
            )}
          </section>
        </aside>
      </section>

      <section className="hospital-tools" aria-label="Hospital tools">
        {TOOLS.map((tool) => (
          <article className={`hospital-tool-card is-${tool.tone}`} key={tool.to}>
            <span className="hospital-tool-icon"><i className={`bi ${tool.icon}`} aria-hidden="true"></i></span>
            <h2>{t(tool.titleKey)}</h2>
            <p>{t(tool.descKey)}</p>
            <Link className="hospital-tool-link" to={tool.to}>{t(tool.linkKey)}</Link>
          </article>
        ))}
      </section>

      <HospitalFooter />
    </div>
  );
}
