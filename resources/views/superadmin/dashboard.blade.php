@extends('layouts.app')

@section('title', 'SuperAdmin HQ Overview - QRMenu SaaS')

@section('styles')
<style>
    .superadmin-hero {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(236, 72, 153, 0.08) 100%);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 1.75rem 2rem;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.25rem;
        box-shadow: var(--shadow-card);
    }

    .plan-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .plan-basic { background: rgba(100, 116, 139, 0.15); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.3); }
    .plan-pro { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
    .plan-business { background: rgba(6, 182, 212, 0.15); color: #06b6d4; border: 1px solid rgba(6, 182, 212, 0.3); }
    .plan-custom { background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3); }

    .theme-preview-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.85rem 1rem;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        transition: all 0.2s ease;
    }
    .theme-preview-card:hover {
        border-color: rgba(99, 102, 241, 0.35);
        transform: translateX(4px);
    }

    .vendor-table-row {
        transition: background 0.15s ease;
    }
    .vendor-table-row:hover {
        background: var(--nav-hover);
    }
</style>
@endsection

@section('content')
<!-- Hero Section -->
<div class="superadmin-hero">
    <div>
        <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.4rem;">
            <span style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: #fff; padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                <i class="fa-solid fa-bolt"></i> HQ Command Center
            </span>
            <span style="font-size: 0.75rem; color: #10b981; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
                Բոլոր համակարգերն ակտիվ են
            </span>
        </div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.85rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem;">
            Պլատֆորմի Գլխավոր Ակնարկ (SuperAdmin)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 600px;">
            Վենդորների, մասնաճյուղերի, պատվերների շրջանառության և բաժանորդագրությունների իրական ժամանակի կառավարում։
        </p>
    </div>

    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
        <a href="{{ route('superadmin.subscriptions.index') }}" class="btn btn-secondary" style="border-radius: 12px; font-size: 0.85rem; padding: 0.65rem 1.15rem;">
            <i class="fa-solid fa-credit-card" style="color: #6366f1;"></i> Բաժանորդագրություններ
        </a>
        <a href="{{ route('superadmin.vendors.index') }}" class="btn btn-primary" style="border-radius: 12px; font-size: 0.85rem; padding: 0.65rem 1.25rem;">
            <i class="fa-solid fa-plus"></i> Ստեղծել Նոր Վենդոր
        </a>
    </div>
</div>

<!-- 4 Modern Stat KPI Cards -->
<div class="grid-4" style="gap: 1.25rem; margin-bottom: 1.75rem;">
    <!-- Total Vendors -->
    <div class="stat-kpi-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="color: var(--text-muted); font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    Ընդհանուր Վենդորներ
                </div>
                <div style="font-size: 2.25rem; font-weight: 800; font-family: 'Outfit'; color: var(--text-main); margin: 0.4rem 0 0.2rem;">
                    {{ $totalVendors }}
                </div>
            </div>
            <div class="kpi-icon-badge" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);">
                <i class="fa-solid fa-store"></i>
            </div>
        </div>
        <div style="font-size: 0.78rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
            <i class="fa-solid fa-circle-check"></i>
            <span>Ակտիվ multi-tenant բիզնեսներ</span>
        </div>
    </div>

    <!-- Active Locations -->
    <div class="stat-kpi-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="color: var(--text-muted); font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    Ակտիվ Մասնաճյուղեր
                </div>
                <div style="font-size: 2.25rem; font-weight: 800; font-family: 'Outfit'; color: var(--text-main); margin: 0.4rem 0 0.2rem;">
                    {{ $totalLocations }}
                </div>
            </div>
            <div class="kpi-icon-badge" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.2) 0%, rgba(6, 182, 212, 0.05) 100%); color: #06b6d4; border: 1px solid rgba(6, 182, 212, 0.3);">
                <i class="fa-solid fa-location-dot"></i>
            </div>
        </div>
        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
            <i class="fa-solid fa-diagram-project"></i>
            <span>Միջինում {{ $totalVendors > 0 ? round($totalLocations / $totalVendors, 1) : 1 }} մասնաճյուղ / վենդոր</span>
        </div>
    </div>

    <!-- Platform Orders -->
    <div class="stat-kpi-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="color: var(--text-muted); font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    Գրանցված Պատվերներ
                </div>
                <div style="font-size: 2.25rem; font-weight: 800; font-family: 'Outfit'; color: var(--text-main); margin: 0.4rem 0 0.2rem;">
                    {{ number_format($totalOrders) }}
                </div>
            </div>
            <div class="kpi-icon-badge" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.05) 100%); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                <i class="fa-solid fa-bell-concierge"></i>
            </div>
        </div>
        <div style="font-size: 0.78rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
            <i class="fa-solid fa-shield-halved"></i>
            <span>0% միջնորդավճարով պատվերներ</span>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="stat-kpi-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="color: var(--text-muted); font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    Պլատֆորմի Շրջանառություն
                </div>
                <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit'; color: var(--primary); margin: 0.4rem 0 0.2rem; white-space: nowrap;">
                    {{ number_format($totalRevenue) }} ֏
                </div>
            </div>
            <div class="kpi-icon-badge" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.05) 100%); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
        </div>
        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
            <i class="fa-solid fa-chart-line"></i>
            <span>Մշակված պատվերների արժեք</span>
        </div>
    </div>
</div>

<!-- Plan Distribution Overview Bar -->
@if(isset($plans) && $plans->count() > 0)
    <div class="glass-card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i>
                <span>Բաժանորդագրությունների Փաթեթների Բաշխվածություն</span>
            </div>
            <a href="{{ route('superadmin.plans.index') }}" style="font-size: 0.78rem; color: var(--primary); text-decoration: none; font-weight: 600;">
                Կառավարել Փաթեթները →
            </a>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
            @foreach($plans as $p)
                <div style="display: flex; align-items: center; gap: 0.6rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.45rem 0.85rem; border-radius: 10px;">
                    <span class="plan-pill plan-{{ strtolower($p->slug) }}">
                        {{ $p->name }}
                    </span>
                    <span style="font-size: 0.85rem; font-weight: 800; font-family: 'Outfit'; color: var(--text-main);">
                        {{ $p->vendors_count ?? 0 }}
                    </span>
                    <span style="font-size: 0.72rem; color: var(--text-muted);">վենդոր</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Main Two-Column Layout: Recent Vendors & Themes -->
<div class="grid-2" style="gap: 1.5rem; align-items: start;">
    <!-- Recent Vendors Modern Table -->
    <div class="glass-card" style="padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 800; font-family: 'Outfit'; color: var(--text-main); margin-bottom: 0.2rem;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: #6366f1;"></i> Վերջին Գրանցված Վենդորները
                </h3>
                <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0;">Վերջին 5 միացած ռեստորանները և սրճարանները</p>
            </div>
            <a href="{{ route('superadmin.vendors.index') }}" class="btn btn-secondary" style="font-size: 0.78rem; padding: 0.4rem 0.8rem; border-radius: 10px;">
                Բոլորը ({{ $totalVendors }}) →
            </a>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Վենդոր</th>
                        <th>Տեսակ</th>
                        <th>Մասնաճյուղ</th>
                        <th>Փաթեթ</th>
                        <th style="text-align: right;">Կարգավիճակ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentVendors as $v)
                        <tr class="vendor-table-row">
                            <td style="padding: 0.85rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(236, 72, 153, 0.2)); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; color: var(--text-main); flex-shrink: 0; overflow: hidden;">
                                        @if($v->logo)
                                            <img src="{{ $v->logo }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            {{ mb_substr($v->name, 0, 2) }}
                                        @endif
                                    </div>
                                    <div style="min-width: 0;">
                                        <a href="{{ route('client.menu', ['vendor_slug' => $v->slug]) }}" target="_blank" style="color: var(--text-main); font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.35rem;">
                                            <span>{{ $v->name }}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.68rem; color: var(--primary);"></i>
                                        </a>
                                        <div style="font-size: 0.72rem; color: var(--text-muted);">/m/{{ $v->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.85rem 1rem;">
                                <span style="font-size: 0.78rem; text-transform: capitalize; color: var(--text-main); font-weight: 600;">
                                    @if($v->type === 'restaurant') 🍽️ Ռեստորան
                                    @elseif($v->type === 'cafe') ☕ Սրճարան
                                    @else 🏨 Հյուրանոց
                                    @endif
                                </span>
                            </td>
                            <td style="padding: 0.85rem 1rem; color: var(--text-main); font-weight: 600;">
                                {{ $v->locations_count }} տեղ
                            </td>
                            <td style="padding: 0.85rem 1rem;">
                                <span class="plan-pill plan-{{ strtolower($v->subscription_plan ?? 'pro') }}">
                                    {{ $v->subscription_plan ?? 'pro' }}
                                </span>
                            </td>
                            <td style="padding: 0.85rem 1rem; text-align: right;">
                                @if($v->is_active)
                                    <span class="badge badge-emerald">
                                        <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Ակտիվ
                                    </span>
                                @else
                                    <span class="badge badge-rose">
                                        <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Կասեցված
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                Դեռևս գրանցված վենդորներ չկան։
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Themes Catalog Showcase -->
    <div class="glass-card" style="padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 800; font-family: 'Outfit'; color: var(--text-main); margin-bottom: 0.2rem;">
                    <i class="fa-solid fa-palette" style="color: var(--primary);"></i> Մենյուի Թեմաներ (Templates)
                </h3>
                <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0;">Կատալոգում հասանելի մոդեռն UI դիզայններ</p>
            </div>
            <span class="badge badge-indigo">{{ count($templates) }} թեմաներ</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
            @foreach($templates as $tmpl)
                <div class="theme-preview-card">
                    <img src="{{ $tmpl->preview_image ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=120&q=80' }}" style="width: 58px; height: 58px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border-color); flex-shrink: 0;" alt="theme">
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                            <span>{{ $tmpl->name }}</span>
                            @if($loop->first)
                                <span style="font-size: 0.65rem; background: rgba(99, 102, 241, 0.2); color: #6366f1; padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: 800;">TOP</span>
                            @endif
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                            Օգտագործվում է <strong>{{ $tmpl->vendors_count }}</strong> ակտիվ վենդորների կողմից
                        </div>
                    </div>
                    <div style="flex-shrink: 0;">
                        <span class="badge badge-cyan" style="font-size: 0.72rem;">
                            {{ $tmpl->slug }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
