@extends('layouts.app')

@section('title', 'Vendor Dashboard - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            {{ $vendor->name }}
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Location: <strong style="color: #f59e0b;">{{ $location?->name ?? 'All Locations' }}</strong>
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('admin.ai.import') }}" class="btn btn-secondary">
            <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i> AI Menu Import
        </a>
        <a href="{{ route('client.menu', ['vendor_slug' => $vendor->slug, 'location_slug' => $location?->slug]) }}" target="_blank" class="btn btn-primary">
            <i class="fa-solid fa-qrcode"></i> View Live Menu
        </a>
    </div>
</div>

<!-- Stat Tiles -->
<div class="grid-4">
    <div class="card" style="border-left: 4px solid #f59e0b;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">TODAY'S ORDERS</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ $todayOrders }}</div>
        <div style="font-size: 0.75rem; color: #10b981; margin-top: 0.25rem;"><i class="fa-solid fa-clock"></i> Live incoming</div>
    </div>

    <div class="card" style="border-left: 4px solid #10b981;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">TODAY'S SALES</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ number_format($todayRevenue) }} {{ $vendor->currency }}</div>
        <div style="font-size: 0.75rem; color: #34d399; margin-top: 0.25rem;">Commission free (0%)</div>
    </div>

    <div class="card" style="border-left: 4px solid #ef4444;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">PENDING KITCHEN ORDERS</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit'; color: #f87171;">{{ $pendingOrdersCount }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Requires action</div>
    </div>

    <div class="card" style="border-left: 4px solid #06b6d4;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">DIGITAL MENU VISITS</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ number_format($dineInVisits + $orderingVisits) }}</div>
        <div style="font-size: 0.75rem; color: #67e8f9; margin-top: 0.25rem;">Dine-In: {{ $dineInVisits }} | Ordering: {{ $orderingVisits }}</div>
    </div>
</div>

<div class="grid-2">
    <!-- Live Orders Stream -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.1rem;"><i class="fa-solid fa-bell-concierge text-amber-500"></i> Live Kitchen Stream</h3>
            <a href="{{ route('admin.orders.index') }}" style="font-size: 0.85rem; color: #f59e0b; text-decoration: none;">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        @if($recentOrders->count() == 0)
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                <i class="fa-solid fa-utensils" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <p>No orders recorded for this location yet.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach($recentOrders as $order)
                    <div style="padding: 1rem; background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <strong style="font-size: 1rem; color: #fff;">{{ $order->order_number }}</strong>
                                <span style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">{{ $order->table_number ?? 'Takeaway' }}</span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">{{ $order->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.35rem;">
                                {{ $order->items->pluck('product_name')->join(', ') }}
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-weight: 800; font-size: 1.1rem; color: #34d399;">{{ number_format($order->total_amount) }} {{ $vendor->currency }}</div>
                            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: {{ $order->status == 'completed' ? '#34d399' : '#f87171' }};">
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
        <div class="card" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(239, 68, 68, 0.05)); border-color: rgba(245, 158, 11, 0.3);">
            <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i> AI Menu Import & AI Translate</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Already have a menu as PDF or Word? AI extracts categories, dishes, prices automatically. Translate your whole menu with 1-click into Armenian, English, Russian, French, German!
            </p>
            <a href="{{ route('admin.ai.import') }}" class="btn btn-primary" style="width: max-content;">
                Launch AI Tools <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="card">
            <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 1rem;"><i class="fa-solid fa-qrcode text-amber-500"></i> Table QR Flyer Stand</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Generate high-resolution printable table stands with embedded logos, table numbers, and custom brand colors.
            </p>
            <a href="{{ route('admin.qr.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-print"></i> Open Table QR Studio
            </a>
        </div>
    </div>
</div>
@endsection
