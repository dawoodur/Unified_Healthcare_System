import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import client from '../api/client';
import PageShell, { Card, EmptyState } from '../components/PageShell';
import { StatusFilters } from '../components/Widgets';

const STATUSES = [
  { value: null, label: 'All' },
  { value: 'initiated', label: 'Initiated' },
  { value: 'pending', label: 'Pending' },
  { value: 'completed', label: 'Completed' },
  { value: 'failed', label: 'Failed' },
  { value: 'refunded', label: 'Refunded' },
];

export default function Transactions() {
  const [params] = useSearchParams();
  const status = params.get('status');
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    setData(null);
    client
      .get('/transactions', { params: { status: status || undefined } })
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load transactions right now.'));
  }

  useEffect(load, [status]);

  return (
    <PageShell
      pageClass="admin-transactions-page"
      icon="bi-cash-stack"
      title="Transactions"
      subtitle="Every payment made on the platform — appointment fees and medicine orders alike."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading transactions"
      loadingMessage="Adding up payments…"
    >
      {data && (
        <>
          <Card
            icon="bi-funnel"
            tone="is-blue"
            title="Filter by status"
            subtitle={`${data.payments.length} transaction${data.payments.length === 1 ? '' : 's'} shown · BDT ${Number(data.total_completed).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} completed total.`}
          >
            <StatusFilters options={STATUSES} active={status} counts={data.counts} />
          </Card>

          <Card icon="bi-receipt" tone="is-green" title="Payments" subtitle="Newest first.">
            {data.payments.length === 0 ? (
              <EmptyState icon="bi-inbox" title="No transactions found" message="Try a different status filter." />
            ) : (
              <div className="admin-table-wrap">
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th>#</th><th>Payer</th><th>For</th><th>Amount</th>
                      <th>Method</th><th>Status</th><th>Paid at</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.payments.map((payment) => (
                      <tr key={payment.payment_id}>
                        <td>{payment.payment_id}</td>
                        <td>
                          {payment.payer}
                          {payment.payer_name && <small className="admin-meta">{payment.payer_name}</small>}
                        </td>
                        <td>
                          {payment.for.title || '—'}
                          {payment.for.detail && <small className="admin-meta">{payment.for.detail}</small>}
                        </td>
                        <td>BDT {Number(payment.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>{payment.method || '—'}</td>
                        <td><span className={`admin-badge is-${payment.status}`}>{payment.status_label}</span></td>
                        <td>{payment.paid_at_label || '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Card>
        </>
      )}
    </PageShell>
  );
}
