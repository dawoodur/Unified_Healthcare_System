import React, { useEffect, useRef } from 'react';

function ext(path) {
  return `${window.DELIVERY_APP_BASE}${path}`;
}

/**
 * One order on /delivery/my-deliveries.
 *
 * The live-sharing panel keeps the exact markup public/js/delivery-share-location.js
 * expects (the [data-delivery-share] element and its data-* attributes) rather
 * than reimplementing 250 lines of geolocation, throttling and route simulation
 * in React. That script used to wire itself up on DOMContentLoaded, which fires
 * long before React mounts, so it now also exposes window.initDeliveryShare()
 * for this effect to call. It is idempotent, so StrictMode's double-invoked
 * effects cannot attach two watchers to the same order.
 *
 * autoSimulate marks the run the agent has just claimed: the script starts
 * that order's route as soon as it wires the panel up, so the patient sees
 * movement from the moment of the claim.
 */
export default function DeliveryCard({ order, csrf, showActions, autoSimulate = false }) {
  const shareRef = useRef(null);

  useEffect(() => {
    if (shareRef.current && typeof window.initDeliveryShare === 'function') {
      window.initDeliveryShare(shareRef.current.parentNode || document);
    }
  }, [order.order_id]);

  return (
    <article className={`delivery-order-card is-${order.status}`}>
      <header>
        <div>
          <strong>Order #{order.order_id}</strong>
          <small>{order.pharmacy_name} · {order.patient_name}</small>
        </div>
        <span className={`delivery-badge is-${order.status}`}>{order.status_label}</span>
      </header>

      <dl>
        <div><dt>Pick up</dt><dd>{order.pharmacy_address || 'Address not listed'}</dd></div>
        <div><dt>Deliver to</dt><dd>{order.delivery_address}</dd></div>
        <div className="is-total"><dt>Total</dt><dd>৳{Number(order.total_amount).toLocaleString()}</dd></div>
      </dl>

      {showActions && order.status === 'out_for_delivery' && (
        <>
          <div className="delivery-row-actions">
            <form method="POST" action={ext(`/orders/${order.order_id}/request-otp`)}
                  data-confirm={`Email a confirmation code to ${order.patient_name}?`}>
              <input type="hidden" name="_token" value={csrf} />
              <button type="submit" className="delivery-btn is-ghost">Request code</button>
            </form>

            <form method="POST" action={ext(`/orders/${order.order_id}/confirm`)}
                  className="delivery-confirm-form"
                  data-confirm="Confirm this delivery as complete?">
              <input type="hidden" name="_token" value={csrf} />
              <input type="text" name="otp_code" maxLength={6} placeholder="6-digit code" required aria-label="Confirmation code" />
              <button type="submit" className="delivery-btn is-primary">Confirm delivery</button>
            </form>
          </div>

          {/*
            Always rendered. The coordinates below are read only by the
            script's Simulate button — real GPS sharing does not use them —
            and it already handles a missing pin by walking a short leg from
            the pharmacy instead of refusing. Gating this panel on them would
            disable live sharing on every order whose patient has no pinned
            door, which is most of them.
          */}
          <div
            className="delivery-share"
            ref={shareRef}
            data-delivery-share
            data-post-url={ext(`/orders/${order.order_id}/location`)}
            data-csrf={csrf}
            data-origin-lat={order.origin_lat ?? ''}
            data-origin-lng={order.origin_lng ?? ''}
            data-dest-lat={order.dest_lat ?? ''}
            data-dest-lng={order.dest_lng ?? ''}
            data-auto-simulate={autoSimulate ? '1' : undefined}
          >
            <label className="delivery-share-toggle">
              <input type="checkbox" data-share-toggle />
              <span>Share my location with {order.patient_name}</span>
            </label>
            <button type="button" className="delivery-btn is-ghost delivery-share-simulate" data-simulate>
              Simulate route
            </button>
            <span className="muted delivery-share-status" data-share-status>Not sharing.</span>
            <small className="muted delivery-share-hint">
              The patient only sees your position while this order is out for delivery.
              A run you have just claimed starts a simulated route by itself, labelled as
              simulated; switch on sharing above to send your real position instead.
            </small>
          </div>
        </>
      )}

      {showActions && order.status === 'accepted' && (
        <div className="delivery-row-actions">
          <form method="POST" action={ext(`/orders/${order.order_id}/accept`)}
                data-confirm="Claim this delivery?">
            <input type="hidden" name="_token" value={csrf} />
            <button type="submit" className="delivery-btn is-primary">
              <i className="bi bi-truck" aria-hidden="true"></i> Claim this delivery
            </button>
          </form>
        </div>
      )}
    </article>
  );
}
