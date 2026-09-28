import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import client from '../api/client';
import PageShell, { Card, EmptyState } from '../components/PageShell';
import { Pager } from '../components/Widgets';

/**
 * Read-only. A video consultation's in-call chat is normally destroyed the
 * moment the call ends (see ConsultationChatService) — this page shows the
 * one exception: a snapshot taken at that same moment, for admin review
 * only, kept for 7 days before ConsultationChatArchivePurgeCommand
 * deletes it for good. Neither the patient nor the doctor can see this —
 * their own copy is still gone the instant the call ends, exactly as before.
 */
function ChatThread({ archiveId, onBack }) {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    setData(null);
    setError(null);
    client
      .get(`/consultation-chat-history/${archiveId}`)
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load this chat — it may have just been purged.'));
  }, [archiveId]);

  return (
    <Card
      icon="bi-camera-video"
      tone="is-teal"
      title={data ? `${data.patient.name} & Dr. ${data.doctor.name}` : 'Loading…'}
      subtitle={data ? `Consultation ended ${data.archived_label}` : undefined}
      actions={<button type="button" className="admin-btn is-ghost" onClick={onBack}><i className="bi bi-arrow-left" aria-hidden="true"></i> Back to list</button>}
    >
      {error && <p className="admin-meta">{error}</p>}
      {!data && !error && <p className="admin-meta">Loading chat…</p>}
      {data && (
        data.messages.length === 0 ? (
          <EmptyState icon="bi-chat" title="Nothing was shared in this call" />
        ) : (
          <div className="admin-inbox-thread">
            {data.messages.map((m) => (
              <div key={m.archive_message_id} className={`admin-inbox-bubble ${m.sender_is_patient ? 'is-low' : 'is-high'}`}>
                <strong>{m.sender_is_patient ? data.patient.name : `Dr. ${data.doctor.name}`}</strong>
                {m.type === 'photo' ? (
                  <a href={m.photo_url} target="_blank" rel="noopener noreferrer">
                    <img src={m.photo_url} alt="Shared during consultation" style={{ maxWidth: '220px', borderRadius: '8px', display: 'block', marginTop: '4px' }} />
                  </a>
                ) : (
                  <p>{m.message_text}</p>
                )}
                <small>{m.sent_at_label}</small>
              </div>
            ))}
          </div>
        )
      )}
    </Card>
  );
}

function ArchiveCard({ archive, onView }) {
  return (
    <Card
      icon="bi-camera-video"
      tone="is-amber"
      title={`${archive.patient.name} & Dr. ${archive.doctor.name}`}
      subtitle={`Appointment #${archive.appointment_id} · ended ${archive.archived_label}`}
      actions={<button type="button" className="admin-btn is-ghost" onClick={() => onView(archive.archive_id)}>View chat</button>}
    >
      <p className="admin-meta" style={{ margin: 0 }}>
        {archive.text_count} message(s){archive.photo_count > 0 ? `, ${archive.photo_count} photo(s)` : ''}
      </p>
      <p className="admin-meta" style={{ margin: '6px 0 0' }}>
        <span className={`admin-badge is-${archive.purge_in_days <= 1 ? 'rejected' : 'pending'}`}>
          {archive.purge_in_days <= 0 ? 'purging today' : `purged in ${archive.purge_in_days} day(s)`}
        </span>
      </p>
    </Card>
  );
}

export default function ConsultationChatHistory() {
  const [params] = useSearchParams();
  const page = params.get('page') || '1';
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [openArchiveId, setOpenArchiveId] = useState(null);

  function load() {
    setError(null);
    setData(null);
    client
      .get('/consultation-chat-history', { params: { page } })
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load consultation chat history right now.'));
  }

  useEffect(load, [page]);

  return (
    <PageShell
      pageClass="admin-drafts-page"
      icon="bi-camera-video"
      title="Consultation chat history"
      subtitle="A video consultation's in-call chat (text and shared photos) is destroyed for the patient and doctor the moment the call ends. A snapshot is kept here, for admin review only, for 7 days before it's permanently deleted."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading history"
      loadingMessage="Fetching recently ended consultations…"
    >
      {data && (
        openArchiveId ? (
          <ChatThread archiveId={openArchiveId} onBack={() => setOpenArchiveId(null)} />
        ) : data.archives.length === 0 ? (
          <Card icon="bi-inbox" tone="is-teal" title="Nothing here">
            <EmptyState icon="bi-check2-circle" title="No recently ended consultations with chat" message="A consultation only appears here if something was actually typed or shared during the call." />
          </Card>
        ) : (
          <>
            {data.archives.map((archive) => (
              <ArchiveCard key={archive.archive_id} archive={archive} onView={setOpenArchiveId} />
            ))}
            <Pager pagination={data.pagination} />
          </>
        )
      )}
    </PageShell>
  );
}
