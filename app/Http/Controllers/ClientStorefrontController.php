<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Location;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AnalyticsLog;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientStorefrontController extends Controller
{
    public function showMenu(Request $request, string $vendor_slug, ?string $location_slug = null)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();

        $location = null;
        if ($location_slug) {
            $location = Location::where('vendor_id', $vendor->id)->where('slug', $location_slug)->first();
        }
        if (!$location) {
            $location = $vendor->locations->first();
        }

        $lang = $request->get('lang', 'hy');
        $table = $request->get('table', null);
        $channel = $request->get('mode', 'dine_in'); // dine_in vs ordering

        // Log analytics visit silently
        try {
            AnalyticsLog::create([
                'vendor_id' => $vendor->id,
                'location_id' => $location?->id,
                'channel' => $channel,
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                'ip_address' => $request->ip(),
                'visit_date' => now()->format('Y-m-d'),
            ]);
        } catch (\Throwable $e) {
            // Ignore analytics log failure
        }

        // Fetch categories and active products
        $categories = Category::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->with(['products' => function ($q) {
                $q->where('is_available', true)
                  ->with(['variations', 'allergens'])
                  ->orderBy('sort_order', 'asc');
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        $themeSlug = $vendor->menuTemplate?->slug ?? 'modern-bistro';

        // Support real-time live preview query parameter overrides
        if ($request->has('menu_template_id') && $request->get('menu_template_id')) {
            $tmpl = \App\Models\MenuTemplate::find($request->get('menu_template_id'));
            if ($tmpl) {
                $themeSlug = $tmpl->slug;
            }
        }
        if ($request->has('theme_mode') && $request->get('theme_mode')) {
            $vendor->theme_mode = $request->get('theme_mode');
        }
        if ($request->has('primary_color') && $request->get('primary_color')) {
            $vendor->primary_color = $request->get('primary_color');
        }
        if ($request->has('accent_color') && $request->get('accent_color')) {
            $vendor->accent_color = $request->get('accent_color');
        }
        if ($request->has('secondary_color') && $request->get('secondary_color')) {
            $vendor->secondary_color = $request->get('secondary_color');
        }
        if ($request->has('bg_color') && $request->get('bg_color')) {
            $vendor->bg_color = $request->get('bg_color');
        }
        if ($request->has('text_color') && $request->get('text_color')) {
            $vendor->text_color = $request->get('text_color');
        }

        return view("storefront.themes.{$themeSlug}", compact(
            'vendor',
            'location',
            'categories',
            'lang',
            'table',
            'channel'
        ));
    }

    public function manifest(string $vendor_slug)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->firstOrFail();

        return response()->json([
            'short_name' => $vendor->name,
            'name' => "{$vendor->name} - Digital Menu & Orders",
            'icons' => [
                [
                    'src' => $vendor->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=192&h=192&q=80',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => $vendor->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=512&h=512&q=80',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ],
            'start_url' => route('client.menu', ['vendor_slug' => $vendor->slug]),
            'background_color' => '#0f172a',
            'theme_color' => $vendor->primary_color ?? '#e11d48',
            'display' => 'standalone',
            'orientation' => 'portrait'
        ]);
    }

    public function serviceWorker(string $vendor_slug)
    {
        $content = "
const CACHE_NAME = 'qrmenu-" . $vendor_slug . "-v1';
const urlsToCache = [
  '/',
  '/css/app.css',
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});
        ";
        return response($content, 200)->header('Content-Type', 'application/javascript');
    }

    public function submitOrder(Request $request, string $vendor_slug)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->firstOrFail();

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'table_number' => 'nullable|string',
            'type' => 'required|string|in:dine_in,takeaway,whatsapp',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_name' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $orderNumber = 'ORD-' . strtoupper(Str::random(6));
        $totalAmount = 0;

        $order = Order::create([
            'vendor_id' => $vendor->id,
            'location_id' => $validated['location_id'],
            'order_number' => $orderNumber,
            'table_number' => $validated['table_number'] ?? 'Counter',
            'type' => $validated['type'],
            'total_amount' => 0,
            'status' => 'pending',
            'customer_name' => $validated['customer_name'] ?? 'Guest',
            'customer_phone' => $validated['customer_phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $unitPrice = $product ? (float)$product->getEffectivePrice($validated['location_id']) : 0;
            $subtotal = $unitPrice * $item['quantity'];
            $totalAmount += $subtotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product?->id,
                'product_name' => $product?->name ?? 'Dish',
                'variation_name' => $item['variation_name'] ?? 'Standard',
                'unit_price' => $unitPrice,
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
            ]);
        }

        $order->update(['total_amount' => $totalAmount]);

        // If WhatsApp type, generate WhatsApp API text link
        $whatsappUrl = null;
        $location = Location::find($validated['location_id']);
        if ($location && !empty($location->whatsapp_number)) {
            $msg = "🧾 *New Order #{$order->order_number}*\n";
            $msg .= "📍 *{$location->name}* " . ($order->table_number ? "({$order->table_number})" : "") . "\n";
            $msg .= "👤 Name: {$order->customer_name}\n";
            $msg .= "--------------------\n";
            foreach ($order->items as $i) {
                $msg .= "• {$i->quantity}x {$i->product_name} ({$i->variation_name}) - " . number_format($i->subtotal) . " {$vendor->currency}\n";
            }
            $msg .= "--------------------\n";
            $msg .= "💰 *Total: " . number_format($order->total_amount) . " {$vendor->currency}*";

            $whatsappUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $location->whatsapp_number) . "?text=" . urlencode($msg);
        }

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'total_amount' => $order->total_amount,
            'whatsapp_url' => $whatsappUrl,
        ]);
    }
}
