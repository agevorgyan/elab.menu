@extends('layouts.app')

@section('title', 'Live Kitchen Orders - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-bell-concierge" style="color: var(--primary);"></i> Live Kitchen Panel
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Real-time incoming Dine-in and WhatsApp orders for <strong>{{ $location?->name ?? 'All Locations' }}</strong>.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center; background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.5rem 1rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 600;">
        <i class="fa-solid fa-signal text-xs"></i> Live Connection Active
    </div>
</div>

<div class="card" style="padding: 0.75rem; margin-bottom: 1.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
    <a href="?status=all" class="btn {{ request('status', 'all') == 'all' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">All Orders</a>
    <a href="?status=pending" class="btn {{ request('status') == 'pending' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">Pending</a>
    <a href="?status=preparing" class="btn {{ request('status') == 'preparing' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">Preparing</a>
    <a href="?status=ready" class="btn {{ request('status') == 'ready' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">Ready</a>
    <a href="?status=completed" class="btn {{ request('status') == 'completed' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">Completed</a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;">
    @foreach($orders as $order)
        <div class="card" style="border-top: 4px solid {{ $order->status == 'pending' ? '#ef4444' : ($order->status == 'preparing' ? '#f59e0b' : ($order->status == 'ready' ? '#3b82f6' : '#10b981')) }}; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                <div>
                    <h3 style="font-family: 'Outfit'; font-size: 1.2rem; font-weight: 800; color: var(--text-main);">{{ $order->order_number }}</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-clock"></i> {{ $order->created_at->format('H:i, d M') }}</span>
                </div>

                <div style="text-align: right;">
                    <span style="background: var(--badge-bg); color: var(--badge-text); font-size: 0.8rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px;">
                        {{ $order->table_number ?? 'Takeaway' }}
                    </span>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; text-transform: uppercase;">
                        {{ str_replace('_', ' ', $order->type) }}
                    </div>
                </div>
            </div>

            @if($order->customer_name)
                <div style="font-size: 0.85rem; color: var(--text-main); margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                    <i class="fa-solid fa-user" style="color: var(--primary);"></i> <strong>{{ $order->customer_name }}</strong> {{ $order->customer_phone ? "({$order->customer_phone})" : '' }}
                </div>
            @endif

            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; max-height: 180px; overflow-y: auto;">
                @foreach($order->items as $item)
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.4rem 0.6rem; border-radius: 8px; color: var(--text-main);">
                        <div>
                            <strong>{{ $item->quantity }}x</strong> {{ $item->product_name }}
                            @if($item->variation_name)
                                <small style="color: var(--text-muted);">({{ $item->variation_name }})</small>
                            @endif
                        </div>
                        <div style="font-weight: 700; color: var(--text-main);">{{ number_format($item->subtotal) }}</div>
                    </div>
                @endforeach
            </div>

            @if($order->notes)
                <div style="font-size: 0.8rem; color: #ef4444; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); padding: 0.4rem 0.6rem; border-radius: 6px; margin-bottom: 1rem;">
                    <i class="fa-solid fa-note-sticky"></i> {{ $order->notes }}
                </div>
            @endif

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 0.75rem; margin-top: auto;">
                <div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);">TOTAL AMOUNT</div>
                    <div style="font-size: 1.2rem; font-weight: 800; color: #10b981; font-family: 'Outfit';">
                        {{ number_format($order->total_amount) }} {{ $vendor->currency }}
                    </div>
                </div>

                <div style="display: flex; gap: 0.4rem;">
                    @if($order->status == 'pending')
                        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="preparing">
                            <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.75rem;">Accept & Prepare</button>
                        </form>
                    @elseif($order->status == 'preparing')
                        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="ready">
                            <button type="submit" class="btn btn-success" style="padding: 0.4rem 0.75rem; font-size: 0.75rem;">Mark Ready</button>
                        </form>
                    @elseif($order->status == 'ready')
                        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; color: #10b981;">Complete</button>
                        </form>
                    @else
                        <span style="font-size: 0.8rem; font-weight: 700; color: #10b981;"><i class="fa-solid fa-circle-check"></i> Completed</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div style="margin-top: 1.5rem;">
    {{ $orders->appends(request()->query())->links() }}
</div>
@endsection
