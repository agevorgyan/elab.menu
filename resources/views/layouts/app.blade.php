<!DOCTYPE html>
<html lang="en" data-theme="dark">
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

    <!-- Prevent FOUC Theme Script -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('qrmenu_theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <style>
        /* Theme Variables - Dark Mode (Default) */
        html[data-theme="dark"] {
            --bg-body: #0b0f19;
            --bg-card: #151c2c;
            --bg-card-hover: #1e293b;
            --bg-sidebar: #0f172a;
            --bg-header: rgba(15, 23, 42, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --nav-hover: rgba(245, 158, 11, 0.08);
            --nav-active: rgba(245, 158, 11, 0.15);
            --nav-active-border: #f59e0b;
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --table-row-border: rgba(255, 255, 255, 0.04);
            --input-bg: rgba(15, 23, 42, 0.8);
            --badge-bg: rgba(245, 158, 11, 0.15);
            --badge-text: #fbbf24;
            --shadow-card: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        /* Theme Variables - Light Mode (Minimalist White & Slate) */
        html[data-theme="light"] {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #f1f5f9;
            --bg-sidebar: #ffffff;
            --bg-header: rgba(255, 255, 255, 0.9);
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --nav-hover: #f1f5f9;
            --nav-active: #e2e8f0;
            --nav-active-border: #2563eb;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --table-row-border: #f1f5f9;
            --input-bg: #ffffff;
            --badge-bg: #dbeafe;
            --badge-text: #1d4ed8;
            --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
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
            z-index: 50;
            transition: transform 0.3s ease, background-color 0.25s ease;
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
            background: linear-gradient(135deg, var(--primary) 0%, #ef4444 100%);
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
            padding: 0.7rem 1.25rem;
            margin: 0.2rem 0.75rem;
            border-radius: 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-item:hover {
            color: var(--text-main);
            background: var(--nav-hover);
        }

        .nav-item.active {
            color: var(--text-main);
            background: var(--nav-active);
            font-weight: 700;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        /* Sidebar Help Banner Widget */
        .sidebar-widget {
            margin: 1rem 1rem 1.5rem;
            padding: 1rem;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            text-align: center;
        }

        .sidebar-widget p {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        /* Main Container */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.3s ease;
        }

        /* Top Header Navbar */
        .header-navbar {
            height: 70px;
            background: var(--bg-header);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-main);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            margin-right: 0.5rem;
        }

        .location-switcher select {
            background: var(--bg-card);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 10px;
            outline: none;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Minimal Theme Toggle Switcher */
        .theme-toggle-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 9999px;
            padding: 0.35rem 0.75rem;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-main);
            box-shadow: var(--shadow-card);
        }

        .theme-toggle-btn i {
            font-size: 0.95rem;
            color: var(--primary);
        }

        .badge-role {
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-superadmin { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-owner { background: var(--badge-bg); color: var(--badge-text); border: 1px solid var(--border-color); }
        .badge-manager { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }

        .content-body {
            padding: 2rem;
            flex: 1;
        }

        /* Minimalist Modern Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-card);
            margin-bottom: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }
        html[data-theme="dark"] .btn-primary {
            color: #000000;
        }
        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: var(--bg-body);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover {
            background: var(--bg-card-hover);
        }

        .btn-success { background: #10b981; color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; }

        /* Alert Notifications */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        /* Cards & Glass Containers */
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-card);
        }

        /* Form Controls & Inputs */
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            background: var(--input-bg);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 0.65rem 0.9rem;
            border-radius: 10px;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }

        .form-select option {
            background: var(--bg-card);
            color: var(--text-main);
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .data-table th {
            text-align: left;
            padding: 0.85rem 1rem;
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--table-row-border);
            vertical-align: middle;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar-overlay.mobile-open { display: block; }
            .main-wrapper { margin-left: 0; }
            .mobile-toggle { display: block; }
            .grid-2 { grid-template-columns: 1fr; }
            .header-navbar { padding: 0 1rem; }
            .content-body { padding: 1.25rem; }
        }
    </style>
    @yield('styles')
</head>
<body x-data="themeApp()">

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" :class="{ 'mobile-open': mobileOpen }" @click="mobileOpen = false"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" :class="{ 'mobile-open': mobileOpen }">
        <div class="sidebar-brand">
            <i class="fa-solid fa-qrcode text-2xl" style="color: var(--primary);"></i>
            <div>
                <h1>QR Menu SaaS</h1>
                <small style="color: var(--text-muted); font-size: 0.7rem;">Minimalist Platform</small>
            </div>
        </div>

        <nav class="sidebar-menu">
            @if(Auth::user()?->isSuperAdmin())
                <div class="menu-category">{{ __('Super Admin') }}</div>
                <a href="{{ route('superadmin.dashboard') }}" class="nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i> {{ __('Dashboard') }}
                </a>
                <a href="{{ route('superadmin.vendors.index') }}" class="nav-item {{ request()->routeIs('superadmin.vendors.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-store"></i> {{ __('Vendor Directory') }}
                </a>
                <a href="{{ route('superadmin.plans.index') }}" class="nav-item {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-box-archive"></i> {{ __('Plans') }}
                </a>
                <a href="{{ route('superadmin.subscriptions.index') }}" class="nav-item {{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-credit-card"></i> {{ __('Vendor Subscriptions') }}
                </a>
            @else
                @php $v = Auth::user()?->vendor; @endphp
                <div class="menu-category">{{ __('Vendor Operations') }}</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i> {{ __('Dashboard') }}
                </a>
                <a href="{{ route('admin.orders.index') }}" class="nav-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" style="justify-content: space-between;">
                    <span><i class="fa-solid fa-bell-concierge"></i> {{ __('Live Kitchen Orders') }}</span>
                    @if($v && !$v->hasFeature('orders'))
                        <span style="font-size: 0.65rem; background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 0.15rem 0.4rem; border-radius: 6px; font-weight: 700;">🔒 PRO</span>
                    @endif
                </a>

                <div class="menu-category">{{ __('Menu & Content') }}</div>
                <a href="{{ route('admin.menu.index') }}" class="nav-item {{ request()->routeIs('admin.menu.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-utensils"></i> {{ __('Menu Builder') }}
                </a>
                <a href="{{ route('admin.ai.import') }}" class="nav-item {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--primary);"></i> {{ __('AI Menu & Translate') }}
                </a>

                <div class="menu-category">{{ __('Storefront & Marketing') }}</div>
                <a href="{{ route('admin.branding.index') }}" class="nav-item {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-palette"></i> {{ __('Theme Customizer') }}
                </a>
                <a href="{{ route('admin.qr.index') }}" class="nav-item {{ request()->routeIs('admin.qr.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-qrcode"></i> {{ __('Table QR Studio') }}
                </a>
                <a href="{{ route('admin.customers.index') }}" class="nav-item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" style="justify-content: space-between;">
                    <span><i class="fa-solid fa-users-gear" style="color: var(--primary);"></i> {{ __('Customers & CRM') }}</span>
                    @if($v && !$v->hasFeature('customers'))
                        <span style="font-size: 0.65rem; background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 0.15rem 0.4rem; border-radius: 6px; font-weight: 700;">🔒 PRO</span>
                    @endif
                </a>
                <a href="{{ route('admin.analytics.index') }}" class="nav-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i> {{ __('Analytics & Traffic') }}
                </a>

                <div class="menu-category">{{ __('Settings & Subscription') }}</div>
                <a href="{{ route('admin.subscription') }}" class="nav-item {{ request()->routeIs('admin.subscription') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary);"></i> {{ __('Subscription') }}
                </a>
                <a href="{{ route('admin.locations.index') }}" class="nav-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}" style="justify-content: space-between;">
                    <span><i class="fa-solid fa-location-dot"></i> {{ __('Multi-Locations') }}</span>
                    @if($v && !$v->hasFeature('locations'))
                        <span style="font-size: 0.65rem; background: rgba(6, 182, 212, 0.2); color: #06b6d4; padding: 0.15rem 0.4rem; border-radius: 6px; font-weight: 700;">🔒 BIZ</span>
                    @endif
                </a>
                <a href="{{ route('admin.team.index') }}" class="nav-item {{ request()->routeIs('admin.team.*') ? 'active' : '' }}" style="justify-content: space-between;">
                    <span><i class="fa-solid fa-users"></i> {{ __('Team & Staff') }}</span>
                    @if($v && !$v->hasFeature('team'))
                        <span style="font-size: 0.65rem; background: rgba(6, 182, 212, 0.2); color: #06b6d4; padding: 0.15rem 0.4rem; border-radius: 6px; font-weight: 700;">🔒 BIZ</span>
                    @endif
                </a>

                @if(Auth::user()?->vendor)
                    <div style="padding: 0.5rem 1rem;">
                        <a href="{{ route('client.menu', ['vendor_slug' => Auth::user()->vendor->slug]) }}" target="_blank" class="btn btn-secondary" style="width: 100%; justify-content: center; font-size: 0.8rem; border-radius: 12px;">
                            <i class="fa-solid fa-external-link"></i> {{ __('Live Storefront') }}
                        </a>
                    </div>
                @endif
            @endif
        </nav>

        <!-- Sidebar Help Widget -->
        <div class="sidebar-widget">
            <p>{{ __('Need help or custom menu translation?') }}</p>
            <a href="mailto:support@qrmenu.local" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; border-radius: 8px;">
                <i class="fa-solid fa-headset"></i> {{ __('Get Support') }}
            </a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <header class="header-navbar">
            <div style="display: flex; align-items: center;">
                <button class="mobile-toggle" @click="mobileOpen = !mobileOpen">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="location-switcher">
                    @if(Auth::user()?->vendor && Auth::user()->vendor->locations->count() > 0)
                        <form action="{{ route('admin.dashboard') }}" method="GET" id="locationSwitchForm">
                            <label style="font-size: 0.8rem; color: var(--text-muted); margin-right: 0.5rem;" class="hidden sm:inline">
                                <i class="fa-solid fa-location-arrow"></i> {{ __('Location:') }}
                            </label>
                            <select name="location_id" onchange="document.getElementById('locationSwitchForm').submit();">
                                @foreach(Auth::user()->vendor->locations as $loc)
                                    <option value="{{ $loc->id }}" {{ session('active_location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->table_count }} {{ __('tables') }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
            </div>

            <div class="user-profile">
                <!-- Admin Language Switcher -->
                <div style="position: relative;" x-data="{ openLang: false }">
                    <button @click="openLang = !openLang" class="theme-toggle-btn" style="border-radius: 10px;">
                        <i class="fa-solid fa-globe" style="color: var(--primary);"></i>
                        <span>
                            @if(app()->getLocale() == 'hy') 🇦🇲 AM
                            @elseif(app()->getLocale() == 'ru') 🇷🇺 RU
                            @else 🇬🇧 EN @endif
                        </span>
                        <i class="fa-solid fa-chevron-down" style="font-size: 0.65rem; color: var(--text-muted);"></i>
                    </button>
                    <div x-show="openLang" @click.outside="openLang = false" x-transition style="position: absolute; right: 0; top: 110%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-card); min-width: 140px; z-index: 100; overflow: hidden; padding: 0.25rem 0;">
                        <a href="{{ route('lang.switch', 'hy') }}" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.85rem; color: var(--text-main); text-decoration: none; font-size: 0.82rem; font-weight: {{ app()->getLocale() == 'hy' ? '700' : '400' }}; background: {{ app()->getLocale() == 'hy' ? 'var(--nav-hover)' : 'transparent' }};">
                            🇦🇲 Հայերեն
                        </a>
                        <a href="{{ route('lang.switch', 'en') }}" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.85rem; color: var(--text-main); text-decoration: none; font-size: 0.82rem; font-weight: {{ app()->getLocale() == 'en' ? '700' : '400' }}; background: {{ app()->getLocale() == 'en' ? 'var(--nav-hover)' : 'transparent' }};">
                            🇬🇧 English
                        </a>
                        <a href="{{ route('lang.switch', 'ru') }}" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.85rem; color: var(--text-main); text-decoration: none; font-size: 0.82rem; font-weight: {{ app()->getLocale() == 'ru' ? '700' : '400' }}; background: {{ app()->getLocale() == 'ru' ? 'var(--nav-hover)' : 'transparent' }};">
                            🇷🇺 Русский
                        </a>
                    </div>
                </div>

                <!-- Light / Dark Theme Switcher Button -->
                <button class="theme-toggle-btn" @click="toggleTheme()" title="Toggle Light / Dark Mode">
                    <i class="fa-solid" :class="currentTheme === 'dark' ? 'fa-sun' : 'fa-moon'"></i>
                    <span x-text="currentTheme === 'dark' ? '{{ __('Light Mode') }}' : '{{ __('Dark Mode') }}'"></span>
                </button>

                <span class="badge-role {{ Auth::user()?->isSuperAdmin() ? 'badge-superadmin' : (Auth::user()?->isVendorOwner() ? 'badge-owner' : 'badge-manager') }}">
                    {{ str_replace('_', ' ', Auth::user()?->role ?? 'staff') }}
                </span>

                <form action="{{ route('logout') }}" method="POST" style="margin-left: 0.25rem;">
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

            @if(session('warning'))
                <div class="alert" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); color: #f59e0b; padding: 0.85rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fa-solid fa-triangle-exclamation"></i> {{ session('warning') }}</span>
                    <button onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 0.85rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</span>
                    <button onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(Auth::user()?->vendor && !Auth::user()?->isSuperAdmin() && Auth::user()->vendor->daysLeft() <= 3 && !request()->routeIs('admin.subscription'))
                <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); color: #f59e0b; padding: 0.85rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <i class="fa-solid fa-clock"></i>
                        <strong>Բաժանորդագրության հիշեցում․</strong> Ձեր փաթեթի/փորձնական շրջանին մնացել է <strong>{{ Auth::user()->vendor->daysLeft() }} օր</strong> ({{ Auth::user()->vendor->subscription_status_label }})։
                    </div>
                    <a href="{{ route('admin.subscription') }}" class="btn btn-primary" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;">
                        Մանրամասներ / Երկարաձգել
                    </a>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        function themeApp() {
            return {
                mobileOpen: false,
                currentTheme: document.documentElement.getAttribute('data-theme') || 'dark',
                toggleTheme() {
                    this.currentTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', this.currentTheme);
                    localStorage.setItem('qrmenu_theme', this.currentTheme);
                }
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
