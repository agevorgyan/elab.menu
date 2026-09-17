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
    </style>
</head>
<body x-data="modernBistroApp()">

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
            <input type="text" x-model="search" placeholder="Search menu..." style="width: 100%; padding: 0.6rem 1rem 0.6rem 2.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); outline: none; font-size: 0.85rem;">
        </div>

        <select onchange="window.location.href='?lang=' + this.value" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); padding: 0.5rem 0.75rem; border-radius: 10px; font-size: 0.85rem;">
            <option value="hy" {{ $lang == 'hy' ? 'selected' : '' }}>🇦🇲 HY</option>
            <option value="en" {{ $lang == 'en' ? 'selected' : '' }}>🇬🇧 EN</option>
            <option value="ru" {{ $lang == 'ru' ? 'selected' : '' }}>🇷🇺 RU</option>
        </select>
    </div>

    <!-- Category Chips -->
    <nav class="category-scroll">
        <button class="cat-chip" :class="{ 'active': activeCat === 'all' }" @click="activeCat = 'all'">All Items</button>
        @foreach($categories as $cat)
            <button class="cat-chip" :class="{ 'active': activeCat === 'cat-{{ $cat->id }}' }" @click="activeCat = 'cat-{{ $cat->id }}'">
                {{ $cat->getTranslatedName($lang) }}
            </button>
        @endforeach
    </nav>

    <!-- Main Dishes -->
    <div style="padding: 1.25rem;">
        @foreach($categories as $cat)
            <div x-show="activeCat === 'all' || activeCat === 'cat-{{ $cat->id }}'" style="margin-bottom: 2rem;">
                <h2 style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-main);">
                    {{ $cat->getTranslatedName($lang) }}
                </h2>

                <div>
                    @foreach($cat->products as $prod)
                        <div class="dish-card" x-show="matchesSearch('{{ strtolower($prod->getTranslatedName($lang)) }}')">
                            <img src="{{ $prod->image }}" class="dish-img">
                            <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-main);">{{ $prod->getTranslatedName($lang) }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $prod->getTranslatedDescription($lang) }}
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 0.5rem;">
                                    <div style="font-family: 'Outfit'; font-weight: 800; color: var(--accent); font-size: 1.15rem;">
                                        {{ number_format($prod->getEffectivePrice($location?->id)) }} {{ $vendor->currency }}
                                    </div>
                                    <button class="btn-add" @click="addToCart({{ json_encode($prod) }})">
                                        + Add
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
            <span>View Order Selection</span>
        </div>
        <div style="font-family: 'Outfit'; font-size: 1.1rem;" x-text="cartTotalPrice + ' {{ $vendor->currency }}'">0 AMD</div>
    </div>

    <!-- Cart Modal -->
    <div x-show="showCartModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); z-index: 100; display: flex; flex-direction: column; justify-content: flex-end;" x-cloak>
        <div style="background: var(--bg-card); border-top: 1px solid var(--border-color); border-radius: 24px 24px 0 0; padding: 1.5rem; max-height: 85vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="font-family: 'Outfit'; font-size: 1.3rem; color: var(--text-main);">Order Summary</h3>
                <button @click="showCartModal = false" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;">✕</button>
            </div>

            <template x-for="(item, idx) in cart" :key="idx">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                    <div>
                        <div style="font-weight: 700; color: var(--text-main);" x-text="item.name"></div>
                        <div style="font-size: 0.8rem; color: var(--accent);" x-text="item.price + ' AMD'"></div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 8px;">
                        <button @click="changeQty(idx, -1)" style="background: none; border: none; color: var(--text-main); font-size: 1rem; cursor: pointer;">-</button>
                        <span x-text="item.qty" style="font-weight: 700; color: var(--text-main);"></span>
                        <button @click="changeQty(idx, 1)" style="background: none; border: none; color: var(--text-main); font-size: 1rem; cursor: pointer;">+</button>
                    </div>
                </div>
            </template>

            <div style="margin-top: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Table Number / Customer Name</label>
                <input type="text" x-model="customerName" placeholder="e.g. Table {{ $table ?? '4' }} or Armen" style="width: 100%; padding: 0.65rem; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main);">
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;">
                <button @click="submitOrder('dine_in')" style="width: 100%; padding: 0.85rem; background: var(--primary); color: #ffffff; font-weight: 800; font-size: 1rem; border: none; border-radius: 12px; cursor: pointer;">
                    <i class="fa-solid fa-paper-plane"></i> Send Order to Kitchen
                </button>
                <button @click="submitOrder('whatsapp')" style="width: 100%; padding: 0.85rem; background: #25d366; color: #fff; font-weight: 800; font-size: 1rem; border: none; border-radius: 12px; cursor: pointer;">
                    <i class="fa-brands fa-whatsapp"></i> Order via WhatsApp
                </button>
            </div>
        </div>
    </div>

    <script>
        function modernBistroApp() {
            return {
                activeCat: 'all',
                search: '',
                cart: [],
                showCartModal: false,
                customerName: '{{ $table ? "Table " . $table : "" }}',
                
                matchesSearch(title) {
                    if (!this.search) return true;
                    return title.includes(this.search.toLowerCase());
                },
                addToCart(prod) {
                    let existing = this.cart.find(c => c.id === prod.id);
                    if (existing) {
                        existing.qty++;
                    } else {
                        this.cart.push({ id: prod.id, name: prod.name, price: prod.price, qty: 1 });
                    }
                },
                changeQty(idx, delta) {
                    this.cart[idx].qty += delta;
                    if (this.cart[idx].qty <= 0) this.cart.splice(idx, 1);
                },
                get cartTotalCount() {
                    return this.cart.reduce((a, b) => a + b.qty, 0);
                },
                get cartTotalPrice() {
                    return this.cart.reduce((a, b) => a + (b.price * b.qty), 0);
                },
                async submitOrder(type) {
                    if (this.cart.length === 0) return;
                    const res = await fetch('{{ route("client.order.submit", ["vendor_slug" => $vendor->slug]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({
                            location_id: {{ $location?->id ?? 1 }},
                            table_number: '{{ $table ?? "Table 4" }}',
                            type: type,
                            customer_name: this.customerName || 'Guest',
                            items: this.cart.map(c => ({ product_id: c.id, quantity: c.qty }))
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (data.whatsapp_url && type === 'whatsapp') {
                            window.location.href = data.whatsapp_url;
                        } else {
                            alert('🎉 Order #' + data.order_number + ' submitted successfully!');
                            this.cart = [];
                            this.showCartModal = false;
                        }
                    }
                }
            }
        }
    </script>
</body>
</html>
