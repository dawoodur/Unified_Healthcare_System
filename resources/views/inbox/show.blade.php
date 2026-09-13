@extends('layouts.app')
@section('title', 'Conversation with ' . $other->displayName())
@section('content')
<div class="card">
  <h1>{{ $other->displayName() }} <span class="badge">{{ ucfirst($other->role) }}</span></h1>
  <p class="muted">{{ $other->uidTag() }}</p>
  <p><a href="{{ route('inbox.index') }}">&larr; Back to inbox</a></p>
</div>

<div class="card">
  <div id="chat-log" style="height:360px;overflow-y:auto;border:1px solid var(--bs-border-color);border-radius:var(--bs-border-radius);padding:0.75rem;margin-bottom:0.75rem;display:flex;flex-direction:column;gap:0.5rem;"></div>
  <form id="chat-form" style="display:flex;gap:0.5rem;">
    <input type="text" id="chat-input" placeholder="Type a message..." maxlength="1000" style="flex:1;" autocomplete="off">
    <button type="submit" class="btn" style="width:auto;">Send</button>
  </form>
</div>

<script>
(function () {
  const myAccountId = {{ auth()->id() }};
  const csrfToken = '{{ csrf_token() }}';
  const sendUrl = '{{ route('inbox.send', $conversation) }}';
  const pollUrl = '{{ route('inbox.poll', $conversation) }}';
  const chatLog = document.getElementById('chat-log');
  const chatForm = document.getElementById('chat-form');
  const chatInput = document.getElementById('chat-input');

  let lastSeenMessageId = 0;

  function appendMessage(message) {
    lastSeenMessageId = Math.max(lastSeenMessageId, message.message_id);

    const isOwn = message.sender_account_id === myAccountId;
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble ' + (isOwn ? 'chat-bubble-own' : 'chat-bubble-other');

    const text = document.createElement('div');
    text.textContent = message.message_text; // textContent, never innerHTML — the other person's message is untrusted input
    bubble.appendChild(text);

    const meta = document.createElement('span');
    meta.className = 'chat-meta';
    meta.textContent = message.sent_at;
    bubble.appendChild(meta);

    chatLog.appendChild(bubble);
    chatLog.scrollTop = chatLog.scrollHeight;
  }

  const history = @json($messages);
  history.forEach(appendMessage);

  async function sendMessage(text) {
    const response = await fetch(sendUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ message_text: text }),
    });
    const data = await response.json();
    if (data.ok) {
      appendMessage(data.message);
    }
  }

  async function poll() {
    const response = await fetch(pollUrl + '?since=' + lastSeenMessageId);
    const data = await response.json();
    data.messages.forEach(appendMessage);
  }

  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    chatInput.value = '';
    sendMessage(text).catch(() => {});
  });

  setInterval(poll, 2000);
})();
</script>
@endsection
