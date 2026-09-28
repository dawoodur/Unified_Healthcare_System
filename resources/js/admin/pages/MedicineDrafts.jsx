import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import client from '../api/client';
import PageShell, { Card, EmptyState } from '../components/PageShell';
import { StatusFilters, Pager, webUrl } from '../components/Widgets';

const STATUSES = [
  { value: 'pending', label: 'To review' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
];

const SUMMARY = [
  { key: 'pending', caption: 'Waiting for review', icon: 'bi-hourglass-split', tone: 'amber' },
  { key: 'approved', caption: 'Approved', icon: 'bi-check2-circle', tone: 'green' },
  { key: 'rejected', caption: 'Rejected', icon: 'bi-x-circle', tone: 'red' },
];

/**
 * A pending draft is editable before it is published: the source is a US drug
 * label written for a different market, so the wording usually needs work
 * before a patient here should read it. Both forms post to the existing web
 * routes, which own publishing to medicine_generic_info.
 */
function DraftCard({ draft, csrf }) {
  const meta = [
    `Fetched from ${draft.source}`,
    draft.queried_as ? `searched as ${draft.queried_as}` : null,
    draft.created_label,
  ].filter(Boolean).join(' · ');

  return (
    <Card
      icon="bi-capsule"
      tone={draft.status === 'pending' ? 'is-amber' : 'is-teal'}
      title={draft.generic_name}
      subtitle={meta}
      actions={draft.source_url && (
        <a className="admin-btn is-ghost" href={draft.source_url} target="_blank" rel="noopener noreferrer">
          <i className="bi bi-box-arrow-up-right" aria-hidden="true"></i> Original label
        </a>
      )}
    >
      {draft.status === 'pending' ? (
        <>
          <form
            method="POST"
            action={webUrl(`/medicine-drafts/${draft.draft_id}/approve`)}
            className="admin-form"
            data-confirm="Publish this to patients?"
          >
            <input type="hidden" name="_token" value={csrf} />

            <div className="admin-field">
              <label htmlFor={`uses-${draft.draft_id}`}>What it is used for (shown to patients)</label>
              <textarea
                id={`uses-${draft.draft_id}`}
                name="uses_en"
                rows={3}
                required
                maxLength={2000}
                defaultValue={draft.uses_en || ''}
              />
            </div>

            <div className="admin-field">
              <label htmlFor={`cautions-${draft.draft_id}`}>Cautions — one per line</label>
              <textarea
                id={`cautions-${draft.draft_id}`}
                name="cautions_en"
                rows={4}
                maxLength={3000}
                defaultValue={draft.cautions_text}
              />
            </div>

            <label className="admin-check">
              <input
                type="checkbox"
                name="is_prescription_only"
                value="1"
                defaultChecked={draft.suggested_prescription_only}
              />
              Prescription-only (patients are told not to self-medicate with it)
            </label>

            <div className="admin-row-actions">
              <button type="submit" className="admin-btn is-primary">Approve &amp; publish</button>
            </div>
          </form>

          <form
            method="POST"
            action={webUrl(`/medicine-drafts/${draft.draft_id}/reject`)}
            data-confirm="Reject this draft?"
            style={{ marginTop: '10px' }}
          >
            <input type="hidden" name="_token" value={csrf} />
            <button type="submit" className="admin-btn is-danger">Reject</button>
          </form>
        </>
      ) : (
        <>
          <p style={{ margin: '0 0 8px' }}><strong>Uses:</strong> {draft.uses_en}</p>
          {draft.cautions_en.length > 0 && (
            <ul>
              {draft.cautions_en.map((caution, index) => <li key={index}>{caution}</li>)}
            </ul>
          )}
          <p className="admin-meta">
            <span className={`admin-badge is-${draft.status}`}>{draft.status}</span>
            {draft.reviewed_label && ` ${draft.reviewed_label}`}
          </p>
        </>
      )}
    </Card>
  );
}

export default function MedicineDrafts() {
  const [params] = useSearchParams();
  const status = params.get('status') || 'pending';
  const page = params.get('page') || '1';
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const csrf = window.CSRF_TOKEN || '';

  function load() {
    setError(null);
    setData(null);
    client
      .get('/medicine-drafts', { params: { status, page } })
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load medicine drafts right now.'));
  }

  useEffect(load, [status, page]);

  return (
    <PageShell
      pageClass="admin-drafts-page"
      icon="bi-capsule"
      title="Medicine information drafts"
      subtitle="Information downloaded from openFDA for medicines the health chat cannot explain yet. Patients never see a draft — it reaches them only when you approve it here. The source is a US drug label, so read it, edit the wording to suit patients here, and reject anything that does not fit."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading drafts"
      loadingMessage="Fetching the review queue…"
    >
      {data && (
        <>
          <section className="admin-stats" aria-label="Draft queue summary">
            {SUMMARY.map((item) => (
              <article className={`admin-stat is-${item.tone}`} key={item.key}>
                <span className="admin-stat-icon"><i className={`bi ${item.icon}`} aria-hidden="true"></i></span>
                <div>
                  <strong>{data.counts[item.key] ?? 0}</strong>
                  <span>{item.caption}</span>
                </div>
              </article>
            ))}
            <article className="admin-stat is-violet">
              <span className="admin-stat-icon"><i className="bi bi-question-circle" aria-hidden="true"></i></span>
              <div>
                <strong>{data.needing_info}</strong>
                <span>Medicines still needing info</span>
              </div>
            </article>
          </section>

          <Card icon="bi-funnel" tone="is-blue" title="Filter by status">
            <StatusFilters options={STATUSES} active={status} counts={data.counts} />
          </Card>

          {data.drafts.length === 0 ? (
            <Card icon="bi-inbox" tone="is-teal" title="Nothing here">
              <EmptyState icon="bi-check2-circle" title={`No ${status} drafts`} message="Try another status, or fetch more below." />
            </Card>
          ) : (
            data.drafts.map((draft) => <DraftCard key={draft.draft_id} draft={draft} csrf={csrf} />)
          )}

          <Pager pagination={data.pagination} />

          <Card icon="bi-terminal" tone="is-violet" title="Fetch more" subtitle="Run this when the machine has internet. It never runs during a patient's chat, so the assistant keeps working offline.">
            <pre className="admin-code">{'php artisan medicines:fetch-info\nphp artisan medicines:fetch-info --generic=metformin'}</pre>
          </Card>
        </>
      )}
    </PageShell>
  );
}
