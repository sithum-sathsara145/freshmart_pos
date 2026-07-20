<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — FreshMart POS</title>
@include('partials.theme')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:36px;width:100%;max-width:380px}
.logo{text-align:center;margin-bottom:28px}
.logo-icon{width:52px;height:52px;background:var(--surface-2);border:.5px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;color:var(--primary)}
.logo-name{font-size:18px;font-weight:500;color:var(--text)}
.logo-sub{font-size:12px;color:var(--text-3);margin-top:3px}
.fg{margin-bottom:16px}
.fg label{display:block;font-size:12px;color:var(--text-3);margin-bottom:6px}
.fg input{width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:7px;color:var(--text);font-size:13px;padding:9px 12px;outline:none;transition:border-color .15s}
.fg input:focus{border-color:var(--primary)}
.fg input::placeholder{color:var(--text-4)}
.error{background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:6px;padding:8px 12px;font-size:12px;margin-bottom:14px;display:flex;align-items:center;gap:6px}
.btn-login{width:100%;height:42px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:7px;font-size:13px;font-weight:500;cursor:pointer;transition:background .15s}
.btn-login:hover{background:var(--primary-soft-hover)}
.remember{display:flex;align-items:center;gap:7px;font-size:12px;color:var(--text-3);margin-bottom:18px}
.remember input{accent-color:var(--primary)}
.divider{text-align:center;font-size:11px;color:var(--text-4);margin:16px 0}
.roles-hint{background:var(--bg);border:.5px solid var(--border);border-radius:7px;padding:10px 12px;margin-top:14px}
.roles-hint p{font-size:11px;color:var(--text-3);margin-bottom:5px}
.role-row{display:flex;justify-content:space-between;font-size:11px;color:var(--text-2);padding:2px 0}
</style>
</head>
<body>
<button type="button" class="theme-toggle" onclick="toggleTheme()" title="Switch theme"
        style="position:fixed;top:16px;right:16px">
    <i class="ti ti-moon"></i><i class="ti ti-sun"></i>
</button>
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
