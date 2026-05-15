@extends('layouts.app')
@section('title', 'Login / Register')

@push('styles')
<style>
.auth-layout{min-height:calc(100vh - 72px);display:flex;align-items:center;justify-content:center;background:radial-gradient(ellipse at 50% 50%,#1a0533 0%,#0f0e17 70%);}
.auth-card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:24px;padding:40px;width:100%;max-width:440px;}
.tab-btn{background:none;border:none;padding:10px 20px;color:#9ca3af;font-weight:600;border-bottom:2px solid transparent;cursor:pointer;transition:all .2s;}
.tab-btn.active{color:#6c63ff;border-color:#6c63ff;}
.form-control,.form-select{background:#0f0e17;border-color:#2d2d44;color:#e8e8f0;}
.form-control:focus,.form-select:focus{background:#0f0e17;border-color:#6c63ff;color:#e8e8f0;box-shadow:0 0 0 .25rem rgba(108,99,255,.25);}
#errorMsg{background:rgba(239,68,68,.1);border:1px solid #ef4444;border-radius:8px;padding:12px;color:#ef4444;display:none;}
</style>
@endpush

@section('content')
<div class="auth-layout">
    <div class="auth-card">
        <div class="d-flex mb-4 border-bottom border-secondary">
            <button class="tab-btn active" id="tabLogin" onclick="switchTab('login')">Login</button>
            <button class="tab-btn"        id="tabReg"   onclick="switchTab('register')">Register</button>
        </div>
        <div id="errorMsg" class="mb-3"></div>

        <!-- Login -->
        <div id="loginForm">
            <div class="mb-3">
                <label class="form-label text-muted small">Email</label>
                <input type="email" class="form-control" id="loginEmail" placeholder="your@email.com">
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small">Password</label>
                <input type="password" class="form-control" id="loginPass" placeholder="••••••••">
            </div>
            <button class="btn btn-primary w-100 py-2" onclick="doLogin()">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Login
            </button>
        </div>

        <!-- Register -->
        <div id="registerForm" class="d-none">
            <div class="mb-3">
                <label class="form-label text-muted small">Name</label>
                <input type="text" class="form-control" id="regName" placeholder="Your name">
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Email</label>
                <input type="email" class="form-control" id="regEmail" placeholder="your@email.com">
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Password</label>
                <input type="password" class="form-control" id="regPass" placeholder="Min 6 characters">
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small">Gender (optional)</label>
                <select class="form-select" id="regGender">
                    <option value="">Prefer not to say</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <button class="btn btn-primary w-100 py-2" onclick="doRegister()">
                <i class="fa-solid fa-user-plus me-2"></i>Create Account
            </button>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">
            <a href="/" class="text-purple">← Continue without account</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchTab(t){
    document.getElementById('loginForm').classList.toggle('d-none',t!=='login');
    document.getElementById('registerForm').classList.toggle('d-none',t!=='register');
    document.getElementById('tabLogin').classList.toggle('active',t==='login');
    document.getElementById('tabReg').classList.toggle('active',t==='register');
    document.getElementById('errorMsg').style.display='none';
}

function showError(msg){const e=document.getElementById('errorMsg');e.textContent=msg;e.style.display='block';}

async function doLogin(){
    const email=document.getElementById('loginEmail').value;
    const pass=document.getElementById('loginPass').value;
    if(!email||!pass)return showError('Please fill all fields');
    const res=await fetch('/api/login',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({email,password:pass})});
    const data=await res.json();
    if(res.ok){
        localStorage.setItem('jwt_token',data.token);
        localStorage.setItem('user_data',JSON.stringify(data.user));
        localStorage.setItem('display_name',data.user.display_name||data.user.name);
        window.location.href='/';
    }else{showError(data.error||'Login failed');}
}

async function doRegister(){
    const name=document.getElementById('regName').value;
    const email=document.getElementById('regEmail').value;
    const pass=document.getElementById('regPass').value;
    const gender=document.getElementById('regGender').value;
    if(!name||!email||!pass)return showError('Please fill all required fields');
    const res=await fetch('/api/register',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({name,email,password:pass,gender:gender||null})});
    const data=await res.json();
    if(res.ok){
        localStorage.setItem('jwt_token',data.token);
        localStorage.setItem('user_data',JSON.stringify(data.user));
        localStorage.setItem('display_name',data.user.display_name||data.user.name);
        window.location.href='/';
    }else{showError(data.message||'Registration failed');}
}
</script>
@endpush