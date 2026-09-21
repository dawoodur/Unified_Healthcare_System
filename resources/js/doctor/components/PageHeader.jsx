import React from 'react';

// Compact version of the Dashboard hero — icon chip + title + subtitle —
// used at the top of Appointments/Availability/Reviews so every doctor
// page carries the same designed look, not just the Dashboard.
export default function PageHeader({ icon, title, subtitle, children }) {
  return (
    <div className="doctor-page-header">
      <div className="doctor-page-header-icon"><i className={`bi ${icon}`} aria-hidden="true"></i></div>
      <div className="doctor-page-header-copy">
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
      {children && <div className="doctor-page-header-actions">{children}</div>}
    </div>
  );
}
