import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function ext(path) {
  return `${window.HOSPITAL_APP_BASE}${path}`;
}

export default function BloodRequests() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/blood-requests')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your blood requests right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="hospital-blood-page"
      icon="bi-droplet-fill"
      title="Blood requests"
      subtitle="Emergency appeals your hospital has sent to eligible donors."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading blood requests"
      loadingMessage="Fetching your sent appeals…"
      actions={
        <a className="hospital-btn is-primary" href={ext('/blood-requests/create')}>
          <i className="bi bi-send" aria-hidden="true"></i> Send a request
        </a>
      }
    >
      {data && (
        <>
          <p className="hospital-notice is-info">
            <i className="bi bi-people" aria-hidden="true"></i>
            Donation claims logged against your hospital are confirmed on the
            {' '}<Link to="/blood-donations">donations page</Link>.
          </p>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-red"><i className="bi bi-megaphone" aria-hidden="true"></i></span>
                <div>
                  <h2>Requests sent</h2>
                  <p>{data.requests.length} appeal{data.requests.length === 1 ? '' : 's'} sent so far.</p>
                </div>
              </div>
            </header>

            {data.requests.length === 0 ? (
              <EmptyState
                icon="bi-droplet"
                title="No requests sent yet"
                message="When you send an emergency appeal, every eligible donor of that blood group is emailed."
              />
            ) : (
              <div className="hospital-blood-list">
                {data.requests.map((request) => (
                  <article className="hospital-blood-row" key={request.blood_request_id}>
                    <span className="hospital-blood-group">{request.blood_group}</span>
                    <div>
                      <strong>{request.message || 'Emergency blood request'}</strong>
                      <small>
                        Sent {request.created_label} · reached {request.recipient_count} eligible
                        {' '}{request.recipient_count === 1 ? 'donor' : 'donors'}
                      </small>
                    </div>
                  </article>
                ))}
              </div>
            )}
          </section>
        </>
      )}
    </PageShell>
  );
}
