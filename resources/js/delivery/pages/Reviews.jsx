import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { EmptyState } from '../components/PageShell';

function Stars({ rating }) {
  return (
    <span className="delivery-stars" aria-label={`${rating} out of 5`}>
      {[1, 2, 3, 4, 5].map((star) => (
        <i key={star} className={`bi ${star <= rating ? 'bi-star-fill' : 'bi-star'}`} aria-hidden="true"></i>
      ))}
    </span>
  );
}

export default function Reviews() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/reviews')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your reviews right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="delivery-reviews-page"
      icon="bi-star"
      title="Reviews"
      subtitle="What patients said about your deliveries. Reviews are anonymous — no name is stored against them."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading reviews"
      loadingMessage="Fetching patient feedback…"
    >
      {data && (
        <>
          <section className="delivery-card delivery-review-summary">
            <div className="delivery-review-score">
              <strong>{data.average ?? '—'}</strong>
              <Stars rating={Math.round(data.average || 0)} />
              <small>{data.count} review{data.count === 1 ? '' : 's'}</small>
            </div>

            <div className="delivery-review-breakdown">
              {data.breakdown.map((row) => {
                const pct = data.count > 0 ? Math.round((row.count / data.count) * 100) : 0;
                return (
                  <div className="delivery-review-bar" key={row.star}>
                    <span>{row.star}<i className="bi bi-star-fill" aria-hidden="true"></i></span>
                    <span className="delivery-rank-track">
                      <span style={{ width: `${Math.max(2, pct)}%` }}></span>
                    </span>
                    <small>{row.count}</small>
                  </div>
                );
              })}
            </div>
          </section>

          <section className="delivery-card">
            <header className="delivery-card-header">
              <div>
                <span className="delivery-card-icon is-amber"><i className="bi bi-chat-quote" aria-hidden="true"></i></span>
                <div>
                  <h2>What patients wrote</h2>
                  <p>Newest first.</p>
                </div>
              </div>
            </header>

            {data.reviews.length === 0 ? (
              <EmptyState
                icon="bi-star"
                title="No reviews yet"
                message="Patients can review you after you deliver their order."
              />
            ) : (
              <div className="delivery-review-list">
                {data.reviews.map((review, index) => (
                  <article className="delivery-review-item" key={index}>
                    <header>
                      <Stars rating={review.rating} />
                      <small>{review.date_label}</small>
                    </header>
                    {review.comment && <p>{review.comment}</p>}
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
