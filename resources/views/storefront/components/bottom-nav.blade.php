<!-- Mobile Bottom Navigation Bar & Persistent Trackers -->
<div class="storefront-bottom-nav-container">
    <!-- Active Order Tracker Floating Pill (Shown when customer has an active order) -->
    <template x-if="activeOrder && !showOrderTracker && !hideTrackerPill">
        <div class="active-order-floating-pill" @click="showOrderTracker = true">
            <div class="order-pill-left">
                <span class="live-pulse-dot"></span>
                <i :class="activeOrder.status_icon || 'fa-solid fa-clock'" class="order-pill-icon"></i>
                <div class="order-pill-info">
                    <div class="order-pill-title">
                        <span>{{ __('menu.order_number_label') }}<strong x-text="activeOrder.order_number"></strong></span>
                        <span class="order-pill-badge" x-text="activeOrder.status_label"></span>
                    </div>
                    <div class="order-pill-desc" x-text="activeOrder.status_desc"></div>
                </div>
            </div>
            <div class="order-pill-actions">
                <button type="button" class="order-pill-open-btn" title="{{ __('menu.track_order_btn') }}">
                    <i class="fa-solid fa-chevron-up"></i>
                </button>
            </div>
        </div>
    </template>

    <!-- Bottom Navigation Bar -->
    <nav class="storefront-bottom-nav" aria-label="Storefront Mobile Navigation">
        <!-- 1. Home / Menu -->
        <button type="button" 
                class="bottom-nav-item" 
                :class="{ 'active': currentTab === 'menu' && !showCartModal && !showInfoModal && !showOrderTracker }"
                @click="goToMenu()">
            <div class="nav-icon-wrapper">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <span class="nav-label">{{ __('menu.nav_home') }}</span>
        </button>

        <!-- 2. Call Waiter -->
        <button type="button" 
                class="bottom-nav-item"
                :class="{ 'active': showWaiterModal }"
                @click="openWaiterModal('call_waiter')">
            <div class="nav-icon-wrapper">
                <i class="fa-solid fa-bell"></i>
            </div>
            <span class="nav-label">{{ __('menu.nav_waiter') }}</span>
        </button>

        <!-- 3. Cart -->
        <button type="button" 
                class="bottom-nav-item bottom-nav-cart" 
                :class="{ 'active': showCartModal, 'has-items': cartTotalCount > 0, 'cart-bump': cartBump }"
                @click="showCartModal = true"
                title="{{ __('menu.view_cart') }}"
                aria-label="{{ __('menu.view_cart') }}">
            <div class="nav-icon-wrapper">
                <i class="fa-solid fa-basket-shopping"></i>
                <span class="nav-badge" x-show="cartTotalCount > 0" x-text="cartTotalCount" x-transition.scale></span>
            </div>
            <span class="nav-label">{{ __('menu.nav_cart') }}</span>
            <span style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0;">{{ __('menu.view_cart') }}</span>
        </button>

        <!-- 4. Info & WiFi -->
        <button type="button" 
                class="bottom-nav-item" 
                :class="{ 'active': showInfoModal }"
                @click="showInfoModal = true">
            <div class="nav-icon-wrapper">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <span class="nav-label">{{ __('menu.nav_info') }}</span>
        </button>
    </nav>
</div>

<style>
    .storefront-bottom-nav-container {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 80;
        pointer-events: none;
    }

    .storefront-bottom-nav {
        pointer-events: auto;
        background: var(--bg-glass);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding-top: 0.5rem;
        padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.07);
        max-width: 640px;
        margin: 0 auto;
    }

    .bottom-nav-item {
        pointer-events: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        padding: 0.35rem 0.2rem;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
    }

    .bottom-nav-item:active {
        transform: scale(0.92);
    }

    .bottom-nav-item.active {
        color: var(--primary);
    }

    .bottom-nav-item .nav-icon-wrapper {
        position: relative;
        font-size: 1.25rem;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.2rem;
        transition: transform 0.2s ease;
    }

    .bottom-nav-item.active .nav-icon-wrapper {
        transform: translateY(-2px);
    }

    .bottom-nav-item .nav-label {
        font-size: 0.72rem;
        font-weight: 600;
        line-height: 1;
        letter-spacing: -0.01em;
    }

    .bottom-nav-item.has-items {
        color: var(--primary);
    }

    .bottom-nav-item.has-items .nav-icon-wrapper i {
        color: var(--primary);
    }

    .nav-badge {
        position: absolute;
        top: -6px;
        right: -10px;
        background: var(--primary);
        color: #ffffff;
        font-size: 0.72rem;
        font-weight: 800;
        min-width: 19px;
        height: 19px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35), 0 0 10px rgba(16, 185, 129, 0.5);
        border: 2px solid var(--bg-card);
        animation: badgePulse 2s infinite;
        z-index: 5;
    }

    .cart-bump .nav-icon-wrapper {
        animation: cartBounce 0.5s ease;
    }

    @keyframes cartBounce {
        0% { transform: scale(1); }
        30% { transform: scale(1.35) rotate(-8deg); }
        60% { transform: scale(0.92) rotate(4deg); }
        100% { transform: scale(1) rotate(0deg); }
    }

    /* Active Order Floating Pill */
    .active-order-floating-pill {
        pointer-events: auto;
        max-width: calc(640px - 2rem);
        margin: 0 auto 0.6rem auto;
        width: calc(100% - 2rem);
        background: var(--bg-glass);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1.5px solid var(--primary);
        border-radius: 16px;
        padding: 0.65rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
        cursor: pointer;
        animation: slideUpFade 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        transition: transform 0.15s ease;
    }

    .active-order-floating-pill:active {
        transform: scale(0.98);
    }

    .order-pill-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .live-pulse-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: dotPulse 1.8s infinite;
        flex-shrink: 0;
    }

    .order-pill-icon {
        font-size: 1.15rem;
        color: var(--primary);
        flex-shrink: 0;
    }

    .order-pill-info {
        min-width: 0;
    }

    .order-pill-title {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .order-pill-badge {
        background: var(--primary);
        color: #ffffff;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
        border-radius: 6px;
        line-height: 1.2;
    }

    .order-pill-desc {
        font-size: 0.73rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 0.1rem;
    }

    .order-pill-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-left: 0.5rem;
        flex-shrink: 0;
    }

    .order-pill-open-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.75rem;
    }

    @keyframes dotPulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    @keyframes badgePulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.08); }
    }

    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
