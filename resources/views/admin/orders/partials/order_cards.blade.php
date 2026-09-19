@if($orders->count() == 0)
    <div style="text-align: center; padding: 3.5rem 1rem; background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 16px;">
        <i class="fa-solid fa-bell-concierge" style="font-size: 3rem; color: var(--text-muted); opacity: 0.5; margin-bottom: 1rem;"></i>
        <h3 style="font-family: 'Outfit'; font-size: 1.2rem; color: var(--text-main); margin-bottom: 0.35rem;">
            Պատվերներ չեն գտնվել
        </h3>
        <p style="color: var(--text-muted); font-size: 0.85rem;">
            Սեղաններից ստացված նոր պատվերներն այստեղ կհայտնվեն ավտոմատ իրական ժամանակում։
        </p>
    </div>
@else
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;">
        @foreach($orders as $order)
            <div class="card order-card" id="order-card-{{ $order->id }}" style="border-top: 4px solid {{ $order->status == 'pending' ? '#ef4444' : ($order->status == 'preparing' ? '#f59e0b' : ($order->status == 'ready' ? '#3b82f6' : '#10b981')) }}; position: relative; transition: all 0.3s ease;">
                
                @if($order->status == 'pending')
                    <div style="position: absolute; top: -10px; right: 12px; background: #ef4444; color: #fff; font-size: 0.68rem; font-weight: 800; padding: 0.15rem 0.6rem; border-radius: 9999px; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4); animation: pulse 1.8s infinite;">
                        ⚡ ՆՈՐ ՊԱՏՎԵՐ
                    </div>
                @endif

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <div>
                        <h3 style="font-family: 'Outfit'; font-size: 1.2rem; font-weight: 800; color: var(--text-main);">{{ $order->order_number }}</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-clock"></i> {{ $order->created_at->format('H:i, d M') }}</span>
                    </div>

                    <div style="text-align: right;">
                        @if($order->type === 'delivery')
                            <span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.4); font-size: 0.8rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <i class="fa-solid fa-motorcycle"></i> ԱՌԱՔՈՒՄ
                            </span>
                        @else
                            <span style="background: var(--badge-bg); color: var(--badge-text); font-size: 0.8rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px;">
                                {{ $order->table_number ?? 'Takeaway' }}
                            </span>
                        @endif
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; text-transform: uppercase;">
                            {{ str_replace('_', ' ', $order->type) }}
                        </div>
                    </div>
                </div>

                @if($order->customer_name || $order->customer_email || $order->customer_phone || $order->delivery_address)
                    <div style="font-size: 0.85rem; color: var(--text-main); margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 0.4rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.4rem;">
                            <div>
                                <i class="fa-solid fa-user" style="color: var(--primary);"></i> <strong>{{ $order->customer_name ?? 'Guest' }}</strong>
                                @if($order->customer_phone)
                                    <span style="color: var(--text-muted); margin-left: 0.3rem;">
                                        <i class="fa-solid fa-phone" style="font-size: 0.75rem; color: {{ $order->type === 'delivery' ? '#f59e0b' : 'var(--primary)' }};"></i> 
                                        <strong>{{ $order->customer_phone }}</strong>
                                    </span>
                                @endif
                                @if($order->customer_email)
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.1rem;">
                                        <i class="fa-solid fa-envelope"></i> {{ $order->customer_email }}
                                    </div>
                                @endif
                            </div>
                            @if($order->marketing_opt_in)
                                <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 4px;" title="Consented to privacy policy and marketing communications">
                                    <i class="fa-solid fa-check-double"></i> Marketing Consent
                                </span>
                            @endif
                        </div>

                        @if($order->delivery_address)
                            <div style="width: 100%; font-size: 0.82rem; color: #f59e0b; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 8px; padding: 0.45rem 0.7rem; display: flex; align-items: flex-start; gap: 0.5rem;">
                                <i class="fa-solid fa-map-location-dot" style="margin-top: 0.15rem; flex-shrink: 0;"></i>
                                <div>
                                    <strong>Առաքման հասցե՝</strong> {{ $order->delivery_address }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; max-height: 180px; overflow-y: auto;">
                    @foreach($order->items as $item)
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.4rem 0.6rem; border-radius: 8px; color: var(--text-main);">
                            <div>
                                <strong>{{ $item->quantity }}x</strong> {{ $item->product_name }}
                                @if($item->variation_name && $item->variation_name !== 'Standard' && $item->variation_name !== 'Standard Portion')
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

                    <div style="display: flex; gap: 0.4rem; align-items: center;">
                        @if($order->status == 'pending')
                            <button type="button" class="btn btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.75rem;" onclick="changeOrderStatus({{ $order->id }}, 'preparing')">
                                <i class="fa-solid fa-fire"></i> Ընդունել
                            </button>
                            <button type="button" class="btn btn-secondary" style="padding: 0.4rem 0.55rem; font-size: 0.75rem; color: #ef4444;" title="Չեղարկել" onclick="if(confirm('Չեղարկե՞լ պատվերը')) changeOrderStatus({{ $order->id }}, 'cancelled')">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        @elseif($order->status == 'preparing')
                            <button type="button" class="btn btn-success" style="padding: 0.4rem 0.75rem; font-size: 0.75rem;" onclick="changeOrderStatus({{ $order->id }}, 'ready')">
                                <i class="fa-solid fa-bell"></i> Պատրաստ է
                            </button>
                        @elseif($order->status == 'ready')
                            <button type="button" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; color: #10b981;" onclick="changeOrderStatus({{ $order->id }}, 'completed')">
                                <i class="fa-solid fa-check"></i> Ավարտել
                            </button>
                        @elseif($order->status == 'cancelled')
                            <span style="font-size: 0.8rem; font-weight: 700; color: #ef4444;"><i class="fa-solid fa-ban"></i> Չեղարկված</span>
                        @else
                            <span style="font-size: 0.8rem; font-weight: 700; color: #10b981;"><i class="fa-solid fa-circle-check"></i> Ավարտված</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $orders->appends(request()->query())->links() }}
    </div>
@endif
