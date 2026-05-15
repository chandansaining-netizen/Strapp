<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'StrangerConnect')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c63ff;
            --primary-dark: #5a52d5;
            --secondary: #ff6584;
            --dark-bg: #0f0e17;
            --card-bg: #1a1a2e;
            --card-border: #2d2d44;
            --text-light: #e8e8f0;
        }
        body { background: var(--dark-bg); color: var(--text-light); font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { font-weight: 800; font-size: 1.5rem; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px; }
        .text-purple { color: var(--primary); }
        .bg-purple { background: var(--primary) !important; }
    </style>
    @stack('styles')
</head>
<body>
    @include('partials.navbar')
    <main>@yield('content')</main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>