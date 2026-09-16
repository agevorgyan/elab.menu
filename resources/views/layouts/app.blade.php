<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'QRMenu SaaS Admin Platform')</title>

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js & FontAwesome Icons -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-body: #0b0f19;
            --bg-card: #151c2c;
            --bg-sidebar: #0f172a;
            --border-color: rgba(255, 255, 255, 0.08);
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-brand h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-menu {
            padding: 1rem 0;
            flex: 1;
            overflow-y: auto;
        }

        .menu-category {
            padding: 0.75rem 1.5rem 0.35rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            color: var(--text-main);
            background: rgba(245, 158, 11, 0.08);
            border-left-color: var(--primary);
        }

        .nav-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        /* Main Container */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Top Header Navbar */
        .header-navbar {
            height: 70px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .location-switcher select {
            background: var(--bg-card);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            outline: none;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .badge-role {
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-superadmin { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
        .badge-owner { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
        .badge-manager { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); }

        .content-body {
            padding: 2rem;
            flex: 1;
        }

        /* Common Cards & Buttons */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            margin-bottom: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
        }
        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.15);
        }

        .btn-success {
            background: #10b981;
            color: #fff;
        }

        .btn-danger {
            background: #ef4444;
            color: #fff;
        }

        /* Alert Notifications */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
        }

        /* Grid Utilities */
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; }
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        @media (max-width: 992px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-qrcode text-amber-500 text-2xl"></i>
            <div>
                <h1>QR Menu SaaS</h1>
                <small style="color: var(--text-muted); font-size: 0.7rem;">Enterprise Suite</small>
            </div>
        </div>

        <nav class="sidebar-menu">
            @if(Auth::user()?->isSuperAdmin())
                <div class="menu-category">Super Admin</div>
                <a href="{{ route('superadmin.dashboard') }}" class="nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
                <a href="{{ route('superadmin.vendors.index') }}" class="nav-item {{ request()->routeIs('superadmin.vendors.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-store"></i> Vendor Directory
                </a>
            @else
                <div class="menu-category">Vendor Operations</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
                <a href="{{ route('admin.orders.index') }}" class="nav-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-bell-concierge"></i> Live Kitchen Orders
                </a>

                <div class="menu-category">Menu & Content</div>
                <a href="{{ route('admin.menu.index') }}" class="nav-item {{ request()->routeIs('admin.menu.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-utensils"></i> Menu Builder
                </a>
                <a href="{{ route('admin.ai.import') }}" class="nav-item {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i> AI Menu Import & Translate
                </a>

                <div class="menu-category">Storefront & Marketing</div>
                <a href="{{ route('admin.branding.index') }}" class="nav-item {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-palette"></i> Theme Customizer
                </a>
                <a href="{{ route('admin.qr.index') }}" class="nav-item {{ request()->routeIs('admin.qr.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-qrcode"></i> Table QR Studio
                </a>
                <a href="{{ route('admin.analytics.index') }}" class="nav-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i> Analytics & Traffic
                </a>

                <div class="menu-category">Settings & Team</div>
                <a href="{{ route('admin.locations.index') }}" class="nav-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-location-dot"></i> Multi-Locations
                </a>
                <a href="{{ route('admin.team.index') }}" class="nav-item {{ request()->routeIs('admin.team.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i> Team & Staff
                </a>

                @if(Auth::user()?->vendor)
                    <div style="padding: 1rem 1.5rem; margin-top: 1rem;">
                        <a href="{{ route('client.menu', ['vendor_slug' => Auth::user()->vendor->slug]) }}" target="_blank" class="btn btn-secondary" style="width: 100%; justify-content: center; font-size: 0.8rem;">
                            <i class="fa-solid fa-external-link"></i> Live Storefront
                        </a>
                    </div>
                @endif
            @endif
        </nav>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <header class="header-navbar">
            <div class="location-switcher">
                @if(Auth::user()?->vendor && Auth::user()->vendor->locations->count() > 0)
                    <form action="{{ route('admin.dashboard') }}" method="GET" id="locationSwitchForm">
                        <label style="font-size: 0.8rem; color: var(--text-muted); margin-right: 0.5rem;"><i class="fa-solid fa-location-arrow"></i> Active Location:</label>
                        <select name="location_id" onchange="document.getElementById('locationSwitchForm').submit();">
                            @foreach(Auth::user()->vendor->locations as $loc)
                                <option value="{{ $loc->id }}" {{ session('active_location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }} ({{ $loc->table_count }} tables)
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            <div class="user-profile">
                <span class="badge-role {{ Auth::user()->isSuperAdmin() ? 'badge-superadmin' : (Auth::user()->isVendorOwner() ? 'badge-owner' : 'badge-manager') }}">
                    {{ str_replace('_', ' ', Auth::user()->role) }}
                </span>
                <span style="font-size: 0.875rem; font-weight: 600;">{{ Auth::user()->name }}</span>

                <form action="{{ route('logout') }}" method="POST" style="margin-left: 0.5rem;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 0.75rem;" title="Logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </header>

        <main class="content-body">
            @if(session('success'))
                <div class="alert alert-success">
                    <span><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>
</html>
