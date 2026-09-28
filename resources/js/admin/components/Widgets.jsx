import React from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { EmptyState } from './PageShell';

/**
 * Vertical bars, same shape as the hospital and doctor sections use — plain
 * CSS, no charting library, so the whole platform keeps one look and one
 * dependency list. Rows are MonthlySeries-shaped: { label, value, formatted }.
 */
export function BarChart({ rows, tone = '' }) {
  const max = Math.max(...(rows || []).map((r) => Number(r.value) || 0), 0);

  if (!rows || rows.length === 0 || max === 0) {
    return <EmptyState icon="bi-bar-chart" title="No data for this period yet" />;
  }

  return (
    <div className={`admin-bars ${tone ? `is-${tone}` : ''}`}>
      {rows.map((item) => {
        const value = Number(item.value || 0);
        const height = value === 0 ? 4 : Math.max(12, Math.round((value / max) * 100));

        return (
          <div className="admin-bar-column" key={item.label}>
            <div className="admin-bar-value">{item.formatted ?? value.toLocaleString()}</div>
            <div className="admin-bar-track"><span style={{ height: `${height}%` }}></span></div>
            <small>{item.label}</small>
          </div>
        );
      })}
    </div>
  );
}

/**
 * A trend over time (e.g. "revenue per month") reading as six independent
 * bars implies six unrelated totals rather than one line moving up and
 * down — this is the same rows shape as BarChart, just drawn as a
 * connected line instead, for whichever chart's own title/icon says "trend"
 * rather than "per month total".
 */
export function LineChart({ rows, tone = '' }) {
  const values = (rows || []).map((r) => Number(r.value) || 0);
  const max = Math.max(...values, 0);

  if (!rows || rows.length === 0 || max === 0) {
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
    <div className={`admin-line ${tone ? `is-${tone}` : ''}`}>
      <div className="admin-line-svg-wrap">
        <svg viewBox={`0 0 ${width} ${height}`} preserveAspectRatio="none" aria-hidden="true">
          <polygon points={areaPoints} className="admin-line-area" />
          <polyline points={linePoints} className="admin-line-path" />
          {points.map((p, i) => <circle key={rows[i].label} cx={p.x} cy={p.y} r="4.5" className="admin-line-dot" />)}
        </svg>
      </div>
      <div className="admin-line-labels">
        {rows.map((item) => (
          <div className="admin-line-label-col" key={item.label}>
            <div className="admin-line-value">{item.formatted ?? Number(item.value || 0).toLocaleString()}</div>
            <small>{item.label}</small>
          </div>
        ))}
      </div>
    </div>
  );
}

const DONUT_TONES = ['#334155', '#4f46e5', '#0d9488', '#be185d'];

/** Same conic-gradient donut used on the doctor side — a category breakdown (e.g. "orders by status") is a proportion of a whole, which a bar-per-status doesn't actually show. */
export function DonutChart({ rows, totalLabel }) {
  const segments = (rows || []).map((r, i) => ({ label: r.label, value: Number(r.value) || 0, color: DONUT_TONES[i % DONUT_TONES.length] }));
  const total = segments.reduce((sum, s) => sum + s.value, 0);

  if (total === 0) {
    return <EmptyState icon="bi-pie-chart" title="No data for this period yet" />;
  }

  let cursor = 0;
  const stops = segments.map((s) => {
    const slice = (s.value / total) * 360;
    const stop = `${s.color} ${cursor}deg ${cursor + slice}deg`;
    cursor += slice;
    return stop;
  }).join(', ');

  return (
    <div className="admin-donut-wrap">
      <div className="admin-donut" style={{ background: `conic-gradient(${stops})` }}>
        <div className="admin-donut-hole">
          <strong>{total.toLocaleString()}</strong>
          <span>{totalLabel}</span>
        </div>
      </div>
      <ul className="admin-donut-legend">
        {segments.map((s) => (
          <li key={s.label}>
            <span className="admin-donut-legend-dot" style={{ background: s.color }}></span>
            {s.label}
            <strong>{Math.round((s.value / total) * 100)}%</strong>
          </li>
        ))}
      </ul>
    </div>
  );
}

/** Horizontal ranked rows — for lists where the label matters more than the shape. */
export function RankList({ rows }) {
  const max = Math.max(...(rows || []).map((r) => Number(r.value) || 0), 0);

  if (!rows || rows.length === 0 || max === 0) {
    return <EmptyState icon="bi-bar-chart" title="Nothing to rank yet" />;
  }

  return (
    <div className="admin-rank-list">
      {rows.map((item, index) => (
        <div className={`admin-rank-row tone-${index % 4}`} key={item.label}>
          <span className="admin-rank-label">{item.label}</span>
          <span className="admin-rank-track">
            <span style={{ width: `${Math.max(4, Math.round((Number(item.value) / max) * 100))}%` }}></span>
          </span>
          <strong>{item.formatted ?? item.value}</strong>
        </div>
      ))}
    </div>
  );
}

/**
 * The status filter shared by transactions, reports, chat training and
 * medicine drafts. Each option is a real link carrying ?status=, so a
 * filtered view can be bookmarked and the back button behaves — which the
 * old self-submitting <select> did not manage.
 */
export function StatusFilters({ options, active, counts }) {
  const [params] = useSearchParams();

  function href(value) {
    const next = new URLSearchParams(params);
    if (value === null) {
      next.delete('status');
    } else {
      next.set('status', value);
    }
    // A different filter is a different result set, so never keep the page.
    next.delete('page');
    const query = next.toString();
    return query ? `?${query}` : '';
  }

  return (
    <div className="admin-filters">
      {options.map((option) => (
        <Link
          key={option.value ?? 'all'}
          to={href(option.value)}
          className={`admin-filter ${(option.value ?? null) === (active ?? null) ? 'is-active' : ''}`}
        >
          {option.label}
          {counts && option.value !== null && (
            <span className="admin-filter-count">{counts[option.value] ?? 0}</span>
          )}
        </Link>
      ))}
    </div>
  );
}

/** Prev / Next over a server-paginated queue. */
export function Pager({ pagination }) {
  const [params] = useSearchParams();

  if (!pagination || pagination.last_page <= 1) {
    return null;
  }

  function href(page) {
    const next = new URLSearchParams(params);
    next.set('page', String(page));
    return `?${next.toString()}`;
  }

  const { current_page: current, last_page: last, total } = pagination;

  return (
    <div className="admin-pager">
      {current > 1 ? (
        <Link className="admin-btn is-ghost" to={href(current - 1)}>
          <i className="bi bi-chevron-left" aria-hidden="true"></i> Previous
        </Link>
      ) : (
        <button type="button" className="admin-btn is-ghost" disabled>
          <i className="bi bi-chevron-left" aria-hidden="true"></i> Previous
        </button>
      )}

      <span className="admin-pager-status">Page {current} of {last} · {total} total</span>

      {current < last ? (
        <Link className="admin-btn is-ghost" to={href(current + 1)}>
          Next <i className="bi bi-chevron-right" aria-hidden="true"></i>
        </Link>
      ) : (
        <button type="button" className="admin-btn is-ghost" disabled>
          Next <i className="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
      )}
    </div>
  );
}

/** Yes/No and status pills, so the same word always gets the same colour. */
export function Badge({ status, children }) {
  return <span className={`admin-badge is-${status}`}>{children}</span>;
}

export function YesNo({ value, yes = 'Yes', no = 'No' }) {
  return <span className={`admin-badge is-${value ? 'completed' : 'pending'}`}>{value ? yes : no}</span>;
}

/** Absolute URL to a Laravel web route, for the plain form posts. */
export function webUrl(path) {
  return `${window.ADMIN_APP_BASE}${path}`;
}
