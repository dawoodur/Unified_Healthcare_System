import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const LINKS = [
  { to: '/dashboard', icon: 'bi-house-door', label: 'Dashboard' },
  { to: '/doctors', icon: 'bi-person-badge', label: 'Doctors' },
  { to: '/facilities', icon: 'bi-building', label: 'Facilities' },
  { to: '/facility-bookings', icon: 'bi-calendar2-check', label: 'Bookings' },
  { to: '/operations', icon: 'bi-heart-pulse', label: 'Operations' },
  { to: '/blood-requests', icon: 'bi-droplet-fill', label: 'Blood' },
  { to: '/appointment-stats', icon: 'bi-graph-up-arrow', label: 'Stats' },
  { to: '/reviews', icon: 'bi-star', label: 'Reviews' },
];

export default function QuickNav() {
  const location = useLocation();

  return (
    <nav className="hospital-workspace-nav" aria-label="Hospital workspace navigation">
      {LINKS.map((item) => {
        // Blood donations live under the same nav entry as blood requests —
        // they are two views of one job, so the tab stays lit across both.
        const isActive = location.pathname === item.to
          || (item.to === '/blood-requests' && location.pathname === '/blood-donations');

        return (
          <Link key={item.to} to={item.to} className={isActive ? 'is-active' : ''}>
            <i className={`bi ${item.icon}`} aria-hidden="true"></i>
            <span>{item.label}</span>
          </Link>
        );
      })}
    </nav>
  );
}
