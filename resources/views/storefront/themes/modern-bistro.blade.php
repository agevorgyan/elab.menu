<!DOCTYPE html>
<html lang="{{ $lang }}" class="{{ $vendor->theme_mode ?? 'light' }}" data-theme="{{ $vendor->theme_mode ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="{{ $vendor->primary_color ?? '#e11d48' }}">
    <link rel="manifest" href="{{ route('client.manifest', ['vendor_slug' => $vendor->slug]) }}">
    <title>{{ $vendor->name }} - Modern Bistro Digital Menu</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @if(!empty($vendor->custom_css))
        <style>{!! strip_tags($vendor->custom_css) !!}</style>
    @endif
    
    <style>
        :root {
            --primary: {{ $vendor->primary_color ?? '#e11d48' }};
            --accent: {{ $vendor->accent_color ?? '#f59e0b' }};
            --secondary: {{ $vendor->secondary_color ?? '#4f46e5' }};
            --bg-main: {{ $vendor->bg_color ?? ($vendor->theme_mode == 'dark' ? '#0f172a' : '#f8fafc') }};
            --bg-card: {{ $vendor->theme_mode == 'dark' ? '#1e293b' : '#ffffff' }};
            --bg-glass: {{ $vendor->theme_mode == 'dark' ? 'rgba(30, 41, 59, 0.85)' : 'rgba(255, 255, 255, 0.95)' }};
            --border-color: {{ $vendor->theme_mode == 'dark' ? 'rgba(255, 255, 255, 0.1)' : '#e2e8f0' }};
            --text-main: {{ $vendor->text_color ?? ($vendor->theme_mode == 'dark' ? '#f8fafc' : '#0f172a') }};
            --text-muted: {{ $vendor->theme_mode == 'dark' ? '#94a3b8' : '#64748b' }};
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background-color: var(--bg-main); color: var(--text-main); min-height: 100vh; padding-bottom: 110px; }

        .hero-bistro {
            padding: 2rem 1.25rem;
            background: linear-gradient(135deg, var(--bg-card), var(--bg-main));
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .category-scroll {
            position: sticky;
            top: 0;
            z-index: 40;
            background: var(--bg-glass);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 1.25rem;
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .category-scroll::-webkit-scrollbar { display: none; }

        .cat-chip {
            padding: 0.45rem 0.9rem;
            border-radius: 9999px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cat-chip.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .dish-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transition: transform 0.2s;
            cursor: pointer;
        }
        .dish-card:active { transform: scale(0.99); }
        .dish-img {
            width: 95px;
            height: 95px;
            border-radius: 12px;
            object-fit: cover;
        }

        .btn-add {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.4rem 0.85rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .cart-bar {
            position: fixed;
            bottom: 1.25rem;
            left: 1.25rem;
            right: 1.25rem;
            z-index: 50;
            background: var(--primary);
            color: #ffffff;
            padding: 0.85rem 1.25rem;
            border-radius: 16px;
            font-weight: 800;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            cursor: pointer;
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
        .category-section {
            scroll-margin-top: 75px;
        }

        /* Toast Notification Feedback */
        .toast-notification {
            position: fixed;
            top: 1.25rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 250;
            background: var(--bg-card);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            padding: 0.7rem 1.25rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 0.88rem;
            font-weight: 600;
            max-width: 90vw;
            pointer-events: none;
            backdrop-filter: blur(12px);
        }
        .toast-anim-enter, .toast-anim-leave {
            transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast-anim-start {
            opacity: 0;
            transform: translate(-50%, -20px) scale(0.95);
        }
        .toast-anim-end {
            opacity: 1;
            transform: translate(-50%, 0) scale(1);
        }
        .toast-success { border-color: rgba(16, 185, 129, 0.4); }
        .toast-success i { color: #10b981; font-size: 1.05rem; }
        .toast-info { border-color: rgba(59, 130, 246, 0.4); }
        .toast-info i { color: #3b82f6; font-size: 1.05rem; }
        .toast-remove { border-color: rgba(239, 68, 68, 0.4); }
        .toast-remove i { color: #ef4444; font-size: 1.05rem; }
    </style>

    @if(!empty($vendor->custom_css))
        <style>
            {!! $vendor->custom_css !!}
        </style>
    @endif
</head>
<body x-data="modernBistroApp()">

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

    <!-- Header Hero -->
    <div class="hero-bistro">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <img src="{{ $vendor->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=200&q=80' }}" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 2px solid var(--primary);">
            <div>
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--text-main);">{{ $vendor->name }}</h1>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                    <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> {{ $location?->name ?? 'Main Location' }}
                    @if($table)
                        • <span style="background: var(--primary); color: #fff; padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: 700;">Table {{ $table }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Search & Language Bar -->
    <div style="padding: 1rem 1.25rem 0.5rem; display: flex; gap: 0.75rem;">
        <div style="flex: 1; position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" x-model="search" placeholder="{{ __('menu.search_placeholder') }}" style="width: 100%; padding: 0.6rem 1rem 0.6rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); outline: none; font-size: 0.85rem;">
        </div>

        <select onchange="window.location.href='?lang=' + this.value" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); padding: 0.5rem 0.75rem; border-radius: 10px; font-size: 0.85rem;">
            <option value="hy" {{ $lang == 'hy' ? 'selected' : '' }}>🇦🇲 HY</option>
            <option value="en" {{ $lang == 'en' ? 'selected' : '' }}>🇬🇧 EN</option>
            <option value="ru" {{ $lang == 'ru' ? 'selected' : '' }}>🇷🇺 RU</option>
        </select>
    </div>

    <!-- Category Chips -->
    <nav class="category-scroll">
        @foreach($categories as $cat)
            <button id="chip-cat-{{ $cat->id }}" class="cat-chip" :class="{ 'active': activeCat === 'cat-{{ $cat->id }}' }" @click="scrollToCat('cat-{{ $cat->id }}')">
                {{ $cat->getTranslatedName($lang) }}
            </button>
        @endforeach
    </nav>

    <!-- Main Dishes -->
    <div style="padding: 1.25rem;">
        @foreach($categories as $cat)
            <div id="cat-{{ $cat->id }}" class="category-section" style="margin-bottom: 2.5rem;">
                <h2 style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-main); padding-bottom: 0.5rem; border-bottom: 2px solid var(--border-color);">
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
                            <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; margin-bottom: 0.25rem;">
                                        <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-main);">{{ $prod->getTranslatedName($lang) }}</div>
                                        @if($prod->is_featured)
                                            <span style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); padding: 0.1rem 0.4rem; border-radius: 6px; font-size: 0.65rem; font-weight: 700;">★ FEATURED</span>
                                        @endif
                                        @if(!empty($prod->dietary_tags))
                                            @foreach($prod->dietary_tags as $tag)
                                                <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.1rem 0.4rem; border-radius: 6px; font-size: 0.65rem; text-transform: uppercase; font-weight: 700;">{{ str_replace('_', ' ', $tag) }}</span>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $prod->getTranslatedDescription($lang) }}
                                    </div>
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
                                    <div style="font-family: 'Outfit'; font-weight: 800; color: var(--accent); font-size: 1.15rem;">
                                        @if($prod->variations->count() > 1)
                                            <small style="font-size: 0.72rem; font-weight: 500; color: var(--text-muted);">{{ __('menu.starting_from') }}</small>
                                            {{ number_format($prod->variations->min('price')) }} {{ $vendor->currency }}
                                        @else
                                            {{ number_format($prod->getEffectivePrice($location?->id)) }} {{ $vendor->currency }}
                                        @endif
                                    </div>
                                    <button class="btn-add" @click.stop='selectDish({{ json_encode($prodPayload) }})'>
                                        @if($prod->variations->count() > 1)
                                            {{ __('menu.select_portion_btn') }}
                                        @else
                                            {{ __('menu.add_btn') }}
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

    <!-- Floating Tray -->
    <div class="cart-bar" x-show="cart.length > 0" @click="showCartModal = true">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="background: rgba(0,0,0,0.2); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;" x-text="cartTotalCount">0</span>
            <span>
                {{ __('menu.view_cart') }}
            </span>
        </div>
        <div style="font-family: 'Outfit'; font-size: 1.1rem;" x-text="cartTotalPrice + ' {{ $vendor->currency }}'">0 AMD</div>
    </div>

    <!-- Variation Selection Modal -->
    <div x-show="showVariationModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 110; display: flex; flex-direction: column; justify-content: flex-end;" x-cloak>
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
                             :style="selectedVariation?.id === v.id ? 'border: 2px solid var(--primary); background: rgba(225, 29, 72, 0.08);' : 'border: 1px solid var(--border-color); background: var(--bg-main);'"
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
            <button type="button" @click="addSelectedVariationToCart()" style="width: 100%; margin-top: 1.25rem; padding: 0.95rem; background: var(--primary); color: #ffffff; border: none; border-radius: 14px; font-weight: 800; font-size: 1.05rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(225, 29, 72, 0.35);">
                <span>
                    <i class="fa-solid fa-plus"></i> {{ __('menu.add_to_cart') }}
                </span>
                <span style="font-family: 'Outfit'; font-size: 1.15rem;" x-text="Number((selectedVariation?.price || selectedDish?.base_price || 0) * variationQty).toLocaleString() + ' {{ $vendor->currency }}'"></span>
            </button>
        </div>
    </div>

    <!-- Cart Modal -->
    <div x-show="showCartModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); z-index: 100; display: flex; flex-direction: column; justify-content: flex-end;" x-cloak>
        <div style="background: var(--bg-card); border-top: 1px solid var(--border-color); border-radius: 24px 24px 0 0; padding: 1.5rem; max-height: 85vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="font-family: 'Outfit'; font-size: 1.3rem; color: var(--text-main);">
                    {{ __('menu.order_summary') }}
                </h3>
                <button @click="showCartModal = false" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;">✕</button>
            </div>

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
                <button type="button" @click="showCartModal = false" style="background: var(--primary); color: #fff; border: none; padding: 0.75rem 1.75rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 15px rgba(225, 29, 72, 0.3);">
                    <i class="fa-solid fa-arrow-left"></i> {{ __('menu.browse_menu') }}
                </button>
            </div>

            <!-- Active Cart Items and Checkout -->
            <div x-show="cart.length > 0">
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
                    
                    <input type="text" x-model="tableNumber" class="input-field" placeholder="{{ __('menu.table') }} {{ $table ?? '4' }}">

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

    @include('storefront.components.storefront-scripts')
</body>
</html>
