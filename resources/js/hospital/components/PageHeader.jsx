import React from 'react';

// Compact version of the Dashboard hero — icon chip + title + subtitle —
// used at the top of every hospital page so they all carry the same
// designed look, not just the Dashboard. Mirrors the doctor PageHeader.
export default function PageHeader({ icon, title, subtitle, children }) {
  return (
    <div className="hospital-page-header">
      <div className="hospital-page-header-icon"><i className={`bi ${icon}`} aria-hidden="true"></i></div>
      <div className="hospital-page-header-copy">
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
      {children && <div className="hospital-page-header-actions">{children}</div>}
    </div>
  );
}
