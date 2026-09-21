import React, { useEffect, useRef, useState } from 'react';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function doctorInboxInitials(name) {
  return String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('');
}

function DoctorInboxFooter() {
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

function DoctorInboxStatCard({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-inbox-stat is-${tone}`}>
      <span className="doctor-inbox-stat-icon">
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

function DoctorInboxAvatar({ account, size = 'normal' }) {
  if (account?.photo_url) {
    return (
      <img
        className={`doctor-inbox-avatar is-${size}`}
        src={account.photo_url}
        alt=""
      />
    );
  }

  return (
    <span className={`doctor-inbox-avatar doctor-inbox-avatar-fallback is-${size}`}>
      {doctorInboxInitials(account?.name) || 'H'}
    </span>
  );
}

export default function DoctorInboxPage() {
  const [data, setData] = useState(null);
  const [selectedConversationId, setSelectedConversationId] = useState(null);
  const [thread, setThread] = useState(null);
  const [loadingThread, setLoadingThread] = useState(false);
  const [threadError, setThreadError] = useState(null);
  const [pageError, setPageError] = useState(null);
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState('all');
  const [draft, setDraft] = useState('');
  const [sending, setSending] = useState(false);
  const [showContacts, setShowContacts] = useState(false);
  const [startingContactId, setStartingContactId] = useState(null);
  const messagesEndRef = useRef(null);

  function loadThread(conversationId, silent = false) {
    if (!conversationId) {
      setThread(null);
      return Promise.resolve();
    }

    if (!silent) {
      setLoadingThread(true);
    }

    setThreadError(null);

    return client
      .get(`/inbox/${conversationId}`)
      .then((res) => {
        setThread(res.data);
        setSelectedConversationId(Number(conversationId));
      })
      .catch(() => {
        if (!silent) {
          setThreadError('Could not load this conversation.');
        }
      })
      .finally(() => {
        if (!silent) {
          setLoadingThread(false);
        }
      });
  }

  function loadInbox(preferredConversationId = null) {
    setPageError(null);

    return client
      .get('/inbox')
      .then((res) => {
        setData(res.data);

        const conversations = res.data.conversations || [];
        const preferred = preferredConversationId
          ? Number(preferredConversationId)
          : null;

        const currentStillExists = selectedConversationId
          && conversations.some(
            (item) => Number(item.conversation_id) === Number(selectedConversationId)
          );

        const nextId = preferred
          || (currentStillExists ? Number(selectedConversationId) : null)
          || conversations[0]?.conversation_id
          || null;

        if (nextId) {
          return loadThread(nextId);
        }

        setSelectedConversationId(null);
        setThread(null);
        return null;
      })
      .catch(() => {
        setPageError('Could not load your inbox right now.');
      });
  }

  useEffect(() => {
    loadInbox();
  }, []);

  useEffect(() => {
    if (!selectedConversationId) return undefined;

    const timer = window.setInterval(() => {
      loadThread(selectedConversationId, true);
    }, 10000);

    return () => window.clearInterval(timer);
  }, [selectedConversationId]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({
      behavior: 'smooth',
      block: 'end',
    });
  }, [thread?.messages?.length]);

  function selectConversation(conversationId) {
    setShowContacts(false);
    setSelectedConversationId(Number(conversationId));
    loadThread(conversationId);
  }

  function startConversation(accountId) {
    setStartingContactId(Number(accountId));
    setThreadError(null);

    client
      .post('/inbox/start', { account_id: accountId })
      .then((res) => {
        setShowContacts(false);
        return loadInbox(res.data.conversation_id);
      })
      .catch((err) => {
        setThreadError(
          err.response?.data?.message || 'Could not start that conversation.'
        );
      })
      .finally(() => setStartingContactId(null));
  }

  function sendMessage(event) {
    event.preventDefault();

    const messageText = draft.trim();
    if (!messageText || !selectedConversationId || sending) return;

    setSending(true);
    setThreadError(null);

    client
      .post(`/inbox/${selectedConversationId}/send`, {
        message_text: messageText,
      })
      .then(() => {
        setDraft('');
        return Promise.all([
          loadThread(selectedConversationId, true),
          client.get('/inbox').then((res) => setData(res.data)),
        ]);
      })
      .catch((err) => {
        setThreadError(
          err.response?.data?.message || 'Could not send your message.'
        );
      })
      .finally(() => setSending(false));
  }

  if (pageError) {
    return (
      <div className="doctor-page doctor-inbox-page">
        <QuickNav />
        <div className="doctor-inbox-state doctor-card" role="alert">
          <span><i className="bi bi-exclamation-circle" aria-hidden="true"></i></span>
          <div>
            <strong>Inbox unavailable</strong>
            <p>{pageError}</p>
            <button type="button" className="btn" onClick={() => loadInbox()}>
              Try again
            </button>
          </div>
        </div>
        <DoctorInboxFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-inbox-page">
        <QuickNav />
        <div className="doctor-inbox-state doctor-card">
          <span><i className="bi bi-chat-square-text" aria-hidden="true"></i></span>
          <div>
            <strong>Loading inbox</strong>
            <p>Preparing your conversations…</p>
          </div>
        </div>
        <DoctorInboxFooter />
      </div>
    );
  }

  const conversations = data.conversations || [];
  const contacts = data.contacts || [];
  const stats = data.stats || {};

  const normalizedSearch = search.trim().toLowerCase();
  const filteredConversations = conversations.filter((conversation) => {
    if (filter === 'unread' && Number(conversation.unread_count || 0) === 0) {
      return false;
    }

    if (!normalizedSearch) return true;

    const haystack = [
      conversation.other?.name,
      conversation.other?.subtitle,
      conversation.last_message?.message_text,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    return haystack.includes(normalizedSearch);
  });

  const activeConversation = conversations.find(
    (conversation) =>
      Number(conversation.conversation_id) === Number(selectedConversationId)
  );

  return (
    <div className="doctor-page doctor-inbox-page">
      <QuickNav />

      <section className="doctor-inbox-hero">
        <div>
          <span className="doctor-inbox-eyebrow">DOCTOR INBOX</span>
          <h1>Inbox</h1>
          <p>
            Coordinate securely with hospitals connected to your practice and
            keep clinical operations in one communication space.
          </p>
        </div>

        <button
          type="button"
          className="doctor-inbox-new-button"
          onClick={() => setShowContacts((value) => !value)}
        >
          <i className={`bi ${showContacts ? 'bi-x-lg' : 'bi-plus-lg'}`} aria-hidden="true"></i>
          {showContacts ? 'Close contacts' : 'New conversation'}
        </button>
      </section>

      <section className="doctor-inbox-stats" aria-label="Inbox summary">
        <DoctorInboxStatCard
          icon="bi-chat-square-text"
          value={stats.total_conversations || 0}
          label="Conversations"
          hint="Active communication threads"
          tone="blue"
        />
        <DoctorInboxStatCard
          icon="bi-envelope-exclamation"
          value={stats.unread_messages || 0}
          label="Unread messages"
          hint="Waiting for your attention"
          tone="amber"
        />
        <DoctorInboxStatCard
          icon="bi-hospital"
          value={stats.assigned_contacts || 0}
          label="Hospital contacts"
          hint="Assigned to your practice"
          tone="teal"
        />
        <DoctorInboxStatCard
          icon="bi-send"
          value={stats.messages_today || 0}
          label="Messages today"
          hint="Across all conversations"
          tone="violet"
        />
      </section>

      <section className="doctor-inbox-shell">
        <aside className="doctor-inbox-list-panel">
          <header className="doctor-inbox-panel-header">
            <div>
              <span className="doctor-inbox-panel-icon is-blue">
                <i className={`bi ${showContacts ? 'bi-building' : 'bi-inbox'}`} aria-hidden="true"></i>
              </span>
              <div>
                <h2>{showContacts ? 'Hospital contacts' : 'Conversations'}</h2>
                <p>
                  {showContacts
                    ? 'Start or reopen a hospital conversation.'
                    : 'Your recent message threads.'}
                </p>
              </div>
            </div>

            {!showContacts && (
              <span className="doctor-inbox-count">{conversations.length}</span>
            )}
          </header>

          {showContacts ? (
            <div className="doctor-inbox-contacts">
              {contacts.length === 0 ? (
                <div className="doctor-inbox-list-empty">
                  <i className="bi bi-hospital" aria-hidden="true"></i>
                  <strong>No assigned hospital contacts</strong>
                  <span>Your active hospital assignments will appear here.</span>
                </div>
              ) : (
                contacts.map((contact) => (
                  <button
                    type="button"
                    className="doctor-inbox-contact"
                    key={contact.account_id}
                    onClick={() => startConversation(contact.account_id)}
                    disabled={startingContactId === Number(contact.account_id)}
                  >
                    <DoctorInboxAvatar account={contact} />
                    <span className="doctor-inbox-contact-copy">
                      <strong>{contact.name}</strong>
                      <small>{contact.subtitle || 'Hospital contact'}</small>
                    </span>
                    <span className="doctor-inbox-contact-action">
                      {startingContactId === Number(contact.account_id)
                        ? 'Opening…'
                        : 'Message'}
                    </span>
                  </button>
                ))
              )}
            </div>
          ) : (
            <>
              <div className="doctor-inbox-list-tools">
                <div className="doctor-inbox-search">
                  <i className="bi bi-search" aria-hidden="true"></i>
                  <input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search conversations…"
                    aria-label="Search conversations"
                  />
                </div>

                <div className="doctor-inbox-filter" aria-label="Conversation filter">
                  <button
                    type="button"
                    className={filter === 'all' ? 'is-active' : ''}
                    onClick={() => setFilter('all')}
                  >
                    All
                  </button>
                  <button
                    type="button"
                    className={filter === 'unread' ? 'is-active' : ''}
                    onClick={() => setFilter('unread')}
                  >
                    Unread
                    {Number(stats.unread_messages || 0) > 0 && (
                      <span>{stats.unread_messages}</span>
                    )}
                  </button>
                </div>
              </div>

              <div className="doctor-inbox-conversations">
                {filteredConversations.length === 0 ? (
                  <div className="doctor-inbox-list-empty">
                    <i className="bi bi-chat-square-dots" aria-hidden="true"></i>
                    <strong>No conversations found</strong>
                    <span>
                      {conversations.length
                        ? 'Try a different search or filter.'
                        : 'Start a conversation with an assigned hospital.'}
                    </span>
                  </div>
                ) : (
                  filteredConversations.map((conversation) => {
                    const isActive =
                      Number(conversation.conversation_id)
                      === Number(selectedConversationId);

                    return (
                      <button
                        type="button"
                        className={`doctor-inbox-conversation ${isActive ? 'is-active' : ''}`}
                        key={conversation.conversation_id}
                        onClick={() => selectConversation(conversation.conversation_id)}
                      >
                        <DoctorInboxAvatar account={conversation.other} />
                        <span className="doctor-inbox-conversation-copy">
                          <span className="doctor-inbox-conversation-line">
                            <strong>{conversation.other?.name || 'Hospital'}</strong>
                            <small>{conversation.last_message?.time_label || ''}</small>
                          </span>
                          <span className="doctor-inbox-conversation-line is-preview">
                            <span>
                              {conversation.last_message?.message_text
                                || 'No messages yet'}
                            </span>
                            {Number(conversation.unread_count || 0) > 0 && (
                              <b>{conversation.unread_count}</b>
                            )}
                          </span>
                        </span>
                      </button>
                    );
                  })
                )}
              </div>
            </>
          )}
        </aside>

        <div className="doctor-inbox-thread-panel">
          {threadError && (
            <div className="doctor-inbox-thread-error">
              <i className="bi bi-exclamation-triangle" aria-hidden="true"></i>
              {threadError}
            </div>
          )}

          {!selectedConversationId ? (
            <div className="doctor-inbox-thread-empty">
              <span><i className="bi bi-chat-heart" aria-hidden="true"></i></span>
              <h2>Select a conversation</h2>
              <p>
                Open an existing thread or start a new conversation with an
                assigned hospital.
              </p>
              <button type="button" onClick={() => setShowContacts(true)}>
                <i className="bi bi-plus-lg" aria-hidden="true"></i>
                New conversation
              </button>
            </div>
          ) : loadingThread && !thread ? (
            <div className="doctor-inbox-thread-empty">
              <span><i className="bi bi-arrow-repeat" aria-hidden="true"></i></span>
              <h2>Loading conversation</h2>
            </div>
          ) : thread ? (
            <>
              <header className="doctor-inbox-thread-header">
                <div>
                  <DoctorInboxAvatar account={thread.other} size="large" />
                  <div>
                    <strong>{thread.other?.name || 'Hospital'}</strong>
                    <span>{thread.other?.subtitle || 'Hospital contact'}</span>
                  </div>
                </div>

                <span className="doctor-inbox-secure-badge">
                  <i className="bi bi-shield-check" aria-hidden="true"></i>
                  Secure inbox
                </span>
              </header>

              <div className="doctor-inbox-messages">
                {thread.messages?.length === 0 ? (
                  <div className="doctor-inbox-no-messages">
                    <i className="bi bi-chat-square-text" aria-hidden="true"></i>
                    <strong>Start the conversation</strong>
                    <span>Send the first message using the box below.</span>
                  </div>
                ) : (
                  thread.messages.map((message) => (
                    <div
                      className={`doctor-inbox-message-row ${message.is_own ? 'is-own' : 'is-other'}`}
                      key={message.message_id}
                    >
                      {!message.is_own && (
                        <DoctorInboxAvatar account={thread.other} size="small" />
                      )}
                      <div className="doctor-inbox-message">
                        <p>{message.message_text}</p>
                        <span>{message.time_label}</span>
                      </div>
                    </div>
                  ))
                )}
                <div ref={messagesEndRef}></div>
              </div>

              <form className="doctor-inbox-composer" onSubmit={sendMessage}>
                <textarea
                  value={draft}
                  onChange={(event) => setDraft(event.target.value)}
                  rows={2}
                  maxLength={1000}
                  placeholder={`Message ${thread.other?.name || 'hospital'}…`}
                  aria-label="Message"
                  onKeyDown={(event) => {
                    if (
                      event.key === 'Enter'
                      && !event.shiftKey
                      && !event.nativeEvent.isComposing
                    ) {
                      event.preventDefault();
                      event.currentTarget.form?.requestSubmit();
                    }
                  }}
                />
                <div>
                  <small>{draft.length}/1000 · Enter to send · Shift+Enter for a new line</small>
                  <button
                    type="submit"
                    disabled={!draft.trim() || sending}
                  >
                    <i className="bi bi-send-fill" aria-hidden="true"></i>
                    {sending ? 'Sending…' : 'Send'}
                  </button>
                </div>
              </form>
            </>
          ) : (
            <div className="doctor-inbox-thread-empty">
              <span><i className="bi bi-chat-square-text" aria-hidden="true"></i></span>
              <h2>{activeConversation?.other?.name || 'Conversation'}</h2>
              <p>Could not display this thread right now.</p>
            </div>
          )}
        </div>
      </section>

      <DoctorInboxFooter />
    </div>
  );
}
