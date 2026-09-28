import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const LINKS = [
  { to: '/dashboard', icon: 'bi-house-door', label: 'Dashboard' },
  { to: '/inventory', icon: 'bi-box-seam', label: 'Inventory' },
  { to: '/orders', icon: 'bi-bag-check', label: 'Orders' },
  { to: '/reviews', icon: 'bi-star', label: 'Reviews' },
];

export default function QuickNav() {
  const location = useLocation();

  return (
    <nav className="pharmacy-workspace-nav" aria-label="Pharmacy workspace navigation">
      {LINKS.map((item) => (
        <Link key={item.to} to={item.to} className={location.pathname === item.to ? 'is-active' : ''}>
          <i className={`bi ${item.icon}`} aria-hidden="true"></i>
          <span>{item.label}</span>
        </Link>
      ))}
    </nav>
  );
}
