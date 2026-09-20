@extends('layouts.app')

@section('title', __('Հաճախորդի Պատմություն') . ' - ' . ($customer->name ?? __('Հյուր')))

@section('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 2.5rem;
        margin-top: 1.5rem;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 0.95rem;
        top: 0.5rem;
        bottom: 0.5rem;
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
        left: -2.5rem;
        top: 0.2rem;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), #d97706);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.88rem;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
        border: 2px solid var(--bg-card);
    }
    @media (max-width: 640px) {
        .timeline {
            padding-left: 1.85rem;
        }
        .timeline::before {
            left: 0.6rem;
        }
        .timeline-icon {
            left: -1.85rem;
            width: 28px;
            height: 28px;
            font-size: 0.75rem;
        }
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 1.5rem;">
    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary" style="font-size: 0.88rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 12px;">
        <i class="fa-solid fa-arrow-left"></i> {{ __('Վերադառնալ Հաճախորդների Բազա') }}
    </a>
</div>

<!-- Customer Profile Header Card -->
<div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.25rem, 3vw, 1.85rem); margin-bottom: 1.5rem; box-shadow: var(--shadow-card);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.25rem;">
        <div style="display: flex; gap: 1.25rem; align-items: center; min-width: 0; flex: 1;">
            <div style="width: clamp(54px, 8vw, 70px); height: clamp(54px, 8vw, 70px); border-radius: 20px; background: rgba(79, 70, 229, 0.15); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.85rem; flex-shrink: 0;">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div style="min-width: 0;">
                <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(1.3rem, 2.5vw, 1.75rem); font-weight: 800; color: var(--text-main); margin: 0; word-break: break-word;">
                    {{ $customer->name ?? __('Հյուր Հաճախորդ') }}
                </h1>
                <div style="display: flex; gap: 0.85rem; color: var(--text-muted); font-size: 0.85rem; margin-top: 0.4rem; flex-wrap: wrap; align-items: center;">
                    @if($customer->phone)
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-phone" style="color: var(--primary);"></i> {{ $customer->phone }}</span>
                    @endif
                    @if($customer->email)
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-envelope" style="color: var(--primary);"></i> {{ $customer->email }}</span>
                    @endif
                    @if($customer->birthdate)
                        <span style="color: #ec4899; font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i class="fa-solid fa-cake-candles"></i> {{ $customer->birthdate->format('d M Y') }} ({{ $customer->birthdate->age }} t.)
                        </span>
                    @endif
                    @if($customer->address)
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-map-pin" style="color: #f59e0b;"></i> {{ $customer->address }}</span>
                    @endif
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-calendar-day"></i> {{ __('Գրանցված է՝') }} {{ $customer->created_at->format('M Y') }}</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
            <span style="background: rgba(79, 70, 229, 0.15); color: #6366f1; border: 1px solid rgba(79, 70, 229, 0.3); padding: 0.4rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                <i class="fa-solid fa-location-dot"></i> {{ $customer->location?->name ?? __('Բոլոր մասնաճյուղերը') }}
            </span>
            @if($customer->marketing_opt_in)
                <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.4rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i class="fa-solid fa-check-double"></i> {{ __('Մարքեթինգը Ակտիվ է') }}
                </span>
            @else
                <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.4rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i class="fa-solid fa-xmark"></i> {{ __('Անջատված') }}
                </span>
            @endif
        </div>
    </div>

    @if($customer->notes)
        <div style="margin-top: 1.25rem; padding: 0.85rem 1.1rem; background: var(--bg-body); border-left: 4px solid var(--primary); border-radius: 10px; font-size: 0.88rem; color: var(--text-main); word-break: break-word;">
            <i class="fa-solid fa-note-sticky" style="color: var(--primary); margin-right: 0.45rem;"></i> <strong>{{ __('Նշումներ՝') }}</strong> {{ $customer->notes }}
        </div>
    @endif
</div>

<!-- Key Performance Stat Cards -->
<div class="grid-4" style="margin-bottom: 2rem;">
    <div class="card kpi-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; padding: 1.2rem; text-align: center; box-shadow: var(--shadow-card);">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">{{ __('Ընդհանուր Պատվերներ') }}</div>
        <div style="font-family: 'Outfit', sans-serif; font-size: 1.65rem; font-weight: 800; color: var(--text-main); margin-top: 0.2rem;">{{ $customer->total_orders_count }}</div>
    </div>
    <div class="card kpi-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; padding: 1.2rem; text-align: center; box-shadow: var(--shadow-card);">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">{{ __('Ընդհանուր Գումար (LTV)') }}</div>
        <div style="font-family: 'Outfit', sans-serif; font-size: 1.65rem; font-weight: 800; color: #10b981; margin-top: 0.2rem;">{{ number_format($customer->total_spent) }} {{ $vendor->currency }}</div>
    </div>
    <div class="card kpi-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; padding: 1.2rem; text-align: center; box-shadow: var(--shadow-card);">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">{{ __('Միջին Չեկ (AOV)') }}</div>
        <div style="font-family: 'Outfit', sans-serif; font-size: 1.65rem; font-weight: 800; color: #f59e0b; margin-top: 0.2rem;">
            {{ $customer->total_orders_count > 0 ? number_format($customer->total_spent / $customer->total_orders_count) : 0 }} {{ $vendor->currency }}
        </div>
    </div>
    <div class="card kpi-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; padding: 1.2rem; text-align: center; box-shadow: var(--shadow-card);">
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">{{ __('Վերջին Ակտիվություն') }}</div>
        <div style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin-top: 0.35rem;">
            {{ $customer->last_order_at ? $customer->last_order_at->diffForHumans() : __('Երբեք') }}
        </div>
    </div>
</div>

<!-- Order History Timeline -->
<div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.25rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
    <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 800; color: var(--text-main); margin: 0 0 1.25rem 0; display: flex; align-items: center; gap: 0.6rem;">
        <span style="color: var(--primary);"><i class="fa-solid fa-clock-rotate-left"></i></span>
        <span>{{ __('Պատվերների Ժամանակացույց (Timeline)') }}</span>
    </h2>

    @if($orders->count() > 0)
        <div class="timeline">
            @foreach($orders as $order)
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>

                    <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 16px; padding: clamp(1rem, 2.5vw, 1.35rem); box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.6rem; margin-bottom: 0.75rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">{{ $order->order_number }}</h3>
                                    <span style="background: {{ $order->status == 'completed' ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)' }}; color: {{ $order->status == 'completed' ? '#10b981' : '#f59e0b' }}; border: 1px solid {{ $order->status == 'completed' ? 'rgba(16,185,129,0.3)' : 'rgba(245,158,11,0.3)' }}; padding: 0.15rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                        {{ $order->status }}
                                    </span>
                                </div>
                                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                    <span><i class="fa-solid fa-calendar"></i> {{ $order->created_at->format('d M Y, H:i') }} ({{ $order->created_at->diffForHumans() }})</span>
                                    <span>•</span>
                                    <span><i class="fa-solid fa-location-dot"></i> {{ $order->location?->name ?? __('Գլխավոր Մասնաճյուղ') }}</span>
                                    @if($order->table_number)
                                        <span>•</span>
                                        <span style="font-weight: 700; color: var(--primary);">{{ __('Սեղան՝') }} {{ $order->table_number }}</span>
                                    @endif
                                </div>
                            </div>

                            <div style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 800; color: #10b981; white-space: nowrap;">
                                {{ number_format($order->total_amount) }} {{ $vendor->currency }}
                            </div>
                        </div>

                        <!-- Ordered Items List -->
                        <div style="display: flex; flex-direction: column; gap: 0.45rem; margin-top: 0.75rem; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                            @foreach($order->items as $item)
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.86rem; color: var(--text-main); flex-wrap: wrap; gap: 0.35rem;">
                                    <span style="word-break: break-word;">
                                        <strong style="color: var(--primary);">{{ $item->quantity }}x</strong> {{ $item->product_name }}
                                        @if($item->variation_name)
                                            <small style="color: var(--text-muted);">({{ $item->variation_name }})</small>
                                        @endif
                                    </span>
                                    <span style="font-weight: 700; font-family: 'Outfit', sans-serif;">{{ number_format($item->subtotal) }} {{ $vendor->currency }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 1.25rem;">
            {{ $orders->links() }}
        </div>
    @else
        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
            <div style="width: 54px; height: 54px; border-radius: 14px; background: rgba(245, 158, 11, 0.1); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem;">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <p style="margin: 0; font-size: 0.95rem; font-weight: 600;">{{ __('Այս հաճախորդի համար պատվերներ դեռ գրանցված չեն:') }}</p>
        </div>
    @endif
</div>
@endsection
