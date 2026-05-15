<div class="d-flex flex-column p-3 h-100" style="background: var(--card-bg); border-right: 1px solid var(--card-border); min-width: 220px;">
    <h6 class="text-muted text-uppercase fw-bold mb-3 small">Navigation</h6>
    <a href="{{ route('welcome') }}" class="btn btn-outline-secondary text-start mb-2">
        <i class="fa-solid fa-house me-2"></i>Home
    </a>
    <a href="{{ route('video.call') }}" class="btn text-start mb-2 {{ request()->routeIs('video.call') ? 'btn-primary' : 'btn-outline-primary' }}">
        <i class="fa-solid fa-video me-2"></i>Video Call
    </a>
    <a href="{{ route('audio.call') }}" class="btn text-start mb-2 {{ request()->routeIs('audio.call') ? 'btn-success' : 'btn-outline-success' }}">
        <i class="fa-solid fa-phone me-2"></i>Audio Call
    </a>
    <a href="{{ route('messaging') }}" class="btn text-start mb-2 {{ request()->routeIs('messaging') ? 'btn-info' : 'btn-outline-info' }}">
        <i class="fa-solid fa-message me-2"></i>Messaging
    </a>
    <hr class="border-secondary">
    <div id="sidebarAuth">
        <a href="{{ route('auth') }}" class="btn btn-outline-light text-start w-100">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Login / Register
        </a>
    </div>
</div>