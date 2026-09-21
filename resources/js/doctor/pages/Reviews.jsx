import React, { useEffect, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function DoctorFooter() {
  return (
    <footer className="patient-dashboard-footer">
      <div className="patient-dashboard-footer-brand">
        <span className="patient-dashboard-footer-dot" aria-hidden="true"></span>
        <div>
          <strong>Telemedicine Platform</strong>
          <small>Connected Healthcare</small>
        </div>
      </div>
      <p>Secure, connected care across your healthcare journey.</p>
    </footer>
  );
}

function SummaryCard({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-reviews-stat is-${tone}`}>
      <span className="doctor-reviews-stat-icon">
        <i className={`bi ${icon}`} aria-hidden="true"></i>
      </span>
      <div>
        <strong>{value}</strong>
        <span>{label}</span>
        <small>{hint}</small>
      </div>
    </article>
  );
}

function Stars({ rating, compact = false }) {
  const rounded = Math.max(0, Math.min(5, Number(rating || 0)));
  return (
    <span
      className={`doctor-reviews-stars ${compact ? 'is-compact' : ''}`}
      aria-label={`${rounded} out of 5 stars`}
    >
      {Array.from({ length: 5 }, (_, index) => (
        <i
          key={index}
          className={`bi ${index < Math.round(rounded) ? 'bi-star-fill' : 'bi-star'}`}
          aria-hidden="true"
        ></i>
      ))}
    </span>
  );
}

export default function Reviews() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [filter, setFilter] = useState('all');

  function load() {
    setError(null);
    client
      .get('/reviews')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your reviews right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  if (error) {
    return (
      <div className="doctor-page doctor-reviews-page">
        <QuickNav />
        <div className="doctor-reviews-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-circle" aria-hidden="true"></i></span>
          <div>
            <strong>Reviews unavailable</strong>
            <p>{error}</p>
            <button type="button" className="btn" onClick={load}>Try again</button>
          </div>
        </div>
        <DoctorFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-reviews-page">
        <QuickNav />
        <div className="doctor-reviews-state doctor-card">
          <span><i className="bi bi-star" aria-hidden="true"></i></span>
          <div>
            <strong>Loading reviews</strong>
            <p>Preparing your patient feedback…</p>
          </div>
        </div>
        <DoctorFooter />
      </div>
    );
  }

  const reviews = data.reviews || [];
  const count = Number(data.count || 0);
  const average = data.average === null ? null : Number(data.average);

  const distribution = [5, 4, 3, 2, 1].map((rating) => {
    const total = reviews.filter((review) => Number(review.rating) === rating).length;
    return {
      rating,
      total,
      percent: count ? Math.round((total / count) * 100) : 0,
    };
  });

  const metrics = {
    distribution,
    fiveStar: distribution.find((item) => item.rating === 5)?.total || 0,
    withComments: reviews.filter((review) => String(review.comment || '').trim()).length,
  };

  const filteredReviews = reviews.filter((review) => {
    const rating = Number(review.rating);

    if (filter === 'five') return rating === 5;
    if (filter === 'four') return rating === 4;
    if (filter === 'lower') return rating <= 3;

    return true;
  });

  return (
    <div className="doctor-page doctor-reviews-page">
      <QuickNav />

      <section className="doctor-reviews-hero">
        <div>
          <span className="doctor-reviews-eyebrow">PATIENT FEEDBACK</span>
          <h1>Reviews</h1>
          <p>
            Review anonymous patient feedback, understand rating patterns,
            and track the experience patients report after care.
          </p>
        </div>

        <div className="doctor-reviews-privacy">
          <span><i className="bi bi-shield-lock" aria-hidden="true"></i></span>
          <div>
            <strong>Patient privacy protected</strong>
            <small>Patient names are never shown on this page.</small>
          </div>
        </div>
      </section>

      <section className="doctor-reviews-stats" aria-label="Review summary">
        <SummaryCard
          icon="bi-star-fill"
          value={average === null ? '—' : average.toFixed(1)}
          label="Average rating"
          hint={average === null ? 'No ratings yet' : 'Out of 5 stars'}
          tone="amber"
        />
        <SummaryCard
          icon="bi-chat-square-heart"
          value={count}
          label="Total reviews"
          hint="Patient feedback received"
          tone="blue"
        />
        <SummaryCard
          icon="bi-patch-check"
          value={metrics.fiveStar}
          label="5-star reviews"
          hint="Highest-rated feedback"
          tone="teal"
        />
        <SummaryCard
          icon="bi-chat-left-text"
          value={metrics.withComments}
          label="Written comments"
          hint="Reviews with a message"
          tone="violet"
        />
      </section>

      <section className="doctor-reviews-layout">
        <div className="doctor-reviews-main">
          <section className="doctor-reviews-distribution-card">
            <header className="doctor-reviews-card-header">
              <div>
                <span className="doctor-reviews-card-icon is-amber">
                  <i className="bi bi-bar-chart" aria-hidden="true"></i>
                </span>
                <div>
                  <h2>Rating distribution</h2>
                  <p>How your ratings are distributed across 1–5 stars.</p>
                </div>
              </div>

              {average !== null && (
                <div className="doctor-reviews-overall">
                  <strong>{average.toFixed(1)}</strong>
                  <Stars rating={average} compact />
                </div>
              )}
            </header>

            <div className="doctor-reviews-distribution">
              {metrics.distribution.map((item) => (
                <div className="doctor-reviews-distribution-row" key={item.rating}>
                  <span className="doctor-reviews-distribution-label">
                    {item.rating} <i className="bi bi-star-fill" aria-hidden="true"></i>
                  </span>
                  <span className="doctor-reviews-distribution-track">
                    <span style={{ width: `${item.percent}%` }}></span>
                  </span>
                  <span className="doctor-reviews-distribution-value">
                    {item.total}
                    <small>{item.percent}%</small>
                  </span>
                </div>
              ))}
            </div>
          </section>

          <section className="doctor-reviews-list-card">
            <header className="doctor-reviews-list-header">
              <div>
                <span className="doctor-reviews-card-icon is-blue">
                  <i className="bi bi-chat-square-quote" aria-hidden="true"></i>
                </span>
                <div>
                  <h2>Patient reviews</h2>
                  <p>Anonymous feedback, newest first.</p>
                </div>
              </div>

              <div className="doctor-reviews-filters" aria-label="Review filter">
                <button
                  type="button"
                  className={filter === 'all' ? 'is-active' : ''}
                  onClick={() => setFilter('all')}
                >
                  All <span>{count}</span>
                </button>
                <button
                  type="button"
                  className={filter === 'five' ? 'is-active' : ''}
                  onClick={() => setFilter('five')}
                >
                  5 stars
                </button>
                <button
                  type="button"
                  className={filter === 'four' ? 'is-active' : ''}
                  onClick={() => setFilter('four')}
                >
                  4 stars
                </button>
                <button
                  type="button"
                  className={filter === 'lower' ? 'is-active' : ''}
                  onClick={() => setFilter('lower')}
                >
                  3 or below
                </button>
              </div>
            </header>

            <div className="doctor-reviews-list">
              {filteredReviews.length === 0 ? (
                <div className="doctor-reviews-empty">
                  <span><i className="bi bi-star" aria-hidden="true"></i></span>
                  <strong>No reviews in this group</strong>
                  <p>
                    {reviews.length
                      ? 'Choose another rating filter to see more feedback.'
                      : 'Patient reviews will appear here when they become available.'}
                  </p>
                </div>
              ) : (
                filteredReviews.map((review, index) => (
                  <article className="doctor-review-item" key={`${review.created_at}-${index}`}>
                    <div className="doctor-review-item-top">
                      <div>
                        <span className="doctor-review-anonymous">
                          <i className="bi bi-person-check" aria-hidden="true"></i>
                        </span>
                        <div>
                          <strong>Verified patient</strong>
                          <small>{review.created_at}</small>
                        </div>
                      </div>

                      <div className="doctor-review-rating">
                        <Stars rating={Number(review.rating)} compact />
                        <strong>{Number(review.rating).toFixed(1)}</strong>
                      </div>
                    </div>

                    <p className={review.comment ? '' : 'is-empty'}>
                      {review.comment || 'No written comment was provided.'}
                    </p>
                  </article>
                ))
              )}
            </div>
          </section>
        </div>

        <aside className="doctor-reviews-sidebar">
          <section className="doctor-reviews-info-card is-violet">
            <span className="doctor-reviews-info-icon">
              <i className="bi bi-shield-check" aria-hidden="true"></i>
            </span>
            <div>
              <h2>Privacy by design</h2>
              <p>
                Reviews are intentionally anonymous here. You can see the
                rating, comment, and date without exposing a patient's identity.
              </p>
            </div>
          </section>

          <section className="doctor-reviews-info-card is-teal">
            <span className="doctor-reviews-info-icon">
              <i className="bi bi-lightbulb" aria-hidden="true"></i>
            </span>
            <div>
              <h2>Use feedback as a signal</h2>
              <p>
                Look for repeated themes across several reviews rather than
                treating a single rating as the complete picture.
              </p>
            </div>
          </section>
        </aside>
      </section>

      <DoctorFooter />
    </div>
  );
}
