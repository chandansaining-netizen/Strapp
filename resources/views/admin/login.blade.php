<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>body{background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;}</style>
</head>
<body>
<div class="card" style="background:#1e293b;border:1px solid #334155;border-radius:20px;padding:40px;width:380px;">
    <div class="text-center mb-4">
        <i class="fa-solid fa-shield-halved" style="font-size:3rem;color:#6366f1;"></i>
        <h4 class="mt-2 fw-bold">Admin Login</h4>
    </div>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.login.post') }}" method="POST">
        @csrf
        <div class="mb-3">
            <input type="email" name="email" class="form-control bg-dark border-secondary text-light"
                   placeholder="Admin Email" required>
        </div>
        <div class="mb-4">
            <input type="password" name="password" class="form-control bg-dark border-secondary text-light"
                   placeholder="Password" required>
        </div>
        <button type="submit" class="btn w-100 py-2" style="background:#6366f1;color:white;">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Login
        </button>
    </form>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</body>
</html>