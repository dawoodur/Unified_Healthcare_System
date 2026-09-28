import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function ext(path) {
  return `${window.HOSPITAL_APP_BASE}${path}`;
}

const CATEGORY_TONES = ['teal', 'violet', 'blue', 'amber', 'green', 'indigo', 'rose'];

export default function Facilities() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/facilities')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your facility list right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="hospital-facilities-page"
      icon="bi-building"
      title="Facilities"
      subtitle="Set which services your hospital offers, and what each one costs per day."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading facilities"
      loadingMessage="Fetching your service catalogue…"
      actions={
        <a className="hospital-btn is-primary" href={ext('/facilities/types/create')}>
          <i className="bi bi-plus-lg" aria-hidden="true"></i> New facility type
        </a>
      }
    >
      {data && (
        <>
          <p className="hospital-notice is-info">
            <i className="bi bi-info-circle" aria-hidden="true"></i>
            You currently offer {data.offered_count} {data.offered_count === 1 ? 'facility' : 'facilities'}.
            Patients only see the ones with a price set.
          </p>

          {data.categories.length === 0 ? (
            <EmptyState icon="bi-building" title="No facility categories yet" />
          ) : (
            data.categories.map((category, index) => (
              <section className="hospital-card" key={category.category_id}>
                <header className="hospital-card-header">
                  <div>
                    {/* Cycled so a long catalogue does not read as one violet wall. */}
                    <span className={`hospital-card-icon is-${CATEGORY_TONES[index % CATEGORY_TONES.length]}`}>
                      <i className="bi bi-grid" aria-hidden="true"></i>
                    </span>
                    <div>
                      <h2>{category.category_name}</h2>
                      <p>{category.types.filter((type) => type.offered).length} of {category.types.length} offered here.</p>
                    </div>
                  </div>
                </header>

                <div className="hospital-facility-grid">
                  {category.types.map((type) => (
                    <article className={`hospital-facility-tile ${type.offered ? 'is-offered' : ''}`} key={type.facility_type_id}>
                      <div className="hospital-facility-tile-head">
                        <strong>{type.name}</strong>
                        {type.is_occupancy && <span className="hospital-chip">Bed</span>}
                      </div>

                      {type.offered ? (
                        <dl>
                          <div><dt>Price</dt><dd>৳{Number(type.price).toLocaleString()}</dd></div>
                          <div><dt>Daily capacity</dt><dd>{type.daily_capacity}</dd></div>
                        </dl>
                      ) : (
                        <p className="hospital-facility-tile-empty">Not offered yet.</p>
                      )}

                      <a className="hospital-tool-link" href={ext(`/facilities/${type.facility_type_id}/edit`)}>
                        {type.offered ? 'Edit price & quota' : 'Start offering this'}
                      </a>
                    </article>
                  ))}
                </div>
              </section>
            ))
          )}
        </>
      )}
    </PageShell>
  );
}
