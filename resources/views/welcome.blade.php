@extends('layouts.app')
@section('title', 'StrangerConnect - Meet Random People')

@push('styles')
<style>
.hero-section {
    min-height: 90vh;
    background: radial-gradient(ellipse at 50% 50%, #1a0533 0%, #0f0e17 70%);
    display: flex; align-items: center;
}
.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
}
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(108,99,255,0.2); }
.stat-number { font-size: 2.5rem; font-weight: 800; color: var(--primary); }
.mode-card {
    background: var(--card-bg);
    border: 2px solid var(--card-border);
    border-radius: 20px;
    padding: 32px 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: block;
    color: inherit;
}
.mode-card:hover { transform: translateY(-6px); color: var(--text-light); }
.mode-card.video:hover { border-color: #6c63ff; box-shadow: 0 12px 40px rgba(108,99,255,0.3); }
.mode-card.audio:hover { border-color: #22c55e; box-shadow: 0 12px 40px rgba(34,197,94,0.3); }
.mode-card.message:hover { border-color: #06b6d4; box-shadow: 0 12px 40px rgba(6,182,212,0.3); }
.mode-icon { font-size: 3rem; margin-bottom: 16px; }
.pulse-dot { width: 10px; height: 10px; background: #22c55e; border-radius: 50%; display: inline-block;
    animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,0.7)} 70%{box-shadow:0 0 0 10px rgba(34,197,94,0)} }
.setup-form { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 20px; padding: 32px; }
</style>
@endpush

@section('content')
<!-- Hero -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <span class="pulse-dot"></span>
                    <small class="text-muted">Live Now</small>
                    <span class="badge bg-success ms-1" id="heroActiveCount">{{ $stats['total_active'] }} Active</span>
                </div>
                <h1 class="display-4 fw-bold mb-3">
                    Meet <span class="text-purple">Random Strangers</span><br>Instantly & Freely
                </h1>
                <p class="lead text-muted mb-4">
                    Video call, audio call, or chat with random people worldwide. 
                    No subscription. No hidden fees. Just connect.
                </p>
                <!-- Live Stats Bar -->
                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number" id="statVideo">{{ $stats['video_active'] }}</div>
                            <small class="text-muted"><i class="fa-solid fa-video text-purple me-1"></i>Video</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number" id="statAudio">{{ $stats['audio_active'] }}</div>
                            <small class="text-muted"><i class="fa-solid fa-phone text-success me-1"></i>Audio</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number" id="statMsg">{{ $stats['message_active'] }}</div>
                            <small class="text-muted"><i class="fa-solid fa-message text-info me-1"></i>Chat</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <!-- Setup Form -->
                <div class="setup-form">
                    <h5 class="fw-bold mb-4 text-center">Quick Start</h5>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Your Display Name <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control bg-dark border-secondary text-light" 
                               id="displayName" placeholder="Enter a name or stay anonymous" maxlength="30">
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small">Gender <span class="text-muted">(optional)</span></label>
                        <select class="form-select bg-dark border-secondary text-light" id="genderSelect">
                            <option value="">Prefer not to say</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <a href="#" class="mode-card video" id="btnVideo">
                                <div class="mode-icon">📹</div>
                                <h5 class="fw-bold mb-1">Video Call</h5>
                                <small class="text-muted">Face-to-face with strangers</small>
                                <div class="mt-2">
                                    <span class="badge bg-purple small" id="badgeVideo">{{ $stats['video_active'] }} online</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" class="mode-card audio" id="btnAudio">
                                <div class="mode-icon">🎙️</div>
                                <h5 class="fw-bold mb-1">Audio</h5>
                                <small class="text-muted">Voice only</small>
                                <div class="mt-2">
                                    <span class="badge bg-success small" id="badgeAudio">{{ $stats['audio_active'] }} online</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" class="mode-card message" id="btnMessage">
                                <div class="mode-icon">💬</div>
                                <h5 class="fw-bold mb-1">Chat</h5>
                                <small class="text-muted">Text & media</small>
                                <div class="mt-2">
                                    <span class="badge bg-info small" id="badgeMsg">{{ $stats['message_active'] }} online</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
// Save guest preferences and navigate
async function saveAndNavigate(mode) {
    const name   = document.getElementById('displayName').value.trim();
    const gender = document.getElementById('genderSelect').value;

    // Create guest session if not logged in
    const jwtToken = localStorage.getItem('jwt_token');
    if (!jwtToken) {
        const guestToken = localStorage.getItem('guest_token');
        if (!guestToken) {
            const res = await fetch('/api/guest/session', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ display_name: name || 'Stranger', gender: gender || null })
            });
            const data = await res.json();
            localStorage.setItem('guest_token',    data.guest_token);
            localStorage.setItem('display_name',   data.display_name);
        }
    }
    if (name) localStorage.setItem('display_name', name);
    if (gender) localStorage.setItem('gender', gender);

    const routes = { video: '/video-call', audio: '/audio-call', message: '/messaging' };
    window.location.href = routes[mode];
}

document.getElementById('btnVideo').addEventListener('click',   e => { e.preventDefault(); saveAndNavigate('video'); });
document.getElementById('btnAudio').addEventListener('click',   e => { e.preventDefault(); saveAndNavigate('audio'); });
document.getElementById('btnMessage').addEventListener('click', e => { e.preventDefault(); saveAndNavigate('message'); });

// Real-time stats via Reverb
const Echo = window.Echo || null;
if (typeof Pusher !== 'undefined') {
    const pusher = new Pusher('{{ config("broadcasting.connections.reverb.key") }}', {
        wsHost: '{{ config("broadcasting.connections.reverb.options.host") }}',
        wsPort: {{ config('broadcasting.connections.reverb.options.port', 8080) }},
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        cluster: 'mt1'
    });
    const channel = pusher.subscribe('public.stats');
    channel.bind('stats.updated', function(data) {
        document.getElementById('heroActiveCount').textContent = data.total_active + ' Active';
        document.getElementById('statVideo').textContent = data.video_active;
        document.getElementById('statAudio').textContent = data.audio_active;
        document.getElementById('statMsg').textContent = data.message_active;
        document.getElementById('badgeVideo').textContent = data.video_active + ' online';
        document.getElementById('badgeAudio').textContent = data.audio_active + ' online';
        document.getElementById('badgeMsg').textContent = data.message_active + ' online';
    });
}
</script>
@endpush