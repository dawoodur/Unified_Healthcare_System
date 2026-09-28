import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import QuickNav from '../components/QuickNav';
import { DeliveryFooter, StateCard } from '../components/PageShell';
import { useT } from '../i18n';

function StatTile({ icon, value, label, hint, tone }) {
  return (
    <article className={`delivery-stat is-${tone}`}>
      <span className="delivery-stat-icon"><i className={`bi ${icon}`} aria-hidden="true"></i></span>
      <div>
        <strong>{value}</strong>
        <span>{label}</span>
        <small>{hint}</small>
      </div>
    </article>
  );
}

const TOOLS = [
  { to: '/available', icon: 'bi-truck', titleKey: 'available_title', descKey: 'available_desc', linkKey: 'see_available', tone: 'orange' },
  { to: '/my-deliveries', icon: 'bi-box-seam', titleKey: 'deliveries_title', descKey: 'deliveries_desc', linkKey: 'my_deliveries', tone: 'blue' },
  { to: '/reviews', icon: 'bi-star', titleKey: 'reviews_title', descKey: 'reviews_desc', linkKey: 'my_reviews', tone: 'amber' },
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
      .catch(() => setError('Could not load your delivery dashboard right now.'));
  }

  useEffect(load, []);

  if (error || !data) {
    return (
      <div className="delivery-page delivery-dashboard-page">
        <QuickNav />
        {error
          ? <StateCard icon="bi-exclamation-circle" title="Dashboard unavailable" message={error} onRetry={load} tone="is-error" />
          : <StateCard icon="bi-truck" title="Loading dashboard" message="Checking today's runs…" />}
        <DeliveryFooter />
      </div>
    );
  }

  const { agent, stats } = data;

  return (
    <div className="delivery-page delivery-dashboard-page">
      <QuickNav />

      <section className="delivery-hero">
        <div>
          <span className="delivery-eyebrow">DELIVERY WORKSPACE</span>
          <h1>{t('welcome', { name: agent.full_name })}</h1>
          <p>
            {agent.uid_tag} · {t('age')} {agent.age} · {agent.gender_label} · {t('blood_group')} {agent.blood_group}
          </p>
        </div>

        <div className="delivery-hero-active">
          <span><i className="bi bi-box-seam" aria-hidden="true"></i></span>
          <div>
            <strong>{stats.active} on the road</strong>
            <small>{stats.available} waiting to be claimed</small>
          </div>
        </div>
      </section>

      <section className="delivery-stats" aria-label="Delivery summary">
        <StatTile icon="bi-truck" value={stats.available.toLocaleString()} label="Available now" hint="Unclaimed orders" tone="orange" />
        <StatTile icon="bi-box-seam" value={stats.active.toLocaleString()} label="Out for delivery" hint="Assigned to you" tone="blue" />
        <StatTile icon="bi-check2-circle" value={stats.delivered.toLocaleString()} label="Delivered" hint="All time" tone="green" />
        <StatTile icon="bi-cash-coin" value={`৳${Number(stats.delivered_value).toLocaleString()}`} label="Value delivered" hint="Across completed runs" tone="amber" />
      </section>

      <section className="delivery-tools" aria-label="Delivery tools">
        {TOOLS.map((tool) => (
          <article className={`delivery-tool-card is-${tool.tone}`} key={tool.to}>
            <span className="delivery-tool-icon"><i className={`bi ${tool.icon}`} aria-hidden="true"></i></span>
            <h2>{t(tool.titleKey)}</h2>
            <p>{t(tool.descKey)}</p>
            <Link className="delivery-tool-link" to={tool.to}>{t(tool.linkKey)}</Link>
          </article>
        ))}
      </section>

      <DeliveryFooter />
    </div>
  );
}
