import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const LINKS = [
  { to: '/dashboard', icon: 'bi-house-door', label: 'Dashboard' },
  { to: '/available', icon: 'bi-truck', label: 'Available' },
  { to: '/my-deliveries', icon: 'bi-box-seam', label: 'My deliveries' },
  { to: '/reviews', icon: 'bi-star', label: 'Reviews' },
];

export default function QuickNav() {
  const location = useLocation();

  return (
    <nav className="delivery-workspace-nav" aria-label="Delivery workspace navigation">
      {LINKS.map((item) => (
        <Link key={item.to} to={item.to} className={location.pathname === item.to ? 'is-active' : ''}>
          <i className={`bi ${item.icon}`} aria-hidden="true"></i>
          <span>{item.label}</span>
        </Link>
      ))}
    </nav>
  );
}
