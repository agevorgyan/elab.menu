@extends('layouts.app')

@section('title', 'Analytics & Traffic - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-chart-pie text-amber-400"></i> Analytics & Menu Health
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Track menu traffic split by Dine-In scans vs online orders.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="?range=7" class="btn {{ $days == 7 ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">Last 7 Days</a>
        <a href="?range=14" class="btn {{ $days == 14 ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">Last 14 Days</a>
        <a href="?range=30" class="btn {{ $days == 30 ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">Last 30 Days</a>
    </div>
</div>

<!-- Stat Tiles -->
<div class="grid-4">
    <div class="card" style="border-left: 4px solid #f59e0b;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">TOTAL MENU VISITS</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit';">{{ number_format($totalVisits) }}</div>
        <div style="font-size: 0.75rem; color: #10b981; margin-top: 0.25rem;">Past {{ $days }} days traffic</div>
    </div>

    <div class="card" style="border-left: 4px solid #3b82f6;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">DINE-IN TABLE SCANS</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit'; color: #60a5fa;">{{ number_format($dineInVisits) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
            {{ $totalVisits > 0 ? round(($dineInVisits / $totalVisits) * 100) : 0 }}% of overall traffic
        </div>
    </div>

    <div class="card" style="border-left: 4px solid #10b981;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">ORDERING TRAFFIC</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit'; color: #34d399;">{{ number_format($orderingVisits) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
            {{ $totalVisits > 0 ? round(($orderingVisits / $totalVisits) * 100) : 0 }}% of overall traffic
        </div>
    </div>

    <div class="card" style="border-left: 4px solid #ec4899;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">MENU COMPLETENESS SCORE</div>
        <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem; font-family: 'Outfit'; color: #f472b6;">98%</div>
        <div style="font-size: 0.75rem; color: #f472b6; margin-top: 0.25rem;">High dish photos & nutrition tags</div>
    </div>
</div>

<div class="grid-2" style="margin-top: 1.5rem;">
    <!-- Top Ordered Dishes -->
    <div class="card">
        <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 1rem;">Top Ordered Dishes</h3>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                    <th style="padding: 0.75rem 0;">Dish Name</th>
                    <th style="padding: 0.75rem 0;">Quantity Sold</th>
                    <th style="padding: 0.75rem 0; text-align: right;">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topDishes as $index => $dish)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 0.75rem 0; font-weight: 600;">
                            <span style="display: inline-block; width: 20px; text-align: center; color: var(--primary); font-weight: 800;">#{{ $index + 1 }}</span>
                            {{ $dish->product_name }}
                        </td>
                        <td style="padding: 0.75rem 0; font-weight: 700;">{{ $dish->total_qty }} orders</td>
                        <td style="padding: 0.75rem 0; text-align: right; color: #34d399; font-weight: 800; font-family: 'Outfit';">
                            {{ number_format($dish->total_revenue) }} {{ $vendor->currency }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Traffic Split Info -->
    <div class="card">
        <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 1rem;">Channel Traffic Comparison</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
            Every visit is recorded by channel so you can see how guests reach your menu:
        </p>

        <div style="margin-bottom: 1.25rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                <span><i class="fa-solid fa-qrcode text-blue-400"></i> Dine-In QR Table Scans</span>
                <span>{{ $dineInVisits }} visits</span>
            </div>
            <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 9999px; overflow: hidden;">
                <div style="width: {{ $totalVisits > 0 ? ($dineInVisits / $totalVisits) * 100 : 0 }}%; height: 100%; background: #3b82f6;"></div>
            </div>
        </div>

        <div style="margin-bottom: 1.25rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">
                <span><i class="fa-solid fa-bag-shopping text-emerald-400"></i> Online Takeaway & WhatsApp Traffic</span>
                <span>{{ $orderingVisits }} visits</span>
            </div>
            <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 9999px; overflow: hidden;">
                <div style="width: {{ $totalVisits > 0 ? ($orderingVisits / $totalVisits) * 100 : 0 }}%; height: 100%; background: #10b981;"></div>
            </div>
        </div>
    </div>
</div>
@endsection
