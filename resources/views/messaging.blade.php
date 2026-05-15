@extends('layouts.app')
@section('title', 'Chat - StrangerConnect')

@push('styles')
<style>
.chat-layout { display: flex; height: calc(100vh - 72px); }
.chat-main   { flex: 1; display: flex; flex-direction: column; background: var(--dark-bg); }
.chat-header { background: var(--card-bg); border-bottom: 1px solid var(--card-border);
    padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; }
.chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
.msg-bubble { max-width: 70%; padding: 10px 16px; border-radius: 18px; word-break: break-word; }
.msg-mine   { background: var(--primary); color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
.msg-theirs { background: var(--card-bg); border: 1px solid var(--card-border); align-self: flex-start; border-bottom-left-radius: 4px; }
.msg-meta   { font-size: 0.72rem; opacity: 0.6; margin-top: 4px; }
.chat-input-bar { background: var(--card-bg); border-top: 1px solid var(--card-border); padding: 16px; }
.status-screen { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 20px; }
.msg-image { max-width: 240px; max-height: 200px; border-radius: 12px; object-fit: cover; cursor: pointer; }
.msg-video { max-width: 280px; border-radius: 12px; }
</style>
@endpush

@section('content')
<div class="chat-layout">
    @include('partials.sidebar')

    <div class="chat-main">
        <!-- Permission Step -->
        <div class="status-screen" id="permStep">
            <div style="font-size:5rem;">💬</div>
            <h4 class="fw-bold">Start Chatting</h4>
            <p class="text-muted">Connect instantly with a random stranger</p>
            <button class="btn btn-primary btn-lg px-5" onclick="startChat()">
                <i class="fa-solid fa-paper-plane me-2"></i>Find a Stranger
            </button>
        </div>

        <!-- Search Step -->
        <div class="status-screen d-none" id="searchStep">
            <div class="spinner-border text-purple" style="width:3rem;height:3rem;" role="status"></div>
            <h5 id="searchText">Finding someone to chat with...</h5>
            <button class="btn btn-outline-danger" onclick="cancelSearch()">Cancel</button>
        </div>

        <!-- Chat Active -->
        <div class="d-none h-100 d-flex flex-column" id="chatStep" style="display:none!important;">
            <!-- Header -->
            <div class="chat-header">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#6c63ff,#ff6584);display:flex;align-items:center;justify-content:center;">👤</div>
                    <div>
                        <div class="fw-bold" id="strangerName">Stranger</div>
                        <small class="text-success">● Connected</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-warning" onclick="skipStranger()">
                        <i class="fa-solid fa-forward-step me-1"></i>Next
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="endChat()">
                        <i class="fa-solid fa-xmark me-1"></i>End
                    </button>
                    <button class="btn btn-sm btn-outline-primary d-none" id="friendBtn" onclick="sendFriendRequest()">
                        <i class="fa-solid fa-user-plus"></i>
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                <div class="text-center text-muted small py-3">
                    <i class="fa-solid fa-lock me-1"></i>Connected — say hello!
                </div>
            </div>

            <!-- Input Bar -->
            <div class="chat-input-bar">
                <div class="d-flex gap-2 align-items-end">
                    <label class="btn btn-outline-secondary" title="Send Image/Video">
                        <i class="fa-solid fa-paperclip"></i>
                        <input type="file" accept="image/*,video/*" class="d-none" id="fileInput" onchange="sendFile(this)">
                    </label>
                    <textarea id="msgInput" class="form-control bg-dark border-secondary text-light"
                        placeholder="Type a message..." rows="1" style="resize:none;border-radius:12px;"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
                    <button class="btn btn-primary px-4" onclick="sendMessage()" style="border-radius:12px;">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
                <small class="text-muted mt-1 d-block" id="guestNote"></small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
let pusher = null, roomChannel = null, waitChannel = null, roomCode = null;

// ── Polling state ─────────────────────────────────────────────────────────
let pollInterval       = null;
let isMatched          = false;
let isSearching        = false;
let searchSeconds      = 0;
const POLL_INTERVAL_MS = 3000;

const jwtToken   = localStorage.getItem('jwt_token');
const guestToken = localStorage.getItem('guest_token');
const userData   = JSON.parse(localStorage.getItem('user_data') || 'null');
const myName     = localStorage.getItem('display_name') || 'You';
let localMessages = [];

function getHeaders(isFormData = false) {
    const h = {};
    if (!isFormData) h['Content-Type'] = 'application/json';
    if (jwtToken)    h['Authorization'] = 'Bearer ' + jwtToken;
    if (guestToken)  h['X-Guest-Token'] = guestToken;
    return h;
}

function showStep(id) {
    document.getElementById('permStep').classList.add('d-none');
    document.getElementById('searchStep').classList.add('d-none');
    const cs = document.getElementById('chatStep');
    cs.classList.add('d-none');
    cs.style.cssText = '';

    if (id === 'chatStep') {
        cs.classList.remove('d-none');
        cs.style.display        = 'flex';
        cs.style.flexDirection  = 'column';
        cs.style.height         = '100%';
    } else {
        document.getElementById(id)?.classList.remove('d-none');
    }
}

// ── Pusher Setup ──────────────────────────────────────────────────────────
function setupPusher() {
    if (pusher) return;

    pusher = new Pusher('{{ config("broadcasting.connections.reverb.key") }}', {
        wsHost:            '{{ config("broadcasting.connections.reverb.options.host") }}',
        wsPort:            '{{ config("broadcasting.connections.reverb.options.port", 8080) }}',
        wssPort:           '{{ config("broadcasting.connections.reverb.options.port", 8080) }}',
        forceTLS:          false,
        disableStats:      true,
        enabledTransports: ['ws', 'wss'],
        cluster:           'mt1',
    });
    pusher.logToConsole = true;

    const k = userData
        ? 'waiting.user.' + userData.id
        : 'waiting.guest.' + guestToken;

    waitChannel = pusher.subscribe(k);
    console.log('[WS] Subscribed to wait channel:', k);
    waitChannel.bind('user.matched', function (data) {
        if (isMatched) return;
        console.log('[WS] Chat matched via WebSocket');
        handleMatch(data.room_code);
    });
}

// ── Start Chat ────────────────────────────────────────────────────────────
async function startChat() {
    // Create guest session if needed
    if (!guestToken && !jwtToken) {
        const res  = await fetch('/api/guest/session', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ display_name: myName }),
        });
        const d = await res.json();
        localStorage.setItem('guest_token',  d.guest_token);
        localStorage.setItem('display_name', d.display_name);
        location.reload();
        return;
    }

    showStep('searchStep');
    document.getElementById('searchText').textContent = 'Finding someone to chat with...';
    setupPusher();
    await joinQueue();
}

// ── Join Queue ────────────────────────────────────────────────────────────
async function joinQueue() {
    if (isMatched || isSearching) return;
    isSearching = true;

    try {
        const res  = await fetch('/api/queue/join', {
            method:  'POST',
            headers: getHeaders(),
            body:    JSON.stringify({ type: 'message' }),
        });
        const data = await res.json();

        if (data.matched) {
            handleMatch(data.room_code);
        } else {
            isSearching = false;
            startPolling();
        }
    } catch (err) {
        console.error('[JOIN] Error:', err);
        isSearching = false;
        setTimeout(joinQueue, 3000);
    }
}

// ── Polling ───────────────────────────────────────────────────────────────
function startPolling() {
    stopPolling();

    pollInterval = setInterval(async () => {
        if (isMatched) { stopPolling(); return; }

        try {
            const res  = await fetch('/api/queue/status', {
                method:  'POST',
                headers: getHeaders(),
                body:    JSON.stringify({ type: 'message' }),
            });
            const data = await res.json();

            if (data.matched) {
                // isMatched = true;
                console.log('[POLL] Chat matched via polling');
                handleMatch(data.room_code);
            } else {
                searchSeconds += 3;
                const t = searchSeconds < 60
                    ? searchSeconds + 's'
                    : Math.floor(searchSeconds / 60) + 'm ' + (searchSeconds % 60) + 's';
                document.getElementById('searchText').textContent =
                    'Finding someone to chat with... (' + t + ')';
            }
        } catch (e) {
            console.warn('[POLL] Status check failed:', e);
        }

    }, POLL_INTERVAL_MS);
}

function stopPolling() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
}

// ── On Match ──────────────────────────────────────────────────────────────
function handleMatch(code) {
    console.log('[MATCH] Matched with room code:', code+''+isMatched);
    if (isMatched) return;
    isMatched   = true;
    isSearching = false;
    stopPolling();
    const oldRoom = roomCode;

    roomCode = code;
    
if (roomChannel && oldRoom) {
    if (roomChannel) pusher.unsubscribe('room.' + roomCode);
}
    roomChannel = pusher.subscribe('room.' + roomCode);

console.log('Subscribed Channel:', roomChannel.name);
    // important
roomChannel.bind('pusher:subscription_succeeded', function () {
    console.log('Pusher subscription success');
});

roomChannel.bind('pusher:subscription_error', function (err) {
    console.log('Subsc:', err);
});


roomChannel.bind_global((eventName, data) => {
    console.log('EVENT:', eventName);
    console.log('DATA:', data);
});


console.log(roomChannel);
    roomChannel.bind('message.sent', function (data) {
       console.log('MESSAGE RECEIVED:', data);
        if (data.message.room_code === roomCode) appendMessage(data.message, false);
    });
    roomChannel.bind('user.skipped', function () {
        appendSystemMsg('Stranger left the chat.');
        setTimeout(() => {
            if (confirm('Start a new chat?')) reconnect();
            else endChat();
        }, 800);
    });


    

    showStep('chatStep');

    document.getElementById('chatMessages').innerHTML =
        '<div class="text-center text-muted small py-3">' +
        '<i class="fa-solid fa-lock me-1"></i>Connected — say hello!</div>';

    if (!jwtToken) {
        document.getElementById('guestNote').textContent =
            '⚠️ Chat history saved locally only (not logged in)';
    }

    if (userData) {
        document.getElementById('friendBtn').classList.remove('d-none');
    }
}

// ── Send Message ──────────────────────────────────────────────────────────
async function sendMessage() {
    const inp  = document.getElementById('msgInput');
    const text = inp.value.trim();
    if (!text || !roomCode) return;
    inp.value = '';

    const msgObj = {
        type: 'text', content: text, sender: myName,
        is_mine: true, created_at: new Date().toISOString(),
    };
    appendMessage(msgObj, true);
    if (!jwtToken) { localMessages.push(msgObj); saveLocal(); }

    await fetch('/api/messages/send', {
        method:  'POST',
        headers: getHeaders(),
        body:    JSON.stringify({ room_code: roomCode, type: 'text', content: text }),
    });
}

// ── Send File ─────────────────────────────────────────────────────────────
async function sendFile(input) {
    const file = input.files[0];
    if (!file || !roomCode) return;

    const isVideo = file.type.startsWith('video/');
    const type    = isVideo ? 'video' : 'image';
    const reader  = new FileReader();

    reader.onload = e => {
        const msgObj = {
            type, file_data: e.target.result.split(',')[1],
            file_mime: file.type, sender: myName,
            is_mine: true, created_at: new Date().toISOString(),
        };
        appendMessage(msgObj, true);
        if (!jwtToken) { localMessages.push(msgObj); saveLocal(); }
    };
    reader.readAsDataURL(file);

    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('type',      type);
    fd.append('file',      file);

    await fetch('/api/messages/send', {
        method:  'POST',
        headers: getHeaders(true),
        body:    fd,
    });

    input.value = '';
}

// ── Render Message ────────────────────────────────────────────────────────
function appendMessage(data, isMine) {
    const box = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'd-flex flex-column ' + (isMine ? 'align-items-end' : 'align-items-start');

    let content = '';
    if (data.type === 'text') {
        content = `<div class="msg-bubble ${isMine ? 'msg-mine' : 'msg-theirs'}">${escHtml(data.content || '')}</div>`;
    } else if (data.type === 'image') {
        const src = data.file_path || (data.file_data
            ? 'data:' + data.file_mime + ';base64,' + data.file_data : '');
        if (src) content = `<img src="${src}" class="msg-image" onclick="window.open(this.src)">`;
    } else if (data.type === 'video') {
        const src = data.file_path || (data.file_data
            ? 'data:' + data.file_mime + ';base64,' + data.file_data : '');
        if (src) content = `<video src="${src}" class="msg-video" controls></video>`;
    }

    div.innerHTML = content +
        `<div class="msg-meta">${isMine ? 'You' : 'Stranger'} · ${new Date(data.created_at).toLocaleTimeString()}</div>`;

    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

function appendSystemMsg(text) {
    const box = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className   = 'text-center text-muted small py-2';
    div.textContent = text;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

function escHtml(t) {
    return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function saveLocal() {
    try { localStorage.setItem('local_msg_' + roomCode, JSON.stringify(localMessages)); } catch (e) {}
}

// ── Navigation ────────────────────────────────────────────────────────────
async function skipStranger() {
    const prev = roomCode;
    await reconnect(prev);
}

async function reconnect(prevRoom) {
    if (roomChannel) { pusher.unsubscribe('room.' + (prevRoom || roomCode)); roomChannel = null; }
    if (prevRoom || roomCode) {
        await fetch('/api/queue/skip', {
            method:  'POST',
            headers: getHeaders(),
            body:    JSON.stringify({ room_code: prevRoom || roomCode }),
        });
    }
    roomCode      = null;
    isMatched     = false;
    isSearching   = false;
    searchSeconds = 0;
    localMessages = [];

    showStep('searchStep');
    document.getElementById('searchText').textContent = 'Finding a new stranger...';
    await joinQueue();
}

async function endChat() {
    const prev = roomCode;
    stopPolling();
    if (prev) {
        await fetch('/api/queue/skip', {
            method:  'POST',
            headers: getHeaders(),
            body:    JSON.stringify({ room_code: prev }),
        });
    } else {
        await fetch('/api/queue/leave', { method: 'POST', headers: getHeaders() });
    }
    window.location.href = '/';
}

async function cancelSearch() {
    stopPolling();
    await fetch('/api/queue/leave', { method: 'POST', headers: getHeaders() });
    window.location.href = '/';
}

async function sendFriendRequest() { alert('Friend request sent!'); }

window.addEventListener('beforeunload', () => {
    stopPolling();
    if (roomCode) {
        navigator.sendBeacon('/api/queue/leave',
            new Blob([JSON.stringify({})], { type: 'application/json' })
        );
    }
});
</script>
@endpush