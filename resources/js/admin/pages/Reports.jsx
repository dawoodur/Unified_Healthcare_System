import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import client from '../api/client';
import PageShell, { Card, EmptyState } from '../components/PageShell';
import { StatusFilters, webUrl } from '../components/Widgets';

const STATUSES = [
  { value: null, label: 'All' },
  { value: 'open', label: 'Open' },
  { value: 'in_review', label: 'In review' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'dismissed', label: 'Dismissed' },
];

const SETTABLE = [
  { value: 'in_review', label: 'In review' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'dismissed', label: 'Dismissed' },
];

/**
 * One report plus the reply form. Posting goes to the existing web route,
 * which also notifies the reporter — so the reply is never silently saved
 * without telling the person who filed it.
 */
function ReportCard({ report, csrf }) {
  return (
    <Card
      icon="bi-flag"
      tone="is-red"
      title={report.subject}
      subtitle={`${report.reporter}${report.reporter_role ? ` (${report.reporter_role})` : ''} · ${report.created_label}`}
      actions={<span className={`admin-badge is-${report.status}`}>{report.status_label}</span>}
    >
      <p style={{ margin: '0 0 12px' }}>{report.description}</p>

      {report.admin_response && (
        <p className="admin-notice is-info">
          <i className="bi bi-chat-left-text" aria-hidden="true"></i>
          <span><strong>Current response:</strong> {report.admin_response}</span>
        </p>
      )}

      <form
        method="POST"
        action={webUrl(`/reports/${report.report_id}/respond`)}
        className="admin-form-row"
        style={{ marginTop: '12px' }}
      >
        <input type="hidden" name="_token" value={csrf} />

        <div className="admin-field">
          <label htmlFor={`response-${report.report_id}`}>Response</label>
          <textarea
            id={`response-${report.report_id}`}
            name="admin_response"
            rows={2}
            defaultValue={report.admin_response || ''}
            maxLength={2000}
          />
        </div>

        <div className="admin-field" style={{ flex: '0 1 180px' }}>
          <label htmlFor={`status-${report.report_id}`}>Set status</label>
          <select id={`status-${report.report_id}`} name="status" defaultValue={
            SETTABLE.some((option) => option.value === report.status) ? report.status : 'in_review'
          }>
            {SETTABLE.map((option) => (
              <option key={option.value} value={option.value}>{option.label}</option>
            ))}
          </select>
        </div>

        <button type="submit" className="admin-btn is-primary">
          <i className="bi bi-send" aria-hidden="true"></i> Save
        </button>
      </form>
    </Card>
  );
}

export default function Reports() {
  const [params] = useSearchParams();
  const status = params.get('status');
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    setData(null);
    client
      .get('/reports', { params: { status: status || undefined } })
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load reports right now.'));
  }

  useEffect(load, [status]);

  return (
    <PageShell
      pageClass="admin-reports-page"
      icon="bi-flag"
      title="Reports"
      subtitle="Bug and system reports submitted by patients, doctors, hospitals, pharmacies and delivery agents."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading reports"
      loadingMessage="Checking the queue…"
    >
      {data && (
        <>
          <Card
            icon="bi-funnel"
            tone="is-blue"
            title="Filter by status"
            subtitle={`${data.reports.length} report${data.reports.length === 1 ? '' : 's'} shown.`}
          >
            <StatusFilters options={STATUSES} active={status} counts={data.counts} />
          </Card>

          {data.reports.length === 0 ? (
            <Card icon="bi-inbox" tone="is-teal" title="Nothing here">
              <EmptyState icon="bi-check2-circle" title="No reports found" message="Try a different status filter." />
            </Card>
          ) : (
            data.reports.map((report) => (
              <ReportCard key={report.report_id} report={report} csrf={csrf} />
            ))
          )}
        </>
      )}
    </PageShell>
  );
}
