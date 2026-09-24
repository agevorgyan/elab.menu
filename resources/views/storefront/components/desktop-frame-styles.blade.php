@php
    $maxWidth = $vendor->getDesktopMaxWidth();
    $isFullWidth = ($maxWidth === '100%');
    $isDark = ($vendor->theme_mode === 'dark');
@endphp

<style>
    :root {
        --desktop-max-width: {{ $maxWidth }};
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
        }
        .toast-anim-start {
            transform: translate(-50%, -100%) !important;
        }
        .toast-anim-end {
            transform: translate(-50%, 0) !important;
        }

        /* Fixed Bottom Navigation & Trackers */
        .storefront-bottom-nav {
            max-width: var(--desktop-max-width) !important;
            border-left: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            border-radius: 20px 20px 0 0 !important;
        }

        .active-order-floating-pill {
            max-width: calc(var(--desktop-max-width) - 2rem) !important;
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) !important;
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
    @endif
</style>
