import React from 'react';
import QuickNav from './QuickNav';
import PageHeader from './PageHeader';

export function HospitalFooter() {
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

/**
 * The loading / error / empty states every hospital page shares, so a page
 * that is still fetching looks designed rather than blank — same idea as
 * the doctor pages' inline state cards, pulled out here because ten pages
 * would otherwise repeat it ten times.
 */
export function StateCard({ icon, title, message, onRetry, tone = '' }) {
  return (
    <div className={`hospital-state hospital-card ${tone}`} role={onRetry ? 'alert' : undefined}>
      <span><i className={`bi ${icon}`} aria-hidden="true"></i></span>
      <div>
        <strong>{title}</strong>
        {message && <p>{message}</p>}
        {onRetry && (
          <button type="button" className="btn" onClick={onRetry}>Try again</button>
        )}
      </div>
    </div>
  );
}

export function EmptyState({ icon, title, message }) {
  return (
    <div className="hospital-empty">
      <i className={`bi ${icon}`} aria-hidden="true"></i>
      <strong>{title}</strong>
      {message && <span>{message}</span>}
    </div>
  );
}

/**
 * One page frame: nav, header, then whatever the page renders, then the
 * footer. `state` short-circuits the body while loading or after an error
 * so each page does not re-implement that branch.
 */
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
  loadingMessage = 'Fetching the latest from your hospital…',
  children,
}) {
  return (
    <div className={`hospital-page ${pageClass || ''}`}>
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

      <HospitalFooter />
    </div>
  );
}
