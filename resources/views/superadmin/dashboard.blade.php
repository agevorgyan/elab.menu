@extends('layouts.app')

@section('title', 'Super Admin Dashboard - QRMenu SaaS')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">Super Admin Overview</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Global metrics & multi-tenant platform status.</p>
    </div>
    <a href="{{ route('superadmin.vendors.index') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add New Vendor
    </a>
</div>

<!-- Stat Tiles -->
<div class="grid-4">
    <div class="card" style="border-left: 4px solid #f59e0b;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">TOTAL VENDORS</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ $totalVendors }}</div>
        <div style="font-size: 0.75rem; color: #10b981; margin-top: 0.25rem;"><i class="fa-solid fa-circle-check"></i> Active Multi-tenants</div>
    </div>

    <div class="card" style="border-left: 4px solid #3b82f6;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">ACTIVE LOCATIONS</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ $totalLocations }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Multi-location accounts</div>
    </div>

    <div class="card" style="border-left: 4px solid #10b981;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">PLATFORM ORDERS</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ number_format($totalOrders) }}</div>
        <div style="font-size: 0.75rem; color: #34d399; margin-top: 0.25rem;">Zero commission orders</div>
    </div>

    <div class="card" style="border-left: 4px solid #ec4899;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">TOTAL PLATFORM REVENUE</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ number_format($totalRevenue) }} AMD</div>
        <div style="font-size: 0.75rem; color: #f472b6; margin-top: 0.25rem;">Processed order value</div>
    </div>
</div>

<div class="grid-2" style="margin-top: 1.5rem;">
    <!-- Recent Vendors -->
    <div class="card">
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem; font-family: 'Outfit';">Recent Vendors</h3>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                    <th style="padding: 0.75rem 0;">Vendor</th>
                    <th style="padding: 0.75rem 0;">Type</th>
                    <th style="padding: 0.75rem 0;">Locations</th>
                    <th style="padding: 0.75rem 0;">Plan</th>
                    <th style="padding: 0.75rem 0;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentVendors as $v)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 0.75rem 0; font-weight: 600;">
                            <a href="{{ route('client.menu', ['vendor_slug' => $v->slug]) }}" target="_blank" style="color: #f59e0b; text-decoration: none;">
                                {{ $v->name }} <i class="fa-solid fa-external-link" style="font-size: 0.75rem;"></i>
                            </a>
                        </td>
                        <td style="padding: 0.75rem 0;"><span style="text-transform: capitalize;">{{ $v->type }}</span></td>
                        <td style="padding: 0.75rem 0;">{{ $v->locations_count }} locs</td>
                        <td style="padding: 0.75rem 0;"><span style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">{{ $v->subscription_plan }}</span></td>
                        <td style="padding: 0.75rem 0;">
                            @if($v->is_active)
                                <span style="color: #34d399; font-weight: 600;"><i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Active</span>
                            @else
                                <span style="color: #f87171; font-weight: 600;"><i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Suspended</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Active Themes Catalog -->
    <div class="card">
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem; font-family: 'Outfit';">Storefront Themes Catalog</h3>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($templates as $tmpl)
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--border-color);">
                    <img src="{{ $tmpl->preview_image }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;" alt="theme">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; font-size: 0.9rem;">{{ $tmpl->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Used by {{ $tmpl->vendors_count }} active vendors</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
