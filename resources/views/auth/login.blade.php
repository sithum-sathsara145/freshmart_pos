<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — FreshMart POS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f1117;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{background:#161821;border:.5px solid #2a2d3a;border-radius:12px;padding:36px;width:100%;max-width:380px}
.logo{text-align:center;margin-bottom:28px}
.logo-icon{width:52px;height:52px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;color:#818cf8}
.logo-name{font-size:18px;font-weight:500;color:#e2e8f0}
.logo-sub{font-size:12px;color:#64748b;margin-top:3px}
.fg{margin-bottom:16px}
.fg label{display:block;font-size:12px;color:#64748b;margin-bottom:6px}
.fg input{width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:7px;color:#e2e8f0;font-size:13px;padding:9px 12px;outline:none;transition:border-color .15s}
.fg input:focus{border-color:#818cf8}
.fg input::placeholder{color:#4a5568}
.error{background:#7f1d1d;color:#fca5a5;border:.5px solid #991b1b;border-radius:6px;padding:8px 12px;font-size:12px;margin-bottom:14px;display:flex;align-items:center;gap:6px}
.btn-login{width:100%;height:42px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:7px;font-size:13px;font-weight:500;cursor:pointer;transition:background .15s}
.btn-login:hover{background:#3c3a96}
.remember{display:flex;align-items:center;gap:7px;font-size:12px;color:#64748b;margin-bottom:18px}
.remember input{accent-color:#818cf8}
.divider{text-align:center;font-size:11px;color:#4a5568;margin:16px 0}
.roles-hint{background:#0f1117;border:.5px solid #2a2d3a;border-radius:7px;padding:10px 12px;margin-top:14px}
.roles-hint p{font-size:11px;color:#64748b;margin-bottom:5px}
.role-row{display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;padding:2px 0}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon"><i class="ti ti-shopping-cart"></i></div>
        <div class="logo-name">FreshMart POS</div>
        <div class="logo-sub">Point of Sale System</div>
    </div>

    @if($errors->any())
    <div class="error"><i class="ti ti-alert-circle" style="font-size:14px"></i>{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="fg">
            <label>Email address</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@freshmart.lk" required autofocus>
        </div>
        <div class="fg">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="remember">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn-login">
            <i class="ti ti-login" style="font-size:14px;margin-right:6px"></i>Sign in
        </button>
    </form>

    <div class="roles-hint">
        <p>Default login credentials:</p>
        <div class="role-row"><span>Super Admin</span><span>admin@freshmart.lk / admin123</span></div>
        <div class="role-row"><span>Manager</span><span>manager@freshmart.lk / admin123</span></div>
        <div class="role-row"><span>Cashier</span><span>cashier@freshmart.lk / admin123</span></div>
    </div>
</div>
</body>
</html>
