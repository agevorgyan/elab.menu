@extends('layouts.app')

@section('title', 'Customer Timeline - ' . ($customer->name ?? 'Guest'))

@section('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 2rem;
        margin-top: 1.5rem;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 0.75rem;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--border-color);
        border-radius: 9999px;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
    }
    .timeline-icon {
        position: absolute;
        left: -2rem;
        top: 0.2rem;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 1.5rem;">
    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">
        <i class="fa-solid fa-arrow-left"></i> Back to Customers Directory
    </a>
</div>

<!-- Customer Profile Header Card -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.25rem;">
        <div style="display: flex; gap: 1.25rem; align-items: center;">
            <div style="width: 70px; height: 70px; border-radius: 20px; background: rgba(79, 70, 229, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div>
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--text-main);">
                    {{ $customer->name ?? 'Guest Customer' }}
                </h1>
                <div style="display: flex; gap: 1rem; color: var(--text-muted); font-size: 0.85rem; margin-top: 0.35rem; flex-wrap: wrap;">
                    @if($customer->phone)
                        <span><i class="fa-solid fa-phone" style="color: var(--primary);"></i> {{ $customer->phone }}</span>
                    @endif
                    @if($customer->email)
                        <span><i class="fa-solid fa-envelope" style="color: var(--primary);"></i> {{ $customer->email }}</span>
                    @endif
                    <span><i class="fa-solid fa-calendar-day"></i> Customer since {{ $customer->created_at->format('M Y') }}</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <span style="background: rgba(79, 70, 229, 0.15); color: #4f46e5; border: 1px solid rgba(79, 70, 229, 0.3); padding: 0.4rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700;">
                <i class="fa-solid fa-location-dot"></i> {{ $customer->location?->name ?? 'All Branches' }}
            </span>
            @if($customer->marketing_opt_in)
                <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.4rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700;">
                    <i class="fa-solid fa-check-double"></i> Marketing Consented
                </span>
            @else
                <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.4rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700;">
                    <i class="fa-solid fa-xmark"></i> Opted Out
                </span>
            @endif
        </div>
    </div>

    @if($customer->notes)
        <div style="margin-top: 1.25rem; padding: 0.75rem 1rem; background: var(--input-bg); border-left: 4px solid var(--primary); border-radius: 8px; font-size: 0.85rem; color: var(--text-main);">
            <i class="fa-solid fa-note-sticky" style="color: var(--primary); margin-right: 0.35rem;"></i> <strong>Notes:</strong> {{ $customer->notes }}
        </div>
    @endif
</div>

<!-- Key Performance Stat Cards -->
<div class="grid-4" style="margin-bottom: 2rem;">
    <div class="card" style="padding: 1rem; text-align: center;">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Total Orders</div>
        <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 800; color: var(--text-main);">{{ $customer->total_orders_count }}</div>
    </div>
    <div class="card" style="padding: 1rem; text-align: center;">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Lifetime Spent (LTV)</div>
        <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 800; color: #10b981;">{{ number_format($customer->total_spent) }} {{ $vendor->currency }}</div>
    </div>
    <div class="card" style="padding: 1rem; text-align: center;">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Average Order Value</div>
        <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 800; color: #f59e0b;">
            {{ $customer->total_orders_count > 0 ? number_format($customer->total_spent / $customer->total_orders_count) : 0 }} {{ $vendor->currency }}
        </div>
    </div>
    <div class="card" style="padding: 1rem; text-align: center;">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Last Active</div>
        <div style="font-family: 'Outfit'; font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-top: 0.2rem;">
            {{ $customer->last_order_at ? $customer->last_order_at->diffForHumans() : 'Never' }}
        </div>
    </div>
</div>

<!-- Order History Timeline -->
<div class="card">
    <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 700; color: var(--text-main); margin-bottom: 1rem;">
        <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> Orders Timeline
    </h2>

    @if($orders->count() > 0)
        <div class="timeline">
            @foreach($orders as $order)
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>

                    <div style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <h3 style="font-family: 'Outfit'; font-size: 1.15rem; font-weight: 800; color: var(--text-main);">{{ $order->order_number }}</h3>
                                    <span style="background: {{ $order->status == 'completed' ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)' }}; color: {{ $order->status == 'completed' ? '#10b981' : '#f59e0b' }}; padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                        {{ $order->status }}
                                    </span>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    <i class="fa-solid fa-calendar"></i> {{ $order->created_at->format('d M Y, H:i') }} ({{ $order->created_at->diffForHumans() }})
                                    • <i class="fa-solid fa-location-dot"></i> {{ $order->location?->name ?? 'Main Branch' }}
                                    @if($order->table_number)
                                        • <span style="font-weight: 700;">{{ $order->table_number }}</span>
                                    @endif
                                </div>
                            </div>

                            <div style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 800; color: #10b981;">
                                {{ number_format($order->total_amount) }} {{ $vendor->currency }}
                            </div>
                        </div>

                        <!-- Ordered Items List -->
                        <div style="display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.75rem; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                            @foreach($order->items as $item)
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-main);">
                                    <span><strong>{{ $item->quantity }}x</strong> {{ $item->product_name }} <small style="color: var(--text-muted);">({{ $item->variation_name }})</small></span>
                                    <span style="font-weight: 700;">{{ number_format($item->subtotal) }} {{ $vendor->currency }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 1rem;">
            {{ $orders->links() }}
        </div>
    @else
        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
            No order history recorded for this customer yet.
        </div>
    @endif
</div>
@endsection
