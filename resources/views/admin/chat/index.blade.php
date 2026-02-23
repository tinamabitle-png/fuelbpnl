@extends('layouts.admin')

@section('title', 'Admin Chat')

@push('styles')
<style>
  :root {
    --stream-indigo: #4f46e5;
    --stream-ice: #f8fafc;
    --stream-ink: #0f172a;
  }

  .stream-admin-shell {
    background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.18), transparent 55%),
      radial-gradient(circle at 10% 20%, rgba(14, 165, 233, 0.15), transparent 50%),
      #f8fafc;
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.3);
    box-shadow: 0 24px 60px -40px rgba(15, 23, 42, 0.6);
  }

  .stream-thread-list button.active {
    background: rgba(79, 70, 229, 0.08);
  }

  .video-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }

  .video-tile {
    background: #0f172a;
    border-radius: 16px;
    overflow: hidden;
    position: relative;
    min-height: 160px;
  }

  .video-tile video,
  .video-tile audio {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .video-label {
    position: absolute;
    left: 10px;
    bottom: 10px;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.7);
    color: #e2e8f0;
  }
</style>
@endpush

@section('content')
<div class="main-content-wrapper">
    <div class="content-area p-6">
        <div class="mb-6 flex flex-col gap-2">
            <div class="inline-flex items-center gap-3">
                <span class="h-11 w-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-500 text-white flex items-center justify-center font-semibold">SC</span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Stream Support Console</h1>
                    <p class="text-slate-500">Live support with Stream realtime.</p>
                </div>
            </div>
        </div>

        <div class="stream-admin-shell p-5">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-4">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                            <div>
                                <h2 class="font-semibold text-gray-800">Threads</h2>
                                <p class="text-xs text-gray-500">Active station conversations</p>
                            </div>
                            <button id="refreshThreads" class="text-sm text-blue-600 hover:text-blue-700">Refresh</button>
                        </div>
                        <div id="threadList" class="stream-thread-list divide-y divide-gray-100 max-h-[600px] overflow-y-auto custom-scrollbar"></div>
                    </div>
                </div>

                <div class="lg:col-span-8">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 h-full flex flex-col">
                        <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-slate-50 to-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 id="threadTitle" class="font-semibold text-gray-900">Select a thread</h2>
                                    <p id="threadMeta" class="text-sm text-gray-500">Waiting for a station owner.</p>
                                </div>
                                <div class="text-xs text-gray-500 flex items-center gap-2" id="threadStatus">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                    Live
                                </div>
                            </div>
                        </div>

                        <div class="px-4 pt-4">
                            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Video Call</h3>
                                        <p class="text-xs text-slate-500">Start or join a live call with the station owner.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button id="videoStartBtn" class="px-3 py-2 text-xs rounded-xl border border-slate-200 hover:bg-slate-50">Start</button>
                                        <button id="videoLeaveBtn" class="px-3 py-2 text-xs rounded-xl border border-slate-200 hover:bg-slate-50" disabled>Leave</button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                    <button id="videoToggleCam" class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">Camera</button>
                                    <button id="videoToggleMic" class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">Mic</button>
                                    <span id="videoStatus" class="ml-auto text-slate-500">Not connected</span>
                                </div>
                                <div id="videoParticipants" class="video-grid"></div>
                            </div>
                        </div>

                        <div id="chatMessages" class="flex-1 p-4 space-y-3 overflow-y-auto custom-scrollbar bg-gradient-to-b from-slate-50 via-white to-white"></div>

                        <div class="border-t border-gray-200 p-4">
                            <form id="chatForm" class="grid grid-cols-1 gap-3">
                                <textarea id="chatInput" rows="3" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Type a response..."></textarea>
                                <div class="flex items-center justify-between gap-4">
                                    <input id="chatFile" type="file" class="text-sm" />
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="markReadBtn" class="px-3 py-2 text-sm rounded-xl border border-gray-200 hover:bg-gray-100">Mark Read</button>
                                        <button type="submit" class="px-4 py-2 text-sm rounded-xl bg-blue-600 text-white hover:bg-blue-700">Send</button>
                                    </div>
                                </div>
                                <p id="chatError" class="text-sm text-red-500"></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
(() => {
  const threadListEl = document.getElementById('threadList');
  const refreshThreadsBtn = document.getElementById('refreshThreads');
  const chatMessagesEl = document.getElementById('chatMessages');
  const threadTitle = document.getElementById('threadTitle');
  const threadMeta = document.getElementById('threadMeta');
  const chatForm = document.getElementById('chatForm');
  const chatInput = document.getElementById('chatInput');
  const chatFile = document.getElementById('chatFile');
  const chatError = document.getElementById('chatError');
  const markReadBtn = document.getElementById('markReadBtn');
  const videoStartBtn = document.getElementById('videoStartBtn');
  const videoLeaveBtn = document.getElementById('videoLeaveBtn');
  const videoToggleCam = document.getElementById('videoToggleCam');
  const videoToggleMic = document.getElementById('videoToggleMic');
  const videoStatus = document.getElementById('videoStatus');
  const videoParticipants = document.getElementById('videoParticipants');

  let currentThreadId = null;
  let lastMessageId = null;
  let messageIds = new Set();
  let streamClient = null;
  let activeChannel = null;
  let videoClient = null;
  let activeCall = null;
  let videoCallType = 'default';
  let videoUserId = null;
  let activeCallThreadId = null;
  let pendingCall = null;
  let callsSubscription = null;
  let lastNotifiedCallId = null;
  let notificationAsked = false;
  let isAutoJoining = false;
  const autoJoinIncomingCalls = true;
  let participantsSubscription = null;
  const participantTiles = new Map();
  const videoBindings = new Map();
  const audioBindings = new Map();
  let activeChannelHandler = null;
  let messagePollTimer = null;
  let threadPollTimer = null;
  let isPollingMessages = false;
  let isPollingThreads = false;

  const renderThreads = (threads) => {
    threadListEl.innerHTML = threads.map(thread => {
      const station = thread.station ? thread.station.name : 'Unknown Station';
      const owner = thread.owner ? (thread.owner.name || thread.owner.email || thread.owner.phone) : 'Owner';
      const ownerId = thread.owner ? thread.owner.id : '';
      const unread = thread.unread_count ? `<span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">${thread.unread_count}</span>` : '';
      return `
        <button class="w-full text-left p-4 hover:bg-gray-50 flex items-center gap-3" data-thread-id="${thread.id}" data-owner-id="${ownerId}">
          <div class="flex-1">
            <div class="font-semibold text-gray-800">${station}</div>
            <div class="text-xs text-gray-500">${owner}</div>
          </div>
          ${unread}
        </button>
      `;
    }).join('');
  };

  const renderMessages = (messages) => {
    chatMessagesEl.innerHTML = messages.map(message => renderMessageBubble(message)).join('');
    chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
  };

  const appendMessages = (messages) => {
    if (!messages.length) return;
    const html = messages.map(message => renderMessageBubble(message)).join('');
    chatMessagesEl.insertAdjacentHTML('beforeend', html);
    chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
  };

  const renderMessageBubble = (message) => {
    const isAdmin = message.sender_role && message.sender_role !== 'merchant';
    const align = isAdmin ? 'justify-end' : 'justify-start';
    const bubble = isAdmin ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200';
    const meta = message.created_at ? new Date(message.created_at).toLocaleString() : '';
    const file = message.file ? `<a href="${message.file.url}" target="_blank" class="underline text-xs">${message.file.name}</a>` : '';
    const body = message.body ? `<div>${message.body}</div>` : '';
    return `
      <div class="flex ${align}">
        <div class="max-w-[70%] rounded-xl px-4 py-2 text-sm ${bubble}">
          ${body}
          ${file}
          <div class="mt-2 text-[10px] opacity-70">${meta}</div>
        </div>
      </div>
    `;
  };

  const fetchThreads = async () => {
    const res = await fetch("{{ route('admin.chat.threads') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success) renderThreads(data.data || []);
  };

  const fetchMessages = async (threadId) => {
    const res = await fetch(`{{ url('/admin/chat/threads') }}/${threadId}/messages`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (!data.success) return [];
    const messages = data.data || [];
    messageIds = new Set();
    lastMessageId = null;
    messages.forEach((message) => {
      if (message.id) messageIds.add(String(message.id));
      if (message.id) lastMessageId = message.id;
    });
    return messages;
  };

  const fetchNewMessages = async () => {
    if (!currentThreadId || isPollingMessages) return;
    isPollingMessages = true;
    try {
      const params = new URLSearchParams();
      if (lastMessageId) params.append('after_id', lastMessageId);
      params.append('limit', '200');
      const res = await fetch(`{{ url('/admin/chat/threads') }}/${currentThreadId}/messages?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (!data.success) return;
      const messages = data.data || [];
      const fresh = [];
      messages.forEach((message) => {
        if (message.id) {
          const id = String(message.id);
          if (messageIds.has(id)) return;
          messageIds.add(id);
          lastMessageId = message.id;
        }
        fresh.push(message);
      });
      appendMessages(fresh);
    } catch (err) {
      // Keep silent to avoid freezing UI on transient errors.
    } finally {
      isPollingMessages = false;
    }
  };

  const startMessagePolling = () => {
    if (messagePollTimer) clearInterval(messagePollTimer);
    messagePollTimer = setInterval(fetchNewMessages, 3000);
  };

  const stopMessagePolling = () => {
    if (messagePollTimer) clearInterval(messagePollTimer);
    messagePollTimer = null;
  };

  const startThreadPolling = () => {
    if (threadPollTimer) clearInterval(threadPollTimer);
    threadPollTimer = setInterval(async () => {
      if (isPollingThreads) return;
      isPollingThreads = true;
      try {
        await fetchThreads();
      } catch (err) {
        // ignore
      } finally {
        isPollingThreads = false;
      }
    }, 5000);
  };

  const loadToken = async () => {
    const res = await fetch("{{ route('admin.chat.stream-token') }}", {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const payload = await res.json();
    if (!payload.success) {
      throw new Error(payload.message || 'Stream token unavailable.');
    }
    return payload.data;
  };

  const ensureStream = async () => {
    if (streamClient) return streamClient;
    const { StreamChat } = await import("/vendor/esm/stream-chat.bundle.mjs");
    const data = await loadToken();
    streamClient = StreamChat.getInstance(data.api_key);
    await streamClient.connectUser(data.user, data.token);
    return streamClient;
  };

  const loadVideoToken = async () => {
    const res = await fetch("{{ route('admin.chat.video-token') }}", {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const payload = await res.json();
    if (!payload.success) {
      throw new Error(payload.message || 'Stream Video token unavailable.');
    }
    return payload.data;
  };

  const ensureVideoClient = async () => {
    if (videoClient) return { client: videoClient, callType: videoCallType };
    const { StreamVideoClient, CallingState } = await import("https://esm.sh/@stream-io/video-client");
    const data = await loadVideoToken();
    videoUserId = data.user?.id || null;
    videoClient = new StreamVideoClient({
      apiKey: data.api_key,
      token: data.token,
      user: data.user,
    });
    videoCallType = data.call_type || 'default';
    if (!callsSubscription) {
      callsSubscription = videoClient.state.calls$.subscribe((calls) => {
        const ringing = calls.find((call) => call.state.callingState === CallingState.RINGING);
        if (!ringing) return;
        if (activeCall && activeCall.id === ringing.id) return;
        const threadId = ringing.state?.custom?.thread_id || null;
        if (threadId && currentThreadId && String(threadId) === String(currentThreadId)) {
          if (!pendingCall || pendingCall.id !== ringing.id) {
            pendingCall = ringing;
            updateVideoStatus('Incoming call');
            if (videoStartBtn) videoStartBtn.textContent = 'Join';
          }
        }
        if (ringing.id !== lastNotifiedCallId) {
          lastNotifiedCallId = ringing.id;
          if ('Notification' in window) {
            if (Notification.permission === 'granted') {
              new Notification('Incoming call', { body: 'A station owner is calling.' });
            } else if (!notificationAsked && Notification.permission !== 'denied') {
              notificationAsked = true;
              Notification.requestPermission().then((permission) => {
                if (permission === 'granted') {
                  new Notification('Incoming call', { body: 'A station owner is calling.' });
                }
              });
            }
          }
        }
        if (autoJoinIncomingCalls) {
          joinIncomingCall(ringing);
        }
      });
    }
    return { client: videoClient, callType: videoCallType };
  };

  const updateVideoStatus = (text) => {
    if (videoStatus) videoStatus.textContent = text;
  };

  const cleanupParticipant = (sessionId) => {
    const tile = participantTiles.get(sessionId);
    if (tile) {
      tile.remove();
      participantTiles.delete(sessionId);
    }
    const unbindVideo = videoBindings.get(sessionId);
    if (unbindVideo) {
      unbindVideo();
      videoBindings.delete(sessionId);
    }
    const unbindAudio = audioBindings.get(sessionId);
    if (unbindAudio) {
      unbindAudio();
      audioBindings.delete(sessionId);
    }
  };

  const renderParticipant = (call, participant) => {
    if (participantTiles.has(participant.sessionId)) return;
    const tile = document.createElement('div');
    tile.className = 'video-tile';
    tile.dataset.sessionId = participant.sessionId;

    const videoEl = document.createElement('video');
    videoEl.autoplay = true;
    videoEl.playsInline = true;
    videoEl.muted = participant.isLocalParticipant;

    const audioEl = document.createElement('audio');
    audioEl.autoplay = true;
    audioEl.hidden = true;

    const label = document.createElement('div');
    label.className = 'video-label';
    label.textContent = participant.name || participant.userId || 'Guest';

    tile.append(videoEl, audioEl, label);
    videoParticipants.appendChild(tile);
    participantTiles.set(participant.sessionId, tile);

    const unbindVideo = call.bindVideoElement(videoEl, participant.sessionId, 'videoTrack');
    const unbindAudio = call.bindAudioElement(audioEl, participant.sessionId, 'audioTrack');
    videoBindings.set(participant.sessionId, unbindVideo);
    audioBindings.set(participant.sessionId, unbindAudio);
  };

  const bindParticipants = (call) => {
    if (participantsSubscription) {
      participantsSubscription.unsubscribe();
      participantsSubscription = null;
    }
    if (call.setViewport) call.setViewport(videoParticipants);
    participantsSubscription = call.state.participants$.subscribe((participants) => {
      const activeIds = new Set(participants.map((p) => p.sessionId));
      participants.forEach((participant) => renderParticipant(call, participant));
      Array.from(participantTiles.keys()).forEach((sessionId) => {
        if (!activeIds.has(sessionId)) cleanupParticipant(sessionId);
      });
    });
  };

  const joinIncomingCall = async (call) => {
    if (!call || isAutoJoining) return;
    if (activeCall && activeCall.id === call.id) return;
    isAutoJoining = true;
    try {
      const threadId = call.state?.custom?.thread_id || null;
      if (threadId && (!currentThreadId || String(currentThreadId) !== String(threadId))) {
        await fetchThreads();
        await openThread(threadId);
      }
      activeCall = call;
      activeCallThreadId = threadId ? String(threadId) : activeCallThreadId;
      pendingCall = null;
      await activeCall.join();
      await activeCall.camera.enable();
      await activeCall.microphone.enable();
      bindParticipants(activeCall);
      updateVideoStatus('Live');
      videoLeaveBtn.disabled = false;
      if (videoStartBtn) videoStartBtn.textContent = 'Start';
    } catch (err) {
      updateVideoStatus(err.message || 'Failed to join call.');
    } finally {
      isAutoJoining = false;
    }
  };

  const leaveVideoCall = async () => {
    if (!activeCall) return;
    try {
      await activeCall.leave();
    } catch (_) {
      // ignore
    }
    if (participantsSubscription) {
      participantsSubscription.unsubscribe();
      participantsSubscription = null;
    }
    Array.from(participantTiles.keys()).forEach((sessionId) => cleanupParticipant(sessionId));
    activeCall = null;
    activeCallThreadId = null;
    pendingCall = null;
    updateVideoStatus('Not connected');
    videoLeaveBtn.disabled = true;
    if (videoStartBtn) videoStartBtn.textContent = 'Start';
  };

  const prepareVideoMembers = async () => {
    if (!currentThreadId) return null;
    const res = await fetch(`{{ url('/admin/chat/threads') }}/${currentThreadId}/video-members`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();
    if (!data.success) {
      throw new Error(data.message || 'Failed to prepare video members.');
    }
    return data.data?.owner_id || null;
  };

  const startVideoCall = async () => {
    if (!currentThreadId) {
      updateVideoStatus('Select a thread first.');
      return;
    }
    updateVideoStatus('Calling...');
    const { client, callType } = await ensureVideoClient();
    if (activeCall && activeCallThreadId && activeCallThreadId !== String(currentThreadId)) {
      await leaveVideoCall();
    }
    if (pendingCall) {
      activeCall = pendingCall;
      activeCallThreadId = pendingCall.state?.custom?.thread_id || String(currentThreadId);
      pendingCall = null;
    } else {
      const ownerId = await prepareVideoMembers();
      if (!ownerId) {
        updateVideoStatus('Owner not found for this thread.');
        return;
      }
      const callId = `thread_${currentThreadId}_${Date.now()}`;
      activeCall = client.call(callType, callId);
      await activeCall.getOrCreate({
        ring: true,
        data: {
          members: [
            { user_id: String(videoUserId) },
            { user_id: String(ownerId) },
          ],
          custom: { thread_id: String(currentThreadId) },
        },
      });
      activeCallThreadId = String(currentThreadId);
    }
    await activeCall.join();
    await activeCall.camera.enable();
    await activeCall.microphone.enable();
    bindParticipants(activeCall);
    updateVideoStatus('Live');
    videoLeaveBtn.disabled = false;
    if (videoStartBtn) videoStartBtn.textContent = 'Start';
  };

  const openThread = async (threadId) => {
    stopMessagePolling();
    if (activeCall && activeCallThreadId && activeCallThreadId !== String(threadId)) {
      await leaveVideoCall();
    }
    pendingCall = null;
    currentThreadId = threadId;
    const button = document.querySelector(`[data-thread-id="${threadId}"]`);
    document.querySelectorAll('#threadList button').forEach(btn => btn.classList.remove('active'));
    if (button) {
      button.classList.add('active');
      const title = button.querySelector('.font-semibold')?.textContent || 'Thread';
      const meta = button.querySelector('.text-xs')?.textContent || '';
      threadTitle.textContent = title;
      threadMeta.textContent = meta;
    }

    const initialMessages = await fetchMessages(threadId);
    renderMessages(initialMessages);
    startMessagePolling();
    updateVideoStatus('Not connected');
    if (videoStartBtn) videoStartBtn.textContent = 'Start';

    try {
      const client = await ensureStream();
      if (activeChannel && activeChannelHandler) {
        activeChannel.off('message.new', activeChannelHandler);
      }
      activeChannel = client.channel('messaging', `thread_${threadId}`);
      await activeChannel.watch();
      activeChannelHandler = (event) => {
        const message = event.message;
        const dbId = message?.db_message_id;
        if (dbId && messageIds.has(String(dbId))) return;
        if (dbId) {
          messageIds.add(String(dbId));
          lastMessageId = Number(dbId);
        }
        const uiMessage = {
          sender_role: message?.sender_role || (message?.user?.role === 'admin' ? 'employee' : 'merchant'),
          body: message?.text || '',
          created_at: message?.created_at || new Date().toISOString(),
        };
        appendMessages([uiMessage]);
      };
      activeChannel.on('message.new', activeChannelHandler);
    } catch (err) {
      // Stream unavailable: fallback polling still runs.
    }
  };

  threadListEl.addEventListener('click', (event) => {
    const btn = event.target.closest('button[data-thread-id]');
    if (!btn) return;
    openThread(btn.dataset.threadId);
  });

  refreshThreadsBtn.addEventListener('click', fetchThreads);

  chatForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    chatError.textContent = '';
    if (!currentThreadId) {
      chatError.textContent = 'Select a thread first.';
      return;
    }
    const message = chatInput.value.trim();
    const file = chatFile.files[0];
    if (!message && !file) {
      chatError.textContent = 'Type a message or attach a file.';
      return;
    }

    if (file) {
      const form = new FormData();
      if (message) form.append('message', message);
      form.append('file', file);
      const res = await fetch(`{{ url('/admin/chat/threads') }}/${currentThreadId}/messages`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: form
      });
      const data = await res.json();
      if (!data.success) {
        chatError.textContent = data.message || 'Failed to send.';
        return;
      }
    } else {
      try {
        const client = await ensureStream();
        const channel = client.channel('messaging', `thread_${currentThreadId}`);
        await channel.sendMessage({
          text: message,
          sender_role: 'employee',
          thread_id: String(currentThreadId),
        });
      } catch (err) {
        const res = await fetch(`{{ url('/admin/chat/threads') }}/${currentThreadId}/messages`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ message })
        });
        const data = await res.json();
        if (!data.success) {
          chatError.textContent = data.message || 'Failed to send.';
          return;
        }
      }
    }

    chatInput.value = '';
    chatFile.value = '';
    await fetchThreads();
  });

  markReadBtn.addEventListener('click', async () => {
    if (!currentThreadId || !lastMessageId) return;
    await fetch(`{{ url('/admin/chat/threads') }}/${currentThreadId}/read`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ message_id: lastMessageId })
    });
    await fetchThreads();
  });

  if (videoStartBtn) {
    videoStartBtn.addEventListener('click', () => {
      startVideoCall().catch((err) => {
        updateVideoStatus(err.message || 'Failed to start call.');
      });
    });
  }

  if (videoLeaveBtn) {
    videoLeaveBtn.addEventListener('click', () => {
      leaveVideoCall();
    });
  }

  if (videoToggleCam) {
    videoToggleCam.addEventListener('click', async () => {
      if (!activeCall) return;
      if (activeCall.camera?.enabled) {
        await activeCall.camera.disable();
      } else {
        await activeCall.camera.enable();
      }
    });
  }

  if (videoToggleMic) {
    videoToggleMic.addEventListener('click', async () => {
      if (!activeCall) return;
      if (activeCall.microphone?.enabled) {
        await activeCall.microphone.disable();
      } else {
        await activeCall.microphone.enable();
      }
    });
  }

  fetchThreads();
  startThreadPolling();
})();
</script>
@endpush
@endsection
