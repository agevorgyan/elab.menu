<!-- Modern Cart & Checkout Modal Component -->
<div x-show="showCartModal" 
     x-cloak
     @click.self="showCartModal = false"
     x-transition:enter="cart-modal-fade-enter"
     x-transition:enter-start="cart-modal-fade-enter-start"
     x-transition:enter-end="cart-modal-fade-enter-end"
     x-transition:leave="cart-modal-fade-leave"
     x-transition:leave-start="cart-modal-fade-enter-end"
     x-transition:leave-end="cart-modal-fade-enter-start"
     class="cart-modal-overlay">
    
    <div class="cart-modal-sheet"
         x-show="showCartModal"
         x-transition:enter="cart-sheet-slide-enter"
         x-transition:enter-start="cart-sheet-slide-enter-start"
         x-transition:enter-end="cart-sheet-slide-enter-end"
         x-transition:leave="cart-sheet-slide-leave"
         x-transition:leave-start="cart-sheet-slide-enter-end"
         x-transition:leave-end="cart-sheet-slide-enter-start">
        
        <!-- Drag Handle on Mobile -->
        <div class="cart-drag-handle" @click="showCartModal = false"></div>

        <!-- Sticky Header -->
        <div class="cart-modal-header">
            <div class="cart-header-left">
                <div class="cart-header-icon-wrap">
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>
                <div>
                    <h3 class="cart-header-title">{{ __('menu.order_summary') }}</h3>
                    <div class="cart-header-subtitle" x-show="cart.length > 0">
                        <span x-text="cartTotalCount"></span>
                        <span x-text="' ' + (cartTotalCount === 1 ? '{{ __('menu.items_count') }}' : '{{ __('menu.items_count') }}')"></span>
                    </div>
                </div>
            </div>
            <button type="button" 
                    @click="showCartModal = false" 
                    class="cart-close-btn"
                    aria-label="{{ __('menu.close') }}"
                    title="{{ __('menu.close') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="cart-modal-body">

            <!-- Empty Cart State -->
            <div x-show="cart.length === 0" class="cart-empty-state">
                <div class="cart-empty-icon-circle">
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>
                <h4 class="cart-empty-title">{{ __('menu.cart_empty') }}</h4>
                <p class="cart-empty-desc">{{ __('menu.cart_empty_desc') }}</p>
                <button type="button" @click="showCartModal = false" class="cart-empty-cta-btn">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>{{ __('menu.browse_menu') }}</span>
                </button>
            </div>

            <!-- Active Cart Items and Checkout Form -->
            <div x-show="cart.length > 0" class="cart-content-stack">
                
                <!-- Items List Stack -->
                <div class="cart-items-container">
                    <div class="cart-section-badge">
                        <i class="fa-solid fa-utensils"></i>
                        <span>{{ __('menu.cart') }}</span>
                        <span class="cart-pill-count" x-text="cartTotalCount"></span>
                    </div>

                    <div class="cart-items-list">
                        <template x-for="(item, idx) in cart" :key="idx">
                            <div class="cart-item-card">
                                <div class="cart-item-info">
                                    <div class="cart-item-name" x-text="item.name"></div>
                                    <template x-if="item.variation_name && item.variation_name !== 'Standard' && item.variation_name !== 'Standard Portion'">
                                        <div class="cart-item-variation">
                                            <span class="cart-item-var-dot"></span>
                                            <span x-text="item.variation_name"></span>
                                        </div>
                                    </template>
                                    <div class="cart-item-price-unit">
                                        <span x-text="Number(item.price).toLocaleString()"></span>
                                        <span class="cart-curr">{{ $vendor->currency }}</span>
                                    </div>
                                </div>

                                <div class="cart-item-controls">
                                    <!-- Stepper -->
                                    <div class="cart-stepper">
                                        <button type="button" 
                                                @click="changeQty(idx, -1)" 
                                                class="cart-stepper-btn"
                                                :class="{ 'is-remove': item.qty === 1 }"
                                                :title="item.qty === 1 ? '{{ __('menu.removed_from_cart') }}' : 'Decrease'"
                                                aria-label="Decrease quantity">
                                            <template x-if="item.qty === 1">
                                                <i class="fa-solid fa-trash-can cart-trash-icon"></i>
                                            </template>
                                            <template x-if="item.qty > 1">
                                                <i class="fa-solid fa-minus"></i>
                                            </template>
                                        </button>
                                        <span class="cart-stepper-val" x-text="item.qty"></span>
                                        <button type="button" 
                                                @click="changeQty(idx, 1)" 
                                                class="cart-stepper-btn cart-stepper-btn-plus"
                                                title="Increase"
                                                aria-label="Increase quantity">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>

                                    <!-- Item Subtotal -->
                                    <div class="cart-item-subtotal">
                                        <span x-text="Number(item.price * item.qty).toLocaleString()"></span>
                                        <span class="cart-subtotal-curr">{{ $vendor->currency }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Kitchen Notes Card -->
                <!-- Order Type Switcher (Dine-In vs Delivery) -->
                <div class="cart-type-toggle-wrap">
                    <button type="button" 
                            @click="orderType = 'dine_in'" 
                            :class="{ 'is-active': orderType === 'dine_in' }"
                            class="cart-type-btn">
                        <i class="fa-solid fa-utensils"></i>
                        <span>{{ __('menu.dine_in') }}</span>
                    </button>
                    <button type="button" 
                            @click="orderType = 'delivery'" 
                            :class="{ 'is-active': orderType === 'delivery' }"
                            class="cart-type-btn">
                        <i class="fa-solid fa-motorcycle"></i>
                        <span>{{ __('menu.delivery') }}</span>
                    </button>
                </div>

                <!-- Kitchen Notes Card -->
                <div class="cart-card-group">
                    <label class="cart-group-label">
                        <i class="fa-regular fa-comment-dots cart-label-icon"></i>
                        <span>{{ __('menu.order_notes') }}</span>
                    </label>
                    <div class="cart-textarea-wrap">
                        <textarea x-model="orderNotes" 
                                  class="cart-textarea" 
                                  rows="2" 
                                  placeholder="{{ __('menu.order_notes_placeholder') }}"></textarea>
                    </div>
                </div>

                <!-- Customer Details Card -->
                <div class="cart-card-group">
                    <label class="cart-group-label">
                        <i class="fa-regular fa-id-card cart-label-icon"></i>
                        <span>{{ __('menu.customer_details') }}</span>
                    </label>

                    <div class="cart-inputs-grid">
                        <!-- Name & Phone -->
                        <div class="cart-input-field-wrap">
                            <i class="fa-regular fa-user cart-input-prefix-icon"></i>
                            <input type="text" 
                                   x-model="customerName" 
                                   class="cart-input" 
                                   placeholder="{{ __('menu.full_name_placeholder') }}">
                        </div>

                        <div class="cart-input-field-wrap">
                            <i class="fa-solid fa-phone cart-input-prefix-icon" :style="orderType === 'delivery' ? 'color: #f59e0b;' : ''"></i>
                            <input type="tel" 
                                   x-model="customerPhone" 
                                   class="cart-input" 
                                   :required="orderType === 'delivery'"
                                   :placeholder="orderType === 'delivery' ? '* ' + '{{ __('menu.phone_placeholder') }}' : '{{ __('menu.phone_placeholder') }}'">
                        </div>

                        <!-- Email & Birthdate -->
                        <div class="cart-input-field-wrap">
                            <i class="fa-regular fa-envelope cart-input-prefix-icon"></i>
                            <input type="email" 
                                   x-model="customerEmail" 
                                   class="cart-input" 
                                   placeholder="{{ __('menu.email_placeholder') }}">
                        </div>

                        <div class="cart-input-field-wrap">
                            <i class="fa-solid fa-cake-candles cart-input-prefix-icon"></i>
                            <input type="date" 
                                   x-model="customerBirthdate" 
                                   class="cart-input" 
                                   title="{{ __('menu.birthdate_placeholder') }}"
                                   placeholder="{{ __('menu.birthdate_placeholder') }}">
                        </div>
                    </div>

                    <!-- Delivery Address Input (Only when orderType === 'delivery') -->
                    <template x-if="orderType === 'delivery'">
                        <div class="cart-delivery-field-block">
                            <label class="cart-subfield-label">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>{{ __('menu.delivery_address') }} <strong style="color: #ef4444;">*</strong></span>
                            </label>
                            <div class="cart-input-field-wrap">
                                <i class="fa-solid fa-motorcycle cart-input-prefix-icon" style="color: #f59e0b;"></i>
                                <input type="text" 
                                       x-model="deliveryAddress" 
                                       class="cart-input cart-delivery-input" 
                                       placeholder="{{ __('menu.delivery_address_placeholder') }}">
                            </div>
                        </div>
                    </template>

                    <!-- Table Selection (Only when orderType === 'dine_in') -->
                    <template x-if="orderType === 'dine_in'">
                        <div>
                            <div class="cart-table-wrap" :class="{ 'is-locked': isTableFixed }">
                                <div class="cart-table-field">
                                    <i class="fa-solid fa-chair cart-input-prefix-icon"></i>
                                    <input type="text" 
                                           x-model="tableNumber" 
                                           :readonly="isTableFixed"
                                           class="cart-input cart-table-input" 
                                           placeholder="{{ __('menu.table') }} {{ $table ?? '4' }}">
                                </div>
                                <template x-if="isTableFixed">
                                    <div class="cart-table-badge">
                                        <i class="fa-solid fa-lock"></i>
                                        <span>{{ __('menu.fixed_qr') }}</span>
                                    </div>
                                </template>
                            </div>

                            <template x-if="isTableFixed">
                                <div class="cart-table-hint">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>{{ __('menu.fixed_qr_notice') }}</span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Pricing Breakdown Summary Card -->
                <div class="cart-summary-card">
                    <div class="cart-summary-row">
                        <span class="cart-summary-label">{{ __('menu.subtotal') }}</span>
                        <span class="cart-summary-val">
                            <strong x-text="Number(cartTotalPrice).toLocaleString()"></strong>
                            <span class="cart-summary-curr">{{ $vendor->currency }}</span>
                        </span>
                    </div>

                    <!-- Dine-in Table Row -->
                    <template x-if="orderType === 'dine_in'">
                        <div class="cart-summary-row">
                            <span class="cart-summary-label">{{ __('menu.table') }}</span>
                            <span class="cart-summary-val" x-text="tableNumber || '{{ $table ?? "Table 4" }}'"></span>
                        </div>
                    </template>

                    <!-- Delivery Type & Address Row -->
                    <template x-if="orderType === 'delivery'">
                        <div>
                            <div class="cart-summary-row">
                                <span class="cart-summary-label">{{ __('menu.order_type') }}</span>
                                <span class="cart-summary-val" style="color: #f59e0b; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <i class="fa-solid fa-motorcycle"></i> {{ __('menu.delivery') }}
                                </span>
                            </div>
                            <template x-if="deliveryAddress">
                                <div class="cart-summary-row" style="margin-top: 0.25rem;">
                                    <span class="cart-summary-label">{{ __('menu.delivery_address') }}</span>
                                    <span class="cart-summary-val cart-summary-addr" x-text="deliveryAddress"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <div class="cart-summary-divider"></div>
                    <div class="cart-summary-row cart-total-row">
                        <span class="cart-total-label">{{ __('menu.total_to_pay') }}</span>
                        <div class="cart-total-price-wrap">
                            <span class="cart-total-number" x-text="Number(cartTotalPrice).toLocaleString()"></span>
                            <span class="cart-total-curr">{{ $vendor->currency }}</span>
                        </div>
                    </div>
                </div>

                <!-- GDPR & Consent Opt-In -->
                <label class="cart-consent-card">
                    <input type="checkbox" x-model="marketingOptIn" class="cart-consent-checkbox">
                    <span class="cart-consent-text">
                        {!! __('menu.privacy_consent', [
                            'privacy_link' => '<a href="'.route('legal.privacy').'" target="_blank" class="cart-legal-link">'.__('menu.privacy_policy').'</a>',
                            'terms_link' => '<a href="'.route('legal.terms').'" target="_blank" class="cart-legal-link">'.__('menu.terms_of_service').'</a>'
                        ]) !!}
                    </span>
                </label>

                <!-- Order Action Buttons -->
                <div class="cart-actions-column">
                    <!-- Main Order Button -->
                    <button type="button" 
                            @click="submitOrder(orderType)" 
                            class="cart-submit-btn"
                            :class="{ 'is-delivery': orderType === 'delivery' }">
                        <div class="cart-submit-left">
                            <template x-if="orderType === 'delivery'">
                                <i class="fa-solid fa-motorcycle"></i>
                            </template>
                            <template x-if="orderType !== 'delivery'">
                                <i class="fa-solid fa-bell-concierge"></i>
                            </template>
                            <span x-text="orderType === 'delivery' ? '{{ __('menu.order_delivery') }}' : '{{ __('menu.checkout') }}'"></span>
                        </div>
                        <div class="cart-submit-price-pill">
                            <span x-text="Number(cartTotalPrice).toLocaleString()"></span>
                            <span class="cart-submit-curr">{{ $vendor->currency }}</span>
                        </div>
                    </button>

                    <!-- WhatsApp Order Button -->
                    <button type="button" 
                            @click="submitOrder('whatsapp')" 
                            class="cart-whatsapp-btn">
                        <i class="fa-brands fa-whatsapp cart-wa-icon"></i>
                        <span x-text="orderType === 'delivery' ? '{{ __('menu.order_delivery_via_whatsapp') }}' : '{{ __('menu.order_via_whatsapp') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Cart Modal Backdrop Overlay */
    .cart-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 150;
        background: rgba(0, 0, 0, 0.74);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        box-sizing: border-box;
    }

    /* Modal Animation Transitions */
    .cart-modal-fade-enter {
        transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .cart-modal-fade-enter-start {
        opacity: 0;
    }
    .cart-modal-fade-enter-end {
        opacity: 1;
    }
    .cart-modal-fade-leave {
        transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .cart-sheet-slide-enter {
        transition: transform 0.34s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.28s ease;
    }
    .cart-sheet-slide-enter-start {
        transform: translateY(100%);
        opacity: 0;
    }
    .cart-sheet-slide-enter-end {
        transform: translateY(0);
        opacity: 1;
    }
    .cart-sheet-slide-leave {
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.18s ease;
    }

    /* Main Bottom-Sheet Container */
    .cart-modal-sheet {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-bottom: none;
        border-radius: 28px 28px 0 0;
        width: 100%;
        max-width: 620px;
        height: 92vh;
        height: 92dvh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 -12px 48px rgba(0, 0, 0, 0.5);
        box-sizing: border-box;
        overflow: hidden;
        position: relative;
    }

    /* Drag Handle Pill */
    .cart-drag-handle {
        width: 44px;
        height: 4.5px;
        border-radius: 9999px;
        background: var(--border-color);
        margin: 0.65rem auto 0.25rem auto;
        cursor: pointer;
        opacity: 0.85;
        transition: opacity 0.2s;
        flex-shrink: 0;
    }
    .cart-drag-handle:hover {
        opacity: 1;
    }

    /* Modal Header */
    .cart-modal-header {
        padding: 0.85rem 1.35rem 0.85rem 1.35rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        background: var(--bg-card);
        flex-shrink: 0;
        z-index: 10;
    }

    .cart-header-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .cart-header-icon-wrap {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--primary) 15%, transparent);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
        box-shadow: 0 2px 10px color-mix(in srgb, var(--primary) 20%, transparent);
    }

    .cart-header-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-main);
        margin: 0;
        line-height: 1.2;
        letter-spacing: -0.01em;
    }

    .cart-header-subtitle {
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--primary);
        margin-top: 0.15rem;
    }

    .cart-close-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        font-size: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        padding: 0;
    }
    .cart-close-btn:hover {
        color: var(--text-main);
        border-color: rgba(255, 255, 255, 0.25);
        transform: rotate(90deg);
    }
    .cart-close-btn:active {
        transform: scale(0.92);
    }

    /* Scrollable Body */
    .cart-modal-body {
        flex: 1;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 1.15rem 1.35rem calc(2rem + env(safe-area-inset-bottom, 0.5rem)) 1.35rem;
        box-sizing: border-box;
    }

    /* Empty Cart State */
    .cart-empty-state {
        text-align: center;
        padding: 3rem 1rem 2rem;
    }

    .cart-empty-icon-circle {
        width: 82px;
        height: 82px;
        border-radius: 50%;
        background: var(--bg-main);
        border: 1.5px solid var(--border-color);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.25rem;
        color: var(--text-muted);
        font-size: 2.2rem;
        opacity: 0.7;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }

    .cart-empty-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-main);
        margin: 0 0 0.4rem 0;
    }

    .cart-empty-desc {
        font-size: 0.88rem;
        color: var(--text-muted);
        max-width: 300px;
        margin: 0 auto 1.6rem auto;
        line-height: 1.45;
    }

    .cart-empty-cta-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        background: var(--primary);
        color: #ffffff;
        border: none;
        padding: 0.85rem 1.85rem;
        border-radius: 14px;
        font-weight: 800;
        font-size: 0.95rem;
        cursor: pointer;
        box-shadow: 0 8px 20px -4px color-mix(in srgb, var(--primary) 40%, transparent);
        transition: all 0.2s ease;
    }
    .cart-empty-cta-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -4px color-mix(in srgb, var(--primary) 50%, transparent);
    }

    /* Cart Content Stack */
    .cart-content-stack {
        display: flex;
        flex-direction: column;
        gap: 1.15rem;
    }

    /* Items Section */
    .cart-items-container {
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        padding: 0.85rem 1rem;
    }

    .cart-section-badge {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .cart-section-badge i {
        color: var(--primary);
    }

    .cart-pill-count {
        background: color-mix(in srgb, var(--primary) 15%, transparent);
        color: var(--primary);
        padding: 0.05rem 0.45rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .cart-items-list {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .cart-item-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0.85rem;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        gap: 0.85rem;
        transition: border-color 0.2s;
    }
    .cart-item-card:hover {
        border-color: color-mix(in srgb, var(--primary) 30%, var(--border-color));
    }

    .cart-item-info {
        min-width: 0;
        flex: 1;
    }

    .cart-item-name {
        font-weight: 700;
        font-size: 0.96rem;
        color: var(--text-main);
        line-height: 1.3;
        word-break: break-word;
    }

    .cart-item-variation {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--primary);
        background: color-mix(in srgb, var(--primary) 12%, transparent);
        border: 1px solid color-mix(in srgb, var(--primary) 22%, transparent);
        padding: 0.1rem 0.5rem;
        border-radius: 6px;
        margin-top: 0.25rem;
        word-break: break-word;
    }

    .cart-item-var-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: var(--primary);
    }

    .cart-item-price-unit {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
        font-family: 'Outfit', sans-serif;
    }

    .cart-curr {
        font-size: 0.74rem;
    }

    .cart-item-controls {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.35rem;
        flex-shrink: 0;
    }

    /* Stepper */
    .cart-stepper {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: 9999px;
        padding: 0.2rem 0.3rem;
    }

    .cart-stepper-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--text-main);
        font-weight: 800;
        font-size: 0.85rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
        padding: 0;
    }
    .cart-stepper-btn:hover {
        background: var(--border-color);
    }
    .cart-stepper-btn:active {
        transform: scale(0.9);
    }

    .cart-stepper-btn.is-remove:hover {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }
    .cart-trash-icon {
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    .cart-stepper-btn.is-remove:hover .cart-trash-icon {
        color: #ef4444;
    }

    .cart-stepper-btn-plus {
        background: var(--primary);
        color: #ffffff;
    }
    .cart-stepper-btn-plus:hover {
        background: var(--primary);
        transform: scale(1.08);
    }

    .cart-stepper-val {
        min-width: 24px;
        text-align: center;
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 0.95rem;
        color: var(--text-main);
    }

    .cart-item-subtotal {
        font-family: 'Outfit', sans-serif;
        font-size: 0.92rem;
        font-weight: 800;
        color: var(--accent);
    }
    .cart-subtotal-curr {
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    /* Order Type Toggle (Dine-in vs Delivery) */
    .cart-type-toggle-wrap {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.4rem;
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        padding: 0.35rem;
        border-radius: 16px;
    }

    .cart-type-btn {
        background: transparent;
        border: none;
        color: var(--text-muted);
        padding: 0.65rem 0.9rem;
        border-radius: 12px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .cart-type-btn:hover {
        color: var(--text-main);
    }
    .cart-type-btn.is-active {
        background: var(--primary);
        color: #ffffff;
        box-shadow: 0 4px 15px color-mix(in srgb, var(--primary) 35%, transparent);
    }

    /* Delivery Address Field */
    .cart-delivery-field-block {
        margin-top: 0.4rem;
        padding-top: 0.75rem;
        border-top: 1px dashed var(--border-color);
    }

    .cart-subfield-label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: #f59e0b;
        margin-bottom: 0.45rem;
    }

    .cart-delivery-input {
        border-color: rgba(245, 158, 11, 0.3);
    }
    .cart-delivery-input:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    }

    .cart-summary-addr {
        max-width: 170px;
        text-align: right;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.82rem;
        color: var(--text-main);
    }

    .cart-submit-btn.is-delivery {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 4px 18px rgba(245, 158, 11, 0.35);
    }
    .cart-submit-btn.is-delivery:hover {
        box-shadow: 0 6px 24px rgba(245, 158, 11, 0.5);
    }

    /* Section Cards (Notes & Customer Info) */
    .cart-card-group {
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        padding: 0.9rem 1.1rem;
    }

    .cart-group-label {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.82rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 0.65rem;
    }

    .cart-label-icon {
        color: var(--primary);
        font-size: 0.95rem;
    }

    .cart-textarea-wrap {
        position: relative;
    }

    .cart-textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 0.75rem 0.95rem;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        color: var(--text-main);
        font-size: 0.88rem;
        line-height: 1.4;
        resize: vertical;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .cart-textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent);
    }

    /* Customer Form Grid */
    .cart-inputs-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.65rem;
        margin-bottom: 0.65rem;
    }

    @media (max-width: 480px) {
        .cart-inputs-grid {
            grid-template-columns: 1fr;
        }
    }

    .cart-input-field-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .cart-input-prefix-icon {
        position: absolute;
        left: 0.9rem;
        color: var(--text-muted);
        font-size: 0.9rem;
        pointer-events: none;
    }

    .cart-input {
        width: 100%;
        box-sizing: border-box;
        padding: 0.72rem 0.85rem 0.72rem 2.35rem;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        color: var(--text-main);
        font-size: 0.86rem;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .cart-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent);
    }

    /* Table Input */
    .cart-table-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .cart-table-field {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
    }

    .cart-table-input {
        padding-right: 7rem;
    }

    .cart-table-wrap.is-locked .cart-table-input {
        background: color-mix(in srgb, var(--input-bg) 70%, #000);
        cursor: not-allowed;
    }

    .cart-table-badge {
        position: absolute;
        right: 0.65rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.74rem;
        font-weight: 700;
        color: #10b981;
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.25);
        padding: 0.2rem 0.55rem;
        border-radius: 8px;
        pointer-events: none;
    }

    .cart-table-hint {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        color: #10b981;
        margin-top: 0.4rem;
        padding: 0 0.2rem;
    }

    /* Pricing Breakdown Card */
    .cart-summary-card {
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        padding: 1rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .cart-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.88rem;
    }

    .cart-summary-label {
        color: var(--text-muted);
    }

    .cart-summary-val {
        color: var(--text-main);
        font-family: 'Outfit', sans-serif;
    }
    .cart-summary-curr {
        font-size: 0.76rem;
        color: var(--text-muted);
    }

    .cart-summary-divider {
        height: 1px;
        background: var(--border-color);
        margin: 0.35rem 0;
    }

    .cart-total-row {
        align-items: baseline;
    }

    .cart-total-label {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -0.01em;
    }

    .cart-total-price-wrap {
        display: flex;
        align-items: baseline;
        gap: 0.35rem;
    }

    .cart-total-number {
        font-family: 'Outfit', sans-serif;
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--accent);
        letter-spacing: -0.02em;
    }

    .cart-total-curr {
        font-size: 0.88rem;
        font-weight: 800;
        color: var(--text-muted);
    }

    /* Consent Card */
    .cart-consent-card {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        background: var(--bg-main);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 0.75rem 0.95rem;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    .cart-consent-card:hover {
        border-color: color-mix(in srgb, var(--primary) 30%, var(--border-color));
    }

    .cart-consent-checkbox {
        margin-top: 0.2rem;
        width: 16px;
        height: 16px;
        accent-color: var(--primary);
        cursor: pointer;
        flex-shrink: 0;
    }

    .cart-consent-text {
        font-size: 0.78rem;
        line-height: 1.45;
        color: var(--text-muted);
    }

    .cart-legal-link {
        color: var(--primary);
        font-weight: 700;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    /* Actions Column */
    .cart-actions-column {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 0.35rem;
    }

    .cart-submit-btn {
        width: 100%;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 85%, #000));
        color: #ffffff;
        font-weight: 800;
        font-size: 1.05rem;
        border: none;
        border-radius: 16px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 8px 24px -4px color-mix(in srgb, var(--primary) 40%, transparent);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        box-sizing: border-box;
    }
    .cart-submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px -4px color-mix(in srgb, var(--primary) 50%, transparent);
    }
    .cart-submit-btn:active {
        transform: scale(0.985);
    }

    .cart-submit-left {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        font-size: 1.02rem;
    }
    .cart-submit-left i {
        font-size: 1.15rem;
    }

    .cart-submit-price-pill {
        display: flex;
        align-items: baseline;
        gap: 0.3rem;
        background: rgba(0, 0, 0, 0.22);
        padding: 0.28rem 0.75rem;
        border-radius: 10px;
        font-family: 'Outfit', sans-serif;
        font-size: 1.15rem;
        font-weight: 800;
    }

    .cart-submit-curr {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .cart-whatsapp-btn {
        width: 100%;
        padding: 0.88rem 1.25rem;
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: #ffffff;
        font-weight: 800;
        font-size: 0.96rem;
        border: none;
        border-radius: 16px;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.65rem;
        box-shadow: 0 6px 20px -3px rgba(37, 211, 102, 0.35);
        transition: all 0.22s ease;
        box-sizing: border-box;
    }
    .cart-whatsapp-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 24px -3px rgba(37, 211, 102, 0.45);
    }
    .cart-whatsapp-btn:active {
        transform: scale(0.985);
    }

    .cart-wa-icon {
        font-size: 1.25rem;
    }
</style>
