import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function ext(path) {
  return `${window.PHARMACY_APP_BASE}${path}`;
}

function MedicineCard({ medicine, csrf }) {
  const [open, setOpen] = useState(false);

  return (
    <article className={`pharmacy-stock-card ${medicine.has_expired ? 'has-expired' : ''}`}>
      <header>
        <div>
          <strong>{medicine.generic_name}</strong>
          <small>
            {[medicine.brand_name, medicine.strength, medicine.form].filter(Boolean).join(' · ')}
          </small>
        </div>
        <span className="pharmacy-badge is-total">{medicine.total_quantity} in stock</span>
      </header>

      <button
        type="button"
        className="pharmacy-batch-toggle"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
      >
        <i className={`bi ${open ? 'bi-chevron-up' : 'bi-chevron-down'}`} aria-hidden="true"></i>
        {medicine.batches.length} {medicine.batches.length === 1 ? 'batch' : 'batches'}
      </button>

      {open && (
        <div className="pharmacy-table-wrap">
          <table className="pharmacy-table">
            <thead>
              <tr>
                <th>Batch</th>
                <th>Expiry</th>
                <th>Price</th>
                <th>Qty</th>
                <th aria-label="Actions"></th>
              </tr>
            </thead>
            <tbody>
              {medicine.batches.map((batch) => (
                <tr key={batch.stock_id} className={batch.is_expired ? 'is-expired' : ''}>
                  <td>{batch.batch_no}</td>
                  <td>
                    {batch.expiry_label}
                    {batch.is_expired && <span className="pharmacy-badge is-expired">Expired</span>}
                  </td>
                  <td>৳{Number(batch.unit_price).toLocaleString()}</td>
                  <td>{batch.quantity_available}</td>
                  <td className="pharmacy-row-actions">
                    <a className="pharmacy-btn is-ghost" href={ext(`/inventory/create?edit=${batch.stock_id}`)}>Edit</a>
                    {/*
                      Removing a batch is the pharmacist confirming they have
                      physically pulled it off the shelf, so it posts to the
                      existing web route that owns that meaning.
                    */}
                    <form method="POST" action={ext(`/inventory/${batch.stock_id}/remove`)}>
                      <input type="hidden" name="_token" value={csrf} />
                      <button type="submit" className="pharmacy-btn is-danger">Done</button>
                    </form>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </article>
  );
}

export default function Inventory() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [query, setQuery] = useState('');
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    client
      .get('/inventory')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your inventory right now.'));
  }

  useEffect(load, []);

  const term = query.trim().toLowerCase();
  const visible = (data?.medicines || []).filter((m) => (
    !term
    || (m.generic_name || '').toLowerCase().includes(term)
    || (m.brand_name || '').toLowerCase().includes(term)
  ));

  return (
    <PageShell
      pageClass="pharmacy-inventory-page"
      icon="bi-box-seam"
      title="Inventory"
      subtitle="Every batch you stock, earliest expiry first so anything expired sorts to the top."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading inventory"
      loadingMessage="Fetching your stock…"
      actions={
        <>
          <a className="pharmacy-btn is-ghost" href={ext('/inventory/medicines/create')}>
            <i className="bi bi-plus-square" aria-hidden="true"></i> New medicine
          </a>
          <a className="pharmacy-btn is-primary" href={ext('/inventory/create')}>
            <i className="bi bi-plus-lg" aria-hidden="true"></i> Add batch
          </a>
        </>
      }
    >
      {data && (
        <>
          {data.totals.expired_batches > 0 && (
            <p className="pharmacy-notice is-error">
              <i className="bi bi-exclamation-triangle" aria-hidden="true"></i>
              {data.totals.expired_batches} {data.totals.expired_batches === 1 ? 'batch has' : 'batches have'} expired.
              They stay listed until you mark them Done after pulling them off the shelf.
            </p>
          )}

          <section className="pharmacy-card">
            <header className="pharmacy-card-header">
              <div>
                <span className="pharmacy-card-icon is-green"><i className="bi bi-capsule" aria-hidden="true"></i></span>
                <div>
                  <h2>Stocked medicines</h2>
                  <p>{data.totals.medicines} medicines across {data.totals.batches} batches.</p>
                </div>
              </div>
            </header>

            <form className="pharmacy-search-form" onSubmit={(e) => e.preventDefault()}>
              <input
                type="search"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Filter by medicine or brand name…"
                aria-label="Filter inventory"
              />
            </form>

            {visible.length === 0 ? (
              <EmptyState
                icon="bi-box"
                title={term ? 'Nothing matches that' : 'No stock yet'}
                message={term ? `No medicine matches “${query}”.` : 'Add your first batch with the button above.'}
              />
            ) : (
              <div className="pharmacy-stock-list">
                {visible.map((medicine) => (
                  <MedicineCard key={medicine.medicine_master_id} medicine={medicine} csrf={csrf} />
                ))}
              </div>
            )}
          </section>
        </>
      )}
    </PageShell>
  );
}
