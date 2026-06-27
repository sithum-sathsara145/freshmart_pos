{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FreshMart POS')</title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {{-- Tabler Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bg: '#0f1117',
                        surface: '#161821',
                        border: '#2a2d3a',
                        primary: '#818cf8',
                    }
                }
            }
        }
    </script>

    <style>
        * { box-sizing: border-box; }
        body { background: #0f1117; color: #e2e8f0; font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #0f1117; }
        ::-webkit-scrollbar-thumb { background: #2a2d3a; border-radius: 3px; }
        .sidebar-link { display: flex; align-items: center; gap: 9px; padding: 8px 14px; font-size: 13px; color: #94a3b8; cursor: pointer; border-left: 2px solid transparent; transition: all .12s; text-decoration: none; }
        .sidebar-link:hover { background: #1e2130; color: #e2e8f0; }
        .sidebar-link.active { background: #1e2130; color: #a5b4fc; border-left-color: #818cf8; }
        .sidebar-link i { font-size: 16px; width: 20px; }
        .sidebar-section { font-size: 10px; color: #4a5568; padding: 10px 14px 3px; letter-spacing: .7px; text-transform: uppercase; }
        .main-topbar { height: 50px; background: #161821; border-bottom: .5px solid #2a2d3a; display: flex; align-items: center; padding: 0 16px; gap: 10px; }
    </style>
    @stack('styles')
</head>
<body>

<div style="display:flex;height:100vh;overflow:hidden">
    {{-- Sidebar --}}
    <div class="app-sidebar" style="width:210px;background:#161821;border-right:.5px solid #2a2d3a;display:flex;flex-direction:column;flex-shrink:0;overflow-y:auto">
        {{-- Logo --}}
        <div style="padding:14px 14px 12px;border-bottom:.5px solid #2a2d3a">
            <div style="font-size:15px;font-weight:500;color:#e2e8f0">
                <i class="ti ti-shopping-cart" style="color:#818cf8;margin-right:6px"></i>FreshMart
            </div>
            <div style="font-size:11px;color:#64748b;margin-top:2px">POS System</div>
        </div>

        {{-- Quick POS button --}}
        <div style="padding:10px 12px;border-bottom:.5px solid #2a2d3a">
            <a href="{{ route('pos') }}" style="display:flex;align-items:center;justify-content:center;gap:6px;background:#312e81;color:#a5b4fc;border-radius:7px;padding:8px;font-size:13px;font-weight:500;text-decoration:none;border:.5px solid #534AB7">
                <i class="ti ti-scan"></i> Open POS
            </a>
        </div>

        {{-- Navigation --}}
        <nav style="flex:1">
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="ti ti-layout-dashboard"></i>Dashboard
            </a>

            <div class="sidebar-section">Sales</div>
            <a href="{{ route('sales.index') }}" class="sidebar-link {{ request()->routeIs('sales*') ? 'active' : '' }}">
                <i class="ti ti-receipt"></i>Sales
            </a>
            <a href="{{ route('quotations.index') }}" class="sidebar-link {{ request()->routeIs('quotations*') ? 'active' : '' }}">
                <i class="ti ti-file-description"></i>Quotations
            </a>
            <a href="{{ route('payments.in') }}" class="sidebar-link {{ request()->routeIs('payments.in*') ? 'active' : '' }}">
                <i class="ti ti-cash"></i>Payment In
            </a>
            <a href="{{ route('sale-returns.index') }}" class="sidebar-link {{ request()->routeIs('sale-returns*') ? 'active' : '' }}">
                <i class="ti ti-arrow-back-up"></i>Sales Returns
            </a>

            <div class="sidebar-section">Purchases</div>
            <a href="{{ route('purchases.index') }}" class="sidebar-link {{ request()->routeIs('purchases*') ? 'active' : '' }}">
                <i class="ti ti-truck"></i>Purchases
            </a>
            <a href="{{ route('payments.out') }}" class="sidebar-link {{ request()->routeIs('payments.out*') ? 'active' : '' }}">
                <i class="ti ti-cash"></i>Payment Out
            </a>
            <a href="{{ route('purchase-returns.index') }}" class="sidebar-link {{ request()->routeIs('purchase-returns*') ? 'active' : '' }}">
                <i class="ti ti-arrow-back-up"></i>Purchase Returns
            </a>

            <div class="sidebar-section">Products</div>
            <a href="{{ route('products.index') }}" class="sidebar-link {{ request()->routeIs('products*') ? 'active' : '' }}">
                <i class="ti ti-package"></i>Products
            </a>
            <a href="{{ route('brands.index') }}" class="sidebar-link">
                <i class="ti ti-tag"></i>Brands
            </a>
            <a href="{{ route('categories.index') }}" class="sidebar-link">
                <i class="ti ti-category"></i>Categories
            </a>

            <div class="sidebar-section">Inventory</div>
            <a href="{{ route('stock.index') }}" class="sidebar-link {{ request()->routeIs('stock.index') ? 'active' : '' }}">
                <i class="ti ti-box"></i>Stock
            </a>
            <a href="{{ route('stock.transfers') }}" class="sidebar-link {{ request()->routeIs('stock.transfers') ? 'active' : '' }}">
                <i class="ti ti-arrows-exchange"></i>Stock Transfer
            </a>
            <a href="{{ route('stock.adjustments') }}" class="sidebar-link {{ request()->routeIs('stock.adjustments') ? 'active' : '' }}">
                <i class="ti ti-adjustments"></i>Adjustments
            </a>

            <div class="sidebar-section">Parties</div>
            <a href="{{ route('customers.index') }}" class="sidebar-link {{ request()->routeIs('customers*') ? 'active' : '' }}">
                <i class="ti ti-users"></i>Customers
            </a>
            <a href="{{ route('suppliers.index') }}" class="sidebar-link {{ request()->routeIs('suppliers*') ? 'active' : '' }}">
                <i class="ti ti-building-store"></i>Suppliers
            </a>

            <div class="sidebar-section">Finance</div>
            <a href="{{ route('accounts.index') }}" class="sidebar-link {{ request()->routeIs('accounts*') ? 'active' : '' }}">
                <i class="ti ti-building-bank"></i>Cash & Bank
            </a>
            <a href="{{ route('expenses.index') }}" class="sidebar-link {{ request()->routeIs('expenses*') ? 'active' : '' }}">
                <i class="ti ti-credit-card"></i>Expenses
            </a>

            <div class="sidebar-section">Reports</div>
            <a href="{{ route('reports.profit_loss') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="ti ti-chart-bar"></i>Reports
            </a>

            <div class="sidebar-section">HRM</div>
            <a href="{{ route('hrm.dashboard') }}" class="sidebar-link {{ request()->routeIs('hrm.*') ? 'active' : '' }}">
                <i class="ti ti-id-badge"></i>HRM
            </a>
            <a href="{{ route('hrm.staff.index') }}" class="sidebar-link">
                <i class="ti ti-users"></i>Staff Members
            </a>

            <div class="sidebar-section">Online</div>
            <a href="{{ route('online-orders.index') }}" class="sidebar-link {{ request()->routeIs('online-orders*') ? 'active' : '' }}">
                <i class="ti ti-shopping-cart"></i>Online Orders
            </a>
            <a href="{{ route('website.index') }}" class="sidebar-link {{ request()->routeIs('website*') ? 'active' : '' }}">
                <i class="ti ti-world"></i>Website Setup
            </a>

            <div class="sidebar-section">System</div>
            <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings*') ? 'active' : '' }}">
                <i class="ti ti-settings"></i>Settings
            </a>
        </nav>

        {{-- User info --}}
        <div style="padding:10px 12px;border-top:.5px solid #2a2d3a">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:28px;height:28px;background:#1e3a5f;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;color:#60a5fa;font-weight:500">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div>
                    <div style="font-size:12px;font-weight:500;color:#e2e8f0">{{ auth()->user()->name }}</div>
                    <div style="font-size:10px;color:#64748b">{{ auth()->user()->getRoleNames()->first() ?? 'Staff' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:#64748b;cursor:pointer;font-size:14px" title="Logout">
                        <i class="ti ti-logout"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Main content --}}
    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
        {{-- Top bar --}}
        <div class="main-topbar">
            <div style="font-size:14px;font-weight:500;color:#e2e8f0;flex:1">@yield('page-title', 'Dashboard')</div>
            {{-- Cashier --}}
            <div style="font-size:11px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;padding:4px 10px;color:#94a3b8">
                <i class="ti ti-user" style="font-size:12px;margin-right:3px"></i>
                {{ auth()->user()->name }}
            </div>
            {{-- Branch indicator --}}
            <div style="font-size:11px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;padding:4px 10px;color:#94a3b8">
                <i class="ti ti-map-pin" style="font-size:12px;margin-right:3px"></i>
                {{ auth()->user()->branch?->name ?? 'No branch' }}
            </div>
            {{-- Date & time (live) --}}
            <div style="font-size:11px;color:#64748b;display:flex;align-items:center;gap:5px">
                <i class="ti ti-clock" style="font-size:12px"></i>
                <span id="live-datetime">{{ now()->format('D, d M Y · h:i:s A') }}</span>
            </div>
        </div>

        {{-- Page content --}}
        <div style="flex:1;overflow-y:auto">
            {{-- Flash messages --}}
            @if(session('success'))
            <div style="background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:7px;padding:10px 14px;margin:12px 16px;font-size:13px;display:flex;align-items:center;gap:8px">
                <i class="ti ti-check-circle" style="font-size:16px"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div style="background:#7f1d1d;color:#fca5a5;border:.5px solid #991b1b;border-radius:7px;padding:10px 14px;margin:12px 16px;font-size:13px;display:flex;align-items:center;gap:8px">
                <i class="ti ti-alert-circle" style="font-size:16px"></i>
                {{ session('error') }}
            </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

<script>
(function () {
    const el = document.getElementById('live-datetime');
    if (!el) return;
    function tick() {
        const d = new Date();
        const date = d.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
        const time = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        el.textContent = date + ' · ' + time;
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

@stack('scripts')
</body>
</html>
