import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import QuickNav from '../components/QuickNav';
import { AdminFooter, StateCard, Card, EmptyState } from '../components/PageShell';
import { BarChart, LineChart } from '../components/Widgets';
import { useT } from '../i18n';

function StatTile({ icon, value, label, hint, tone }) {
  return (
    <article className={`admin-stat is-${tone}`}>
      <span className="admin-stat-icon"><i className={`bi ${icon}`} aria-hidden="true"></i></span>
      <div>
        <strong>{value}</strong>
        <span>{label}</span>
        {hint && <small>{hint}</small>}
      </div>
    </article>
  );
}

/**
 * The six other console pages. The newest (medicine drafts) has a title in
 * the dashboard lang files but no description or link caption, so that's
 * written here rather than left as a raw key.
 */
const TOOLS = [
  { to: '/users', icon: 'bi-search', titleKey: 'search_users_title', descKey: 'search_users_desc', linkKey: 'search_users', tone: 'indigo' },
  { to: '/transactions', icon: 'bi-cash-stack', titleKey: 'transactions_title', descKey: 'transactions_desc', linkKey: 'view_transactions', tone: 'green' },
  { to: '/doctor-verifications', icon: 'bi-patch-check', titleKey: 'verifications_title', descKey: 'verifications_desc', linkKey: 'review_certificates', tone: 'blue' },
  { to: '/reports', icon: 'bi-flag', titleKey: 'reports_title', descKey: 'reports_desc', linkKey: 'view_reports', tone: 'red' },
  { to: '/analytics', icon: 'bi-graph-up-arrow', titleKey: 'analytics_title', descKey: 'analytics_desc', linkKey: 'view_analytics', tone: 'rose' },
  { to: '/medicine-drafts', icon: 'bi-capsule', titleKey: 'medicine_drafts_title', desc: 'Read, edit and approve medicine information before any patient sees it.', link: 'Review drafts', tone: 'teal' },
];

/** One row of the live system-health list. */
function HealthRow({ icon, label, note, value, tone }) {
  return (
    <li>
      <i className={`bi ${icon}`} aria-hidden="true"></i>
      <span>
        {label}
        {note && <small className="admin-meta">{note}</small>}
      </span>
      <span className={`admin-badge is-${tone}`}>{value}</span>
    </li>
  );
}

export default function Dashboard() {
  const t = useT('dashboard');
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/dashboard')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load the admin dashboard right now.'));
  }

  useEffect(load, []);

  if (error || !data) {
    return (
      <div className="admin-page admin-dashboard-page">
        <QuickNav />
        {error
          ? <StateCard icon="bi-exclamation-circle" title="Dashboard unavailable" message={error} onRetry={load} tone="is-error" />
          : <StateCard icon="bi-speedometer2" title="Loading dashboard" message="Checking the platform…" />}
        <AdminFooter />
      </div>
    );
  }

  const { admin, counts, charts, recent_activity: activity, system_health: health } = data;

  return (
    <div className="admin-page admin-dashboard-page">
      <QuickNav />

      <section className="admin-hero">
        <div>
          <span className="admin-eyebrow">ADMIN CONSOLE</span>
          <h1>{t('welcome', { name: admin.full_name })}</h1>
          <p>{admin.uid_tag} · {admin.email}</p>
        </div>

        <div className="admin-hero-occupancy">
          <span><i className="bi bi-patch-check" aria-hidden="true"></i></span>
          <div>
            <strong>{counts.pending_doctor_verifications} awaiting review</strong>
            <small>Doctor certificates</small>
          </div>
        </div>
      </section>

      <section className="admin-stats" aria-label="Platform totals">
        <StatTile icon="bi-people" value={counts.patients.toLocaleString()} label={t('patients')} tone="teal" />
        <StatTile
          icon="bi-person-badge"
          value={counts.doctors.toLocaleString()}
          label={t('doctors')}
          hint={`${counts.pending_doctor_verifications} ${t('pending_verification')}`}
          tone="blue"
        />
        <StatTile icon="bi-building" value={counts.hospitals.toLocaleString()} label={t('hospitals')} tone="violet" />
        <StatTile icon="bi-capsule" value={counts.pharmacies.toLocaleString()} label={t('pharmacies')} tone="green" />
        <StatTile icon="bi-truck" value={counts.delivery_agents.toLocaleString()} label={t('delivery_agents')} tone="amber" />
      </section>

      <div className="admin-layout">
        <div className="admin-main">
          {/* The Blade page drew these two as one two-series line chart —
              restored as lines (kept as two cards rather than merged back
              into one dual-series chart, so each keeps its own scale).
              Appointments stays a bar chart: a monthly count, not a trend
              the original page framed as a line either. */}
          <Card icon="bi-graph-up-arrow" tone="is-indigo" title={t('system_overview_title')} subtitle="New registrations, all roles, per month.">
            <LineChart rows={charts.registrations} tone="indigo" />
          </Card>

          <Card icon="bi-calendar2-check" tone="is-blue" title={t('overview_appointments_series')} subtitle="Appointments booked per month.">
            <BarChart rows={charts.appointments} tone="teal" />
          </Card>

          <Card icon="bi-cash-coin" tone="is-green" title={t('revenue_overview_title')} subtitle="Completed payments per month.">
            <LineChart rows={charts.revenue} tone="green" />
          </Card>
        </div>

        <aside className="admin-sidebar">
          <Card icon="bi-activity" tone="is-rose" title={t('recent_activity_title')} subtitle="Registrations and completed payments, newest first.">
            {activity.length === 0 ? (
              <EmptyState icon="bi-clock-history" title={t('no_recent_activity')} />
            ) : (
              <ul className="admin-activity-list">
                {activity.map((item, index) => (
                  <li key={`${item.message}-${index}`}>
                    <i className={`bi ${item.icon}`} aria-hidden="true"></i>
                    <span>{item.message}</span>
                    <small>{item.when}</small>
                  </li>
                ))}
              </ul>
            )}
          </Card>

          {/* Every figure below is live-checked server-side — the DB is
              actually pinged and the disk actually read — so these badges
              mean something rather than being decoration. */}
          <Card icon="bi-hdd-network" tone="is-teal" title={t('system_health_title')} subtitle="Checked when this page loaded.">
            <ul className="admin-activity-list">
              <HealthRow
                icon="bi-hdd-network"
                label={t('health_server_label')}
                value={t('health_server_value')}
                tone="completed"
              />
              <HealthRow
                icon="bi-database"
                label={t('health_database_label')}
                value={health.db_ok ? t('health_database_ok', { size: health.db_size_mb.toFixed(1) }) : t('health_database_down')}
                tone={health.db_ok ? 'completed' : 'rejected'}
              />
              <HealthRow
                icon="bi-hdd"
                label={t('health_storage_label')}
                note={t('health_storage_free', { free: health.storage_free_gb })}
                value={`${health.storage_used_percent}%`}
                tone={health.storage_used_percent >= 90 ? 'rejected' : 'dismissed'}
              />
              <HealthRow
                icon="bi-exclamation-octagon"
                label={t('health_failed_jobs_label')}
                value={health.failed_jobs}
                tone={health.failed_jobs > 0 ? 'rejected' : 'completed'}
              />
            </ul>
          </Card>
        </aside>
      </div>

      <section className="admin-tools" aria-label="Admin tools">
        {TOOLS.map((tool) => (
          <article className={`admin-tool-card is-${tool.tone}`} key={tool.to}>
            <span className="admin-tool-icon"><i className={`bi ${tool.icon}`} aria-hidden="true"></i></span>
            <h2>{t(tool.titleKey)}</h2>
            {/* verifications_desc carries a :count placeholder — passing the
                live number keeps the card honest instead of printing the
                placeholder verbatim. */}
            <p>{tool.descKey ? t(tool.descKey, { count: counts.pending_doctor_verifications }) : tool.desc}</p>
            <Link className="admin-tool-link" to={tool.to}>{tool.linkKey ? t(tool.linkKey) : tool.link}</Link>
          </article>
        ))}
      </section>

      <AdminFooter />
    </div>
  );
}
