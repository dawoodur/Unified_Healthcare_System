import React from 'react';

// Plain React port of resources/views/partials/donut-chart.blade.php's
// conic-gradient CSS trick — same "hand-rolled, no charting library" spirit.
// segments: [{ label, value, color }]
export default function DonutChart({ segments, totalLabel }) {
  const total = segments.reduce((sum, s) => sum + s.value, 0);
  let cursor = 0;
  const stops = total > 0
    ? segments.map((s) => {
        const slice = (s.value / total) * 360;
        const stop = `${s.color} ${cursor}deg ${cursor + slice}deg`;
        cursor += slice;
        return stop;
      }).join(', ')
    : 'var(--bs-border-color) 0deg 360deg';

  return (
    <div className="donut-chart-wrap">
      <div className="donut-chart" style={{ background: `conic-gradient(${stops})` }}>
        <div className="donut-chart-hole">
          <strong>{total}</strong>
          <span className="muted">{totalLabel}</span>
        </div>
      </div>
      <ul className="donut-legend">
        {segments.map((s) => (
          <li key={s.label}>
            <span className="donut-legend-dot" style={{ background: s.color }}></span>
            {s.label}
            <strong>{total > 0 ? Math.round((s.value / total) * 100) : 0}%</strong>
          </li>
        ))}
      </ul>
    </div>
  );
}
