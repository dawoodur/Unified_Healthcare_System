import React, { useEffect, useMemo, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function DoctorNotificationsFooter() {
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

function NotificationStat({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-notification-stat is-${tone}`}>
      <span className="doctor-notification-stat-icon">
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

function notificationVisual(type) {
  const value = String(type || '').toLowerCase();

  if (value.includes('appointment') || value.includes('reminder')) {
    return { icon: 'bi-calendar2-check', tone: 'blue' };
  }
  if (value.includes('record') || value.includes('prescription')) {
    return { icon: 'bi-file-earmark-medical', tone: 'violet' };
  }
  if (value.includes('message') || value.includes('inbox')) {
    return { icon: 'bi-chat-dots', tone: 'teal' };
  }
  if (value.includes('report') || value.includes('reject') || value.includes('cancel')) {
    return { icon: 'bi-exclamation-triangle', tone: 'amber' };
  }
  if (value.includes('payment') || value.includes('earning')) {
    return { icon: 'bi-wallet2', tone: 'green' };
  }

  return { icon: 'bi-bell', tone: 'blue' };
}

export default function Notifications() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [filter, setFilter] = useState('all');

  function load() {
    setError(null);
    return client
      .get('/notifications')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load your notifications right now.'));
  }

  useEffect(() => {
    load();
  }, []);

  const filtered = useMemo(() => {
    if (!data) return [];
    if (filter === 'new') return data.notifications.filter((item) => item.was_unread);
    if (filter === 'today') return data.notifications.filter((item) => item.is_today);
    return data.notifications;
  }, [data, filter]);

  if (error && !data) {
    return (
      <div className="doctor-page doctor-notifications-page">
        <QuickNav />
        <div className="doctor-notifications-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-circle" aria-hidden="true"></i></span>
          <div>
            <strong>Notifications unavailable</strong>
            <p>{error}</p>
            <button type="button" className="btn" onClick={load}>Try again</button>
          </div>
        </div>
        <DoctorNotificationsFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-notifications-page">
        <QuickNav />
        <div className="doctor-notifications-state doctor-card">
          <span><i className="bi bi-bell" aria-hidden="true"></i></span>
          <div>
            <strong>Loading notifications</strong>
            <p>Collecting your latest updates…</p>
          </div>
        </div>
        <DoctorNotificationsFooter />
      </div>
    );
  }

  return (
    <div className="doctor-page doctor-notifications-page">
      <QuickNav />

      <section className="doctor-notifications-hero">
        <div>
          <span className="doctor-notifications-eyebrow">DOCTOR NOTIFICATIONS</span>
          <h1>Notifications</h1>
          <p>
            Review appointment, inbox, record-access, prescription, and account
            updates related to your doctor workspace.
          </p>
        </div>
        <div className="doctor-notifications-hero-note">
          <i className="bi bi-check2-circle" aria-hidden="true"></i>
          <div>
            <strong>Marked as read</strong>
            <small>Opening this page clears the topbar unread badge.</small>
          </div>
        </div>
      </section>

      <section className="doctor-notification-stats" aria-label="Notification summary">
        <NotificationStat
          icon="bi-bell-fill"
          value={data.stats.unread_before_open}
          label="New"
          hint="Unread before opening"
          tone="amber"
        />
        <NotificationStat
          icon="bi-calendar-day"
          value={data.stats.today}
          label="Today"
          hint="Received today"
          tone="blue"
        />
        <NotificationStat
          icon="bi-clock-history"
          value={data.stats.last_7_days}
          label="Last 7 days"
          hint="Recent activity"
          tone="violet"
        />
        <NotificationStat
          icon="bi-list-check"
          value={data.stats.total}
          label="Total"
          hint="All notifications"
          tone="teal"
        />
      </section>

      <section className="doctor-notifications-layout">
        <main className="doctor-notifications-main">
          <header className="doctor-notifications-toolbar">
            <div>
              <span className="doctor-notifications-toolbar-icon">
                <i className="bi bi-inbox" aria-hidden="true"></i>
              </span>
              <div>
                <h2>Notification center</h2>
                <p>Newest updates appear first.</p>
              </div>
            </div>

            <div className="doctor-notifications-filters">
              <button
                type="button"
                className={filter === 'all' ? 'is-active' : ''}
                onClick={() => setFilter('all')}
              >
                All <span>{data.stats.total}</span>
              </button>
              <button
                type="button"
                className={filter === 'new' ? 'is-active' : ''}
                onClick={() => setFilter('new')}
              >
                New <span>{data.stats.unread_before_open}</span>
              </button>
              <button
                type="button"
                className={filter === 'today' ? 'is-active' : ''}
                onClick={() => setFilter('today')}
              >
                Today <span>{data.stats.today}</span>
              </button>
            </div>
          </header>

          {filtered.length === 0 ? (
            <div className="doctor-notifications-empty">
              <span><i className="bi bi-bell-slash" aria-hidden="true"></i></span>
              <h2>No notifications here</h2>
              <p>There are no updates matching this filter.</p>
            </div>
          ) : (
            <div className="doctor-notifications-list">
              {filtered.map((notification) => {
                const visual = notificationVisual(notification.type);
                return (
                  <article
                    key={notification.notification_id}
                    className={`doctor-notification-item is-${visual.tone} ${notification.was_unread ? 'is-new' : ''}`}
                  >
                    <span className="doctor-notification-item-icon">
                      <i className={`bi ${visual.icon}`} aria-hidden="true"></i>
                    </span>

                    <div className="doctor-notification-item-copy">
                      <div>
                        <strong>{notification.type_label}</strong>
                        {notification.was_unread && <span className="doctor-notification-new-badge">New</span>}
                      </div>
                      <p>{notification.message}</p>
                      <small>
                        <i className="bi bi-clock" aria-hidden="true"></i>
                        {notification.when_label}
                      </small>
                    </div>
                  </article>
                );
              })}
            </div>
          )}
        </main>

        <aside className="doctor-notifications-sidebar">
          <section className="doctor-notifications-side-card is-violet">
            <span><i className="bi bi-shield-check" aria-hidden="true"></i></span>
            <div>
              <h2>Account updates</h2>
              <p>
                Notifications are private to your signed-in account and are not
                shared with patients.
              </p>
            </div>
          </section>

          <section className="doctor-notifications-side-card is-blue">
            <span><i className="bi bi-chat-square-text" aria-hidden="true"></i></span>
            <div>
              <h2>Need to reply?</h2>
              <p>Hospital messages are handled separately in the secure Inbox.</p>
              <a href={`${window.DOCTOR_APP_BASE}/inbox`}>
                Open Inbox <i className="bi bi-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </section>

          <section className="doctor-notifications-side-card is-amber">
            <span><i className="bi bi-exclamation-square" aria-hidden="true"></i></span>
            <div>
              <h2>Something looks wrong?</h2>
              <p>Use Report an issue from the topbar to contact platform support.</p>
            </div>
          </section>
        </aside>
      </section>

      <DoctorNotificationsFooter />
    </div>
  );
}
