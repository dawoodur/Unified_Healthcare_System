import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { Card, EmptyState } from '../components/PageShell';
import { webUrl } from '../components/Widgets';

/**
 * Approve/reject post plain forms to the existing web routes, which own the
 * side effect (flipping the doctor's own verification_status, which is what
 * makes them visible in patient search) and redirect back here.
 */
export default function DoctorVerifications() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    client
      .get('/doctor-verifications')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load doctor verifications right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="admin-verifications-page"
      icon="bi-patch-check"
      title="Doctor verifications"
      subtitle="Review uploaded certificates. Approving one makes that doctor visible in patient search; rejecting keeps them hidden."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading certificates"
      loadingMessage="Checking the review queue…"
    >
      {data && (
        <>
          <Card
            icon="bi-hourglass-split"
            tone="is-amber"
            title="Pending review"
            subtitle={`${data.pending.length} certificate${data.pending.length === 1 ? '' : 's'} waiting.`}
          >
            {data.pending.length === 0 ? (
              <EmptyState icon="bi-check2-circle" title="Nothing waiting on review" message="Every uploaded certificate has been decided." />
            ) : (
              <div className="admin-table-wrap">
                <table className="admin-table">
                  <thead>
                    <tr><th>Doctor</th><th>Uploaded</th><th>Certificate</th><th>Decision</th></tr>
                  </thead>
                  <tbody>
                    {data.pending.map((certificate) => (
                      <tr key={certificate.certificate_id}>
                        <td>
                          Dr. {certificate.doctor_name || '—'}
                          {certificate.doctor_uid && <small className="admin-meta">{certificate.doctor_uid}</small>}
                        </td>
                        <td>{certificate.uploaded_label}</td>
                        <td>
                          <a
                            href={webUrl(`/doctor-verifications/${certificate.certificate_id}/download`)}
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            View file
                          </a>
                        </td>
                        <td>
                          <div className="admin-row-actions">
                            <form
                              method="POST"
                              action={webUrl(`/doctor-verifications/${certificate.certificate_id}/approve`)}
                              data-confirm={`Approve Dr. ${certificate.doctor_name || 'this doctor'}'s certificate? They will become visible to patients.`}
                            >
                              <input type="hidden" name="_token" value={csrf} />
                              <button type="submit" className="admin-btn is-primary">Approve</button>
                            </form>
                            <form
                              method="POST"
                              action={webUrl(`/doctor-verifications/${certificate.certificate_id}/reject`)}
                              data-confirm={`Reject Dr. ${certificate.doctor_name || 'this doctor'}'s certificate?`}
                            >
                              <input type="hidden" name="_token" value={csrf} />
                              <button type="submit" className="admin-btn is-danger">Reject</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Card>

          <Card
            icon="bi-clock-history"
            tone="is-teal"
            title="Previously reviewed"
            subtitle={`${data.reviewed.length} decided.`}
          >
            {data.reviewed.length === 0 ? (
              <EmptyState icon="bi-archive" title="No certificates reviewed yet" />
            ) : (
              <div className="admin-table-wrap">
                <table className="admin-table">
                  <thead>
                    <tr><th>Doctor</th><th>Uploaded</th><th>Certificate</th><th>Status</th><th>Reviewed at</th></tr>
                  </thead>
                  <tbody>
                    {data.reviewed.map((certificate) => (
                      <tr key={certificate.certificate_id}>
                        <td>
                          Dr. {certificate.doctor_name || '—'}
                          {certificate.doctor_uid && <small className="admin-meta">{certificate.doctor_uid}</small>}
                        </td>
                        <td>{certificate.uploaded_label}</td>
                        <td>
                          <a
                            href={webUrl(`/doctor-verifications/${certificate.certificate_id}/download`)}
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            View file
                          </a>
                        </td>
                        <td><span className={`admin-badge is-${certificate.status}`}>{certificate.status_label}</span></td>
                        <td>{certificate.reviewed_label || '—'}</td>
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
