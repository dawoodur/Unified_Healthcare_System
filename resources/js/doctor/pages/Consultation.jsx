import React, { useEffect, useRef, useState } from 'react';
import axios from 'axios';
import { Link, useParams } from 'react-router-dom';
import client from '../api/client';
import QuickNav from '../components/QuickNav';

function DoctorConsultationFooter() {
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

function ConsultationStat({ icon, value, label, hint, tone }) {
  return (
    <article className={`doctor-consultation-stat is-${tone}`}>
      <span className="doctor-consultation-stat-icon">
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

function initials(name) {
  return String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('');
}

export default function Consultation() {
  const { appointmentId } = useParams();

  const [data, setData] = useState(null);
  const [pageError, setPageError] = useState(null);
  const [callStatus, setCallStatus] = useState('Not connected yet — start your camera when you are ready.');
  const [cameraBusy, setCameraBusy] = useState(false);
  const [callRunning, setCallRunning] = useState(false);
  const [messages, setMessages] = useState([]);
  const [chatClosed, setChatClosed] = useState(false);
  const [chatError, setChatError] = useState(null);
  const [draft, setDraft] = useState('');
  const [sending, setSending] = useState(false);

  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);
  const messagesEndRef = useRef(null);
  const photoInputRef = useRef(null);

  const peerConnectionRef = useRef(null);
  const localStreamRef = useRef(null);
  const signalTimerRef = useRef(null);
  const chatTimerRef = useRef(null);
  const lastSignalIdRef = useRef(0);
  const lastMessageIdRef = useRef(0);
  const mountedRef = useRef(true);

  const directAxios = axios.create({
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
  });

  function load() {
    setPageError(null);

    return client
      .get(`/consultation/${appointmentId}`)
      .then((res) => {
        if (!mountedRef.current) return;

        setData(res.data);
        setMessages(res.data.chat_history || []);
        setChatClosed(Boolean(res.data.chat_closed));

        const maxMessageId = (res.data.chat_history || []).reduce(
          (max, item) => Math.max(max, Number(item.message_id || 0)),
          0
        );
        lastMessageIdRef.current = maxMessageId;

        if (res.data.session.status === 'ended') {
          setCallStatus('This consultation has ended.');
        }
      })
      .catch((err) => {
        if (!mountedRef.current) return;
        setPageError(
          err.response?.data?.message || 'Could not load this consultation room.'
        );
      });
  }

  useEffect(() => {
    mountedRef.current = true;
    load();

    return () => {
      mountedRef.current = false;
      if (signalTimerRef.current) window.clearInterval(signalTimerRef.current);
      if (chatTimerRef.current) window.clearInterval(chatTimerRef.current);
      if (peerConnectionRef.current) peerConnectionRef.current.close();
      if (localStreamRef.current) {
        localStreamRef.current.getTracks().forEach((track) => track.stop());
      }
    };
  }, [appointmentId]);

  useEffect(() => {
    if (!data || chatClosed) return undefined;

    const poll = async () => {
      try {
        const response = await directAxios.get(
          `${data.endpoints.chat_poll}?since=${lastMessageIdRef.current}`
        );

        if (!mountedRef.current) return;

        if (response.data.ended) {
          handleChatEnded();
          return;
        }

        const incoming = response.data.messages || [];
        if (incoming.length) {
          setMessages((current) => {
            const known = new Set(current.map((item) => Number(item.message_id)));
            const additions = incoming.filter(
              (item) => !known.has(Number(item.message_id))
            );
            return additions.length ? [...current, ...additions] : current;
          });

          incoming.forEach((item) => {
            lastMessageIdRef.current = Math.max(
              lastMessageIdRef.current,
              Number(item.message_id || 0)
            );
          });
        }
      } catch {
        // A temporary polling failure should not close the consultation.
      }
    };

    chatTimerRef.current = window.setInterval(poll, 2000);
    return () => {
      if (chatTimerRef.current) window.clearInterval(chatTimerRef.current);
      chatTimerRef.current = null;
    };
  }, [data, chatClosed]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
  }, [messages.length]);

  async function sendSignal(signalType, payloadObject) {
    if (!data) return;

    await directAxios.post(data.endpoints.signal, {
      signal_type: signalType,
      payload: JSON.stringify(payloadObject),
    });
  }

  function createPeerConnection() {
    const peer = new RTCPeerConnection({
      iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
    });

    peer.onicecandidate = (event) => {
      if (event.candidate) {
        sendSignal('ice_candidate', event.candidate).catch(() => {});
      }
    };

    peer.ontrack = (event) => {
      if (remoteVideoRef.current) {
        remoteVideoRef.current.srcObject = event.streams[0];
      }
      setCallStatus('Connected.');
    };

    peer.onconnectionstatechange = () => {
      if (peer.connectionState === 'connected') {
        setCallStatus('Connected.');
      } else if (
        peer.connectionState === 'disconnected'
        || peer.connectionState === 'failed'
      ) {
        setCallStatus('Call disconnected.');
      }
    };

    return peer;
  }

  async function pollSignals() {
    if (!data || !peerConnectionRef.current) return;

    const response = await directAxios.get(
      `${data.endpoints.poll}?since=${lastSignalIdRef.current}`
    );

    for (const signal of response.data.signals || []) {
      lastSignalIdRef.current = Math.max(
        lastSignalIdRef.current,
        Number(signal.signal_id || 0)
      );

      const payload = JSON.parse(signal.payload);
      const peer = peerConnectionRef.current;
      if (!peer) return;

      if (signal.signal_type === 'offer') {
        await peer.setRemoteDescription(new RTCSessionDescription(payload));
        const answer = await peer.createAnswer();
        await peer.setLocalDescription(answer);
        await sendSignal('answer', answer);
      } else if (signal.signal_type === 'ice_candidate') {
        try {
          await peer.addIceCandidate(new RTCIceCandidate(payload));
        } catch {
          // Candidates can arrive before the remote description; later candidates are sufficient.
        }
      } else if (signal.signal_type === 'hangup') {
        endCall(false);
      }
    }
  }

  async function startCamera() {
    if (!data || data.session.status === 'ended' || cameraBusy || callRunning) return;

    setCameraBusy(true);
    setPageError(null);

    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: true,
      });

      localStreamRef.current = stream;
      if (localVideoRef.current) {
        localVideoRef.current.srcObject = stream;
      }

      const peer = createPeerConnection();
      peerConnectionRef.current = peer;
      stream.getTracks().forEach((track) => peer.addTrack(track, stream));

      setCallRunning(true);
      setCallStatus(`Waiting for ${data.appointment.patient.full_name} to join…`);

      await pollSignals();
      signalTimerRef.current = window.setInterval(() => {
        pollSignals().catch(() => {});
      }, 1500);
    } catch (error) {
      setCallStatus(`Could not access camera or microphone: ${error.message}`);
    } finally {
      setCameraBusy(false);
    }
  }

  function stopLocalMedia() {
    if (signalTimerRef.current) {
      window.clearInterval(signalTimerRef.current);
      signalTimerRef.current = null;
    }

    if (peerConnectionRef.current) {
      peerConnectionRef.current.close();
      peerConnectionRef.current = null;
    }

    if (localStreamRef.current) {
      localStreamRef.current.getTracks().forEach((track) => track.stop());
      localStreamRef.current = null;
    }

    if (localVideoRef.current) localVideoRef.current.srcObject = null;
    if (remoteVideoRef.current) remoteVideoRef.current.srcObject = null;
    setCallRunning(false);
  }

  function handleChatEnded() {
    setChatClosed(true);
    setMessages([]);
    lastMessageIdRef.current = 0;
    setChatError(null);

    if (chatTimerRef.current) {
      window.clearInterval(chatTimerRef.current);
      chatTimerRef.current = null;
    }
  }

  async function endCall(notifyOther = true) {
    if (!data) return;

    if (notifyOther) {
      sendSignal('hangup', {}).catch(() => {});
    }

    stopLocalMedia();
    setCallStatus('Call ended.');

    try {
      await directAxios.post(data.endpoints.end);
    } catch {
      // The other participant may already have ended the session.
    }

    handleChatEnded();
  }

  async function sendMessage(event) {
    event.preventDefault();

    const text = draft.trim();
    if (!text || sending || chatClosed || !data) return;

    setSending(true);
    setChatError(null);

    try {
      const response = await directAxios.post(data.endpoints.chat_send, {
        message_text: text,
      });

      if (response.data.ok && response.data.message) {
        const message = response.data.message;
        lastMessageIdRef.current = Math.max(
          lastMessageIdRef.current,
          Number(message.message_id || 0)
        );
        setMessages((current) => [...current, message]);
        setDraft('');
      }
    } catch (error) {
      if (error.response?.status === 409) {
        handleChatEnded();
      } else {
        setChatError('Could not send that message. Please try again.');
      }
    } finally {
      setSending(false);
    }
  }

  async function sendPhoto(file) {
    if (!file || !data || chatClosed) return;

    if (file.size > data.max_photo_bytes) {
      setChatError(
        `That photo is too large. Please use an image under ${Math.round(
          data.max_photo_bytes / (1024 * 1024)
        )} MB.`
      );
      return;
    }

    const body = new FormData();
    body.append('photo', file);

    setChatError(null);

    try {
      const response = await directAxios.post(data.endpoints.chat_photo, body);

      if (response.data.ok && response.data.message) {
        const message = response.data.message;
        lastMessageIdRef.current = Math.max(
          lastMessageIdRef.current,
          Number(message.message_id || 0)
        );
        setMessages((current) => [...current, message]);
      }
    } catch (error) {
      if (error.response?.status === 409) {
        handleChatEnded();
      } else if (error.response?.status === 422) {
        setChatError('Please share a JPG, PNG, or WebP image.');
      } else {
        setChatError('Could not share that photo. Please try again.');
      }
    }
  }

  function photoUrl(photoId) {
    return data.endpoints.chat_photo_show.replace(
      '__PHOTO__',
      encodeURIComponent(photoId)
    );
  }

  if (pageError && !data) {
    return (
      <div className="doctor-page doctor-consultation-page">
        <QuickNav />
        <div className="doctor-consultation-state doctor-card" role="alert">
          <span><i className="bi bi-camera-video-off" aria-hidden="true"></i></span>
          <div>
            <strong>Consultation unavailable</strong>
            <p>{pageError}</p>
            <Link to="/appointments">Back to appointments</Link>
          </div>
        </div>
        <DoctorConsultationFooter />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="doctor-page doctor-consultation-page">
        <QuickNav />
        <div className="doctor-consultation-state doctor-card">
          <span><i className="bi bi-camera-video" aria-hidden="true"></i></span>
          <div>
            <strong>Preparing consultation</strong>
            <p>Loading the secure video room…</p>
          </div>
        </div>
        <DoctorConsultationFooter />
      </div>
    );
  }

  const patient = data.appointment.patient;
  const sessionEnded = data.session.status === 'ended';

  return (
    <div className="doctor-page doctor-consultation-page">
      <QuickNav />

      <section className="doctor-consultation-hero">
        <div>
          <span className="doctor-consultation-eyebrow">VIDEO CONSULTATION</span>
          <h1>Consultation with {patient.full_name}</h1>
          <p>
            {data.appointment.date_label} · {data.appointment.time_range_label}
            {' · '}Queue #{data.appointment.serial_number}
          </p>
        </div>

        <Link className="doctor-consultation-back" to="/appointments">
          <i className="bi bi-arrow-left" aria-hidden="true"></i>
          Back to appointments
        </Link>
      </section>

      <section className="doctor-consultation-stats" aria-label="Consultation summary">
        <ConsultationStat
          icon="bi-person-heart"
          value={patient.age || '—'}
          label="Patient age"
          hint={patient.blood_group ? `Blood group ${patient.blood_group}` : 'Blood group not recorded'}
          tone="blue"
        />
        <ConsultationStat
          icon="bi-list-ol"
          value={`#${data.appointment.serial_number}`}
          label="Queue serial"
          hint={data.appointment.status_label}
          tone="teal"
        />
        <ConsultationStat
          icon="bi-camera-video"
          value={data.session.status_label}
          label="Call status"
          hint={sessionEnded ? 'Session is closed' : 'Peer-to-peer video room'}
          tone="violet"
        />
        <ConsultationStat
          icon="bi-shield-lock"
          value="Private"
          label="Live chat"
          hint="Cleared when the call ends"
          tone="amber"
        />
      </section>

      <section className="doctor-consultation-layout">
        <main className="doctor-consultation-call-card">
          <header className="doctor-consultation-card-header">
            <div>
              <span className="doctor-consultation-card-icon is-violet">
                <i className="bi bi-camera-video" aria-hidden="true"></i>
              </span>
              <div>
                <h2>Video room</h2>
                <p>{callStatus}</p>
              </div>
            </div>

            <span className={`doctor-consultation-session-badge is-${data.session.status}`}>
              <i className="bi bi-circle-fill" aria-hidden="true"></i>
              {data.session.status_label}
            </span>
          </header>

          <div className="doctor-consultation-video-grid">
            <article>
              <div className="doctor-consultation-video-label">
                <span>You</span>
                <small>Doctor</small>
              </div>
              <video ref={localVideoRef} autoPlay playsInline muted></video>
            </article>

            <article>
              <div className="doctor-consultation-video-label">
                <span>{patient.full_name}</span>
                <small>Patient</small>
              </div>
              <video ref={remoteVideoRef} autoPlay playsInline></video>
            </article>
          </div>

          <div className="doctor-consultation-controls">
            <button
              type="button"
              className="is-start"
              onClick={startCamera}
              disabled={cameraBusy || callRunning || sessionEnded}
            >
              <i className="bi bi-camera-video-fill" aria-hidden="true"></i>
              {cameraBusy ? 'Starting…' : callRunning ? 'Camera active' : 'Start camera'}
            </button>

            <button
              type="button"
              className="is-hangup"
              onClick={() => endCall(true)}
              disabled={!callRunning && !sessionEnded}
            >
              <i className="bi bi-telephone-x-fill" aria-hidden="true"></i>
              Hang up
            </button>

            {data.appointment.prescription_exists ? (
              <Link
                className="is-prescription"
                to={`/appointments/${appointmentId}/prescription`}
              >
                <i className="bi bi-file-earmark-medical" aria-hidden="true"></i>
                View prescription
              </Link>
            ) : data.appointment.is_completed ? (
              <Link
                className="is-prescription"
                to={`/appointments/${appointmentId}/prescription`}
              >
                <i className="bi bi-file-earmark-plus" aria-hidden="true"></i>
                Issue prescription
              </Link>
            ) : null}
          </div>
        </main>

        <aside className="doctor-consultation-patient-card">
          <header>
            <span className="doctor-consultation-card-icon is-blue">
              <i className="bi bi-person-vcard" aria-hidden="true"></i>
            </span>
            <div>
              <h2>Patient</h2>
              <p>Consultation participant</p>
            </div>
          </header>

          <div className="doctor-consultation-patient-profile">
            {patient.photo_url ? (
              <img src={patient.photo_url} alt="" />
            ) : (
              <span>{initials(patient.full_name) || 'PT'}</span>
            )}
            <div>
              <strong>{patient.full_name}</strong>
              <small>{patient.gender_label} · {patient.age ? `${patient.age} years` : 'Age not recorded'}</small>
            </div>
          </div>

          <dl>
            <div><dt>Blood group</dt><dd>{patient.blood_group || '—'}</dd></div>
            <div><dt>Appointment</dt><dd>{data.appointment.appointment_type_label}</dd></div>
            <div><dt>Date</dt><dd>{data.appointment.date_label}</dd></div>
            <div><dt>Time</dt><dd>{data.appointment.time_range_label}</dd></div>
          </dl>

          <div className="doctor-consultation-privacy-note">
            <i className="bi bi-shield-check" aria-hidden="true"></i>
            <span>
              Video is peer-to-peer. Consultation chat and shared photos are
              temporary and deleted when the call ends.
            </span>
          </div>
        </aside>

        <section className="doctor-consultation-chat-card">
          <header className="doctor-consultation-card-header">
            <div>
              <span className="doctor-consultation-card-icon is-teal">
                <i className="bi bi-chat-square-text" aria-hidden="true"></i>
              </span>
              <div>
                <h2>Consultation chat</h2>
                <p>Messages and photos exist only during this live session.</p>
              </div>
            </div>
            <span className="doctor-consultation-private-badge">
              <i className="bi bi-trash3" aria-hidden="true"></i>
              Not saved
            </span>
          </header>

          {chatClosed ? (
            <div className="doctor-consultation-chat-ended">
              <span><i className="bi bi-trash3" aria-hidden="true"></i></span>
              <strong>Chat cleared</strong>
              <p>This consultation has ended. Messages and shared photos were deleted.</p>
            </div>
          ) : (
            <>
              <div className="doctor-consultation-chat-log">
                {messages.length === 0 ? (
                  <div className="doctor-consultation-chat-empty">
                    <i className="bi bi-chat-heart" aria-hidden="true"></i>
                    <strong>No messages yet</strong>
                    <span>You can chat before or during the video call.</span>
                  </div>
                ) : (
                  messages.map((message) => {
                    const own = Number(message.sender_account_id) === Number(data.my_account_id);
                    return (
                      <div
                        key={message.message_id}
                        className={`doctor-consultation-message-row ${own ? 'is-own' : 'is-other'}`}
                      >
                        <div className={`doctor-consultation-message ${message.type === 'photo' ? 'is-photo' : ''}`}>
                          {message.type === 'photo' ? (
                            <a
                              href={photoUrl(message.photo_id)}
                              target="_blank"
                              rel="noreferrer"
                            >
                              <img
                                src={photoUrl(message.photo_id)}
                                alt="Shared consultation"
                              />
                            </a>
                          ) : (
                            <p>{message.message_text}</p>
                          )}
                          <small>{message.sent_at}</small>
                        </div>
                      </div>
                    );
                  })
                )}
                <div ref={messagesEndRef}></div>
              </div>

              {chatError && (
                <div className="doctor-consultation-chat-error">
                  <i className="bi bi-exclamation-triangle" aria-hidden="true"></i>
                  {chatError}
                </div>
              )}

              <form className="doctor-consultation-chat-form" onSubmit={sendMessage}>
                <textarea
                  rows={2}
                  maxLength={1000}
                  value={draft}
                  onChange={(event) => setDraft(event.target.value)}
                  placeholder={`Message ${patient.full_name}…`}
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
                  <label className="doctor-consultation-photo-button">
                    <i className="bi bi-image" aria-hidden="true"></i>
                    Photo
                    <input
                      ref={photoInputRef}
                      type="file"
                      accept="image/jpeg,image/png,image/webp"
                      hidden
                      onChange={(event) => {
                        const file = event.target.files?.[0];
                        event.target.value = '';
                        if (file) sendPhoto(file);
                      }}
                    />
                  </label>

                  <small>{draft.length}/1000 · Shift+Enter for a new line</small>

                  <button type="submit" disabled={!draft.trim() || sending}>
                    <i className="bi bi-send-fill" aria-hidden="true"></i>
                    {sending ? 'Sending…' : 'Send'}
                  </button>
                </div>
              </form>
            </>
          )}
        </section>
      </section>

      <DoctorConsultationFooter />
    </div>
  );
}
