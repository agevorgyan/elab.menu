@extends('layouts.app')

@section('title', 'Vendor Dashboard - ' . $vendor->name)

@section('content')
<!-- Hero Welcome Banner -->
<div class="card" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(99, 102, 241, 0.06) 50%, rgba(239, 68, 68, 0.04) 100%); border-color: rgba(245, 158, 11, 0.2); padding: 1.75rem 2rem; margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem; min-width: 0;">
            @if($vendor->logo)
                <img src="{{ $vendor->logo }}" alt="{{ $vendor->name }}" style="width: 56px; height: 56px; border-radius: 16px; object-fit: cover; border: 2px solid var(--border-color); box-shadow: 0 8px 20px rgba(0,0,0,0.2); flex-shrink: 0;">
            @else
                <div style="width: 56px; height: 56px; border-radius: 16px; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.6rem; font-weight: 800; box-shadow: 0 8px 20px var(--primary-glow); flex-shrink: 0;">
                    <i class="fa-solid fa-store"></i>
                </div>
            @endif
            <div style="min-width: 0;">
                <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                    <h1 style="font-size: 1.65rem; font-weight: 800; margin: 0; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $vendor->name }}
                    </h1>
                    <span class="badge badge-emerald" style="padding: 0.2rem 0.6rem; font-size: 0.72rem;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; box-shadow: 0 0 6px #10b981;"></span>
                        {{ $location?->name ?? 'All Branches' }}
                    </span>
                    <span class="badge badge-amber" style="padding: 0.2rem 0.55rem; font-size: 0.7rem;">
                        0% Commission
                    </span>
                </div>
                <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 0.35rem;" class="truncate-text">
                    Welcome back! Real-time operations overview for your restaurant menu, orders, and table stands.
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center;">
            <a href="{{ route('admin.ai.import') }}" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.55rem 1rem;">
                <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--primary);"></i> AI Menu Import
            </a>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.55rem 1rem;">
                <i class="fa-solid fa-bell-concierge" style="color: #ef4444;"></i> Kitchen Orders
            </a>
            <a href="{{ route('client.menu', ['vendor_slug' => $vendor->slug, 'location_slug' => $location?->slug]) }}" target="_blank" class="btn btn-primary" style="font-size: 0.82rem; padding: 0.55rem 1.1rem;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Live Menu
            </a>
        </div>
    </div>
</div>

<!-- Modern Stat KPI Tiles -->
<div class="grid-4" style="margin-bottom: 2rem;">
    <!-- Tile 1: Orders -->
    <div class="stat-kpi-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
            <div style="min-width: 0;">
                <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">TODAY'S ORDERS</div>
                <div style="font-size: 2.1rem; font-weight: 800; margin-top: 0.4rem; font-family: 'Outfit'; color: var(--text-main); line-height: 1.1;">
                    {{ $todayOrders }}
                </div>
            </div>
            <div class="kpi-icon-badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.85rem; font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 0.65rem;">
            <span>Live incoming orders</span>
            <span class="badge badge-emerald" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">
                ↑ 8.2%
            </span>
        </div>
    </div>

    <!-- Tile 2: Revenue -->
    <div class="stat-kpi-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
            <div style="min-width: 0;">
                <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">TODAY'S REVENUE</div>
                <div style="font-size: 2.1rem; font-weight: 800; margin-top: 0.4rem; font-family: 'Outfit'; color: var(--text-main); line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ number_format($todayRevenue) }} <small style="font-size: 0.95rem; font-weight: 600; color: var(--primary);">{{ $vendor->currency }}</small>
                </div>
            </div>
            <div class="kpi-icon-badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fa-solid fa-coins"></i>
            </div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.85rem; font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 0.65rem;">
            <span>Direct to restaurant (0%)</span>
            <span class="badge badge-emerald" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">
                ↑ 12.4%
            </span>
        </div>
    </div>

    <!-- Tile 3: Pending Orders -->
    <div class="stat-kpi-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
            <div style="min-width: 0;">
                <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">PENDING ORDERS</div>
                <div style="font-size: 2.1rem; font-weight: 800; margin-top: 0.4rem; font-family: 'Outfit'; color: {{ $pendingOrdersCount > 0 ? '#ef4444' : 'var(--text-main)' }}; line-height: 1.1;">
                    {{ $pendingOrdersCount }}
                </div>
            </div>
            <div class="kpi-icon-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fa-solid fa-bell"></i>
            </div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.85rem; font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 0.65rem;">
            <span>Kitchen queue stream</span>
            @if($pendingOrdersCount > 0)
                <span class="badge badge-rose" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">
                    ● Action Needed
                </span>
            @else
                <span class="badge badge-emerald" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">
                    ✓ All clear
                </span>
            @endif
        </div>
    </div>

    <!-- Tile 4: Digital Traffic -->
    <div class="stat-kpi-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
            <div style="min-width: 0;">
                <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">DIGITAL SCANS</div>
                <div style="font-size: 2.1rem; font-weight: 800; margin-top: 0.4rem; font-family: 'Outfit'; color: var(--text-main); line-height: 1.1;">
                    {{ number_format($dineInVisits + $orderingVisits) }}
                </div>
            </div>
            <div class="kpi-icon-badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fa-solid fa-qrcode"></i>
            </div>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.85rem; font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 0.65rem;">
            <span class="truncate-text">Dine-In: {{ $dineInVisits }} | Online: {{ $orderingVisits }}</span>
            <span class="badge badge-indigo" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">
                7 Days
            </span>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Live Orders Stream Card -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; gap: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem; min-width: 0;">
                <span style="width: 34px; height: 34px; border-radius: 10px; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                    <i class="fa-solid fa-bell-concierge"></i>
                </span>
                <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    Customer Orders Stream
                </h3>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary" style="font-size: 0.78rem; padding: 0.35rem 0.75rem; border-radius: 8px;">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        @if($recentOrders->count() == 0)
            <div style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--input-bg); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem; color: var(--text-muted);">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">No active orders right now</h4>
                <p style="font-size: 0.85rem; max-width: 320px; margin: 0 auto;">Incoming Dine-In and online orders will instantly appear here via WebSockets.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                @foreach($recentOrders as $order)
                    <div style="padding: 0.95rem 1.15rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 14px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; transition: all 0.2s ease;">
                        <div style="min-width: 0; flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <strong style="font-size: 0.98rem; color: var(--text-main); font-family: 'Outfit';">{{ $order->order_number }}</strong>
                                <span class="badge badge-amber" style="font-size: 0.72rem; padding: 0.15rem 0.5rem;">
                                    <i class="fa-solid fa-chair text-xs"></i> {{ $order->table_number ? 'Table ' . $order->table_number : 'Takeaway' }}
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.25rem;">
                                    <i class="fa-regular fa-clock text-xs"></i> {{ $order->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 340px;">
                                {{ $order->items->pluck('product_name')->join(', ') }}
                            </div>
                        </div>

                        <div style="text-align: right; flex-shrink: 0;">
                            <div style="font-weight: 800; font-size: 1.15rem; color: #10b981; font-family: 'Outfit'; line-height: 1.2;">
                                {{ number_format($order->total_amount) }} <span style="font-size: 0.85rem;">{{ $vendor->currency }}</span>
                            </div>
                            <div style="margin-top: 0.25rem;">
                                @if($order->status == 'completed')
                                    <span class="badge badge-emerald" style="font-size: 0.68rem; padding: 0.15rem 0.45rem;">
                                        ● COMPLETED
                                    </span>
                                @elseif($order->status == 'ready')
                                    <span class="badge badge-indigo" style="font-size: 0.68rem; padding: 0.15rem 0.45rem;">
                                        ● READY
                                    </span>
                                @elseif($order->status == 'preparing')
                                    <span class="badge badge-amber" style="font-size: 0.68rem; padding: 0.15rem 0.45rem;">
                                        ● PREPARING
                                    </span>
                                @else
                                    <span class="badge badge-rose" style="font-size: 0.68rem; padding: 0.15rem 0.45rem;">
                                        ● PENDING
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Quick Tools & Actions -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Tool 1: AI Suite -->
        <div class="card" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(245, 158, 11, 0.05) 100%); border-color: rgba(139, 92, 246, 0.25);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <span style="width: 40px; height: 40px; border-radius: 12px; background: rgba(139, 92, 246, 0.18); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </span>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0; color: var(--text-main);">
                        AI Menu & Multilingual Suite
                    </h3>
                    <span style="font-size: 0.72rem; color: var(--primary); font-weight: 700;">Smart Menu Digitizer</span>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.5;">
                Extract dishes automatically from PDF or Word documents. Translate your entire menu into Armenian, English, Russian, French, and German in 1-click.
            </p>
            <a href="{{ route('admin.ai.import') }}" class="btn btn-primary" style="align-self: flex-start; font-size: 0.82rem; padding: 0.55rem 1.1rem;">
                Launch AI Suite <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <!-- Tool 2: QR Code Studio -->
        <div class="card" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.08) 0%, rgba(59, 130, 246, 0.04) 100%); border-color: rgba(6, 182, 212, 0.25);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <span style="width: 40px; height: 40px; border-radius: 12px; background: rgba(6, 182, 212, 0.18); color: #06b6d4; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                    <i class="fa-solid fa-qrcode"></i>
                </span>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0; color: var(--text-main);">
                        Table QR Code Studio
                    </h3>
                    <span style="font-size: 0.72rem; color: #06b6d4; font-weight: 700;">Printable Table Stands</span>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.5;">
                Generate high-resolution printable table stand flyers with custom logos, table numbers, and brand colors that connect directly to your digital menu.
            </p>
            <a href="{{ route('admin.qr.index') }}" class="btn btn-secondary" style="align-self: flex-start; font-size: 0.82rem; padding: 0.55rem 1.1rem;">
                <i class="fa-solid fa-print"></i> Open Stand Studio
            </a>
        </div>
    </div>
</div>
@endsection
