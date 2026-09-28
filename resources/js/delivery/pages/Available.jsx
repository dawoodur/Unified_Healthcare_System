import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';
import DeliveryCard from '../components/DeliveryCard';

export default function Available() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    client
      .get('/available')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load available deliveries right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="delivery-available-page"
      icon="bi-truck"
      title="Available deliveries"
      subtitle="Orders a pharmacy has packed and nobody has claimed yet. Oldest first."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading available deliveries"
      loadingMessage="Checking what is waiting…"
    >
      {data && (
        <section className="delivery-card">
          <header className="delivery-card-header">
            <div>
              <span className="delivery-card-icon is-orange"><i className="bi bi-truck" aria-hidden="true"></i></span>
              <div>
                <h2>Waiting to be claimed</h2>
                <p>{data.orders.length} order{data.orders.length === 1 ? '' : 's'} available.</p>
              </div>
            </div>
          </header>

          {data.orders.length === 0 ? (
            <EmptyState
              icon="bi-check2-circle"
              title="Nothing waiting"
              message="Every packed order has been claimed. Check back shortly."
            />
          ) : (
            <div className="delivery-order-grid">
              {data.orders.map((order) => (
                <DeliveryCard key={order.order_id} order={order} csrf={csrf} showActions />
              ))}
            </div>
          )}
        </section>
      )}
    </PageShell>
  );
}
