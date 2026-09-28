import React from 'react';
import QuickNav from './QuickNav';

export function PageHeader({ icon, title, subtitle, children }) {
  return (
    <div className="admin-page-header">
      <div className="admin-page-header-icon"><i className={`bi ${icon}`} aria-hidden="true"></i></div>
      <div className="admin-page-header-copy">
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
      {children && <div className="admin-page-header-actions">{children}</div>}
    </div>
  );
}

export function AdminFooter() {
  return (
    <footer className="patient-dashboard-footer">
      <div className="patient-dashboard-footer-brand">
        <span className="patient-dashboard-footer-dot" aria-hidden="true"></span>
        <div>
          <strong>Telemedicine Platform</strong>
          <small>Connected Healthcare</small>
        </div>
      </div>
      <p>Secure, connected care across your healthcare journey.</p>
    </footer>
  );
}

export function StateCard({ icon, title, message, onRetry, tone = '' }) {
  return (
    <div className={`admin-state admin-card ${tone}`} role={onRetry ? 'alert' : undefined}>
      <span><i className={`bi ${icon}`} aria-hidden="true"></i></span>
      <div>
        <strong>{title}</strong>
        {message && <p>{message}</p>}
        {onRetry && <button type="button" className="btn" onClick={onRetry}>Try again</button>}
      </div>
    </div>
  );
}

export function EmptyState({ icon, title, message }) {
  return (
    <div className="admin-empty">
      <i className={`bi ${icon}`} aria-hidden="true"></i>
      <strong>{title}</strong>
      {message && <span>{message}</span>}
    </div>
  );
}

/** A card with the standard icon + heading + subheading header. */
export function Card({ icon, tone = '', title, subtitle, actions, children }) {
  return (
    <section className="admin-card">
      <header className="admin-card-header">
        <div>
          <span className={`admin-card-icon ${tone}`}><i className={`bi ${icon}`} aria-hidden="true"></i></span>
          <div>
            <h2>{title}</h2>
            {subtitle && <p>{subtitle}</p>}
          </div>
        </div>
        {actions}
      </header>
      {children}
    </section>
  );
}

export default function PageShell({
  pageClass,
  icon,
  title,
  subtitle,
  actions,
  loading,
  error,
  onRetry,
  loadingTitle = 'Loading',
  loadingMessage = 'Fetching the latest from the platform…',
  children,
}) {
  return (
    <div className={`admin-page ${pageClass || ''}`}>
      <QuickNav />
      <PageHeader icon={icon} title={title} subtitle={subtitle}>{actions}</PageHeader>

      {error ? (
        <StateCard
          icon="bi-exclamation-circle"
          title={`${title} unavailable`}
          message={error}
          onRetry={onRetry}
          tone="is-error"
        />
      ) : loading ? (
        <StateCard icon={icon} title={loadingTitle} message={loadingMessage} />
      ) : (
        children
      )}

      <AdminFooter />
    </div>
  );
}
