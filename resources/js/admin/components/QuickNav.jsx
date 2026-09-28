import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const LINKS = [
  { to: '/dashboard', icon: 'bi-speedometer2', label: 'Dashboard' },
  { to: '/users', icon: 'bi-search', label: 'Users' },
  { to: '/transactions', icon: 'bi-cash-stack', label: 'Transactions' },
  { to: '/doctor-verifications', icon: 'bi-patch-check', label: 'Verifications' },
  { to: '/reports', icon: 'bi-flag', label: 'Reports' },
  { to: '/analytics', icon: 'bi-graph-up-arrow', label: 'Analytics' },
  { to: '/medicine-drafts', icon: 'bi-capsule', label: 'Drafts' },
  { to: '/scanned-prescriptions', icon: 'bi-camera', label: 'Scans' },
  { to: '/inbox-history', icon: 'bi-clock-history', label: 'Inbox history' },
  { to: '/consultation-chat-history', icon: 'bi-camera-video', label: 'Call chats' },
];

export default function QuickNav() {
  const location = useLocation();

  return (
    <nav className="admin-workspace-nav" aria-label="Admin console navigation">
      {LINKS.map((item) => (
        <Link key={item.to} to={item.to} className={location.pathname === item.to ? 'is-active' : ''}>
          <i className={`bi ${item.icon}`} aria-hidden="true"></i>
          <span>{item.label}</span>
        </Link>
      ))}
    </nav>
  );
}
