<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="{{ $vendor->primary_color ?? '#e11d48' }}">
    <link rel="manifest" href="{{ route('client.manifest', ['vendor_slug' => $vendor->slug]) }}">
    <title>{{ $vendor->name }} - Menu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: {{ $vendor->primary_color ?? '#e11d48' }}; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #f8fafc; padding-bottom: 100px; }
        .hero { padding: 2rem 1.25rem; background: linear-gradient(135deg, #1e293b, #0f172a); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .dish-card { background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; display: flex; gap: 1rem; }
        .dish-img { width: 90px; height: 90px; border-radius: 8px; object-fit: cover; }
    </style>
</head>
<body x-data="{ cart: [], showCart: false }">
    <div class="hero">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <img src="{{ $vendor->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=200&q=80' }}" style="width: 60px; height: 60px; border-radius: 12px; object-fit: cover;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800;">{{ $vendor->name }}</h1>
                <p style="font-size: 0.85rem; color: #94a3b8;">{{ $location?->name ?? 'Main Location' }}</p>
            </div>
        </div>
    </div>

    <div style="padding: 1.25rem;">
        @foreach($categories as $cat)
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--primary);">{{ $cat->getTranslatedName($lang) }}</h2>
                @foreach($cat->products as $prod)
                    <div class="dish-card">
                        <img src="{{ $prod->image }}" class="dish-img">
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="font-weight: 700; font-size: 1rem;">{{ $prod->getTranslatedName($lang) }}</div>
                                <div style="font-size: 0.8rem; color: #94a3b8;">{{ $prod->getTranslatedDescription($lang) }}</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                <div style="font-weight: 800; color: #f59e0b;">{{ number_format($prod->price) }} {{ $vendor->currency }}</div>
                                <button @click="cart.push({ id: {{ $prod->id }}, name: '{{ $prod->name }}', price: {{ $prod->price }}, qty: 1 })" style="background: var(--primary); color: #fff; border: none; padding: 0.35rem 0.75rem; border-radius: 6px; font-weight: 700; cursor: pointer;">
                                    + Add
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div style="position: fixed; bottom: 1rem; left: 1rem; right: 1rem; background: var(--primary); color: #fff; padding: 1rem; border-radius: 12px; font-weight: 800; display: flex; justify-content: space-between; align-items: center;" x-show="cart.length > 0">
        <span>Order Tray (<span x-text="cart.length"></span>)</span>
        <button @click="alert('Order sent!')" style="background: #000; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700;">Submit Order</button>
    </div>
</body>
</html>
