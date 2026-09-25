<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $siteName = \App\Models\SystemSetting::getSiteName();
        $seoTitle = \App\Models\SystemSetting::getSeoTitle(app()->getLocale()) ?? ($siteName . ' — ' . __('hero_title'));
        $seoDesc = \App\Models\SystemSetting::getSeoDescription(app()->getLocale()) ?? __('hero_subtitle');
        $seoKeywords = \App\Models\SystemSetting::getSeoKeywords();
        $siteFavicon = \App\Models\SystemSetting::getFavicon();
        $siteLogoDark = \App\Models\SystemSetting::getLogoDark();
        $ogImage = \App\Models\SystemSetting::getOgImage();
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    @if($seoKeywords)
        <meta name="keywords" content="{{ $seoKeywords }}">
    @endif

    <!-- Dynamic Favicon -->
    <link rel="icon" type="image/png" href="{{ $siteFavicon }}">
    <link rel="apple-touch-icon" href="{{ $siteFavicon }}">

    <!-- Open Graph / Facebook / Telegram -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    @if($ogImage)
        <meta property="og:image" content="{{ str_starts_with($ogImage, 'http') ? $ogImage : url($ogImage) }}">
    @endif

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    @if($ogImage)
        <meta name="twitter:image" content="{{ str_starts_with($ogImage, 'http') ? $ogImage : url($ogImage) }}">
    @endif

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">

    <style>
        :root {
            --bg-main: #070913;
            --bg-card: rgba(15, 23, 42, 0.7);
            --bg-card-hover: rgba(30, 41, 59, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(245, 158, 11, 0.4);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-amber: #f59e0b;
            --accent-red: #ef4444;
            --accent-emerald: #10b981;
            --accent-cyan: #06b6d4;
            --accent-gradient: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            --accent-gradient-cyan: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            --shadow-card: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            --shadow-glow: 0 0 35px rgba(245, 158, 11, 0.25);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow Backgrounds */
        .ambient-glow-1 {
            position: absolute;
            top: 0;
            left: 20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.12) 0%, rgba(239, 68, 68, 0.04) 50%, transparent 70%);
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
        }
        .ambient-glow-2 {
            position: absolute;
            top: 1400px;
            right: 5%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.1) 0%, rgba(59, 130, 246, 0.03) 50%, transparent 70%);
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* Top Sticky Header */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background: rgba(7, 9, 19, 0.82);
            border-bottom: 1px solid var(--border-glass);
            transition: all 0.3s ease;
        }
        .nav-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 76px;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: var(--text-primary);
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--accent-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
        }
        .brand-text {
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .brand-text span {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.75rem;
            list-style: none;
        }
        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .nav-links a:hover {
            color: var(--text-primary);
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Language Switcher Pill */
        .lang-switch {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            border-radius: 30px;
            padding: 3px;
        }
        .lang-btn {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .lang-btn.active {
            background: var(--accent-amber);
            color: #000000;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .btn-login {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.55rem 1rem;
            transition: color 0.2s;
        }
        .btn-login:hover {
            color: var(--text-primary);
        }
        .btn-register-top {
            background: var(--accent-gradient);
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            padding: 0.65rem 1.35rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            white-space: nowrap;
        }
        .btn-register-top:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
        }

        /* Mobile Hamburger */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Hero Section */
        .hero-section {
            padding: 5rem 0 4rem;
            position: relative;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 3.5rem;
            align-items: center;
        }
        .hero-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            color: var(--accent-amber);
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            margin-bottom: 1.5rem;
        }
        .hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: 3.25rem;
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 1.5rem;
        }
        .hero-title .gradient-text {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--text-secondary);
            line-height: 1.65;
            margin-bottom: 2.25rem;
            max-width: 620px;
        }
        .hero-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }
        .btn-cta-primary {
            background: var(--accent-gradient);
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
            transition: all 0.25s ease;
        }
        .btn-cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.55);
        }
        .btn-cta-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            text-decoration: none;
            padding: 1rem 1.75rem;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            transition: all 0.2s ease;
        }
        .btn-cta-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        .hero-trust-list {
            display: flex;
            align-items: center;
            gap: 1.75rem;
            flex-wrap: wrap;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .trust-item i {
            color: var(--accent-emerald);
        }

        /* 3D Phone Mockup Container */
        .mockup-wrapper {
            position: relative;
            display: flex;
            justify-content: center;
        }
        .phone-frame {
            width: 320px;
            background: #0f172a;
            border: 2px solid rgba(255, 255, 255, 0.12);
            border-radius: 36px;
            padding: 1.25rem;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8), 0 0 40px rgba(245, 158, 11, 0.15);
            position: relative;
            overflow: hidden;
            transform: perspective(1000px) rotateY(-5deg) rotateX(4deg);
            transition: transform 0.4s ease;
        }
        .phone-frame:hover {
            transform: perspective(1000px) rotateY(0deg) rotateX(0deg);
        }
        .phone-notch {
            width: 120px;
            height: 18px;
            background: #050811;
            border-radius: 0 0 14px 14px;
            margin: -1.25rem auto 1rem;
        }
        .phone-header-venue {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-glass);
        }
        .phone-venue-name {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .phone-table-badge {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .phone-dish-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--border-glass);
            border-radius: 18px;
            padding: 0.85rem;
            margin-bottom: 1rem;
        }
        .phone-dish-img {
            width: 100%;
            height: 125px;
            border-radius: 12px;
            object-fit: cover;
            margin-bottom: 0.65rem;
            background: linear-gradient(135deg, #334155, #1e293b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        .phone-dish-title {
            font-size: 0.92rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        .phone-dish-price {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--accent-amber);
            margin-bottom: 0.5rem;
        }

        /* Floating AI Waiter Bubble */
        .ai-floating-bubble {
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.95), rgba(15, 23, 42, 0.95));
            border: 1px solid rgba(139, 92, 246, 0.4);
            border-radius: 16px;
            padding: 0.85rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            margin-bottom: 1rem;
            position: relative;
        }
        .ai-bubble-tag {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: #a78bfa;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }
        .ai-bubble-tag i {
            color: #c084fc;
        }
        .ai-bubble-quote {
            font-size: 0.78rem;
            color: #e2e8f0;
            line-height: 1.4;
            font-style: italic;
        }
        .phone-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        .phone-btn {
            background: var(--accent-gradient);
            border: none;
            color: #fff;
            padding: 0.6rem 0.5rem;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: 700;
            text-align: center;
            cursor: pointer;
        }
        .phone-btn-alt {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border-glass);
            color: #fff;
        }

        /* Section Commons */
        .section {
            padding: 6rem 0;
            position: relative;
        }
        .section-header {
            text-align: center;
            max-width: 750px;
            margin: 0 auto 4rem;
        }
        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            color: var(--accent-amber);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            padding: 0.35rem 0.9rem;
            border-radius: 30px;
            margin-bottom: 1rem;
        }
        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin-bottom: 1rem;
        }
        .section-subtitle {
            font-size: 1.05rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* 1. Comparison Section (Paper vs Digital) */
        .vs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .vs-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .vs-card:hover {
            transform: translateY(-4px);
        }
        .vs-card-paper {
            border-top: 4px solid var(--accent-red);
        }
        .vs-card-qr {
            border-top: 4px solid var(--accent-emerald);
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.05), rgba(15, 23, 42, 0.85));
            border-color: rgba(16, 185, 129, 0.25);
        }
        .vs-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-glass);
        }
        .vs-card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
        }
        .vs-badge-bad {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .vs-badge-good {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .vs-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }
        .vs-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .vs-icon-bad {
            color: #ef4444;
            font-size: 1.1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .vs-icon-good {
            color: #10b981;
            font-size: 1.1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* 2. AI Waiter Feature Section & Live Simulator */
        .ai-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }
        .ai-features-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .ai-feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            gap: 1.25rem;
            transition: all 0.25s ease;
        }
        .ai-feature-card:hover {
            border-color: rgba(139, 92, 246, 0.4);
            transform: translateX(6px);
        }
        .ai-feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        .ai-feature-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
            color: var(--text-primary);
        }
        .ai-feature-desc {
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Simulator Card */
        .simulator-box {
            background: linear-gradient(145deg, #101528, #0c101d);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 28px;
            padding: 2.25rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(139, 92, 246, 0.15);
        }
        .sim-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-glass);
        }
        .sim-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sim-title i {
            color: #c084fc;
        }
        .dish-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
            margin-bottom: 1.75rem;
        }
        .dish-tab-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.65rem 0.85rem;
            border-radius: 12px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s ease;
        }
        .dish-tab-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        .dish-tab-btn.active {
            background: rgba(139, 92, 246, 0.2);
            border-color: #a78bfa;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
        }

        .sim-output-card {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 1.25rem;
            position: relative;
        }
        .sim-recommendation-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #a78bfa;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .sim-recommendation-text {
            font-size: 0.95rem;
            color: #f1f5f9;
            line-height: 1.5;
            font-style: italic;
            margin-bottom: 1rem;
        }
        .sim-upsell-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(16, 185, 129, 0.1);
            border: 1px dashed rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 0.6rem 0.85rem;
            font-size: 0.8rem;
            color: #34d399;
            font-weight: 600;
        }

        /* 3. Interactive ROI Calculator */
        .calculator-card {
            background: linear-gradient(135deg, rgba(20, 26, 46, 0.85), rgba(12, 17, 30, 0.9));
            border: 1px solid var(--border-glass);
            border-radius: 32px;
            padding: 3.5rem;
            box-shadow: var(--shadow-card);
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 3.5rem;
            align-items: center;
        }
        .calc-inputs {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .calc-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .calc-label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            font-weight: 600;
        }
        .calc-value-display {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--accent-amber);
        }
        .calc-slider {
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.15);
            outline: none;
            -webkit-appearance: none;
            appearance: none;
            cursor: pointer;
        }
        .calc-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--accent-amber);
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.6);
            cursor: pointer;
            transition: transform 0.1s;
        }
        .calc-slider::-webkit-slider-thumb:hover {
            transform: scale(1.15);
        }

        .calc-results-box {
            background: rgba(10, 14, 26, 0.8);
            border: 1px solid rgba(245, 158, 11, 0.25);
            border-radius: 24px;
            padding: 2.25rem;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }
        .calc-res-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }
        .calc-main-num {
            font-family: 'Outfit', sans-serif;
            font-size: 2.75rem;
            font-weight: 900;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.1;
            margin-bottom: 0.35rem;
        }
        .calc-main-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
        }
        .calc-sub-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-glass);
            margin-bottom: 2rem;
        }
        .calc-metric-item {
            text-align: center;
        }
        .calc-metric-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: #10b981;
        }
        .calc-metric-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .btn-calc-cta {
            display: block;
            width: 100%;
            background: var(--accent-gradient);
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            padding: 0.85rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
            transition: all 0.2s ease;
        }
        .btn-calc-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
        }

        /* 4. Features Bento Grid */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.75rem;
        }
        .bento-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 2rem;
            transition: transform 0.25s ease, border-color 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .bento-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.2);
            background: var(--bg-card-hover);
        }
        .bento-icon-circle {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }
        .bento-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.65rem;
        }
        .bento-card p {
            font-size: 0.92rem;
            color: var(--text-secondary);
            line-height: 1.55;
        }

        /* 5. Dynamic Pricing Section */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 1.75rem;
            align-items: stretch;
        }
        .pricing-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 28px;
            padding: 2.25rem;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: all 0.3s ease;
        }
        .pricing-card:hover {
            transform: translateY(-6px);
            border-color: rgba(255, 255, 255, 0.25);
        }
        .pricing-card.is-popular {
            border: 2px solid var(--accent-amber);
            background: linear-gradient(180deg, rgba(245, 158, 11, 0.08) 0%, rgba(15, 23, 42, 0.9) 100%);
            box-shadow: 0 20px 40px -10px rgba(245, 158, 11, 0.25);
        }
        .popular-badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent-gradient);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 14px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
            white-space: nowrap;
        }
        .plan-header {
            margin-bottom: 1.5rem;
        }
        .plan-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
        }
        .plan-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            min-height: 40px;
        }
        .plan-price-box {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-glass);
        }
        .plan-price-num {
            font-family: 'Outfit', sans-serif;
            font-size: 2.25rem;
            font-weight: 900;
            color: var(--text-primary);
        }
        .plan-price-unit {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .plan-features-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 2rem;
            flex-grow: 1;
        }
        .plan-feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        .plan-feature-item i {
            color: var(--accent-emerald);
            font-size: 0.85rem;
            margin-top: 3px;
            flex-shrink: 0;
        }
        .btn-plan-cta {
            display: block;
            width: 100%;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            padding: 0.85rem;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        .btn-plan-primary {
            background: var(--accent-gradient);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
        }
        .btn-plan-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
        }
        .btn-plan-secondary {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
        }
        .btn-plan-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        /* 6. FAQ Accordion */
        .faq-accordion {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .faq-item {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 18px;
            overflow: hidden;
            transition: border-color 0.2s ease;
        }
        .faq-item:hover {
            border-color: rgba(255, 255, 255, 0.2);
        }
        .faq-question {
            padding: 1.35rem 1.75rem;
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            user-select: none;
        }
        .faq-question i {
            color: var(--accent-amber);
            font-size: 0.95rem;
            transition: transform 0.25s ease;
        }
        .faq-item.open .faq-question i {
            transform: rotate(180deg);
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0, 1, 0, 1);
            padding: 0 1.75rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .faq-item.open .faq-answer {
            max-height: 500px;
            padding-bottom: 1.5rem;
            transition: max-height 0.35s ease-in-out;
        }

        /* 7. Final High-Impact CTA Banner */
        .final-cta-section {
            padding: 5rem 0;
        }
        .final-cta-card {
            background: radial-gradient(circle at center, #1e1b4b 0%, #0f172a 70%, #070913 100%);
            border: 1px solid rgba(245, 158, 11, 0.35);
            border-radius: 36px;
            padding: 4.5rem 2rem;
            text-align: center;
            box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.7), 0 0 50px rgba(245, 158, 11, 0.2);
            position: relative;
            overflow: hidden;
        }
        .final-cta-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.85rem;
            font-weight: 900;
            margin-bottom: 1.25rem;
            line-height: 1.2;
        }
        .final-cta-subtitle {
            font-size: 1.15rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 2.5rem;
        }
        .final-cta-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        .btn-whatsapp {
            background: #25d366;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            padding: 0.9rem 1.65rem;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.35);
            transition: all 0.2s ease;
        }
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.5);
        }
        .btn-telegram {
            background: #229ed9;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            padding: 0.9rem 1.65rem;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 15px rgba(34, 158, 217, 0.35);
            transition: all 0.2s ease;
        }
        .btn-telegram:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 158, 217, 0.5);
        }

        /* Footer */
        .site-footer {
            border-top: 1px solid var(--border-glass);
            background: #04060c;
            padding: 4.5rem 0 2rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1.5fr 1fr;
            gap: 3rem;
            margin-bottom: 3.5rem;
        }
        .footer-brand p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-top: 1rem;
            line-height: 1.6;
            max-width: 320px;
        }
        .footer-column h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
        }
        .footer-column ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .footer-column a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.2s;
        }
        .footer-column a:hover {
            color: var(--text-primary);
        }
        .contact-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .contact-entry {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.88rem;
            color: var(--text-secondary);
        }
        .contact-entry i {
            color: var(--accent-amber);
            font-size: 1rem;
            width: 20px;
        }
        .social-row {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .social-link {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .social-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .nav-links {
                display: none;
            }
            .hero-grid, .vs-grid, .ai-grid, .calculator-card, .footer-grid {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
            .bento-grid {
                grid-template-columns: 1fr 1fr;
            }
            .hero-title {
                font-size: 2.5rem;
            }
            .calculator-card {
                padding: 2rem;
            }
        }
        @media (max-width: 640px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }
            .hero-title {
                font-size: 2.1rem;
            }
            .hero-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-cta-primary, .btn-cta-secondary {
                justify-content: center;
                text-align: center;
            }
            .nav-actions .btn-login {
                display: none;
            }
            .final-cta-title {
                font-size: 2rem;
            }
            .calc-sub-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Ambient Glow Blobs -->
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <!-- 1. Sticky Navigation Header -->
    <header class="site-header">
        <div class="container">
            <div class="nav-wrapper">
                <a href="{{ route('landing') }}" class="brand-logo" style="text-decoration: none; display: flex; align-items: center; gap: 0.65rem;">
                    @if($siteLogoDark)
                        <img src="{{ $siteLogoDark }}" alt="{{ $siteName }}" style="height: 38px; max-width: 170px; object-fit: contain;">
                    @else
                        <div class="logo-icon">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div class="brand-text">{{ $siteName }}</div>
                    @endif
                </a>

                <ul class="nav-links">
                    <li><a href="#comparison">{{ __('nav_comparison') }}</a></li>
                    <li><a href="#ai-waiter">{{ __('nav_ai') }}</a></li>
                    <li><a href="#calculator">{{ __('nav_calculator') }}</a></li>
                    <li><a href="#features">{{ __('nav_features') }}</a></li>
                    <li><a href="#pricing">{{ __('nav_pricing') }}</a></li>
                    <li><a href="#faq">{{ __('nav_faq') }}</a></li>
                    <li><a href="#contacts">{{ __('nav_contacts') }}</a></li>
                </ul>

                <div class="nav-actions">
                    <!-- Language Switcher -->
                    <div class="lang-switch">
                        <a href="{{ route('lang.switch', 'hy') }}" class="lang-btn {{ app()->getLocale() === 'hy' ? 'active' : '' }}">HY</a>
                        <a href="{{ route('lang.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                    </div>

                    @auth
                        @if(Auth::user()->isSuperAdmin())
                            <a href="{{ route('superadmin.dashboard') }}" class="btn-register-top">
                                <i class="fa-solid fa-gauge-high"></i> SuperAdmin
                            </a>
                        @else
                            <a href="{{ route('admin.dashboard') }}" class="btn-register-top">
                                <i class="fa-solid fa-gauge-high"></i> {{ __('Dashboard') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-login">{{ __('nav_login') }}</a>
                        <a href="{{ route('register.show') }}" class="btn-register-top">
                            {{ __('nav_register_cta') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- 2. Hero Section (Hook & Value Prop) -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-grid">
                <div>
                    <div class="hero-badge-pill">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>{{ __('hero_badge') }}</span>
                    </div>

                    <h1 class="hero-title">
                        @if(!empty($settings['hero_title_'.app()->getLocale()]))
                            {{ $settings['hero_title_'.app()->getLocale()] }}
                        @else
                            {!! app()->getLocale() === 'hy' 
                                ? 'Ավելացրեք ռեստորանի շրջանառությունը <span class="gradient-text">+30%-ով</span> խելացի QR մենյուի և AI-ի շնորհիվ'
                                : 'Boost Restaurant Revenue by <span class="gradient-text">+30%</span> with Smart QR Menu & AI Waiter' 
                            !!}
                        @endif
                    </h1>

                    <p class="hero-subtitle">
                        @if(!empty($settings['hero_subtitle_'.app()->getLocale()]))
                            {{ $settings['hero_subtitle_'.app()->getLocale()] }}
                        @else
                            {{ __('hero_subtitle') }}
                        @endif
                    </p>

                    <div class="hero-actions">
                        <a href="{{ route('register.show') }}" class="btn-cta-primary">
                            <span>{{ __('hero_cta_primary') }}</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                        @php
                            $demoSlug = $settings['demo_vendor_slug'] ?? 'bistro-yerevan';
                        @endphp
                        <a href="{{ route('client.menu', ['vendor_slug' => $demoSlug]) }}" target="_blank" class="btn-cta-secondary">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                            <span>{{ __('hero_cta_secondary') }}</span>
                        </a>
                    </div>

                    <div class="hero-trust-list">
                        <div class="trust-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>{{ __('hero_trust_badge1') }}</span>
                        </div>
                        <div class="trust-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>{{ __('hero_trust_badge2') }}</span>
                        </div>
                        <div class="trust-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>{{ __('hero_trust_badge3') }}</span>
                        </div>
                    </div>
                </div>

                <!-- 3D Phone Mockup Visual -->
                <div class="mockup-wrapper">
                    <div class="phone-frame">
                        <div class="phone-notch"></div>
                        <div class="phone-header-venue">
                            <span class="phone-venue-name">{{ __('hero_mockup_restaurant') }}</span>
                            <span class="phone-table-badge">Active</span>
                        </div>

                        <div class="phone-dish-card">
                            <div class="phone-dish-img">
                                🥩
                            </div>
                            <div class="phone-dish-title">Ribeye Steak Prime Black Angus</div>
                            <div class="phone-dish-price">7 900 AMD</div>
                        </div>

                        <!-- AI Floating Bubble Simulation -->
                        <div class="ai-floating-bubble">
                            <div class="ai-bubble-tag">
                                <i class="fa-solid fa-robot"></i> AI Waiter Recommendation
                            </div>
                            <div class="ai-bubble-quote">
                                {{ __('hero_mockup_ai_bubble') }}
                            </div>
                        </div>

                        <div class="phone-actions">
                            <button class="phone-btn">{{ __('hero_mockup_add_order') }}</button>
                            <button class="phone-btn phone-btn-alt">{{ __('hero_mockup_call_waiter') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Comparison Section (Paper Menu vs elab QR Menu) -->
    <section id="comparison" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('nav_comparison') }}</div>
                <h2 class="section-title">{{ __('vs_title') }}</h2>
                <p class="section-subtitle">{{ __('vs_subtitle') }}</p>
            </div>

            <div class="vs-grid">
                <!-- Old Paper Menu -->
                <div class="vs-card vs-card-paper">
                    <div class="vs-card-head">
                        <h3 class="vs-card-title">{{ __('vs_paper_title') }}</h3>
                        <span class="vs-badge-bad">{{ __('vs_paper_badge') }}</span>
                    </div>
                    <ul class="vs-list">
                        <li>
                            <i class="fa-solid fa-xmark vs-icon-bad"></i>
                            <span>{{ __('vs_paper_item1') }}</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-xmark vs-icon-bad"></i>
                            <span>{{ __('vs_paper_item2') }}</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-xmark vs-icon-bad"></i>
                            <span>{{ __('vs_paper_item3') }}</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-xmark vs-icon-bad"></i>
                            <span>{{ __('vs_paper_item4') }}</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-xmark vs-icon-bad"></i>
                            <span>{{ __('vs_paper_item5') }}</span>
                        </li>
                    </ul>
                </div>

                <!-- elab Digital QR Menu -->
                <div class="vs-card vs-card-qr">
                    <div class="vs-card-head">
                        <h3 class="vs-card-title">{{ __('vs_qr_title') }}</h3>
                        <span class="vs-badge-good">{{ __('vs_qr_badge') }}</span>
                    </div>
                    <ul class="vs-list">
                        <li>
                            <i class="fa-solid fa-check vs-icon-good"></i>
                            <span>{{ __('vs_qr_item1') }}</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-check vs-icon-good"></i>
                            <span>{{ __('vs_qr_item2') }}</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-check vs-icon-good"></i>
                            <span>{{ __('vs_qr_item3') }}</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-check vs-icon-good"></i>
                            <span>{{ __('vs_qr_item4') }}</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-check vs-icon-good"></i>
                            <span>{{ __('vs_qr_item5') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. AI Waiter & Sommelier Feature Section -->
    <section id="ai-waiter" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('ai_section_badge') }}</div>
                <h2 class="section-title">{{ __('ai_section_title') }}</h2>
                <p class="section-subtitle">{{ __('ai_section_subtitle') }}</p>
            </div>

            <div class="ai-grid">
                <div class="ai-features-column">
                    <div class="ai-feature-card">
                        <div class="ai-feature-icon">
                            <i class="fa-solid fa-wine-glass"></i>
                        </div>
                        <div>
                            <h3 class="ai-feature-title">{{ __('ai_feature1_title') }}</h3>
                            <p class="ai-feature-desc">{{ __('ai_feature1_desc') }}</p>
                        </div>
                    </div>

                    <div class="ai-feature-card">
                        <div class="ai-feature-icon">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <h3 class="ai-feature-title">{{ __('ai_feature2_title') }}</h3>
                            <p class="ai-feature-desc">{{ __('ai_feature2_desc') }}</p>
                        </div>
                    </div>

                    <div class="ai-feature-card">
                        <div class="ai-feature-icon">
                            <i class="fa-solid fa-language"></i>
                        </div>
                        <div>
                            <h3 class="ai-feature-title">{{ __('ai_feature3_title') }}</h3>
                            <p class="ai-feature-desc">{{ __('ai_feature3_desc') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Interactive AI Simulator -->
                <div class="simulator-box">
                    <div class="sim-header">
                        <div class="sim-title">
                            <i class="fa-solid fa-robot"></i>
                            <span>{{ __('ai_interactive_demo_title') }}</span>
                        </div>
                        <span style="font-size: 0.72rem; background: rgba(139, 92, 246, 0.2); color: #c084fc; padding: 2px 8px; border-radius: 12px; font-weight: 700;">LIVE SIM</span>
                    </div>

                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                        {{ __('ai_select_dish_label') }}
                    </p>

                    <div class="dish-tabs">
                        <button class="dish-tab-btn active" onclick="switchDish('steak')">{{ __('ai_dish_steak') }}</button>
                        <button class="dish-tab-btn" onclick="switchDish('pizza')">{{ __('ai_dish_pizza') }}</button>
                        <button class="dish-tab-btn" onclick="switchDish('salad')">{{ __('ai_dish_salad') }}</button>
                        <button class="dish-tab-btn" onclick="switchDish('dessert')">{{ __('ai_dish_dessert') }}</button>
                    </div>

                    <div class="sim-output-card">
                        <div class="sim-recommendation-label">
                            <i class="fa-solid fa-sparkles"></i> {{ __('ai_demo_recommends') }}
                        </div>
                        <div id="sim-text" class="sim-recommendation-text">
                            @if(app()->getLocale() === 'hy')
                                «Տավարի Սթեյքի հյութեղությունը հիանալի կընդգծի Areni Noir պահուստային կարմիր գինին (3 200 AMD) և ջեռոցում բոված հազարաթերթ բանջարեղենը։»
                            @else
                                "To elevate this Prime Ribeye, we recommend pairing it with a glass of Areni Noir Reserve Red Wine and char-grilled asparagus."
                            @endif
                        </div>
                        <div class="sim-upsell-stat">
                            <span><i class="fa-solid fa-arrow-trend-up"></i> +32% Average Check Lift</span>
                            <span>Automatic Cross-Sell</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. Interactive ROI & Savings Calculator -->
    <section id="calculator" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('nav_calculator') }}</div>
                <h2 class="section-title">{{ __('calc_title') }}</h2>
                <p class="section-subtitle">{{ __('calc_subtitle') }}</p>
            </div>

            <div class="calculator-card">
                <div class="calc-inputs">
                    <div class="calc-group">
                        <div class="calc-label-row">
                            <span>{{ __('calc_tables_label') }}</span>
                            <span id="tables-val" class="calc-value-display">20</span>
                        </div>
                        <input type="range" id="tables-slider" class="calc-slider" min="5" max="80" value="20" step="1" oninput="calculateROI()">
                    </div>

                    <div class="calc-group">
                        <div class="calc-label-row">
                            <span>{{ __('calc_guests_label') }}</span>
                            <span id="guests-val" class="calc-value-display">8</span>
                        </div>
                        <input type="range" id="guests-slider" class="calc-slider" min="2" max="25" value="8" step="1" oninput="calculateROI()">
                    </div>

                    <div class="calc-group">
                        <div class="calc-label-row">
                            <span>{{ __('calc_check_label') }}</span>
                            <span id="check-val" class="calc-value-display">8 500 AMD</span>
                        </div>
                        <input type="range" id="check-slider" class="calc-slider" min="3000" max="30000" value="8500" step="500" oninput="calculateROI()">
                    </div>
                </div>

                <div class="calc-results-box">
                    <div class="calc-res-title">{{ __('calc_result_title') }}</div>
                    <div id="calc-revenue" class="calc-main-num">+420 000 ֏</div>
                    <div class="calc-main-desc">{{ __('calc_extra_revenue') }}</div>

                    <div class="calc-sub-metrics">
                        <div class="calc-metric-item">
                            <div id="calc-print" class="calc-metric-value">360 000 ֏</div>
                            <div class="calc-metric-label">{{ __('calc_print_saved') }}</div>
                        </div>
                        <div class="calc-metric-item">
                            <div class="calc-metric-value">2.2x</div>
                            <div class="calc-metric-label">{{ __('calc_table_turnover') }}</div>
                        </div>
                    </div>

                    <a href="{{ route('register.show') }}" class="btn-calc-cta">
                        {{ __('calc_cta') }} <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Features Grid (Bento Style) -->
    <section id="features" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('nav_features') }}</div>
                <h2 class="section-title">{{ __('features_title') }}</h2>
                <p class="section-subtitle">{{ __('features_subtitle') }}</p>
            </div>

            <div class="bento-grid">
                <div class="bento-card">
                    <div>
                        <div class="bento-icon-circle" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                            <i class="fa-solid fa-mobile-screen"></i>
                        </div>
                        <h3>{{ __('feat_pwa_title') }}</h3>
                        <p>{{ __('feat_pwa_desc') }}</p>
                    </div>
                </div>

                <div class="bento-card">
                    <div>
                        <div class="bento-icon-circle" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                            <i class="fa-solid fa-bell-concierge"></i>
                        </div>
                        <h3>{{ __('feat_kds_title') }}</h3>
                        <p>{{ __('feat_kds_desc') }}</p>
                    </div>
                </div>

                <div class="bento-card">
                    <div>
                        <div class="bento-icon-circle" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                            <i class="fa-solid fa-hand"></i>
                        </div>
                        <h3>{{ __('feat_call_title') }}</h3>
                        <p>{{ __('feat_call_desc') }}</p>
                    </div>
                </div>

                <div class="bento-card">
                    <div>
                        <div class="bento-icon-circle" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </div>
                        <h3>{{ __('feat_ai_import_title') }}</h3>
                        <p>{{ __('feat_ai_import_desc') }}</p>
                    </div>
                </div>

                <div class="bento-card">
                    <div>
                        <div class="bento-icon-circle" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                            <i class="fa-solid fa-palette"></i>
                        </div>
                        <h3>{{ __('feat_branding_title') }}</h3>
                        <p>{{ __('feat_branding_desc') }}</p>
                    </div>
                </div>

                <div class="bento-card">
                    <div>
                        <div class="bento-icon-circle" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
                            <i class="fa-solid fa-print"></i>
                        </div>
                        <h3>{{ __('feat_qr_studio_title') }}</h3>
                        <p>{{ __('feat_qr_studio_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. Dynamic Subscription Plans (Pricing) -->
    <section id="pricing" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('nav_pricing') }}</div>
                <h2 class="section-title">{{ __('pricing_title') }}</h2>
                <p class="section-subtitle">{{ __('pricing_subtitle') }}</p>
            </div>

            <div class="pricing-grid">
                @foreach($plans as $plan)
                    @php
                        $isPopular = ($plan->slug === 'pro');
                    @endphp
                    <div class="pricing-card {{ $isPopular ? 'is-popular' : '' }}">
                        @if($isPopular)
                            <div class="popular-badge">{{ __('pricing_popular_badge') }}</div>
                        @endif

                        <div class="plan-header">
                            <h3 class="plan-name">{{ $plan->name }}</h3>
                            <p class="plan-desc">{{ $plan->description }}</p>
                        </div>

                        <div class="plan-price-box">
                            @if($plan->is_custom || $plan->price <= 0)
                                <div class="plan-price-num" style="font-size: 1.75rem;">
                                    {{ app()->getLocale() === 'hy' ? 'Պայմանագրային' : 'Custom' }}
                                </div>
                                <div class="plan-price-unit">{{ app()->getLocale() === 'hy' ? 'Անհատական լուծում' : 'Bespoke integration' }}</div>
                            @else
                                <span class="plan-price-num">{{ number_format($plan->price, 0, '.', ' ') }}</span>
                                <span class="plan-price-unit">AMD / {{ $plan->billing_interval === 'yearly' ? (app()->getLocale() === 'hy' ? 'տարի' : 'year') : (app()->getLocale() === 'hy' ? 'ամիս' : 'month') }}</span>
                            @endif
                        </div>

                        <ul class="plan-features-list">
                            @if(is_array($plan->features))
                                @foreach($plan->features as $feature)
                                    <li class="plan-feature-item">
                                        <i class="fa-solid fa-check"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            @endif
                            <li class="plan-feature-item" style="color: var(--accent-amber); font-weight: 600;">
                                <i class="fa-solid fa-bolt" style="color: var(--accent-amber);"></i>
                                <span>{{ __('pricing_plan_trial') }}</span>
                            </li>
                        </ul>

                        <div>
                            @if($plan->is_custom)
                                <a href="#contacts" class="btn-plan-cta btn-plan-secondary">
                                    {{ __('pricing_cta_custom') }}
                                </a>
                            @else
                                <a href="{{ route('register.show', ['plan' => $plan->slug]) }}" class="btn-plan-cta {{ $isPopular ? 'btn-plan-primary' : 'btn-plan-secondary' }}">
                                    {{ __('pricing_cta_start') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- 8. FAQ Section -->
    <section id="faq" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('nav_faq') }}</div>
                <h2 class="section-title">{{ __('faq_title') }}</h2>
                <p class="section-subtitle">{{ __('faq_subtitle') }}</p>
            </div>

            <div class="faq-accordion">
                <div class="faq-item open">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>{{ __('faq_q1') }}</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>{{ __('faq_a1') }}</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>{{ __('faq_q2') }}</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>{{ __('faq_a2') }}</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>{{ __('faq_q3') }}</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>{{ __('faq_a3') }}</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>{{ __('faq_q4') }}</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>{{ __('faq_a4') }}</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>{{ __('faq_q5') }}</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>{{ __('faq_a5') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 9. Final High-Impact CTA Banner -->
    <section class="final-cta-section">
        <div class="container">
            <div class="final-cta-card">
                <h2 class="final-cta-title">
                    {!! app()->getLocale() === 'hy'
                        ? 'Պատրա՞ստ եք ռեստորանը տեղափոխել <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">նոր մակարդակ</span>'
                        : 'Ready to Transform Your <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Dining Experience</span>?' 
                    !!}
                </h2>
                <p class="final-cta-subtitle">
                    {{ __('cta_final_subtitle') }}
                </p>

                <div class="final-cta-buttons">
                    <a href="{{ route('register.show') }}" class="btn-cta-primary" style="padding: 1.1rem 2.5rem; font-size: 1.15rem;">
                        <span>{{ __('cta_final_btn') }}</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    @php
                        $rawPhone = $settings['contact_phone'] ?? '+37455776066';
                        $cleanPhone = preg_replace('/[^0-9]/', '', $settings['contact_whatsapp'] ?? $rawPhone);
                        $tgHandle = str_replace('@', '', $settings['contact_telegram'] ?? '+37455776066');
                    @endphp

                    <a href="https://wa.me/{{ $cleanPhone }}?text=Hello%2C%20I%20am%20interested%20in%20elab.menu%20QR%20system" target="_blank" class="btn-whatsapp">
                        <i class="fa-brands fa-whatsapp" style="font-size: 1.25rem;"></i>
                        <span>WhatsApp</span>
                    </a>

                    <a href="https://t.me/{{ $tgHandle }}" target="_blank" class="btn-telegram">
                        <i class="fa-brands fa-telegram" style="font-size: 1.25rem;"></i>
                        <span>Telegram</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 10. Footer Section with SuperAdmin Contacts -->
    <footer id="contacts" class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="{{ route('landing') }}" class="brand-logo" style="text-decoration: none; display: flex; align-items: center; gap: 0.65rem;">
                        @if($siteLogoDark)
                            <img src="{{ $siteLogoDark }}" alt="{{ $siteName }}" style="height: 36px; max-width: 160px; object-fit: contain;">
                        @else
                            <div class="logo-icon">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                            <div class="brand-text">{{ $siteName }}</div>
                        @endif
                    </a>
                    <p>{{ \App\Models\SystemSetting::getSiteTagline() ?: __('footer_description') }}</p>
                </div>

                <div class="footer-column">
                    <h4>{{ __('footer_quick_links') }}</h4>
                    <ul>
                        <li><a href="#comparison">{{ __('nav_comparison') }}</a></li>
                        <li><a href="#ai-waiter">{{ __('nav_ai') }}</a></li>
                        <li><a href="#calculator">{{ __('nav_calculator') }}</a></li>
                        <li><a href="#pricing">{{ __('nav_pricing') }}</a></li>
                        <li><a href="#faq">{{ __('nav_faq') }}</a></li>
                        <li><a href="{{ route('login') }}">{{ __('nav_login') }}</a></li>
                        <li><a href="{{ route('register.show') }}">{{ __('nav_register_cta') }}</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>{{ __('footer_contact_us') }}</h4>
                    <div class="contact-list">
                        <div class="contact-entry">
                            <i class="fa-solid fa-phone"></i>
                            <a href="tel:{{ $settings['contact_phone'] ?? '+37455776066' }}">
                                {{ $settings['contact_phone'] ?? '+37455776066' }}
                            </a>
                        </div>
                        <div class="contact-entry">
                            <i class="fa-brands fa-whatsapp" style="color: #25d366;"></i>
                            <a href="https://wa.me/{{ $cleanPhone }}" target="_blank">
                                {{ $settings['contact_whatsapp'] ?? '+37455776066' }} (WhatsApp)
                            </a>
                        </div>
                        <div class="contact-entry">
                            <i class="fa-brands fa-telegram" style="color: #229ed9;"></i>
                            <a href="https://t.me/{{ $tgHandle }}" target="_blank">
                                Telegram: {{ $settings['contact_telegram'] ?? '+37455776066' }}
                            </a>
                        </div>
                        <div class="contact-entry">
                            <i class="fa-solid fa-envelope"></i>
                            <a href="mailto:{{ $settings['contact_email'] ?? 'menu@elab.am' }}">
                                {{ $settings['contact_email'] ?? 'menu@elab.am' }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="footer-column">
                    <h4>{{ __('footer_socials') }}</h4>
                    <div class="social-row">
                        @php
                            $fb = $settings['social_facebook'] ?? 'https://facebook.com/elab.menu';
                            if (!str_starts_with($fb, 'http')) {
                                $fb = 'https://facebook.com/'.ltrim($fb, '@');
                            }
                            $insta = $settings['social_instagram'] ?? 'https://instagram.com/elab.menu';
                            if (!str_starts_with($insta, 'http')) {
                                $insta = 'https://instagram.com/'.ltrim($insta, '@');
                            }
                        @endphp
                        <a href="{{ $fb }}" target="_blank" class="social-link" title="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="{{ $insta }}" target="_blank" class="social-link" title="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                    </div>
                    <div style="margin-top: 1.5rem;">
                        <h4>{{ __('footer_legal') }}</h4>
                        <ul>
                            <li><a href="{{ route('legal.privacy') }}">{{ __('footer_privacy') }}</a></li>
                            <li><a href="{{ route('legal.terms') }}">{{ __('footer_terms') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    @if(!empty($settings['footer_copyright']))
                        {{ $settings['footer_copyright'] }}
                    @else
                        &copy; {{ date('Y') }} {{ $siteName }}. {{ __('footer_rights') }}
                    @endif
                </div>
                <div style="display: flex; gap: 1rem;">
                    <span>Yerevan, Armenia</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Interactive Scripts for Simulator & ROI Calculator -->
    <script>
        // AI Waiter Simulator Data
        const dishData = {
            steak: {
                hy: "«Տավարի Սթեյքի հյութեղությունը հիանալի կընդգծի Areni Noir պահուստային կարմիր գինին (3 200 AMD) և ջեռոցում բոված հազարաթերթ բանջարեղենը։»",
                en: "\"To elevate this Prime Ribeye, we recommend pairing it with a glass of Areni Noir Reserve Red Wine and char-grilled asparagus.\"",
                lift: "+32% Check Lift"
            },
            pizza: {
                hy: "«Դիավոլա պիցցայի կծվությունը իդեալական մեղմացնում է մեր թարմ իտալական ցիտրուսային լիմոնադը (1 400 AMD) կամ սառը սպիտակ գինին։»",
                en: "\"For spicy Diavola pizza, the AI suggests our homemade San Pellegrino citrus spritz or chilled Italian Pinot Grigio.\"",
                lift: "+28% Check Lift"
            },
            salad: {
                hy: "«Թարմ Բուրատա աղցանի հետ հիանալի համադրվում են իտալական տաք ֆոկաչիան (1 200 AMD) և թեթև ռոզե գինին։»",
                en: "\"Pairing Burrata salad with crispy rosemary focaccia and Provence Rosé wine increases satisfaction and check value.\"",
                lift: "+24% Check Lift"
            },
            dessert: {
                hy: "«Շոկոլադե Ֆոնդանի տաք շոկոլադի հետ անհրաժեշտ է թարմ աղացած էսպրեսսո (900 AMD) և վանիլային պաղպաղակի գնդիկ։»",
                en: "\"For Chocolate Fondant, AI automatically offers a freshly roasted Double Espresso and artisanal vanilla gelato.\"",
                lift: "+35% Check Lift"
            }
        };

        const currentLocale = "{{ app()->getLocale() }}";

        function switchDish(dishKey) {
            document.querySelectorAll('.dish-tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const item = dishData[dishKey];
            const textElem = document.getElementById('sim-text');
            textElem.style.opacity = 0;
            setTimeout(() => {
                textElem.innerText = item[currentLocale] || item['en'];
                textElem.style.opacity = 1;
            }, 150);
        }

        // Interactive ROI Calculator Logic
        function calculateROI() {
            const tables = parseInt(document.getElementById('tables-slider').value);
            const guests = parseInt(document.getElementById('guests-slider').value);
            const avgCheck = parseInt(document.getElementById('check-slider').value);

            document.getElementById('tables-val').innerText = tables;
            document.getElementById('guests-val').innerText = guests;
            document.getElementById('check-val').innerText = avgCheck.toLocaleString() + ' AMD';

            // Calculate extra monthly revenue from AI upsell (+18% conservative average) & table turnover
            const dailyOrders = tables * guests;
            const dailyRevenue = dailyOrders * avgCheck;
            const monthlyRevenue = dailyRevenue * 30;
            
            // 18% upsell growth
            const extraMonthlyProfit = Math.round(monthlyRevenue * 0.08); 
            // Printing savings: ~ 15,000 AMD per table per year
            const printingSaved = tables * 18000;

            document.getElementById('calc-revenue').innerText = '+' + extraMonthlyProfit.toLocaleString() + ' ֏';
            document.getElementById('calc-print').innerText = printingSaved.toLocaleString() + ' ֏';
        }

        // FAQ Accordion Toggle
        function toggleFaq(element) {
            const parent = element.parentElement;
            const wasOpen = parent.classList.contains('open');

            // Close all
            document.querySelectorAll('.faq-item').forEach(item => item.classList.remove('open'));

            // Toggle current
            if (!wasOpen) {
                parent.classList.add('open');
            }
        }

        // Run initial calculation
        calculateROI();
    </script>
</body>
</html>
