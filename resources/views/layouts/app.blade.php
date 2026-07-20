{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FreshMart POS')</title>

    {{-- Theme tokens. Included first so the saved theme is applied before the
         browser paints anything. --}}
    @include('partials.theme')

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
                        bg: 'var(--bg)',
                        surface: 'var(--surface)',
                        border: 'var(--border)',
                        primary: 'var(--primary)',
                    }
                }
            }
        }
    </script>

    <style>
        * { box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        .sidebar-link { display: flex; align-items: center; gap: 9px; padding: 8px 14px; font-size: 13px; color: var(--text-2); cursor: pointer; border-left: 2px solid transparent; transition: all .12s; text-decoration: none; }
        .sidebar-link:hover { background: var(--surface-2); color: var(--text); }
        .sidebar-link.active { background: var(--surface-2); color: var(--primary-text); border-left-color: var(--primary); }
        .sidebar-link i { font-size: 16px; width: 20px; }
        .sidebar-section { font-size: 10px; color: var(--text-4); padding: 10px 14px 3px; letter-spacing: .7px; text-transform: uppercase; }
        .main-topbar { height: 50px; background: var(--surface); border-bottom: .5px solid var(--border); display: flex; align-items: center; padding: 0 16px; gap: 10px; }
    </style>
    @stack('styles')
</head>
<body>

<div style="display:flex;height:100vh;overflow:hidden">
    {{-- Sidebar --}}
    <div class="app-sidebar" style="width:210px;background:var(--surface);border-right:.5px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;overflow-y:auto">
        {{-- Logo --}}
        <div style="padding:14px 14px 12px;border-bottom:.5px solid var(--border)">
            <div style="font-size:15px;font-weight:500;color:var(--text)">
                <i class="ti ti-shopping-cart" style="color:var(--primary);margin-right:6px"></i>FreshMart
            </div>
            <div style="font-size:11px;color:var(--text-3);margin-top:2px">POS System</div>
        </div>

        {{-- Quick POS button --}}
        @can('pos.access')
        <div style="padding:10px 12px;border-bottom:.5px solid var(--border)">
            <a href="{{ route('pos') }}" style="display:flex;align-items:center;justify-content:center;gap:6px;background:var(--primary-soft);color:var(--primary-text);border-radius:7px;padding:8px;font-size:13px;font-weight:500;text-decoration:none;border:.5px solid var(--primary-border)">
                <i class="ti ti-scan"></i> Open POS
            </a>
        </div>
        @endcan

        {{-- Navigation. Every link is gated on the same permission as its route, and
             each section heading only renders if the user can reach something in it —
             so nobody is shown a link that would 403. --}}
        <nav style="flex:1">
            @can('dashboard.view')
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="ti ti-layout-dashboard"></i>Dashboard
            </a>
            @endcan

            @canany(['sales.view','quotations.view','payments.in.view','sale_returns.view'])
            <div class="sidebar-section">Sales</div>
            @can('sales.view')
            <a href="{{ route('sales.index') }}" class="sidebar-link {{ request()->routeIs('sales*') ? 'active' : '' }}">
                <i class="ti ti-receipt"></i>Sales
            </a>
            @endcan
            @can('quotations.view')
            <a href="{{ route('quotations.index') }}" class="sidebar-link {{ request()->routeIs('quotations*') ? 'active' : '' }}">
                <i class="ti ti-file-description"></i>Quotations
            </a>
            @endcan
            @can('payments.in.view')
            <a href="{{ route('payments.in') }}" class="sidebar-link {{ request()->routeIs('payments.in*') ? 'active' : '' }}">
                <i class="ti ti-cash"></i>Payment In
            </a>
            @endcan
            @can('sale_returns.view')
            <a href="{{ route('sale-returns.index') }}" class="sidebar-link {{ request()->routeIs('sale-returns*') ? 'active' : '' }}">
                <i class="ti ti-arrow-back-up"></i>Sales Returns
            </a>
            @endcan
            @endcanany

            @canany(['purchases.view','payments.out.view','purchase_returns.view'])
            <div class="sidebar-section">Purchases</div>
            @can('purchases.view')
            <a href="{{ route('purchases.index') }}" class="sidebar-link {{ request()->routeIs('purchases*') ? 'active' : '' }}">
                <i class="ti ti-truck"></i>Purchases
            </a>
            @endcan
            @can('payments.out.view')
            <a href="{{ route('payments.out') }}" class="sidebar-link {{ request()->routeIs('payments.out*') ? 'active' : '' }}">
                <i class="ti ti-cash"></i>Payment Out
            </a>
            @endcan
            @can('purchase_returns.view')
            <a href="{{ route('purchase-returns.index') }}" class="sidebar-link {{ request()->routeIs('purchase-returns*') ? 'active' : '' }}">
                <i class="ti ti-arrow-back-up"></i>Purchase Returns
            </a>
            @endcan
            @endcanany

            @canany(['products.view','catalog.brands.manage','catalog.categories.manage','barcodes.print'])
            <div class="sidebar-section">Products</div>
            @can('products.view')
            <a href="{{ route('products.index') }}" class="sidebar-link {{ request()->routeIs('products*') ? 'active' : '' }}">
                <i class="ti ti-package"></i>Products
            </a>
            @endcan
            @can('catalog.brands.manage')
            <a href="{{ route('brands.index') }}" class="sidebar-link">
                <i class="ti ti-tag"></i>Brands
            </a>
            @endcan
            @can('catalog.categories.manage')
            <a href="{{ route('categories.index') }}" class="sidebar-link">
                <i class="ti ti-category"></i>Categories
            </a>
            @endcan
            @can('barcodes.print')
            <a href="{{ route('barcodes.labels') }}" class="sidebar-link {{ request()->routeIs('barcodes.labels') ? 'active' : '' }}">
                <i class="ti ti-barcode"></i>Barcode labels
            </a>
            @endcan
            @endcanany

            @canany(['stock.view','stock.transfer','stock.adjust'])
            <div class="sidebar-section">Inventory</div>
            @can('stock.view')
            <a href="{{ route('stock.index') }}" class="sidebar-link {{ request()->routeIs('stock.index') ? 'active' : '' }}">
                <i class="ti ti-box"></i>Stock
            </a>
            @endcan
            @can('stock.transfer')
            <a href="{{ route('stock.transfers') }}" class="sidebar-link {{ request()->routeIs('stock.transfers') ? 'active' : '' }}">
                <i class="ti ti-arrows-exchange"></i>Stock Transfer
            </a>
            @endcan
            @can('stock.adjust')
            <a href="{{ route('stock.adjustments') }}" class="sidebar-link {{ request()->routeIs('stock.adjustments') ? 'active' : '' }}">
                <i class="ti ti-adjustments"></i>Adjustments
            </a>
            @endcan
            @endcanany

            @canany(['customers.view','suppliers.view'])
            <div class="sidebar-section">Parties</div>
            @can('customers.view')
            <a href="{{ route('customers.index') }}" class="sidebar-link {{ request()->routeIs('customers*') ? 'active' : '' }}">
                <i class="ti ti-users"></i>Customers
            </a>
            @endcan
            @can('suppliers.view')
            <a href="{{ route('suppliers.index') }}" class="sidebar-link {{ request()->routeIs('suppliers*') ? 'active' : '' }}">
                <i class="ti ti-building-store"></i>Suppliers
            </a>
            @endcan
            @endcanany

            @canany(['accounts.view','expenses.view','counter_sessions.view'])
            <div class="sidebar-section">Finance</div>
            @can('accounts.view')
            <a href="{{ route('accounts.index') }}" class="sidebar-link {{ request()->routeIs('accounts*') ? 'active' : '' }}">
                <i class="ti ti-building-bank"></i>Cash & Bank
            </a>
            @endcan
            @can('expenses.view')
            <a href="{{ route('expenses.index') }}" class="sidebar-link {{ request()->routeIs('expenses*') ? 'active' : '' }}">
                <i class="ti ti-credit-card"></i>Expenses
            </a>
            @endcan
            @can('counter_sessions.view')
            <a href="{{ route('counter-sessions.index') }}" class="sidebar-link {{ request()->routeIs('counter-sessions*') ? 'active' : '' }}">
                <i class="ti ti-coin"></i>Counter Sessions
            </a>
            @endcan
            @endcanany

            @can('reports.view')
            <div class="sidebar-section">Reports</div>
            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="ti ti-chart-histogram"></i>Reports
            </a>
            @endcan

            @canany(['hrm.view','hrm.self.view'])
            <div class="sidebar-section">HRM</div>
            @can('hrm.view')
            <a href="{{ route('hrm.dashboard') }}" class="sidebar-link {{ request()->routeIs('hrm.*') ? 'active' : '' }}">
                <i class="ti ti-id-badge"></i>HRM
            </a>
            <a href="{{ route('hrm.staff.index') }}" class="sidebar-link">
                <i class="ti ti-users"></i>Staff Members
            </a>
            @endcan
            {{-- Self-service is separate from the management area: a cashier gets
                 this link without hrm.view, and so without any HRM access. --}}
            @can('hrm.self.view')
            <a href="{{ route('my.index') }}" class="sidebar-link {{ request()->routeIs('my.*') ? 'active' : '' }}">
                <i class="ti ti-user-circle"></i>My HR
            </a>
            @endcan
            @endcanany

            @canany(['online_orders.view','website.manage'])
            <div class="sidebar-section">Online</div>
            @can('online_orders.view')
            <a href="{{ route('online-orders.index') }}" class="sidebar-link {{ request()->routeIs('online-orders*') ? 'active' : '' }}">
                <i class="ti ti-shopping-cart"></i>Online Orders
            </a>
            @endcan
            @can('website.manage')
            <a href="{{ route('website.index') }}" class="sidebar-link {{ request()->routeIs('website*') ? 'active' : '' }}">
                <i class="ti ti-world"></i>Website Setup
            </a>
            @endcan
            @endcanany

            @canany(['settings.access','roles.view','roles.manage'])
            <div class="sidebar-section">System</div>
            @can('settings.access')
            <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings*') ? 'active' : '' }}">
                <i class="ti ti-settings"></i>Settings
            </a>
            @endcan
            @canany(['roles.view','roles.manage'])
            <a href="{{ route('roles.index') }}" class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <i class="ti ti-shield-lock"></i>Roles & Permissions
            </a>
            @endcanany
            @endcanany
        </nav>

        {{-- User info --}}
        <div style="padding:10px 12px;border-top:.5px solid var(--border)">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:28px;height:28px;background:var(--info-soft);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--info);font-weight:500">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div>
                    <div style="font-size:12px;font-weight:500;color:var(--text)">{{ auth()->user()->name }}</div>
                    <div style="font-size:10px;color:var(--text-3)">{{ auth()->user()->getRoleNames()->first() ?? 'Staff' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:var(--text-3);cursor:pointer;font-size:14px" title="Logout">
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
            <div style="font-size:14px;font-weight:500;color:var(--text);flex:1">@yield('page-title', 'Dashboard')</div>
            {{-- Cashier --}}
            <div style="font-size:11px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;padding:4px 10px;color:var(--text-2)">
                <i class="ti ti-user" style="font-size:12px;margin-right:3px"></i>
                {{ auth()->user()->name }}
            </div>
            {{-- Branch indicator / switcher --}}
            @if(auth()->user()->seesAllBranches())
            @php($branchOptions = \App\Support\CurrentBranch::options())
            <div x-data="{ open:false }" style="position:relative">
                <button type="button" @click="open = !open" @click.outside="open = false"
                    style="font-size:11px;background:{{ \App\Support\CurrentBranch::isAll() ? 'var(--warning-soft-2)' : 'var(--surface-2)' }};border:.5px solid {{ \App\Support\CurrentBranch::isAll() ? 'var(--warning-border)' : 'var(--border)' }};border-radius:6px;padding:4px 10px;color:{{ \App\Support\CurrentBranch::isAll() ? 'var(--warning-2)' : 'var(--text-2)' }};cursor:pointer;display:flex;align-items:center;gap:4px">
                    <i class="ti ti-map-pin" style="font-size:12px"></i>
                    {{ \App\Support\CurrentBranch::name() }}
                    <i class="ti ti-chevron-down" style="font-size:11px"></i>
                </button>
                <div x-show="open" x-cloak x-transition.opacity
                    style="position:absolute;top:calc(100% + 6px);right:0;min-width:190px;background:var(--surface-2);border:.5px solid var(--border);border-radius:8px;padding:5px;z-index:60;box-shadow:0 8px 24px var(--shadow)">
                    <form method="POST" action="{{ route('branch.switch') }}">
                        @csrf
                        <button type="submit" name="branch_id" value=""
                            style="width:100%;text-align:left;background:{{ \App\Support\CurrentBranch::isAll() ? 'var(--border)' : 'transparent' }};border:0;border-radius:6px;padding:7px 9px;color:var(--warning-2);font-size:12px;cursor:pointer;display:flex;align-items:center;gap:6px">
                            <i class="ti ti-building-store" style="font-size:13px"></i> All branches
                            <span style="margin-left:auto;font-size:10px;color:var(--text-3)">view only</span>
                        </button>
                        <div style="height:.5px;background:var(--border);margin:4px 2px"></div>
                        @foreach($branchOptions as $b)
                        <button type="submit" name="branch_id" value="{{ $b->id }}"
                            style="width:100%;text-align:left;background:{{ \App\Support\CurrentBranch::id() === (int) $b->id ? 'var(--border)' : 'transparent' }};border:0;border-radius:6px;padding:7px 9px;color:var(--text);font-size:12px;cursor:pointer;display:flex;align-items:center;gap:6px">
                            <i class="ti ti-map-pin" style="font-size:13px;color:var(--text-3)"></i> {{ $b->name }}
                        </button>
                        @endforeach
                    </form>
                </div>
            </div>
            @else
            <div style="font-size:11px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;padding:4px 10px;color:var(--text-2)">
                <i class="ti ti-map-pin" style="font-size:12px;margin-right:3px"></i>
                {{ auth()->user()->branch?->name ?? 'No branch' }}
            </div>
            @endif
            {{-- Date & time (live) --}}
            <div style="font-size:11px;color:var(--text-3);display:flex;align-items:center;gap:5px">
                <i class="ti ti-clock" style="font-size:12px"></i>
                <span id="live-datetime">{{ now()->format('D, d M Y · h:i:s A') }}</span>
            </div>
            {{-- Light / dark --}}
            <button type="button" class="theme-toggle" onclick="toggleTheme()" title="Switch theme">
                <i class="ti ti-moon"></i><i class="ti ti-sun"></i>
            </button>
        </div>

        {{-- Page content --}}
        <div style="flex:1;overflow-y:auto">
            {{-- Flash messages --}}
            @if(session('success'))
            <div style="background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:7px;padding:10px 14px;margin:12px 16px;font-size:13px;display:flex;align-items:center;gap:8px">
                <i class="ti ti-check-circle" style="font-size:16px"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div style="background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:7px;padding:10px 14px;margin:12px 16px;font-size:13px;display:flex;align-items:center;gap:8px">
                <i class="ti ti-alert-circle" style="font-size:16px"></i>
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div style="background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:7px;padding:10px 14px;margin:12px 16px;font-size:13px">
                <div style="display:flex;align-items:center;gap:8px;font-weight:500;margin-bottom:4px">
                    <i class="ti ti-alert-triangle" style="font-size:16px"></i> Please fix the following:
                </div>
                <ul style="margin:4px 0 0;padding-left:26px">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
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
