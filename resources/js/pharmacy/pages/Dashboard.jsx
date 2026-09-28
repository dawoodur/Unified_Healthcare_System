import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import QuickNav from '../components/QuickNav';
import { PharmacyFooter, StateCard, EmptyState } from '../components/PageShell';
import { useT } from '../i18n';

// A stock breakdown (in stock / low / out) is a proportion of a whole —
// this card's own icon has always said "pie chart", but it was rendered
// as horizontal bars. Same colors the bars used (tone-0/1/2 = green/amber/
// red), just drawn as a real donut.
const STOCK_TONES = ['#15803d', '#b45309', '#dc2626'];

function StockDonut({ rows }) {
  const total = rows.reduce((sum, r) => sum + r.value, 0);
  if (total === 0) {
    return <EmptyState icon="bi-box" title="No stock data yet" />;
  }

  let cursor = 0;
  const stops = rows.map((r, i) => {
    const slice = (r.value / total) * 360;
    const stop = `${STOCK_TONES[i % STOCK_TONES.length]} ${cursor}deg ${cursor + slice}deg`;
    cursor += slice;
    return stop;
  }).join(', ');

  return (
    <div className="pharmacy-donut-wrap">
      <div className="pharmacy-donut" style={{ background: `conic-gradient(${stops})` }}>
        <div className="pharmacy-donut-hole">
          <strong>{total.toLocaleString()}</strong>
          <span>batches</span>
        </div>
      </div>
      <ul className="pharmacy-donut-legend">
        {rows.map((r, i) => (
          <li key={r.label}>
            <span className="pharmacy-donut-legend-dot" style={{ background: STOCK_TONES[i % STOCK_TONES.length] }}></span>
            {r.label}
            <strong>{Math.round((r.value / total) * 100)}%</strong>
          </li>
        ))}
      </ul>
    </div>
  );
}

function StatTile({ icon, value, label, hint, tone }) {
  return (
    <article className={`pharmacy-stat is-${tone}`}>
      <span className="pharmacy-stat-icon"><i className={`bi ${icon}`} aria-hidden="true"></i></span>
      <div>
        <strong>{value}</strong>
        <span>{label}</span>
        <small>{hint}</small>
      </div>
    </article>
  );
}

const TOOLS = [
  { to: '/inventory', icon: 'bi-box-seam', titleKey: 'inventory_title', descKey: 'inventory_desc', linkKey: 'manage_inventory', tone: 'green' },
  { to: '/orders', icon: 'bi-bag-check', titleKey: 'orders_title', descKey: 'orders_desc', linkKey: 'view_orders', tone: 'blue' },
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
      .catch(() => setError('Could not load your pharmacy dashboard right now.'));
  }

  useEffect(load, []);

  if (error || !data) {
    return (
      <div className="pharmacy-page pharmacy-dashboard-page">
        <QuickNav />
        {error
          ? <StateCard icon="bi-exclamation-circle" title="Dashboard unavailable" message={error} onRetry={load} tone="is-error" />
          : <StateCard icon="bi-capsule" title="Loading dashboard" message="Gathering today's stock and orders…" />}
        <PharmacyFooter />
      </div>
    );
  }

  const { pharmacy, stats, stock_overview: stock } = data;
  const stockTotal = stock.in_stock + stock.low_stock + stock.out_of_stock;

  return (
    <div className="pharmacy-page pharmacy-dashboard-page">
      <QuickNav />

      <section className="pharmacy-hero">
        <div>
          <span className="pharmacy-eyebrow">PHARMACY WORKSPACE</span>
          <h1>{pharmacy.pharmacy_name}</h1>
          <p>
            {pharmacy.uid_tag}
            {pharmacy.etin_number ? ` · ${t('etin')} ${pharmacy.etin_number}` : ''}
            {pharmacy.address ? ` · ${pharmacy.address}` : ''}
          </p>
        </div>

        <div className="pharmacy-hero-stock">
          <span><i className="bi bi-capsule" aria-hidden="true"></i></span>
          <div>
            <strong>{stats.total_medicines} medicines</strong>
            <small>{stock.low_stock} running low · {stock.out_of_stock} out of stock</small>
          </div>
        </div>
      </section>

      <section className="pharmacy-stats" aria-label="Pharmacy summary">
        <StatTile icon="bi-capsule" value={stats.total_medicines.toLocaleString()} label={t('stat_total_medicines')} hint="Distinct medicines stocked" tone="green" />
        <StatTile icon="bi-exclamation-triangle" value={stats.low_stock.toLocaleString()} label={t('stat_low_stock')} hint={`At or below ${stock.threshold} units`} tone="amber" />
        <StatTile icon="bi-bag-check" value={stats.today_orders.toLocaleString()} label={t('stat_today_orders')} hint="Placed today" tone="blue" />
        <StatTile icon="bi-cash-coin" value={`৳${Number(stats.today_sales).toLocaleString()}`} label={t('stat_today_sales')} hint="Excluding cancelled" tone="teal" />
      </section>

      <section className="pharmacy-layout">
        <div className="pharmacy-main">
          <section className="pharmacy-card">
            <header className="pharmacy-card-header">
              <div>
                <span className="pharmacy-card-icon is-green"><i className="bi bi-pie-chart" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('stock_overview_title')}</h2>
                  <p>How your {stockTotal} stocked {stockTotal === 1 ? 'batch' : 'batches'} break down.</p>
                </div>
              </div>
            </header>

            <StockDonut
              rows={[
                { label: t('stock_in_stock'), value: stock.in_stock },
                { label: t('stock_low_stock'), value: stock.low_stock },
                { label: t('stock_out_of_stock'), value: stock.out_of_stock },
              ]}
            />
          </section>

          <section className="pharmacy-card">
            <header className="pharmacy-card-header">
              <div>
                <span className="pharmacy-card-icon is-blue"><i className="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('top_selling_title')}</h2>
                  <p>Most-ordered medicines from your pharmacy.</p>
                </div>
              </div>
            </header>

            {data.top_medicines.length === 0 ? (
              <EmptyState icon="bi-graph-up" title={t('no_top_selling')} />
            ) : (
              <div className="pharmacy-rank-list">
                {data.top_medicines.map((row, index) => {
                  const max = Math.max(...data.top_medicines.map((m) => m.value), 1);
                  return (
                    <div className={`pharmacy-rank-row tone-${index % 4}`} key={row.label}>
                      <span className="pharmacy-rank-label">{row.label}</span>
                      <span className="pharmacy-rank-track">
                        <span style={{ width: `${Math.max(4, Math.round((row.value / max) * 100))}%` }}></span>
                      </span>
                      <strong>{row.formatted}</strong>
                    </div>
                  );
                })}
              </div>
            )}
          </section>
        </div>

        <aside className="pharmacy-sidebar">
          <section className="pharmacy-card">
            <header className="pharmacy-card-header">
              <div>
                <span className="pharmacy-card-icon is-red"><i className="bi bi-calendar-x" aria-hidden="true"></i></span>
                <div>
                  <h2>{t('expiring_title')}</h2>
                  <p>Expired or expiring within 30 days.</p>
                </div>
              </div>
            </header>

            {data.expiring.length === 0 ? (
              <EmptyState icon="bi-check2-circle" title={t('no_expiring')} />
            ) : (
              <div className="pharmacy-people-list">
                {data.expiring.map((row) => (
                  <div className="pharmacy-person-row" key={row.stock_id}>
                    <span className={`pharmacy-chip-icon ${row.is_expired ? 'is-expired' : 'is-soon'}`}>
                      <i className={`bi ${row.is_expired ? 'bi-x-octagon' : 'bi-hourglass-split'}`} aria-hidden="true"></i>
                    </span>
                    <div>
                      <strong>{row.medicine}</strong>
                      <small>
                        {t('col_batch')} {row.batch_no} · {row.expiry_label} · {row.quantity} left
                      </small>
                    </div>
                    <span className={`pharmacy-badge ${row.is_expired ? 'is-expired' : 'is-soon'}`}>
                      {row.is_expired ? t('expiring_status_expired') : t('expiring_status_soon')}
                    </span>
                  </div>
                ))}
              </div>
            )}
          </section>
        </aside>
      </section>

      <section className="pharmacy-tools" aria-label="Pharmacy tools">
        {TOOLS.map((tool) => (
          <article className={`pharmacy-tool-card is-${tool.tone}`} key={tool.to}>
            <span className="pharmacy-tool-icon"><i className={`bi ${tool.icon}`} aria-hidden="true"></i></span>
            <h2>{t(tool.titleKey)}</h2>
            <p>{t(tool.descKey)}</p>
            <Link className="pharmacy-tool-link" to={tool.to}>{t(tool.linkKey)}</Link>
          </article>
        ))}
      </section>

      <PharmacyFooter />
    </div>
  );
}
