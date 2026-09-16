<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="{{ $vendor->primary_color ?? '#06b6d4' }}">
    <link rel="manifest" href="{{ route('client.manifest', ['vendor_slug' => $vendor->slug]) }}">
    <title>{{ $vendor->name }} - Digital Cafe Menu</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: {{ $vendor->primary_color ?? '#06b6d4' }}; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: radial-gradient(circle at top, #1e1b4b, #0f172a); color: #fff; min-height: 100vh; padding: 1.25rem; padding-bottom: 100px; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 1rem; margin-bottom: 1rem; display: flex; gap: 1rem; }
    </style>
</head>
<body x-data="{ cartCount: 0 }">
    <div style="text-align: center; margin-bottom: 2rem;">
        <img src="{{ $vendor->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=200&q=80' }}" style="width: 75px; height: 75px; border-radius: 50%; border: 3px solid var(--primary); object-fit: cover;">
        <h1 style="font-size: 1.75rem; font-weight: 800; margin-top: 0.5rem;">{{ $vendor->name }}</h1>
        <p style="font-size: 0.85rem; color: #94a3b8;">{{ $location?->name ?? 'Cafe Location' }}</p>
    </div>

    @foreach($categories as $cat)
        <div style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 1rem; background: linear-gradient(135deg, #06b6d4, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                {{ $cat->getTranslatedName($lang) }}
            </h2>

            @foreach($cat->products as $prod)
                <div class="glass-card">
                    <img src="{{ $prod->image }}" style="width: 85px; height: 85px; border-radius: 12px; object-fit: cover;">
                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 1.05rem;">{{ $prod->getTranslatedName($lang) }}</div>
                            <div style="font-size: 0.8rem; color: #94a3b8;">{{ $prod->getTranslatedDescription($lang) }}</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                            <div style="font-weight: 800; color: #06b6d4; font-size: 1.1rem;">{{ number_format($prod->price) }} {{ $vendor->currency }}</div>
                            <button @click="cartCount++" style="background: linear-gradient(135deg, #06b6d4, #3b82f6); color: #fff; border: none; padding: 0.35rem 0.75rem; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                + Select
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    <div style="position: fixed; bottom: 1rem; left: 1.25rem; right: 1.25rem; background: #06b6d4; color: #000; padding: 0.85rem 1.25rem; border-radius: 16px; font-weight: 800; display: flex; justify-content: space-between; align-items: center;" x-show="cartCount > 0">
        <span>Selected Items (<span x-text="cartCount"></span>)</span>
        <button @click="alert('Order submitted via WhatsApp!')" style="background: #000; color: #fff; border: none; padding: 0.4rem 0.85rem; border-radius: 8px; font-weight: 700;">Submit Order</button>
    </div>
</body>
</html>
