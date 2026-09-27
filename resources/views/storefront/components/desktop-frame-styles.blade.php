@php
    $maxWidth = $vendor->getDesktopMaxWidth();
    $isFullWidth = ($maxWidth === '100%');
    $isDark = ($vendor->theme_mode === 'dark');
    $isExplicitMobileNarrow = in_array($maxWidth, ['480px', '500px']);
@endphp

<style>
    :root {
        --desktop-max-width: {{ $maxWidth }};
    }

    /* Content Visibility Performance Optimization for Large Menus (150+ Dishes) */
    .category-section {
        content-visibility: auto;
        contain-intrinsic-size: auto 600px;
    }

    .dish-card, .glass-card {
        content-visibility: auto;
        contain-intrinsic-size: auto 130px;
    }

    /* Responsive Multi-Column Grid on Tablet & Desktop */
    @media (min-width: 768px) {
        .dishes-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
            gap: 1.25rem !important;
        }

        .dish-card, .glass-card {
            margin-bottom: 0 !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            box-sizing: border-box !important;
        }

        .dish-card .dish-img, .glass-card .dish-img {
            width: 100% !important;
            height: 165px !important;
            border-radius: 12px !important;
            object-fit: cover !important;
            margin-bottom: 0.75rem !important;
        }

        .dish-card > div:last-child, .glass-card > div:last-child {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
    }

    @if(!$isFullWidth)
    @media (min-width: 601px) {
        body {
            background-color: {{ $isDark ? '#090d16' : '#f1f5f9' }} !important;
            background-image: {{ $isDark 
                ? 'radial-gradient(circle at 50% 0%, rgba(225, 29, 72, 0.12) 0%, transparent 55%), radial-gradient(circle at 80% 100%, rgba(79, 70, 229, 0.08) 0%, transparent 55%)' 
                : 'radial-gradient(circle at 50% 0%, rgba(225, 29, 72, 0.05) 0%, transparent 55%), radial-gradient(circle at 80% 100%, rgba(79, 70, 229, 0.04) 0%, transparent 55%)' }} !important;
            background-attachment: fixed !important;
            min-height: 100vh;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start !important;
            margin: 0 !important;
            overflow-x: hidden;
        }

        .storefront-app-shell {
            width: 100% !important;
            max-width: var(--desktop-max-width) !important;
            min-height: 100vh;
            background-color: var(--bg-main) !important;
            box-shadow: 0 0 60px rgba(0, 0, 0, {{ $isDark ? '0.5' : '0.12' }}), 0 25px 60px -15px rgba(0, 0, 0, {{ $isDark ? '0.6' : '0.18' }}) !important;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            position: relative;
            box-sizing: border-box;
            margin: 0 auto !important;
        }

        /* Fixed / Floating Top Notification Banners */
        .toast-notification {
            max-width: var(--desktop-max-width) !important;
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) !important;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            box-sizing: border-box;
            z-index: 100000 !important;
        }
        .toast-anim-start {
            transform: translate(-50%, -100%) !important;
        }
        .toast-anim-end {
            transform: translate(-50%, 0) !important;
        }

        /* Fixed Bottom Navigation & Trackers */
        .storefront-bottom-nav-container {
            max-width: var(--desktop-max-width) !important;
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) !important;
            width: 100% !important;
        }

        .storefront-bottom-nav {
            max-width: var(--desktop-max-width) !important;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            border-radius: 20px 20px 0 0 !important;
        }

        .active-order-floating-pill {
            max-width: calc(var(--desktop-max-width) - 1.5rem) !important;
            margin: 0 auto 0.6rem auto !important;
            left: auto !important;
            transform: none !important;
        }

        .ai-waiter-floating-bubble {
            left: auto !important;
            right: calc(50% - (var(--desktop-max-width) / 2) + 1.25rem) !important;
            transform: none !important;
        }

        /* Modals & Bottom Sheets */
        .cart-modal-sheet {
            max-width: var(--desktop-max-width) !important;
            margin: 0 auto !important;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
        }

        /* Sticky Navigation Bar */
        .category-scroll {
            max-width: 100%;
        }
    }

    /* Wide Desktop Adaptive Layout (Expands from mobile phone constraint on modern large monitors) */
    @if(!$isExplicitMobileNarrow)
    @media (min-width: 992px) {
        .storefront-app-shell {
            max-width: min(1180px, 94vw) !important;
            border-radius: 20px;
            margin: 1.5rem auto 3rem auto !important;
            border: 1px solid var(--border-color) !important;
        }

        .toast-notification,
        .storefront-bottom-nav-container,
        .storefront-bottom-nav {
            max-width: min(1180px, 94vw) !important;
        }

        .cart-modal-sheet {
            max-width: 620px !important;
            border-radius: 24px !important;
            bottom: auto !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.45) !important;
            max-height: 88vh !important;
        }

        .ai-waiter-floating-bubble {
            right: calc(50% - (min(1180px, 94vw) / 2) + 1.5rem) !important;
        }

        .dishes-grid {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)) !important;
            gap: 1.5rem !important;
        }
    }
    @endif
    @endif
</style>
