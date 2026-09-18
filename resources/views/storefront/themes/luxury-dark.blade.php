<!DOCTYPE html>
<html lang="{{ $lang }}" class="{{ $vendor->theme_mode ?? 'dark' }}" data-theme="{{ $vendor->theme_mode ?? 'dark' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="{{ $vendor->primary_color ?? '#f59e0b' }}">
    <link rel="manifest" href="{{ route('client.manifest', ['vendor_slug' => $vendor->slug]) }}">
    <title>{{ $vendor->name }} - Digital Menu</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js & FontAwesome -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {!! $vendor->custom_css ? '<style>' . strip_tags($vendor->custom_css) . '</style>' : '' !!}

    <style>
        :root {
            --primary: {{ $vendor->primary_color ?? '#f59e0b' }};
            --accent: {{ $vendor->accent_color ?? $vendor->primary_color ?? '#f59e0b' }};
            --secondary: {{ $vendor->secondary_color ?? '#4f46e5' }};
            --bg-main: {{ $vendor->bg_color ?? ($vendor->theme_mode == 'light' ? '#f8fafc' : '#09090b') }};
            --bg-card: {{ $vendor->theme_mode == 'light' ? '#ffffff' : '#18181b' }};
            --bg-glass: {{ $vendor->theme_mode == 'light' ? 'rgba(255, 255, 255, 0.9)' : 'rgba(24, 24, 27, 0.85)' }};
            --border-color: {{ $vendor->theme_mode == 'light' ? '#e2e8f0' : 'rgba(255, 255, 255, 0.1)' }};
            --text-main: {{ $vendor->text_color ?? ($vendor->theme_mode == 'light' ? '#0f172a' : '#f4f4f5') }};
            --text-muted: {{ $vendor->theme_mode == 'light' ? '#64748b' : '#a1a1aa' }};
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }
        body { background-color: var(--bg-main); color: var(--text-main); min-height: 100vh; padding-bottom: calc(115px + env(safe-area-inset-bottom, 0.5rem)); }

        /* Cover Header */
        .cover-header {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .cover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.2), var(--bg-main));
        }

        /* Vendor Info Box */
        .vendor-hero {
            margin-top: -60px;
            padding: 0 1.25rem;
            position: relative;
            z-index: 10;
            display: flex;
            align-items: flex-end;
            gap: 1.25rem;
        }
        .vendor-logo {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .vendor-meta h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
        }

        /* Category Nav */
        .category-nav-wrapper {
            position: sticky;
            top: 0;
            z-index: 40;
            background: var(--bg-main);
            border-bottom: none;
            padding: 0.5rem 1rem 0.75rem;
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .category-nav-wrapper::-webkit-scrollbar { display: none; }
        .cat-chip {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .cat-chip.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .category-section {
            scroll-margin-top: 75px;
            margin-bottom: 2rem;
        }

        /* Dish Cards */
        .dish-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            gap: 1rem;
            padding: 0.85rem;
            margin-bottom: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        .dish-card:active { transform: scale(0.98); }
        .dish-img {
            width: 105px;
            height: 105px;
            border-radius: 12px;
            object-fit: cover;
        }
        .dish-content { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .dish-title { font-family: 'Outfit', sans-serif; font-size: 1.05rem; font-weight: 700; color: var(--text-main); }
        .dish-desc { font-size: 0.8rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-top: 0.25rem; }
        .dish-price { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.15rem; color: var(--accent); }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border: none;
            min-width: 42px;
            height: 42px;
            padding: 0 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.25);
        }
        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: scale(0.92);
        }
        .btn-primary i {
            font-size: 1.15rem;
        }

        .input-field {
            width: 100%;
            padding: 0.65rem 0.9rem;
            background: var(--bg-main);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-main);
            font-size: 0.85rem;
            outline: none;
            margin-bottom: 0.65rem;
        }
        .input-field.is-locked {
            cursor: not-allowed !important;
            background: rgba(16, 185, 129, 0.08) !important;
            border-color: rgba(16, 185, 129, 0.35) !important;
            color: var(--text-main) !important;
            font-weight: 700 !important;
            padding-right: 2.25rem !important;
            user-select: none !important;
        }

        /* Toast Notification Feedback - Full Width Top Banner */
        .toast-notification {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 300;
            background: var(--bg-card);
            color: var(--text-main);
            border-bottom: 2px solid var(--primary);
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.5);
            padding: max(0.85rem, env(safe-area-inset-top, 0.85rem)) 1.25rem 0.85rem 1.25rem;
            border-radius: 0 0 16px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            font-weight: 700;
            text-align: center;
            box-sizing: border-box;
            pointer-events: none;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .toast-notification span {
            max-width: 90%;
            word-break: break-word;
        }
        .toast-anim-enter, .toast-anim-leave {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast-anim-start {
            opacity: 0;
            transform: translateY(-100%);
        }
        .toast-anim-end {
            opacity: 1;
            transform: translateY(0);
        }
        .toast-success { border-bottom-color: #10b981; }
        .toast-success i { color: #10b981; font-size: 1.15rem; flex-shrink: 0; }
        .toast-info { border-bottom-color: #f59e0b; }
        .toast-info i { color: #f59e0b; font-size: 1.15rem; flex-shrink: 0; }
        .toast-remove { border-bottom-color: #ef4444; }
        .toast-remove i { color: #ef4444; font-size: 1.15rem; flex-shrink: 0; }
    </style>
</head>
<body x-data="storefrontApp()" x-init="initScrollSpy()">

    <!-- Toast Notification Feedback -->
    <div x-show="toast.show" 
         x-transition:enter="toast-anim-enter"
         x-transition:enter-start="toast-anim-start"
         x-transition:enter-end="toast-anim-end"
         x-transition:leave="toast-anim-leave"
         x-transition:leave-start="toast-anim-end"
         x-transition:leave-end="toast-anim-start"
         class="toast-notification"
         :class="'toast-' + toast.type"
         x-cloak>
        <i :class="toast.icon"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- PWA Install Banner -->
    <div x-show="showPWA" style="background: var(--primary); color: #ffffff; padding: 0.6rem 1rem; font-size: 0.8rem; font-weight: 700; display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fa-solid fa-mobile-screen"></i> Add menu to Home Screen for instant access!</span>
        <button @click="showPWA = false" style="background: none; border: none; color: inherit; font-weight: 800; cursor: pointer;">✕</button>
    </div>

    <!-- Header Cover -->
    <div class="cover-header" style="background-image: url('{{ $vendor->cover_image ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=80' }}');">
        <div class="cover-overlay"></div>
    </div>

    <!-- Vendor Hero -->
    <div class="vendor-hero">
        <img src="{{ $vendor->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80' }}" class="vendor-logo">
        <div class="vendor-meta">
            <h1>{{ $vendor->name }}</h1>
            <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                <span><i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> {{ $location?->name ?? 'Main Branch' }}</span>
                @if($table)
                    <span style="background: var(--primary); color: #ffffff; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 700;">Table {{ $table }}</span>
                @endif
            </div>
        </div>
    </div>


    <!-- Language & Search Bar -->
    <div style="padding: 1.25rem 1.25rem 0.5rem; display: flex; gap: 0.75rem;">
        <div style="flex: 1; position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" x-model="search" placeholder="{{ __('menu.search_placeholder') }}" style="width: 100%; padding: 0.65rem 1rem 0.65rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-main); outline: none; font-size: 0.85rem;">
        </div>

        <select onchange="window.location.href='?lang=' + this.value" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); padding: 0.5rem 0.75rem; border-radius: 12px; font-size: 0.85rem;">
            <option value="hy" {{ $lang == 'hy' ? 'selected' : '' }}>🇦🇲 HY</option>
            <option value="en" {{ $lang == 'en' ? 'selected' : '' }}>🇬🇧 EN</option>
            <option value="ru" {{ $lang == 'ru' ? 'selected' : '' }}>🇷🇺 RU</option>
        </select>
    </div>

    <!-- Category Tabs Navigation (Smooth Scroll + Auto Scrollspy) -->
    <nav class="category-nav-wrapper" id="categoryNavWrapper" style="margin-top: 0.5rem;">
        @foreach($categories as $cat)
            <button id="chip-cat-{{ $cat->id }}" class="cat-chip" :class="{ 'active': activeCat === 'cat-{{ $cat->id }}' }" @click="scrollToCat('cat-{{ $cat->id }}')">
                {{ $cat->getTranslatedName($lang) }}
            </button>
        @endforeach
    </nav>

    <!-- Main Dishes List (Continuous scroll sections) -->
    <div style="padding: 1.25rem;">
        @foreach($categories as $cat)
            <div id="cat-{{ $cat->id }}" class="category-section">
                <h2 style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-main);">
                    {{ $cat->getTranslatedName($lang) }}
                </h2>

                <div>
                    @foreach($cat->products as $prod)
                        @php
                            $prodPayload = [
                                'id' => $prod->id,
                                'name' => $prod->getTranslatedName($lang),
                                'image' => $prod->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80',
                                'description' => $prod->getTranslatedDescription($lang),
                                'base_price' => (float)$prod->getEffectivePrice($location?->id),
                                'variations' => $prod->variations->map(fn($v) => [
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'price' => (float)$v->price,
                                    'is_default' => (bool)$v->is_default
                                ])->values(),
                            ];
                        @endphp
                        <div class="dish-card" x-show='matchesSearch({!! json_encode(mb_strtolower($prod->getTranslatedName($lang))) !!})' @click='selectDish({{ json_encode($prodPayload) }})'>
                            <img src="{{ $prod->image }}" class="dish-img">
                            <div class="dish-content">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; margin-bottom: 0.25rem;">
                                        <div class="dish-title">{{ $prod->getTranslatedName($lang) }}</div>
                                        @if($prod->is_featured)
                                            <span style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); padding: 0.1rem 0.4rem; border-radius: 6px; font-size: 0.65rem; font-weight: 700;">★ FEATURED</span>
                                        @endif
                                        @if(!empty($prod->dietary_tags))
                                            @foreach($prod->dietary_tags as $tag)
                                                <span style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.1rem 0.4rem; border-radius: 6px; font-size: 0.65rem; text-transform: uppercase; font-weight: 700;">{{ str_replace('_', ' ', $tag) }}</span>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="dish-desc">{{ $prod->getTranslatedDescription($lang) }}</div>
                                    @if($prod->calories || $prod->preparation_time_min || ($prod->allergens && $prod->allergens->count() > 0))
                                        <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 0.35rem; font-size: 0.72rem; color: var(--text-muted); flex-wrap: wrap;">
                                            @if($prod->calories)
                                                <span><i class="fa-solid fa-fire" style="color: #f97316;"></i> {{ $prod->calories }} kcal</span>
                                            @endif
                                            @if($prod->preparation_time_min)
                                                <span><i class="fa-solid fa-clock"></i> {{ $prod->preparation_time_min }} {{ __('menu.mins') }}</span>
                                            @endif
                                            @if($prod->allergens && $prod->allergens->count() > 0)
                                                <span><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i>
                                                    {{ __('menu.allergens') }}
                                                    {{ $prod->allergens->pluck('icon')->join(' ') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 0.5rem;">
                                    <div class="dish-price">
                                        @if($prod->variations->count() > 1)
                                            <small style="font-size: 0.72rem; font-weight: 500; color: var(--text-muted);">{{ __('menu.starting_from') }}</small>
                                            {{ number_format($prod->variations->min('price')) }} {{ $vendor->currency }}
                                        @else
                                            {{ number_format($prod->getEffectivePrice($location?->id)) }} {{ $vendor->currency }}
                                        @endif
                                    </div>
                                    
                                    <button class="btn btn-primary" @click.stop='selectDish({{ json_encode($prodPayload) }})' aria-label="{{ __('menu.add_to_cart') }}" title="{{ __('menu.add_to_cart') }}">
                                        @if($prod->variations->count() > 1)
                                            <span>{{ __('menu.select_portion_btn') }}</span>
                                        @else
                                            <i class="fa-solid fa-cart-plus"></i>
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>


    <!-- Variation Selection Modal -->
    <div x-show="showVariationModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(10px); z-index: 110; display: flex; flex-direction: column; justify-content: flex-end;" x-cloak>
        <div style="background: var(--bg-card); border-top: 1px solid var(--border-color); border-radius: 24px 24px 0 0; padding: 1.5rem; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div style="display: flex; gap: 0.85rem; align-items: center;">
                    <img :src="selectedDish?.image" style="width: 65px; height: 65px; object-fit: cover; border-radius: 12px;" alt="dish">
                    <div>
                        <h3 style="font-family: 'Outfit'; font-size: 1.2rem; font-weight: 700; color: var(--text-main);" x-text="selectedDish?.name"></h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" x-text="selectedDish?.description"></p>
                    </div>
                </div>
                <button @click="showVariationModal = false" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer; padding: 0.25rem;">✕</button>
            </div>

            <div style="margin: 1.25rem 0 0.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.65rem;">
                    {{ __('menu.choose_portion') }}
                </label>

                <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                    <template x-for="v in selectedDish?.variations" :key="v.id">
                        <div @click="selectedVariation = v"
                             :style="selectedVariation?.id === v.id ? 'border: 2px solid var(--primary); background: rgba(245, 158, 11, 0.12);' : 'border: 1px solid var(--border-color); background: var(--bg-main);'"
                             style="border-radius: 12px; padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                <div style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--primary); display: flex; align-items: center; justify-content: center;">
                                    <div x-show="selectedVariation?.id === v.id" style="width: 10px; height: 10px; border-radius: 50%; background: var(--primary);"></div>
                                </div>
                                <span style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;" x-text="v.name"></span>
                            </div>
                            <span style="font-weight: 800; font-family: 'Outfit'; color: var(--accent); font-size: 1.05rem;" x-text="Number(v.price).toLocaleString() + ' {{ $vendor->currency }}'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Quantity Selector -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding: 0.75rem 1rem; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 12px;">
                <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);">
                    {{ __('menu.quantity') }}
                </span>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <button type="button" @click="if(variationQty > 1) variationQty--" style="width: 34px; height: 34px; border-radius: 50%; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); font-weight: 800; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">-</button>
                    <span style="font-weight: 800; font-size: 1.1rem; min-width: 24px; text-align: center; color: var(--text-main);" x-text="variationQty">1</span>
                    <button type="button" @click="variationQty++" style="width: 34px; height: 34px; border-radius: 50%; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); font-weight: 800; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">+</button>
                </div>
            </div>

            <!-- Confirm Add to Cart CTA -->
            <button type="button" @click="addSelectedVariationToCart()" style="width: 100%; margin-top: 1.25rem; padding: 0.95rem; background: var(--primary); color: #ffffff; border: none; border-radius: 14px; font-weight: 800; font-size: 1.05rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);">
                <span>
                    <i class="fa-solid fa-cart-plus"></i> {{ __('menu.add_to_cart') }}
                </span>
                <span style="font-family: 'Outfit'; font-size: 1.15rem;" x-text="Number((selectedVariation?.price || selectedDish?.base_price || 0) * variationQty).toLocaleString() + ' {{ $vendor->currency }}'"></span>
            </button>
        </div>
    </div>

    <!-- Cart Modal (Full Screen) -->
    <div x-show="showCartModal" 
         style="position: fixed; inset: 0; width: 100%; height: 100%; height: 100dvh; background: var(--bg-card); z-index: 200; display: flex; flex-direction: column; overflow: hidden;" 
         x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-4">

        <div style="background: var(--bg-card); width: 100%; max-width: 640px; margin: 0 auto; height: 100%; height: 100dvh; display: flex; flex-direction: column; box-sizing: border-box;">
            
            <!-- Sticky Top Header -->
            <div style="padding: max(1rem, env(safe-area-inset-top)) 1.25rem 0.85rem 1.25rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); background: var(--bg-card); position: sticky; top: 0; z-index: 10;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <div style="width: 38px; height: 38px; border-radius: 12px; background: rgba(245, 158, 11, 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                    <h3 style="font-family: 'Outfit'; font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        {{ __('menu.order_summary') }}
                    </h3>
                </div>
                <button type="button" @click="showCartModal = false" style="background: var(--bg-main); border: 1px solid var(--border-color); color: var(--text-main); width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; cursor: pointer; flex-shrink: 0;">
                    ✕
                </button>
            </div>

            <!-- Scrollable Content -->
            <div style="flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 1.25rem 1.25rem calc(2.5rem + env(safe-area-inset-bottom)) 1.25rem;">

            <!-- Empty Cart State -->
            <div x-show="cart.length === 0" style="text-align: center; padding: 2.5rem 1rem 1.5rem;">
                <div style="width: 76px; height: 76px; border-radius: 50%; background: var(--bg-main); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <i class="fa-solid fa-basket-shopping" style="font-size: 2rem; color: var(--text-muted); opacity: 0.5;"></i>
                </div>
                <h4 style="font-family: 'Outfit'; font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.4rem;">
                    {{ __('menu.cart_empty') }}
                </h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); max-width: 280px; margin: 0 auto 1.5rem; line-height: 1.4;">
                    {{ __('menu.cart_empty_desc') }}
                </p>
                <button type="button" @click="showCartModal = false" style="background: var(--primary); color: #fff; border: none; padding: 0.75rem 1.75rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 15px rgba(217, 119, 6, 0.3);">
                    <i class="fa-solid fa-arrow-left"></i> {{ __('menu.browse_menu') }}
                </button>
            </div>

            <!-- Active Cart Items and Checkout -->
            <div x-show="cart.length > 0">
                <!-- Items -->
                <template x-for="(item, idx) in cart" :key="idx">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                        <div>
                            <div style="font-weight: 700; color: var(--text-main);" x-text="item.name"></div>
                            <template x-if="item.variation_name && item.variation_name !== 'Standard' && item.variation_name !== 'Standard Portion'">
                                <div style="font-size: 0.75rem; color: var(--primary); font-weight: 700; margin-top: 0.1rem;" x-text="'• ' + item.variation_name"></div>
                            </template>
                            <div style="font-size: 0.85rem; color: var(--accent); font-family: 'Outfit'; margin-top: 0.15rem;" x-text="Number(item.price).toLocaleString() + ' {{ $vendor->currency }}'"></div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 8px;">
                            <button @click="changeQty(idx, -1)" style="background: none; border: none; color: var(--text-main); font-size: 1rem; cursor: pointer;">-</button>
                            <span x-text="item.qty" style="font-weight: 700; color: var(--text-main);"></span>
                            <button @click="changeQty(idx, 1)" style="background: none; border: none; color: var(--text-main); font-size: 1rem; cursor: pointer;">+</button>
                        </div>
                    </div>
                </template>

                <!-- Order Notes Field -->
                <div style="margin-top: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.4rem;">
                        {{ __('menu.order_notes') }}
                    </label>
                    <textarea x-model="orderNotes" class="input-field" rows="2" style="resize: vertical;" placeholder="{{ __('menu.order_notes_placeholder') }}"></textarea>
                </div>

                <!-- Customer Details Form -->
                <div style="margin-top: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.4rem;">
                        {{ __('menu.customer_details') }}
                    </label>

                    <input type="text" x-model="customerName" class="input-field" placeholder="{{ __('menu.full_name_placeholder') }}">
                    <input type="tel" x-model="customerPhone" class="input-field" placeholder="{{ __('menu.phone_placeholder') }}">
                    <input type="email" x-model="customerEmail" class="input-field" placeholder="{{ __('menu.email_placeholder') }}">
                    <input type="date" x-model="customerBirthdate" class="input-field" placeholder="{{ __('menu.birthdate_placeholder') }}">
                    
                    <div style="position: relative; margin-bottom: 0.65rem;">
                        <input type="text" 
                               x-model="tableNumber" 
                               :readonly="isTableFixed"
                               :class="{ 'is-locked': isTableFixed }"
                               class="input-field" 
                               style="margin-bottom: 0;"
                               placeholder="{{ __('menu.table') }} {{ $table ?? '4' }}">
                        <template x-if="isTableFixed">
                            <span style="position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 0.85rem;" title="Ֆիքսված է QR-ով">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                        </template>
                    </div>
                    <template x-if="isTableFixed">
                        <div style="font-size: 0.72rem; color: #10b981; margin-top: -0.35rem; margin-bottom: 0.65rem; display: flex; align-items: center; gap: 0.35rem;">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Սեղանի համարը ֆիքսված է QR կոդով և փոփոխման ենթակա չէ</span>
                        </div>
                    </template>

                    <!-- Consent & Marketing Opt-in -->
                    <label style="display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.78rem; color: var(--text-muted); cursor: pointer; margin-top: 0.5rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 0.65rem 0.85rem; border-radius: 10px;">
                        <input type="checkbox" x-model="marketingOptIn" style="margin-top: 0.15rem;">
                        <span>
                            {!! __('menu.privacy_consent', [
                                'privacy_link' => '<a href="'.route('legal.privacy').'" target="_blank" style="color: var(--primary); font-weight: 700; text-decoration: underline;">'.__('menu.privacy_policy').'</a>',
                                'terms_link' => '<a href="'.route('legal.terms').'" target="_blank" style="color: var(--primary); font-weight: 700; text-decoration: underline;">'.__('menu.terms_of_service').'</a>'
                            ]) !!}
                        </span>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.25rem;">
                    <button @click="submitOrder('dine_in')" style="width: 100%; padding: 0.85rem; background: var(--primary); color: #ffffff; font-weight: 800; font-size: 1rem; border: none; border-radius: 12px; cursor: pointer;">
                        <i class="fa-solid fa-paper-plane"></i> {{ __('menu.checkout') }}
                    </button>
                    <button @click="submitOrder('whatsapp')" style="width: 100%; padding: 0.85rem; background: #25d366; color: #fff; font-weight: 800; font-size: 1rem; border: none; border-radius: 12px; cursor: pointer;">
                        <i class="fa-brands fa-whatsapp"></i> {{ __('menu.order_via_whatsapp') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    @include('storefront.components.bottom-nav')
    @include('storefront.components.info-modal')
    @include('storefront.components.order-tracker-modal')
    @include('storefront.components.waiter-modal')
    @include('storefront.components.storefront-scripts')
</body>
</html>
