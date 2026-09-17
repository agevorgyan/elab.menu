@extends('layouts.app')

@section('title', 'Vendor Dashboard - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800;">
            Dashboard Overview
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Real-time analytics for <strong style="color: var(--primary);">{{ $vendor->name }}</strong> ({{ $location?->name ?? 'All Locations' }}).
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('admin.ai.import') }}" class="btn btn-secondary">
            <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--primary);"></i> AI Menu Import
        </a>
        <a href="{{ route('client.menu', ['vendor_slug' => $vendor->slug, 'location_slug' => $location?->slug]) }}" target="_blank" class="btn btn-primary">
            <i class="fa-solid fa-qrcode"></i> Live Menu
        </a>
    </div>
</div>

<!-- Stat Tiles Inspired by Mockup -->
<div class="grid-4">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">TODAY'S ORDERS</div>
            <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 9999px;">↑ 8.2%</span>
        </div>
        <div style="font-size: 2.2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ $todayOrders }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Live incoming orders</div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">TODAY'S REVENUE</div>
            <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 9999px;">↑ 12.4%</span>
        </div>
        <div style="font-size: 2.2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ number_format($todayRevenue) }} <small style="font-size: 1rem;">{{ $vendor->currency }}</small></div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Commission free (0%)</div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">PENDING ORDERS</div>
            <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 9999px;">● Action Required</span>
        </div>
        <div style="font-size: 2.2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit'; color: #ef4444;">{{ $pendingOrdersCount }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Kitchen pending stream</div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">DIGITAL TRAFFIC</div>
            <span style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 9999px;">7 Days</span>
        </div>
        <div style="font-size: 2.2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ number_format($dineInVisits + $orderingVisits) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Dine-In: {{ $dineInVisits }} | Online: {{ $orderingVisits }}</div>
    </div>
</div>

<div class="grid-2">
    <!-- Live Orders Stream Card -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.15rem; font-weight: 700;">
                <i class="fa-solid fa-bell-concierge" style="color: var(--primary);"></i> Customer Orders Stream
            </h3>
            <a href="{{ route('admin.orders.index') }}" style="font-size: 0.85rem; color: var(--primary); font-weight: 600; text-decoration: none;">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        @if($recentOrders->count() == 0)
            <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                <i class="fa-solid fa-utensils" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <p>No customer orders recorded yet.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach($recentOrders as $order)
                    <div style="padding: 1rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <strong style="font-size: 1rem;">{{ $order->order_number }}</strong>
                                <span style="background: var(--badge-bg); color: var(--badge-text); padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                    {{ $order->table_number ?? 'Takeaway' }}
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">{{ $order->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.35rem;">
                                {{ $order->items->pluck('product_name')->join(', ') }}
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-weight: 800; font-size: 1.1rem; color: #10b981; font-family: 'Outfit';">{{ number_format($order->total_amount) }} {{ $vendor->currency }}</div>
                            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: {{ $order->status == 'completed' ? '#10b981' : '#ef4444' }};">
                                ● {{ $order->status }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Quick Tools & Actions -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(245, 158, 11, 0.05)); border-color: rgba(59, 130, 246, 0.2);">
            <h3 style="font-family: 'Outfit'; font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--primary);"></i> AI Menu & Multilingual Suite
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                Import PDF/Word menus automatically and translate all dishes into Armenian, English, Russian, French, and German in 1-click.
            </p>
            <a href="{{ route('admin.ai.import') }}" class="btn btn-primary" style="width: max-content;">
                Open AI Suite <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="card">
            <h3 style="font-family: 'Outfit'; font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-qrcode" style="color: var(--primary);"></i> Table QR Code Studio
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                Printable table stand flyers with custom logos, table numbers, and brand colors.
            </p>
            <a href="{{ route('admin.qr.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-print"></i> Table Stand Studio
            </a>
        </div>
    </div>
</div>
@endsection
