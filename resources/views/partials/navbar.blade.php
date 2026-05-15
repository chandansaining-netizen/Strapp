<nav class="navbar navbar-expand-lg sticky-top" style="background: rgba(15,14,23,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid #2d2d44;">
    <div class="container">
        <a class="navbar-brand text-purple" href="{{ route('welcome') }}">
            <!-- <i class="fa-solid fa-infinity me-2"></i>StrangerConnect -->
        </a>
        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link text-light" href="{{ route('video.call') }}">
                        <i class="fa-solid fa-video me-1 text-purple"></i>Video
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light" href="{{ route('audio.call') }}">
                        <i class="fa-solid fa-phone me-1 text-purple"></i>Audio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light" href="{{ route('messaging') }}">
                        <i class="fa-solid fa-message me-1 text-purple"></i>Chat
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <div id="navUserInfo" class="d-none">
                    <span class="text-light me-2" id="navUserName"></span>
                    <button class="btn btn-sm btn-outline-danger" onclick="appLogout()">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </div>
                <a href="{{ route('auth') }}" class="btn btn-sm btn-outline-light" id="navLoginBtn">
                    <i class="fa-solid fa-right-to-bracket me-1"></i>Login / Register
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
// Shared auth state
(function(){
    const token = localStorage.getItem('jwt_token');
    const user  = JSON.parse(localStorage.getItem('user_data') || 'null');
    if (token && user) {
        document.getElementById('navLoginBtn').classList.add('d-none');
        document.getElementById('navUserInfo').classList.remove('d-none');
        document.getElementById('navUserName').textContent = user.display_name || user.name;
    }
})();

function appLogout() {
    const token = localStorage.getItem('jwt_token');
    fetch('/api/logout', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' }
    }).finally(() => {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('user_data');
        window.location.href = '/';
    });
}
</script>