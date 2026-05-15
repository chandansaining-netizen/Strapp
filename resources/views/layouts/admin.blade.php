<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — @yield('title','Dashboard')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body{background:#0f172a;color:#e2e8f0;min-height:100vh;}
        .sidebar{background:#1e293b;width:240px;min-height:100vh;padding:20px 0;border-right:1px solid #334155;}
        .sidebar-brand{padding:0 20px 20px;border-bottom:1px solid #334155;margin-bottom:20px;}
        .sidebar-link{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#94a3b8;text-decoration:none;transition:.2s;}
        .sidebar-link:hover,.sidebar-link.active{background:#334155;color:#fff;}
        .main-content{flex:1;padding:24px;}
        .stat-card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;}
        .stat-number{font-size:2rem;font-weight:700;color:#6366f1;}
        .table-dark{--bs-table-bg:#1e293b;--bs-table-border-color:#334155;}
        .badge-live{background:#22c55e;color:white;animation:blink 2s infinite;}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:.5}}
    </style>
</head>
<body>
<div class="d-flex">
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="fw-bold text-indigo-400 fs-5">
                <i class="fa-solid fa-shield-halved me-2" style="color:#6366f1"></i>Admin Panel
            </div>
            <small class="text-muted">{{ session('admin_name','Admin') }}</small>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard')?'active':'' }}">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a href="{{ route('admin.live') }}" class="sidebar-link {{ request()->routeIs('admin.live')?'active':'' }}">
            <i class="fa-solid fa-circle" style="color:#22c55e;font-size:.5rem"></i> Live Users
            <span class="badge badge-live ms-auto px-2">LIVE</span>
        </a>
        <a href="{{ route('admin.messages') }}" class="sidebar-link {{ request()->routeIs('admin.messages')?'active':'' }}">
            <i class="fa-solid fa-messages"></i> Messages
        </a>
        <a href="{{ route('admin.calls') }}" class="sidebar-link {{ request()->routeIs('admin.calls')?'active':'' }}">
            <i class="fa-solid fa-phone"></i> Call Logs
        </a>
        <a href="{{ route('admin.users') }}" class="sidebar-link {{ request()->routeIs('admin.users')?'active':'' }}">
            <i class="fa-solid fa-users"></i> Users
        </a>
        <hr style="border-color:#334155;margin:10px 20px;">
        <a href="{{ route('welcome') }}" class="sidebar-link">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> View Site
        </a>
        <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="sidebar-link w-100 border-0 text-danger" style="background:none;cursor:pointer;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </button>
        </form>
    </div>
    <div class="main-content">@yield('content')</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>