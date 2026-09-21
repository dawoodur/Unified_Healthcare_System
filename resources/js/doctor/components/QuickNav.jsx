import React from 'react';
import { Link, useLocation } from 'react-router-dom';

function ext(path) {
  return `${window.DOCTOR_APP_BASE}${path}`;
}

const LINKS = [
  { type: 'link', to: '/dashboard', icon: 'bi-house-door', label: 'Dashboard' },
  { type: 'link', to: '/appointments', icon: 'bi-calendar2-check', label: 'Appointments' },
  { type: 'link', to: '/availability', icon: 'bi-calendar-week', label: 'Availability' },
  { type: 'link', to: '/records', icon: 'bi-folder2-open', label: 'Records' },
  { type: 'link', to: '/inbox', icon: 'bi-chat-dots', label: 'Inbox' },
  { type: 'link', to: '/reviews', icon: 'bi-star', label: 'Reviews' },
  { type: 'link', to: '/analytics', icon: 'bi-graph-up-arrow', label: 'Analytics' },
];

export default function QuickNav() {
  const location = useLocation();

  return (
    <nav className="doctor-dashboard-workspace-nav" aria-label="Doctor workspace navigation">
      {LINKS.map((item) => {
        const content = (
          <>
            <i className={`bi ${item.icon}`} aria-hidden="true"></i>
            <span>{item.label}</span>
          </>
        );

        if (item.type === 'link') {
          const isActive = location.pathname === item.to
            || (item.to === '/appointments' && (location.pathname.startsWith('/appointments/') || location.pathname.startsWith('/consultation/')))
            || (item.to === '/availability' && location.pathname === '/leave');
          return (
            <Link key={item.label} to={item.to} className={isActive ? 'is-active' : ''}>
              {content}
            </Link>
          );
        }

        return (
          <a key={item.label} href={item.href}>
            {content}
          </a>
        );
      })}
    </nav>
  );
}
