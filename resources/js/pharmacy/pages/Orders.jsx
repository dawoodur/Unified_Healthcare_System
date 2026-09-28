import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function ext(path) {
  return `${window.PHARMACY_APP_BASE}${path}`;
}

function OrderCard({ order, csrf }) {
  return (
    <article className={`pharmacy-order-card is-${order.status}`}>
      <header>
        <div>
          <strong>{order.patient_name}</strong>
          <small>Order #{order.order_id} · {order.placed_label}</small>
        </div>
        <span className={`pharmacy-badge is-${order.status}`}>{order.status_label}</span>
      </header>

      <ul className="pharmacy-order-items">
        {order.items.map((item, index) => (
          <li key={index}>
            <span>{item.name}{item.brand ? ` (${item.brand})` : ''}</span>
            <span className="pharmacy-order-qty">× {item.quantity}</span>
            <span>৳{Number(item.unit_price).toLocaleString()}</span>
          </li>
        ))}
      </ul>

      <dl>
        <div><dt>Subtotal</dt><dd>৳{Number(order.subtotal).toLocaleString()}</dd></div>
        {Number(order.discount_amount) > 0 && (
          <div><dt>Discount</dt><dd>−৳{Number(order.discount_amount).toLocaleString()}</dd></div>
        )}
        <div className="is-total"><dt>Total</dt><dd>৳{Number(order.total_amount).toLocaleString()}</dd></div>
        <div><dt>Deliver to</dt><dd>{order.delivery_address || '—'}</dd></div>
        <div><dt>Agent</dt><dd>{order.delivery_agent || 'Not assigned'}</dd></div>
      </dl>

      {order.status === 'placed' && (
        <div className="pharmacy-row-actions">
          {/*
            Accepting moves stock and tells the patient their order is being
            prepared; cancelling releases it. Both stay on the existing web
            routes that already own those effects.
          */}
          <form method="POST" action={ext(`/orders/${order.order_id}/accept`)}>
            <input type="hidden" name="_token" value={csrf} />
            <button type="submit" className="pharmacy-btn is-primary">Accept order</button>
          </form>
          <form method="POST" action={ext(`/orders/${order.order_id}/cancel`)}>
            <input type="hidden" name="_token" value={csrf} />
            <button type="submit" className="pharmacy-btn is-danger">Cancel</button>
          </form>
        </div>
      )}
    </article>
  );
}

export default function Orders() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    client
      .get('/orders')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your orders right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="pharmacy-orders-page"
      icon="bi-bag-check"
      title="Orders"
      subtitle="Medicine orders placed with your pharmacy, oldest first so nothing waits too long."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading orders"
      loadingMessage="Fetching your order queue…"
    >
      {data && (
        <>
          <section className="pharmacy-card">
            <header className="pharmacy-card-header">
              <div>
                <span className="pharmacy-card-icon is-amber"><i className="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <div>
                  <h2>Needs attention</h2>
                  <p>{data.pending.length} order{data.pending.length === 1 ? '' : 's'} still in progress.</p>
                </div>
              </div>
            </header>

            {data.pending.length === 0 ? (
              <EmptyState icon="bi-check2-circle" title="Nothing waiting" message="Every order has been dealt with." />
            ) : (
              <div className="pharmacy-order-grid">
                {data.pending.map((order) => <OrderCard key={order.order_id} order={order} csrf={csrf} />)}
              </div>
            )}
          </section>

          <section className="pharmacy-card">
            <header className="pharmacy-card-header">
              <div>
                <span className="pharmacy-card-icon is-teal"><i className="bi bi-check2-all" aria-hidden="true"></i></span>
                <div>
                  <h2>Settled</h2>
                  <p>{data.completed.length} delivered or cancelled.</p>
                </div>
              </div>
            </header>

            {data.completed.length === 0 ? (
              <EmptyState icon="bi-archive" title="Nothing settled yet" />
            ) : (
              <div className="pharmacy-order-grid">
                {data.completed.map((order) => <OrderCard key={order.order_id} order={order} csrf={csrf} />)}
              </div>
            )}
          </section>
        </>
      )}
    </PageShell>
  );
}
