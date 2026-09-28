import React, { useEffect, useRef, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';
import DeliveryCard from '../components/DeliveryCard';

export default function MyDeliveries() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const csrf = window.CSRF_TOKEN || '';

  // Claiming an order posts to the web route, which redirects here and flashes
  // the order id (spa-shell.blade.php turns that into DELIVERY_AUTO_SIMULATE).
  // Captured on the first render because the share script clears the global as
  // soon as it starts that run's route.
  const autoSimulateId = useRef(window.DELIVERY_AUTO_SIMULATE ?? null).current;

  function load() {
    setError(null);
    client
      .get('/my-deliveries')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your deliveries right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="delivery-mine-page"
      icon="bi-box-seam"
      title="My deliveries"
      subtitle="Runs you have claimed. Share your location so the patient can follow you in, then confirm with their code."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading your deliveries"
      loadingMessage="Fetching your runs…"
    >
      {data && (
        <>
          <section className="delivery-card">
            <header className="delivery-card-header">
              <div>
                <span className="delivery-card-icon is-orange"><i className="bi bi-geo-alt" aria-hidden="true"></i></span>
                <div>
                  <h2>On the road</h2>
                  <p>{data.pending.length} out for delivery.</p>
                </div>
              </div>
            </header>

            {data.pending.length === 0 ? (
              <EmptyState
                icon="bi-truck"
                title="Nothing on the road"
                message="Claim an order from Available to get started."
              />
            ) : (
              <div className="delivery-order-grid">
                {data.pending.map((order) => (
                  <DeliveryCard
                    key={order.order_id}
                    order={order}
                    csrf={csrf}
                    showActions
                    autoSimulate={order.order_id === autoSimulateId}
                  />
                ))}
              </div>
            )}
          </section>

          <section className="delivery-card">
            <header className="delivery-card-header">
              <div>
                <span className="delivery-card-icon is-green"><i className="bi bi-check2-all" aria-hidden="true"></i></span>
                <div>
                  <h2>Finished</h2>
                  <p>{data.completed.length} delivered or cancelled.</p>
                </div>
              </div>
            </header>

            {data.completed.length === 0 ? (
              <EmptyState icon="bi-archive" title="Nothing finished yet" />
            ) : (
              <div className="delivery-order-grid">
                {data.completed.map((order) => (
                  <DeliveryCard key={order.order_id} order={order} csrf={csrf} showActions={false} />
                ))}
              </div>
            )}
          </section>
        </>
      )}
    </PageShell>
  );
}
