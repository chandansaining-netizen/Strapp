@extends('layouts.app')
@section('title', 'Audio Call - StrangerConnect')

@push('styles')
<style>
.audio-layout { min-height: calc(100vh - 72px); background: var(--dark-bg);
    display: flex; align-items: center; justify-content: center; }
.audio-card { background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: 24px; padding: 48px 40px; max-width: 460px; width: 100%; text-align: center; }
.avatar-ring { width: 140px; height: 140px; border-radius: 50%; margin: 0 auto 24px;
    background: linear-gradient(135deg, #6c63ff, #ff6584);
    display: flex; align-items: center; justify-content: center; font-size: 3.5rem;
    animation: ringPulse 2s infinite; position: relative; }
@keyframes ringPulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(108,99,255,0.5); }
    50%      { box-shadow: 0 0 0 20px rgba(108,99,255,0); }
}
.wave-bars { display: flex; align-items: center; justify-content: center; gap: 4px; height: 40px; margin: 16px 0; }
.wave-bar { width: 4px; background: #6c63ff; border-radius: 4px; animation: wave 1.2s infinite ease-in-out; }
.wave-bar:nth-child(1){animation-delay:0s;height:20px}
.wave-bar:nth-child(2){animation-delay:.1s;height:30px}
.wave-bar:nth-child(3){animation-delay:.2s;height:40px}
.wave-bar:nth-child(4){animation-delay:.3s;height:30px}
.wave-bar:nth-child(5){animation-delay:.4s;height:20px}
@keyframes wave { 0%,100%{transform:scaleY(0.5)} 50%{transform:scaleY(1)} }
.timer { font-size: 1.5rem; font-weight: 300; color: #9ca3af; letter-spacing: 4px; }
.ctrl-row { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
.ctrl-btn { width: 60px; height: 60px; border-radius: 50%; border: none; font-size: 1.3rem; cursor: pointer; transition: all .2s; }
</style>
@endpush

@section('content')
<div class="audio-layout">
    <div class="audio-card">
        <!-- Permission Step -->
        <div id="permStep">
            <div style="font-size:5rem; margin-bottom: 20px;">🎙️</div>
            <h4 class="fw-bold mb-2">Audio Call</h4>
            <p class="text-muted mb-4">Allow microphone access to talk with random strangers</p>
            <button class="btn btn-primary btn-lg px-5" onclick="requestPermissions()">
                <i class="fa-solid fa-microphone me-2"></i>Allow & Start
            </button>
        </div>

        <!-- Search Step -->
        <div id="searchStep" class="d-none">
            <div class="avatar-ring">🔍</div>
            <h5 id="statusText">Finding someone to talk to...</h5>
            <p class="text-muted small" id="waitInfo"></p>
            <div class="mt-3">
                <button class="btn btn-outline-danger" onclick="cancelSearch()">Cancel</button>
            </div>
        </div>

        <!-- Call Active -->
        <div id="callStep" class="d-none">
            <div class="avatar-ring" id="avatarRing">👤</div>
            <h5 id="strangerLabel">Stranger</h5>
            <div class="wave-bars" id="waveBars">
                <div class="wave-bar"></div><div class="wave-bar"></div>
                <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
            </div>
            <div class="timer" id="callTimer">00:00</div>
            <div class="ctrl-row">
                <button class="ctrl-btn" id="btnMic" onclick="toggleMic()"
                        style="background:#374151; color:white;" title="Mute">
                    <i class="fa-solid fa-microphone" id="micIcon"></i>
                </button>
                <button class="ctrl-btn" onclick="skipStranger()"
                        style="background:#f59e0b; color:white;" title="Next">
                    <i class="fa-solid fa-forward-step"></i>
                </button>
                <button class="ctrl-btn" onclick="endCall()"
                        style="background:#ef4444; color:white;" title="End">
                    <i class="fa-solid fa-phone-slash"></i>
                </button>
            </div>
            <div id="friendSection" class="mt-3 d-none">
                <button class="btn btn-outline-primary btn-sm" onclick="sendFriendRequest()">
                    <i class="fa-solid fa-user-plus me-1"></i>Add Friend
                </button>
            </div>
        </div>

        <!-- Hidden audio element -->
        <audio id="remoteAudio" autoplay></audio>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
// ── State ─────────────────────────────────────────────────────────────────
let localStream  = null;
let peerConn     = null;
let roomCode     = null;
let isInitiator  = false;
let pusher       = null;
let roomChannel  = null;
let waitChannel  = null;
let micEnabled   = true;
let makingOffer  = false;
let myIdentity   = null;   // ← our own sender ID to filter own signals

// Timer
let timerInterval = null;
let callSeconds   = 0;

// Polling state
let pollInterval      = null;
let isMatched         = false;
let isSearching       = false;
let searchSeconds     = 0;
const POLL_INTERVAL_MS = 3000;

const jwtToken   = localStorage.getItem('jwt_token');
const guestToken = localStorage.getItem('guest_token');
const userData   = JSON.parse(localStorage.getItem('user_data') || 'null');

// Build identity string — must match what SignalController builds
if (userData) {
    myIdentity = 'user_' + userData.id;
} else if (guestToken) {
    myIdentity = 'guest_' + guestToken;
}

const iceConfig = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' },
    ]
};

function getHeaders() {
    const h = { 'Content-Type': 'application/json' };
    if (jwtToken)   h['Authorization'] = 'Bearer ' + jwtToken;
    if (guestToken) h['X-Guest-Token'] = guestToken;
    return h;
}

// ── Step switcher ─────────────────────────────────────────────────────────
function show(id) {
    ['permStep', 'searchStep', 'callStep'].forEach(s =>
        document.getElementById(s).classList.add('d-none')
    );
    document.getElementById(id).classList.remove('d-none');
}

// ── Permissions ───────────────────────────────────────────────────────────
async function requestPermissions() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({
            audio: true,
            video: false,
        });
        show('searchStep');
        setupPusher();
        await joinQueue();
    } catch (err) {
        console.error('[PERM]', err);
        alert('Microphone permission denied!\nPlease allow access and try again.');
    }
}

// ── Pusher / Reverb ───────────────────────────────────────────────────────
function setupPusher() {
    pusher = new Pusher('{{ config("broadcasting.connections.reverb.key") }}', {
        wsHost:            '{{ config("broadcasting.connections.reverb.options.host") }}',
        wsPort:            {{ config('broadcasting.connections.reverb.options.port', 8080) }},
        wssPort:           {{ config('broadcasting.connections.reverb.options.port', 8080) }},
        forceTLS:          false,
        disableStats:      true,
        enabledTransports: ['ws', 'wss'],
        cluster:           'mt1',
    });

    const waitKey = userData
        ? 'waiting.user.' + userData.id
        : 'waiting.guest.' + guestToken;

    waitChannel = pusher.subscribe(waitKey);
    waitChannel.bind('user.matched', function (data) {
        if (isMatched) return;
        console.log('[WS] Audio matched via WebSocket');
        handleMatch(data.room_code, false); // partner = NOT initiator
    });
}

// ── Join Queue ────────────────────────────────────────────────────────────
async function joinQueue() {
    if (isMatched || isSearching) return;
    isSearching = true;

    try {
        const res  = await fetch('/api/queue/join', {
            method:  'POST',
            headers: getHeaders(),
            body:    JSON.stringify({ type: 'audio' }),
        });
        const data = await res.json();

        if (data.matched) {
            handleMatch(data.room_code, data.is_initiator);
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

// ── Polling fallback ──────────────────────────────────────────────────────
function startPolling() {
    stopPolling();

    pollInterval = setInterval(async () => {
        if (isMatched) { stopPolling(); return; }

        try {
            const res  = await fetch('/api/queue/status', {
                method:  'POST',
                headers: getHeaders(),
                body:    JSON.stringify({ type: 'audio' }),
            });
            const data = await res.json();

            if (data.matched) {
                console.log('[POLL] Audio matched via polling');
                handleMatch(data.room_code, data.is_initiator);
            } else {
                searchSeconds += 3;
                const t = searchSeconds < 60
                    ? searchSeconds + 's'
                    : Math.floor(searchSeconds / 60) + 'm ' + (searchSeconds % 60) + 's';
                document.getElementById('statusText').textContent =
                    'Finding someone to talk to... (' + t + ')';
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
function handleMatch(code, initiator) {
    if (isMatched) return;  // guard: WS + poll can both fire, only handle once
    isMatched   = true;
    isSearching = false;
    makingOffer = false;
    stopPolling();

    roomCode    = code;
    isInitiator = initiator;

    console.log('[MATCH] room=' + roomCode
        + ' | initiator=' + isInitiator
        + ' | me=' + myIdentity);

    // Subscribe to room signaling channel
    if (roomChannel) pusher.unsubscribe('room.' + roomCode);
    roomChannel = pusher.subscribe('room.' + roomCode);
    roomChannel.bind('webrtc.signal', handleSignal);
    roomChannel.bind('user.skipped',  onPartnerSkipped);

    // Show call UI
    show('callStep');
    startTimer();

    if (userData) {
        document.getElementById('friendSection').classList.remove('d-none');
    }

    // Only initiator creates offer — delay so other side subscribes first
    if (isInitiator) {
        setTimeout(createOffer, 1200);
    }
}

// ── WebRTC: Create Offer ──────────────────────────────────────────────────
async function createOffer() {
    if (!isInitiator) return;

    closePeer();
    peerConn    = buildPeerConnection();
    makingOffer = true;

    try {
        const offer = await peerConn.createOffer();

        if (peerConn.signalingState !== 'stable') {
            console.warn('[OFFER] Skipped — state is', peerConn.signalingState);
            makingOffer = false;
            return;
        }

        await peerConn.setLocalDescription(offer);
        makingOffer = false;

        console.log('[OFFER] Sending offer');
        await sendSignal(JSON.stringify(peerConn.localDescription), 'offer');

    } catch (err) {
        makingOffer = false;
        console.error('[OFFER] Error:', err);
    }
}

// ── WebRTC: Handle Incoming Signals ──────────────────────────────────────
async function handleSignal(data) {
    const { signal, type, from } = data;

    // ── CRITICAL: Ignore our own signals ─────────────────────────────────
    if (from === myIdentity) {
        console.log('[SIGNAL] Ignoring own signal type=' + type);
        return;
    }

    console.log('[SIGNAL] Received type=' + type
        + ' | from=' + from
        + ' | state=' + (peerConn ? peerConn.signalingState : 'no-peer'));

    try {

        // ── OFFER (only non-initiator handles this) ───────────────────────
        if (type === 'offer') {
            if (isInitiator) {
                console.warn('[SIGNAL] Initiator received offer — ignoring');
                return;
            }

            if (!peerConn) {
                peerConn = buildPeerConnection();
            }

            if (peerConn.signalingState !== 'stable') {
                console.warn('[SIGNAL] Cannot accept offer — state is:', peerConn.signalingState);
                return;
            }

            await peerConn.setRemoteDescription(
                new RTCSessionDescription(JSON.parse(signal))
            );

            const answer = await peerConn.createAnswer();

            if (peerConn.signalingState !== 'have-remote-offer') {
                console.warn('[SIGNAL] State changed before setLocalDescription:', peerConn.signalingState);
                return;
            }

            await peerConn.setLocalDescription(answer);
            console.log('[ANSWER] Sending answer');
            await sendSignal(JSON.stringify(peerConn.localDescription), 'answer');
        }

        // ── ANSWER (only initiator handles this) ──────────────────────────
        else if (type === 'answer') {
            if (!isInitiator) {
                console.warn('[SIGNAL] Non-initiator received answer — ignoring');
                return;
            }

            if (!peerConn) {
                console.warn('[SIGNAL] Got answer but peerConn is null');
                return;
            }

            // Only valid when we have sent an offer and are waiting for answer
            if (peerConn.signalingState !== 'have-local-offer') {
                console.warn('[SIGNAL] Cannot set answer — state is:', peerConn.signalingState, '— ignoring');
                return;
            }

            await peerConn.setRemoteDescription(
                new RTCSessionDescription(JSON.parse(signal))
            );
            console.log('[ANSWER] Remote description set — call should be live');
        }

        // ── ICE CANDIDATE (both sides) ────────────────────────────────────
        else if (type === 'ice-candidate') {
            if (!peerConn) {
                console.warn('[ICE] No peerConn — ignoring candidate');
                return;
            }

            // Must have remote description before adding candidates
            if (!peerConn.remoteDescription || !peerConn.remoteDescription.type) {
                console.warn('[ICE] No remote description yet — ignoring candidate');
                return;
            }

            try {
                await peerConn.addIceCandidate(
                    new RTCIceCandidate(JSON.parse(signal))
                );
            } catch (iceErr) {
                // Non-fatal — log and continue
                console.warn('[ICE] addIceCandidate failed (non-fatal):', iceErr.message);
            }
        }

    } catch (err) {
        console.error('[SIGNAL] handleSignal crash:', err.name, '—', err.message);
    }
}

// ── Build RTCPeerConnection ───────────────────────────────────────────────
function buildPeerConnection() {
    const pc = new RTCPeerConnection(iceConfig);

    // Add local audio tracks
    if (localStream) {
        localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
    }

    // Play remote audio when it arrives
    pc.ontrack = (e) => {
        console.log('[PEER] Remote audio track received');
        const audio = document.getElementById('remoteAudio');
        if (audio.srcObject !== e.streams[0]) {
            audio.srcObject = e.streams[0];
        }
    };

    // Send ICE candidates as gathered
    pc.onicecandidate = (e) => {
        if (e.candidate) {
            sendSignal(JSON.stringify(e.candidate), 'ice-candidate');
        }
    };

    pc.oniceconnectionstatechange = () => {
        console.log('[ICE] State:', pc.iceConnectionState);
        if (pc.iceConnectionState === 'failed') {
            console.warn('[ICE] Failed — restarting ICE');
            pc.restartIce();
        }
    };

    pc.onsignalingstatechange = () => {
        console.log('[PEER] Signaling state:', pc.signalingState);
    };

    pc.onconnectionstatechange = () => {
        console.log('[PEER] Connection state:', pc.connectionState);
    };

    return pc;
}

// ── Close & Cleanup Peer ──────────────────────────────────────────────────
function closePeer() {
    if (peerConn) {
        peerConn.ontrack                    = null;
        peerConn.onicecandidate             = null;
        peerConn.oniceconnectionstatechange = null;
        peerConn.onsignalingstatechange     = null;
        peerConn.onconnectionstatechange    = null;
        peerConn.close();
        peerConn = null;
    }
}

// ── Send Signal ───────────────────────────────────────────────────────────
async function sendSignal(signal, type) {
    try {
        await fetch('/api/signal', {
            method:  'POST',
            headers: getHeaders(),
            body:    JSON.stringify({ room_code: roomCode, signal, type }),
        });
    } catch (err) {
        console.error('[SIGNAL] Send failed:', err);
    }
}

// ── Timer ─────────────────────────────────────────────────────────────────
function startTimer() {
    callSeconds   = 0;
    timerInterval = setInterval(() => {
        callSeconds++;
        const m = String(Math.floor(callSeconds / 60)).padStart(2, '0');
        const s = String(callSeconds % 60).padStart(2, '0');
        document.getElementById('callTimer').textContent = m + ':' + s;
    }, 1000);
}

function stopTimer() {
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
}

// ── Mic Toggle ────────────────────────────────────────────────────────────
function toggleMic() {
    micEnabled = !micEnabled;
    localStream.getAudioTracks().forEach(t => t.enabled = micEnabled);
    document.getElementById('micIcon').className = micEnabled
        ? 'fa-solid fa-microphone'
        : 'fa-solid fa-microphone-slash';
    document.getElementById('btnMic').style.background = micEnabled
        ? '#374151' : '#ef4444';
}

// ── Skip / End / Cancel ───────────────────────────────────────────────────
async function skipStranger() {
    const prev = roomCode;
    resetCallState();

    if (prev) {
        await fetch('/api/queue/skip', {
            method:  'POST',
            headers: getHeaders(),
            body:    JSON.stringify({ room_code: prev }),
        });
    }

    searchSeconds = 0;
    show('searchStep');
    document.getElementById('statusText').textContent = 'Finding next stranger...';
    await joinQueue();
}

async function endCall() {
    const prev = roomCode;
    stopPolling();
    resetCallState();

    if (prev) {
        await fetch('/api/queue/skip', {
            method:  'POST',
            headers: getHeaders(),
            body:    JSON.stringify({ room_code: prev }),
        });
    } else {
        await fetch('/api/queue/leave', { method: 'POST', headers: getHeaders() });
    }

    if (localStream) localStream.getTracks().forEach(t => t.stop());
    window.location.href = '/';
}

async function cancelSearch() {
    stopPolling();
    await fetch('/api/queue/leave', { method: 'POST', headers: getHeaders() });
    window.location.href = '/';
}

function onPartnerSkipped() {
    console.log('[ROOM] Partner left');
    resetCallState();
    searchSeconds = 0;
    show('searchStep');
    document.getElementById('statusText').textContent = 'Stranger left. Finding next...';
    setTimeout(joinQueue, 1500);
}

// ── Reset Everything ──────────────────────────────────────────────────────
function resetCallState() {
    isMatched   = false;
    isSearching = false;
    makingOffer = false;
    stopPolling();
    stopTimer();
    closePeer();

    if (roomChannel) {
        pusher.unsubscribe('room.' + roomCode);
        roomChannel = null;
    }

    document.getElementById('remoteAudio').srcObject = null;
    document.getElementById('friendSection').classList.add('d-none');
    roomCode = null;
}

async function sendFriendRequest() {
    alert('Friend request sent!');
}

// ── Cleanup on tab close ──────────────────────────────────────────────────
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