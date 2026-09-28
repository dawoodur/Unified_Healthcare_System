import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function ext(path) {
  return `${window.HOSPITAL_APP_BASE}${path}`;
}

function OperationCard({ operation, showOffer }) {
  return (
    <article className={`hospital-op-card is-${operation.status}`}>
      <header>
        <div>
          <strong>{operation.patient_name}</strong>
          <small>{operation.facility_type || 'Operation request'}</small>
        </div>
        <span className={`hospital-badge is-${operation.status}`}>{operation.status}</span>
      </header>

      {operation.patient_notes && <p className="hospital-op-notes">“{operation.patient_notes}”</p>}

      <dl>
        <div><dt>Surgeon</dt><dd>{operation.assigned_doctor ? `Dr. ${operation.assigned_doctor}` : 'Not assigned'}</dd></div>
        <div><dt>Scheduled</dt><dd>{operation.scheduled_date || 'Not scheduled'}{operation.scheduled_time ? ` · ${operation.scheduled_time}` : ''}</dd></div>
        <div><dt>Serial</dt><dd>{operation.serial_number ? `#${operation.serial_number}` : '—'}</dd></div>
        <div><dt>Price</dt><dd>{operation.price ? `৳${Number(operation.price).toLocaleString()}` : '—'}</dd></div>
      </dl>

      {showOffer && (
        <a className="hospital-tool-link" href={ext(`/operations/${operation.operation_request_id}/offer`)}>
          Make an offer
        </a>
      )}
    </article>
  );
}

export default function Operations() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/operations')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load operation requests right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="hospital-operations-page"
      icon="bi-heart-pulse"
      title="Operation requests"
      subtitle="Patients asking your hospital to schedule a procedure, and the ones already settled."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading operations"
      loadingMessage="Fetching your operation queue…"
    >
      {data && (
        <>
          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-rose"><i className="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <div>
                  <h2>Awaiting your response</h2>
                  <p>{data.pending.length} request{data.pending.length === 1 ? '' : 's'} still in motion.</p>
                </div>
              </div>
            </header>

            {data.pending.length === 0 ? (
              <EmptyState icon="bi-clipboard2-check" title="Nothing waiting" message="No operation requests need your attention." />
            ) : (
              <div className="hospital-op-grid">
                {data.pending.map((operation) => (
                  <OperationCard key={operation.operation_request_id} operation={operation} showOffer />
                ))}
              </div>
            )}
          </section>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-teal"><i className="bi bi-check2-all" aria-hidden="true"></i></span>
                <div>
                  <h2>Settled</h2>
                  <p>{data.completed.length} completed, declined or cancelled.</p>
                </div>
              </div>
            </header>

            {data.completed.length === 0 ? (
              <EmptyState icon="bi-archive" title="Nothing settled yet" />
            ) : (
              <div className="hospital-op-grid">
                {data.completed.map((operation) => (
                  <OperationCard key={operation.operation_request_id} operation={operation} showOffer={false} />
                ))}
              </div>
            )}
          </section>
        </>
      )}
    </PageShell>
  );
}
