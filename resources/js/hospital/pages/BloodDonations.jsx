import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function ext(path) {
  return `${window.HOSPITAL_APP_BASE}${path}`;
}

export default function BloodDonations() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  // Confirm/reject pay the donor's reward points and start their next
  // eligible-date countdown, so they post to the existing web routes that
  // already own those effects rather than a second path behind the API.
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    client
      .get('/blood-donations')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load donation claims right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="hospital-blood-page"
      icon="bi-droplet-half"
      title="Donation confirmations"
      subtitle="Donors who say they gave blood here. Confirming pays their reward points."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading donations"
      loadingMessage="Fetching donation claims…"
      actions={<Link className="hospital-btn is-ghost" to="/blood-requests">Blood requests</Link>}
    >
      {data && (
        <>
          <p className="hospital-notice is-info">
            <i className="bi bi-award" aria-hidden="true"></i>
            A confirmed donation pays the donor {data.points_per_donation} reward points.
          </p>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-red"><i className="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <div>
                  <h2>Waiting for confirmation</h2>
                  <p>{data.pending.length} claim{data.pending.length === 1 ? '' : 's'} to review.</p>
                </div>
              </div>
            </header>

            {data.pending.length === 0 ? (
              <EmptyState icon="bi-check2-circle" title="Nothing to review" message="No donation claims are waiting." />
            ) : (
              <div className="hospital-people-list">
                {data.pending.map((donation) => (
                  <div className="hospital-person-row" key={donation.donation_id}>
                    <span className="hospital-blood-group">{donation.blood_group || '—'}</span>
                    <div>
                      <strong>{donation.patient_name}</strong>
                      <small>Logged for {donation.donated_label}</small>
                    </div>
                    <div className="hospital-row-actions">
                      <form method="POST" action={ext(`/blood-donations/${donation.donation_id}/confirm`)}>
                        <input type="hidden" name="_token" value={csrf} />
                        <button type="submit" className="hospital-btn is-primary">Confirm</button>
                      </form>
                      <form method="POST" action={ext(`/blood-donations/${donation.donation_id}/reject`)}>
                        <input type="hidden" name="_token" value={csrf} />
                        <button type="submit" className="hospital-btn is-danger">Reject</button>
                      </form>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </section>

          <section className="hospital-card">
            <header className="hospital-card-header">
              <div>
                <span className="hospital-card-icon is-teal"><i className="bi bi-clock-history" aria-hidden="true"></i></span>
                <div>
                  <h2>Recently reviewed</h2>
                  <p>The last {data.reviewed.length} decisions.</p>
                </div>
              </div>
            </header>

            {data.reviewed.length === 0 ? (
              <EmptyState icon="bi-archive" title="Nothing reviewed yet" />
            ) : (
              <div className="hospital-people-list">
                {data.reviewed.map((donation) => (
                  <div className="hospital-person-row" key={donation.donation_id}>
                    <span className="hospital-blood-group">{donation.blood_group || '—'}</span>
                    <div>
                      <strong>{donation.patient_name}</strong>
                      <small>
                        {donation.donated_label}
                        {donation.reject_reason ? ` · ${donation.reject_reason}` : ''}
                      </small>
                    </div>
                    <span className={`hospital-badge is-${donation.status}`}>{donation.status}</span>
                  </div>
                ))}
              </div>
            )}
          </section>
        </>
      )}
    </PageShell>
  );
}
