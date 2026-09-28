import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import client from '../api/client';
import PageShell, { Card, EmptyState } from '../components/PageShell';
import { StatusFilters, Pager, webUrl } from '../components/Widgets';

const STATUSES = [
  { value: 'pending_review', label: 'To review' },
  { value: 'verified', label: 'Verified' },
  { value: 'rejected', label: 'Rejected' },
];

const SUMMARY = [
  { key: 'pending_review', caption: 'Waiting for review', icon: 'bi-hourglass-split', tone: 'amber' },
  { key: 'verified', caption: 'Verified', icon: 'bi-patch-check', tone: 'green' },
  { key: 'rejected', caption: 'Rejected', icon: 'bi-x-circle', tone: 'red' },
];

/**
 * A patient scanning in their own paper prescription unlocks ordering its
 * medicines only once an admin confirms the document here — see
 * Patient::unverifiedScannedMedicineIds() for the gate this Approve/Reject
 * pair actually flips. Both forms post to the existing web routes, which
 * own that side effect, same convention as every other admin queue.
 */
function ScanCard({ prescription, csrf }) {
  const meta = [
    prescription.issued_label,
    prescription.external_doctor_name ? `Dr. ${prescription.external_doctor_name}` : null,
    prescription.external_hospital_name,
  ].filter(Boolean).join(' · ');

  return (
    <Card
      icon="bi-camera"
      tone={prescription.status === 'pending_review' ? 'is-amber' : 'is-teal'}
      title={prescription.patient_name}
      subtitle={meta}
      actions={prescription.document_url && (
        <a className="admin-btn is-ghost" href={prescription.document_url} target="_blank" rel="noopener noreferrer">
          <i className="bi bi-file-earmark-image" aria-hidden="true"></i> View document
        </a>
      )}
    >
      <p className="admin-meta" style={{ margin: '0 0 8px' }}>
        Name matched on document: <strong>{prescription.scanned_patient_name}</strong>
      </p>

      {prescription.diagnosis_notes && (
        <p style={{ margin: '0 0 8px' }}>{prescription.diagnosis_notes}</p>
      )}

      {prescription.medicines.length > 0 && (
        <>
          <p className="admin-meta" style={{ margin: '4px 0' }}><strong>Medicines</strong></p>
          <ul>
            {prescription.medicines.map((name, index) => <li key={index}>{name}</li>)}
          </ul>
        </>
      )}

      {prescription.facility_items.length > 0 && (
        <>
          <p className="admin-meta" style={{ margin: '4px 0' }}><strong>Tests / facilities</strong></p>
          <ul>
            {prescription.facility_items.map((item, index) => <li key={index}>{item.name} <span className="admin-meta">({item.category})</span></li>)}
          </ul>
        </>
      )}

      {prescription.status === 'pending_review' ? (
        <div className="admin-row-actions">
          <form method="POST" action={webUrl(`/scanned-prescriptions/${prescription.prescription_id}/approve`)} data-confirm="Verify this scanned prescription?">
            <input type="hidden" name="_token" value={csrf} />
            <button type="submit" className="admin-btn is-primary">Verify</button>
          </form>
          <form method="POST" action={webUrl(`/scanned-prescriptions/${prescription.prescription_id}/reject`)} data-confirm="Reject this scanned prescription?">
            <input type="hidden" name="_token" value={csrf} />
            <button type="submit" className="admin-btn is-danger">Reject</button>
          </form>
        </div>
      ) : (
        <p className="admin-meta">
          <span className={`admin-badge is-${prescription.status === 'verified' ? 'approved' : 'rejected'}`}>{prescription.status}</span>
          {prescription.reviewed_label && ` by ${prescription.reviewed_label}`}
        </p>
      )}
    </Card>
  );
}

export default function ScannedPrescriptions() {
  const [params] = useSearchParams();
  const status = params.get('status') || 'pending_review';
  const page = params.get('page') || '1';
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    setData(null);
    client
      .get('/scanned-prescriptions', { params: { status, page } })
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load scanned prescriptions right now.'));
  }

  useEffect(load, [status, page]);

  return (
    <PageShell
      pageClass="admin-drafts-page"
      icon="bi-camera"
      title="Scanned prescriptions"
      subtitle="Patients can scan in a paper prescription or lab report themselves. Its medicines already show up on the patient's account, but ordering them stays blocked until you check the attached document here and verify or reject it."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading scans"
      loadingMessage="Fetching the review queue…"
    >
      {data && (
        <>
          <section className="admin-stats" aria-label="Scan queue summary">
            {SUMMARY.map((item) => (
              <article className={`admin-stat is-${item.tone}`} key={item.key}>
                <span className="admin-stat-icon"><i className={`bi ${item.icon}`} aria-hidden="true"></i></span>
                <div>
                  <strong>{data.counts[item.key] ?? 0}</strong>
                  <span>{item.caption}</span>
                </div>
              </article>
            ))}
          </section>

          <Card icon="bi-funnel" tone="is-blue" title="Filter by status">
            <StatusFilters options={STATUSES} active={status} counts={data.counts} />
          </Card>

          {data.prescriptions.length === 0 ? (
            <Card icon="bi-inbox" tone="is-teal" title="Nothing here">
              <EmptyState icon="bi-check2-circle" title={`No ${status.replace('_', ' ')} scans`} message="Try another status." />
            </Card>
          ) : (
            data.prescriptions.map((prescription) => (
              <ScanCard key={prescription.prescription_id} prescription={prescription} csrf={csrf} />
            ))
          )}

          <Pager pagination={data.pagination} />
        </>
      )}
    </PageShell>
  );
}
