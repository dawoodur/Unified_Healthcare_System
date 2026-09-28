import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import client from '../api/client';
import PageShell, { Card, EmptyState } from '../components/PageShell';
import { Pager } from '../components/Widgets';

/**
 * Read-only. A conversation only shows up here once InboxService archived
 * it (the booking/order it existed for is done) and only for 7 days —
 * after that PurgeArchivedInboxConversationsCommand deletes it for good.
 * There is nothing to approve/reject/edit, so unlike the other admin
 * queues this page has no plain-form actions at all.
 */
function ConversationThread({ conversationId, onBack }) {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    setData(null);
    setError(null);
    client
      .get(`/inbox-history/${conversationId}`)
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load this conversation — it may have just been purged.'));
  }, [conversationId]);

  return (
    <Card
      icon="bi-chat-square-text"
      tone="is-teal"
      title={data ? `${data.participants[0].name} & ${data.participants[1].name}` : 'Loading…'}
      subtitle={data ? `Archived ${data.archived_label}` : undefined}
      actions={<button type="button" className="admin-btn is-ghost" onClick={onBack}><i className="bi bi-arrow-left" aria-hidden="true"></i> Back to list</button>}
    >
      {error && <p className="admin-meta">{error}</p>}
      {!data && !error && <p className="admin-meta">Loading conversation…</p>}
      {data && (
        data.messages.length === 0 ? (
          <EmptyState icon="bi-chat" title="No messages were ever sent in this conversation" />
        ) : (
          <div className="admin-inbox-thread">
            {data.messages.map((m) => (
              <div key={m.message_id} className={`admin-inbox-bubble ${m.sender_is_low ? 'is-low' : 'is-high'}`}>
                <strong>{m.sender_is_low ? data.participants[0].name : data.participants[1].name}</strong>
                <p>{m.message_text}</p>
                <small>{m.sent_at}</small>
              </div>
            ))}
          </div>
        )
      )}
    </Card>
  );
}

function ConversationCard({ conversation, onView }) {
  const [a, b] = conversation.participants;

  return (
    <Card
      icon="bi-chat-dots"
      tone="is-amber"
      title={`${a.name} (${a.role_label}) & ${b.name} (${b.role_label})`}
      subtitle={`${conversation.message_count} message(s) · archived ${conversation.archived_label}`}
      actions={<button type="button" className="admin-btn is-ghost" onClick={() => onView(conversation.conversation_id)}>View conversation</button>}
    >
      <p className="admin-meta" style={{ margin: 0 }}>
        {conversation.last_message_preview ? `"${conversation.last_message_preview}"` : <em>No messages were ever sent</em>}
      </p>
      <p className="admin-meta" style={{ margin: '6px 0 0' }}>
        <span className={`admin-badge is-${conversation.purge_in_days <= 1 ? 'rejected' : 'pending'}`}>
          {conversation.purge_in_days <= 0 ? 'purging today' : `purged in ${conversation.purge_in_days} day(s)`}
        </span>
      </p>
    </Card>
  );
}

export default function InboxHistory() {
  const [params] = useSearchParams();
  const page = params.get('page') || '1';
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [openConversationId, setOpenConversationId] = useState(null);

  function load() {
    setError(null);
    setData(null);
    client
      .get('/inbox-history', { params: { page } })
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load inbox history right now.'));
  }

  useEffect(load, [page]);

  return (
    <PageShell
      pageClass="admin-drafts-page"
      icon="bi-clock-history"
      title="Inbox history"
      subtitle="A patient's chat with a doctor, hospital, pharmacy or delivery agent closes automatically once the visit/order it was about is done. Read-only, and kept here for 7 days after it closes before being permanently deleted."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading history"
      loadingMessage="Fetching recently closed conversations…"
    >
      {data && (
        openConversationId ? (
          <ConversationThread conversationId={openConversationId} onBack={() => setOpenConversationId(null)} />
        ) : data.conversations.length === 0 ? (
          <Card icon="bi-inbox" tone="is-teal" title="Nothing here">
            <EmptyState icon="bi-check2-circle" title="No recently closed conversations" message="Conversations appear here for 7 days after a booking or order finishes." />
          </Card>
        ) : (
          <>
            {data.conversations.map((c) => (
              <ConversationCard key={c.conversation_id} conversation={c} onView={setOpenConversationId} />
            ))}
            <Pager pagination={data.pagination} />
          </>
        )
      )}
    </PageShell>
  );
}
