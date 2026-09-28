import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell from '../components/PageShell';

export default function PaymentMethods() {
  const [methods, setMethods] = useState(null);
  const [error, setError] = useState(null);
  const [saving, setSaving] = useState(false);
  const [notice, setNotice] = useState(null);

  function load() {
    setError(null);
    client
      .get('/payment-methods')
      .then((res) => setMethods(res.data.methods))
      .catch(() => setError('Could not load your payment methods right now.'));
  }

  useEffect(load, []);

  function toggle(id) {
    setMethods((current) => current.map((m) => (
      m.payment_method_id === id ? { ...m, accepted: !m.accepted } : m
    )));
  }

  function setDetails(id, value) {
    setMethods((current) => current.map((m) => (
      m.payment_method_id === id ? { ...m, account_details: value } : m
    )));
  }

  function save(event) {
    event.preventDefault();
    setSaving(true);
    setNotice(null);

    const accepted = methods.filter((m) => m.accepted);

    client
      .put('/payment-methods', {
        accepted: accepted.map((m) => m.payment_method_id),
        account_details: Object.fromEntries(
          accepted.map((m) => [m.payment_method_id, m.account_details || null])
        ),
      })
      .then((res) => setNotice({ tone: 'ok', text: res.data.message }))
      .catch(() => setNotice({ tone: 'error', text: 'Could not save that. Please try again.' }))
      .finally(() => setSaving(false));
  }

  return (
    <PageShell
      pageClass="hospital-payments-page"
      icon="bi-credit-card"
      title="Payment methods"
      subtitle="What patients can pay with at your front desk, and the account details to show them."
      loading={!methods}
      error={error}
      onRetry={load}
      loadingTitle="Loading payment methods"
      loadingMessage="Fetching what you accept…"
    >
      {methods && (
        <form className="hospital-card" onSubmit={save}>
          <header className="hospital-card-header">
            <div>
              <span className="hospital-card-icon is-amber"><i className="bi bi-wallet2" aria-hidden="true"></i></span>
              <div>
                <h2>Accepted at this hospital</h2>
                <p>Tick everything you take. Account details are shown to patients before they visit.</p>
              </div>
            </div>
          </header>

          {notice && (
            <p className={`hospital-notice is-${notice.tone}`}>
              <i className={`bi ${notice.tone === 'ok' ? 'bi-check2-circle' : 'bi-exclamation-triangle'}`} aria-hidden="true"></i>
              {notice.text}
            </p>
          )}

          <div className="hospital-payment-list">
            {methods.map((method) => (
              <div className={`hospital-payment-row ${method.accepted ? 'is-on' : ''}`} key={method.payment_method_id}>
                <label>
                  <input
                    type="checkbox"
                    checked={method.accepted}
                    onChange={() => toggle(method.payment_method_id)}
                  />
                  <span>{method.method_name}</span>
                </label>

                <input
                  type="text"
                  maxLength={190}
                  placeholder="Account number / details shown to patients"
                  value={method.account_details || ''}
                  onChange={(e) => setDetails(method.payment_method_id, e.target.value)}
                  disabled={!method.accepted}
                  aria-label={`${method.method_name} account details`}
                />
              </div>
            ))}
          </div>

          <button type="submit" className="hospital-btn is-primary" disabled={saving}>
            {saving ? 'Saving…' : 'Save payment methods'}
          </button>
        </form>
      )}
    </PageShell>
  );
}
