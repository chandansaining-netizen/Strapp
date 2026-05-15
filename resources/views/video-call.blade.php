@extends('layouts.app')
@section('title', 'Video Call - StrangerConnect')

@push('styles')
<style>
.call-layout { display: flex; height: calc(100vh - 72px); background: #000; }
.video-container { flex: 1; position: relative; background: #111; }
.video-remote { width: 100%; height: 100%; object-fit: cover; background: #1a1a2e; }
.video-local  { position: absolute; bottom: 90px; right: 20px; width: 200px; height: 130px;
    object-fit: cover; border-radius: 12px; border: 2px solid #6c63ff; background: #0f0e17; z-index: 10; }
.controls-bar { position: absolute; bottom: 0; left: 0; right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.9));
    padding: 20px; display: flex; align-items: center; justify-content: center; gap: 16px; }
.ctrl-btn { width: 56px; height: 56px; border-radius: 50%; border: none; cursor: pointer;
    font-size: 1.2rem; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
.ctrl-btn.danger  { background: #ef4444; color: white; }
.ctrl-btn.warning { background: #f59e0b; color: white; }
.ctrl-btn.success { background: #22c55e; color: white; }
.ctrl-btn.secondary { background: #374151; color: white; }
.ctrl-btn:hover { transform: scale(1.1); }
.status-overlay { position: absolute; inset: 0; background: rgba(15,14,23,0.95);
    display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 20; }
.spinner-ring { width: 70px; height: 70px; border: 4px solid #2d2d44; border-top: 4px solid #6c63ff;
    border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
@keyframes spin { to { transform: rotate(360deg); } }
.stranger-name { position: absolute; top: 20px; left: 20px; background: rgba(0,0,0,0.6);
    padding: 6px 14px; border-radius: 20px; font-size: 0.9rem; }
.friend-btn { position: absolute; top: 20px; right: 20px; background: rgba(108,99,255,0.8);
    border: none; padding: 6px 14px; border-radius: 20px; color: white; font-size: 0.85rem; cursor: pointer; display: none; }
</style>
@endpush

@section('content')
<div class="call-layout">
    <div class="video-container">
        <!-- Remote Video -->
        <video id="remoteVideo" class="video-remote" autoplay playsinline></video>
        <!-- Local Video -->
        <video id="localVideo"  class="video-local"  autoplay playsinline muted></video>

        <!-- Stranger name badge -->
        <div class="stranger-name text-light" id="strangerName">
            <i class="fa-solid fa-user me-1 text-purple"></i><span>Waiting...</span>
        </div>

        <!-- Friend request button (only for logged in users) -->
        <button class="friend-btn" id="friendBtn" onclick="sendFriendRequest()">
            <i class="fa-solid fa-user-plus me-1"></i>Add Friend
        </button>

        <!-- Status overlay (searching / permission) -->
        <div class="status-overlay" id="statusOverlay">
            <div id="permissionStep">
                <div class="text-center mb-4">
                    <div style="font-size:4rem;">📷</div>
                    <h4 class="mt-3">Camera & Mic Access</h4>
                    <p class="text-muted">We need permission to start your video call</p>
                    <button class="btn btn-primary btn-lg px-5" onclick="requestPermissions()">
                        <i class="fa-solid fa-lock-open me-2"></i>Allow Access & Start
                    </button>
                </div>
            </div>
            <div id="searchStep" class="d-none text-center">
                <div class="spinner-ring"></div>
                <h5 id="statusText">Finding a stranger...</h5>
                <p class="text-muted small mt-2" id="waitingCount"></p>
                <button class="btn btn-outline-danger mt-3" onclick="cancelSearch()">Cancel</button>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls-bar" id="controlsBar" style="display:none!important;">
            <button class="ctrl-btn secondary" id="btnMuteMic" onclick="toggleMic()" title="Mute">
                <i class="fa-solid fa-microphone" id="micIcon"></i>
            </button>
            <button class="ctrl-btn secondary" id="btnMuteCam" onclick="toggleCam()" title="Camera">
                <i class="fa-solid fa-video" id="camIcon"></i>
            </button>
            <button class="ctrl-btn warning" onclick="skipStranger()" title="Next">
                <i class="fa-solid fa-forward-step"></i>
            </button>
            <button class="ctrl-btn danger" onclick="endCall()" title="End">
                <i class="fa-solid fa-phone-slash"></i>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
// ── State ─────────────────────────────────────────────────────────────────
let localStream   = null;
let peerConn      = null;
let roomCode      = null;
let isInitiator   = false;
let pusher        = null;
let roomChannel   = null;
let waitChannel   = null;
let micEnabled    = true;
let camEnabled    = true;
let makingOffer   = false;   // ← prevents offer collision
let myIdentity    = null;    // ← our own sender ID (to ignore own signals)

// Polling state
let pollInterval      = null;
let isMatched         = false;
let isSearching       = false;
let searchSeconds     = 0;
const POLL_INTERVAL_MS = 3000;

const jwtToken    = localStorage.getItem('jwt_token');
const guestToken  = localStorage.getItem('guest_token');
const userData    = JSON.parse(localStorage.getItem('user_data') || 'null');

// Build our own identity string (must match what SignalController builds)
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
        console.log('[WS] Matched via WebSocket');
        handleMatch(data.room_code, false);  // partner = NOT initiator
    });
}

// ── Permissions ───────────────────────────────────────────────────────────
async function requestPermissions() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: true,
        });
        document.getElementById('localVideo').srcObject = localStream;
        document.getElementById('permissionStep').classList.add('d-none');
        document.getElementById('searchStep').classList.remove('d-none');
        setupPusher();
        await joinQueue();
    } catch (err) {
        console.error('[PERM]', err);
        alert('Camera & Microphone permission denied.\nPlease allow access and try again.');
    }
}

// ── Queue ─────────────────────────────────────────────────────────────────
async function joinQueue() {
    if (isMatched || isSearching) return;
    isSearching = true;

    try {
        const res  = await fetch('/api/queue/join', {
            method:  'POST',
            headers: getHeaders(),
            body:    JSON.stringify({ type: 'video' }),
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

// ── Polling ───────────────────────────────────────────────────────────────
function startPolling() {
    stopPolling();
    pollInterval = setInterval(async () => {
        if (isMatched) { stopPolling(); return; }
        try {
            const res  = await fetch('/api/queue/status', {
                method:  'POST',
                headers: getHeaders(),
                body:    JSON.stringify({ type: 'video' }),
            });
            const data = await res.json();

            if (data.matched) {
                console.log('[POLL] Matched via poll');
                handleMatch(data.room_code, data.is_initiator);
            } else {
                searchSeconds += 3;
                const t = searchSeconds < 60
                    ? searchSeconds + 's'
                    : Math.floor(searchSeconds / 60) + 'm ' + (searchSeconds % 60) + 's';
                document.getElementById('statusText').textContent   = 'Finding a stranger...';
                document.getElementById('waitingCount').textContent = 'Searching for ' + t;
            }
        } catch (e) {
            console.warn('[POLL] failed:', e);
        }
    }, POLL_INTERVAL_MS);
}

function stopPolling() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
}

// ── On Match ──────────────────────────────────────────────────────────────
function handleMatch(code, initiator) {
    if (isMatched) return;  // guard against WS + poll both firing
    isMatched   = true;
    isSearching = false;
    stopPolling();

    roomCode    = code;
    isInitiator = initiator;
    makingOffer = false;

    console.log('[MATCH] room=' + roomCode + ' initiator=' + isInitiator + ' me=' + myIdentity);

    // Subscribe to room signaling channel
    if (roomChannel) pusher.unsubscribe('room.' + roomCode);
    roomChannel = pusher.subscribe('room.' + roomCode);
    roomChannel.bind('webrtc.signal', handleSignal);
    roomChannel.bind('user.skipped',  onPartnerSkipped);

    // Show call UI
    document.getElementById('statusOverlay').style.display = 'none';
    const bar = document.getElementById('controlsBar');
    bar.style.cssText = '';
    bar.style.display = 'flex';

    if (userData) document.getElementById('friendBtn').style.display = 'block';

    // Only the initiator creates the offer
    // Delay slightly so the non-initiator has time to subscribe to the channel
    if (isInitiator) {
        setTimeout(createOffer, 1200);
    }
}

// ── WebRTC: Create Offer ──────────────────────────────────────────────────
async function createOffer() {
    if (!isInitiator) return;

    // Clean up any old connection
    closePeer();
    peerConn    = buildPeerConnection();
    makingOffer = true;

    try {
        const offer = await peerConn.createOffer();

        // Check state before setting — connection could close mid-flight
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

    // ── CRITICAL: Ignore signals from ourselves ───────────────────────────
    if (from === myIdentity) {
        console.log('[SIGNAL] Ignoring own signal type=' + type);
        return;
    }

    console.log('[SIGNAL] Received type=' + type + ' from=' + from
        + ' state=' + (peerConn ? peerConn.signalingState : 'no-peer'));

    try {
        // ── OFFER (only non-initiator should receive this) ────────────────
        if (type === 'offer') {
            if (isInitiator) {
                console.warn('[SIGNAL] Initiator got offer — ignoring (not expected)');
                return;
            }

            // Build peer if not yet created
            if (!peerConn) {
                peerConn = buildPeerConnection();
            }

            // Must be in stable state to accept an offer
            if (peerConn.signalingState !== 'stable') {
                console.warn('[SIGNAL] Cannot accept offer in state:', peerConn.signalingState);
                return;
            }

            await peerConn.setRemoteDescription(new RTCSessionDescription(JSON.parse(signal)));

            const answer = await peerConn.createAnswer();

            // Check state again before setting local description
            if (peerConn.signalingState !== 'have-remote-offer') {
                console.warn('[SIGNAL] State changed before setLocalDescription:', peerConn.signalingState);
                return;
            }

            await peerConn.setLocalDescription(answer);
            console.log('[ANSWER] Sending answer');
            await sendSignal(JSON.stringify(peerConn.localDescription), 'answer');
        }

        // ── ANSWER (only initiator should receive this) ───────────────────
        else if (type === 'answer') {
            if (!isInitiator) {
                console.warn('[SIGNAL] Non-initiator got answer — ignoring');
                return;
            }

            if (!peerConn) {
                console.warn('[SIGNAL] Got answer but no peerConn');
                return;
            }

            // Only set remote description if we're expecting an answer
            if (peerConn.signalingState !== 'have-local-offer') {
                console.warn('[SIGNAL] Cannot set answer in state:', peerConn.signalingState, '— ignoring');
                return;
            }

            await peerConn.setRemoteDescription(new RTCSessionDescription(JSON.parse(signal)));
            console.log('[ANSWER] Remote description set successfully');
        }

        // ── ICE CANDIDATE (both sides) ────────────────────────────────────
        else if (type === 'ice-candidate') {
            if (!peerConn) {
                console.warn('[ICE] Got candidate but no peerConn — ignoring');
                return;
            }

            // Only add candidate if remote description is set
            if (!peerConn.remoteDescription || !peerConn.remoteDescription.type) {
                console.warn('[ICE] Ignoring candidate — no remote description yet');
                // Queue it for later (optional enhancement)
                return;
            }

            try {
                await peerConn.addIceCandidate(new RTCIceCandidate(JSON.parse(signal)));
            } catch (iceErr) {
                // Non-fatal — some candidates just fail
                console.warn('[ICE] addIceCandidate failed (non-fatal):', iceErr.message);
            }
        }

    } catch (err) {
        console.error('[SIGNAL] handleSignal error:', err.name, err.message);
    }
}

// ── WebRTC: Build Peer Connection ─────────────────────────────────────────
function buildPeerConnection() {
    const pc = new RTCPeerConnection(iceConfig);

    // Add local tracks
    if (localStream) {
        localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
    }

    // Show remote stream when it arrives
    pc.ontrack = (e) => {
        console.log('[PEER] Remote track received');
        const remoteVideo = document.getElementById('remoteVideo');
        if (remoteVideo.srcObject !== e.streams[0]) {
            remoteVideo.srcObject = e.streams[0];
        }
    };

    // Send ICE candidates as they are gathered
    pc.onicecandidate = (e) => {
        if (e.candidate) {
            sendSignal(JSON.stringify(e.candidate), 'ice-candidate');
        }
    };

    pc.oniceconnectionstatechange = () => {
        console.log('[ICE] Connection state:', pc.iceConnectionState);
        if (pc.iceConnectionState === 'failed') {
            console.warn('[ICE] Connection failed — attempting restart');
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

function closePeer() {
    if (peerConn) {
        peerConn.ontrack         = null;
        peerConn.onicecandidate  = null;
        peerConn.oniceconnectionstatechange = null;
        peerConn.onsignalingstatechange     = null;
        peerConn.onconnectionstatechange    = null;
        peerConn.close();
        peerConn = null;
    }
}

// ── Send Signal to Backend ────────────────────────────────────────────────
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

// ── Controls ──────────────────────────────────────────────────────────────
function toggleMic() {
    micEnabled = !micEnabled;
    localStream.getAudioTracks().forEach(t => t.enabled = micEnabled);
    document.getElementById('micIcon').className = micEnabled
        ? 'fa-solid fa-microphone' : 'fa-solid fa-microphone-slash';
    document.getElementById('btnMuteMic').style.background = micEnabled ? '#374151' : '#ef4444';
}

function toggleCam() {
    camEnabled = !camEnabled;
    localStream.getVideoTracks().forEach(t => t.enabled = camEnabled);
    document.getElementById('camIcon').className = camEnabled
        ? 'fa-solid fa-video' : 'fa-solid fa-video-slash';
    document.getElementById('btnMuteCam').style.background = camEnabled ? '#374151' : '#ef4444';
}

async function skipStranger() {
    const prev = roomCode;
    resetCallState();

    if (prev) {
        await fetch('/api/queue/skip', {
            method:  'POST', headers: getHeaders(),
            body:    JSON.stringify({ room_code: prev }),
        });
    }

    // Reset search UI
    document.getElementById('statusOverlay').style.display = 'flex';
    document.getElementById('searchStep').classList.remove('d-none');
    document.getElementById('permissionStep').classList.add('d-none');
    document.getElementById('statusText').textContent   = 'Finding a new stranger...';
    document.getElementById('waitingCount').textContent = '';
    searchSeconds = 0;

    await joinQueue();
}

async function endCall() {
    const prev = roomCode;
    stopPolling();
    resetCallState();

    if (prev) {
        await fetch('/api/queue/skip', {
            method:  'POST', headers: getHeaders(),
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
    console.log('[ROOM] Partner skipped/left');
    resetCallState();

    document.getElementById('statusOverlay').style.display = 'flex';
    document.getElementById('searchStep').classList.remove('d-none');
    document.getElementById('permissionStep').classList.add('d-none');
    document.getElementById('statusText').textContent   = 'Stranger left. Finding next...';
    document.getElementById('waitingCount').textContent = '';
    searchSeconds = 0;

    setTimeout(joinQueue, 1500);
}

function resetCallState() {
    isMatched   = false;
    isSearching = false;
    makingOffer = false;
    stopPolling();
    closePeer();

    if (roomChannel) {
        pusher.unsubscribe('room.' + roomCode);
        roomChannel = null;
    }

    document.getElementById('remoteVideo').srcObject = null;
    document.getElementById('friendBtn').style.display  = 'none';
    document.getElementById('controlsBar').style.display = 'none';
    roomCode = null;
}

async function sendFriendRequest() {
    alert('Friend request sent!');
}

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