@extends('layouts.app')
@section('title', 'Consultation')
@section('content')
@php
  $isDoctor = auth()->user()->role === 'doctor';
  $otherPartyName = $isDoctor ? $appointment->patient->full_name : ('Dr. ' . $appointment->doctor->full_name);
@endphp

<div class="card">
  <h1>Consultation with {{ $otherPartyName }}</h1>
  <p class="muted">
    {{ $appointment->appointment_date->format('D, M j Y') }} at
    {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}
    &middot; Serial #{{ $appointment->serial_number }}
  </p>
  <p id="call-status" class="muted">Not connected yet — click "Start Camera" to begin.</p>
</div>

<div class="card">
  <div class="grid grid-2">
    <div>
      <p class="muted" style="margin-bottom:0.3rem;">You</p>
      <video id="local-video" autoplay playsinline muted style="width:100%;background:#000;border-radius:8px;"></video>
    </div>
    <div>
      <p class="muted" style="margin-bottom:0.3rem;">{{ $otherPartyName }}</p>
      <video id="remote-video" autoplay playsinline style="width:100%;background:#000;border-radius:8px;"></video>
    </div>
  </div>

  <div style="margin-top:1rem;">
    <button id="start-btn" type="button" class="btn">Start Camera</button>
    <button id="hangup-btn" type="button" class="btn btn-danger" disabled>Hang Up</button>
  </div>
</div>

<div class="card">
  <h2>Chat</h2>
  <div id="chat-log" style="height:260px;overflow-y:auto;border:1px solid var(--bs-border-color);border-radius:var(--bs-border-radius);padding:0.75rem;margin-bottom:0.75rem;display:flex;flex-direction:column;gap:0.5rem;"></div>
  <form id="chat-form" style="display:flex;gap:0.5rem;">
    <input type="text" id="chat-input" placeholder="Type a message..." maxlength="1000" style="flex:1;" autocomplete="off">
    <button type="submit" class="btn" style="width:auto;">Send</button>
  </form>
</div>

@if ($isDoctor)
  @if ($appointment->status !== 'completed')
    <div class="card">
      <h2>Finished the consultation?</h2>
      <p class="muted">Mark this visit as complete to write an e-prescription right here in the room.</p>
      <form method="POST" action="{{ route('doctor.appointments.visited', $appointment) }}">
        @csrf
        <button type="submit" class="btn">Mark as Visited</button>
      </form>
    </div>
  @elseif ($appointment->prescription)
    <div class="card">
      <h2>Prescription</h2>
      <p class="alert alert-success">Prescription issued for this visit.</p>
    </div>
  @elseif ($canWritePrescription)
    <div class="card">
      <h2>E-Prescription</h2>
      <p class="muted">Write it here — no need to leave the call.</p>
    </div>
    @include('doctor.partials.prescription-form', compact('appointment', 'draft', 'medicines', 'draftMedicines', 'allergies'))
  @endif
@endif

<script>
(function () {
  // --- Setup: values the rest of this script needs, filled in by Blade ---
  const appointmentId = {{ $appointment->appointment_id }};
  const csrfToken = '{{ csrf_token() }}';
  const signalUrl = '{{ route('consultation.signal', $appointment) }}';
  const pollUrl = '{{ route('consultation.poll', $appointment) }}';
  // Deciding who makes the first move avoids both sides trying to call each
  // other at once — the patient always creates the offer, the doctor always
  // answers it. Simple and deterministic, no coordination needed beyond that.
  const isCaller = {{ $isDoctor ? 'false' : 'true' }};

  const localVideo = document.getElementById('local-video');
  const remoteVideo = document.getElementById('remote-video');
  const startBtn = document.getElementById('start-btn');
  const hangupBtn = document.getElementById('hangup-btn');
  const statusEl = document.getElementById('call-status');

  let peerConnection = null;
  let localStream = null;
  let lastSeenSignalId = 0;
  let pollTimer = null;

  function setStatus(text) {
    statusEl.textContent = text;
  }

  // Sends one handshake message to the server for the other browser to pick up.
  async function sendSignal(signalType, payloadObject) {
    await fetch(signalUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ signal_type: signalType, payload: JSON.stringify(payloadObject) }),
    });
  }

  function createPeerConnection() {
    // A public STUN server just helps two browsers behind home/office
    // routers discover how to reach each other directly — it does not
    // relay any actual call data.
    const pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });

    pc.onicecandidate = (event) => {
      if (event.candidate) {
        sendSignal('ice_candidate', event.candidate);
      }
    };

    pc.ontrack = (event) => {
      remoteVideo.srcObject = event.streams[0];
      setStatus('Connected.');
    };

    pc.onconnectionstatechange = () => {
      if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
        setStatus('Call disconnected.');
      }
    };

    return pc;
  }

  async function startCamera() {
    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    localVideo.srcObject = localStream;

    peerConnection = createPeerConnection();
    localStream.getTracks().forEach((track) => peerConnection.addTrack(track, localStream));

    startBtn.disabled = true;
    hangupBtn.disabled = false;
    setStatus('Waiting for ' + {!! json_encode($otherPartyName) !!} + ' to join...');

    if (isCaller) {
      const offer = await peerConnection.createOffer();
      await peerConnection.setLocalDescription(offer);
      await sendSignal('offer', offer);
    }

    pollTimer = setInterval(pollForSignals, 1500);
  }

  async function pollForSignals() {
    const response = await fetch(pollUrl + '?since=' + lastSeenSignalId);
    const data = await response.json();

    for (const signal of data.signals) {
      lastSeenSignalId = Math.max(lastSeenSignalId, signal.signal_id);
      const payload = JSON.parse(signal.payload);

      if (signal.signal_type === 'offer' && !isCaller) {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(payload));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        await sendSignal('answer', answer);
      } else if (signal.signal_type === 'answer' && isCaller) {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(payload));
      } else if (signal.signal_type === 'ice_candidate') {
        try {
          await peerConnection.addIceCandidate(new RTCIceCandidate(payload));
        } catch (e) {
          // Candidates that arrive before the remote description is set are
          // harmless to skip — WebRTC will still connect using the others.
        }
      } else if (signal.signal_type === 'hangup') {
        endCall(false);
      }
    }
  }

  function endCall(notifyOther) {
    if (pollTimer) clearInterval(pollTimer);
    if (notifyOther) sendSignal('hangup', {});
    if (peerConnection) peerConnection.close();
    if (localStream) localStream.getTracks().forEach((track) => track.stop());

    startBtn.disabled = false;
    hangupBtn.disabled = true;
    setStatus('Call ended.');
  }

  startBtn.addEventListener('click', () => startCamera().catch((e) => setStatus('Could not access camera/microphone: ' + e.message)));
  hangupBtn.addEventListener('click', () => endCall(true));

  // --- Chat: independent of the video call — works as soon as the room
  // loads, no need to click "Start Camera" first. Same polling idea as
  // the WebRTC signals above, just its own timer and its own "since" cursor.
  const myAccountId = {{ auth()->id() }};
  const chatSendUrl = '{{ route('consultation.chat.send', $appointment) }}';
  const chatPollUrl = '{{ route('consultation.chat.poll', $appointment) }}';
  const chatLog = document.getElementById('chat-log');
  const chatForm = document.getElementById('chat-form');
  const chatInput = document.getElementById('chat-input');

  let lastSeenMessageId = 0;

  function appendChatMessage(message) {
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

  // Pre-fill with whatever was already said before this page load.
  const chatHistory = @json($chatHistory);
  chatHistory.forEach(appendChatMessage);

  async function sendChatMessage(text) {
    const response = await fetch(chatSendUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ message_text: text }),
    });
    const data = await response.json();
    if (data.ok) {
      appendChatMessage(data.message);
    }
  }

  async function pollChat() {
    const response = await fetch(chatPollUrl + '?since=' + lastSeenMessageId);
    const data = await response.json();
    data.messages.forEach(appendChatMessage);
  }

  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    chatInput.value = '';
    sendChatMessage(text).catch(() => {});
  });

  setInterval(pollChat, 2000);
})();
</script>
@endsection
