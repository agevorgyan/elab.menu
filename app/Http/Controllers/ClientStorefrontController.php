<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Location;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\AnalyticsLog;
use App\Models\PushSubscription;
use App\Http\Requests\SubmitOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $lang = $request->get('lang') ?? session('app_locale', 'hy');
        if (!in_array($lang, ['hy', 'en', 'ru'])) {
            $lang = 'hy';
        }
        session(['app_locale' => $lang, 'locale' => $lang]);
        \Illuminate\Support\Facades\App::setLocale($lang);
        $table = $request->get('table', null);
        $channel = $request->get('mode', 'dine_in'); // dine_in vs ordering

        // Dispatch analytics visit asynchronously to background queue
        \App\Jobs\RecordAnalyticsVisitJob::dispatch([
            'vendor_id' => $vendor->id,
            'location_id' => $location?->id,
            'channel' => $channel,
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'ip_address' => $request->ip(),
            'visit_date' => now()->format('Y-m-d'),
        ]);

        // Fetch categories and active products
        $categories = Category::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->with(['products' => function ($q) {
                $q->where('is_available', true)
                  ->with(['variations', 'allergens', 'overrides'])
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

self.addEventListener('install', event => {
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(clients.claim());
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
      .catch(() => fetch(event.request))
  );
});
        ";
        return response($content, 200)->header('Content-Type', 'application/javascript');
    }

    public function submitOrder(SubmitOrderRequest $request, string $vendor_slug, \App\Actions\CreateOrderAction $createOrderAction)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->firstOrFail();

        if (!$vendor->hasFeature('orders')) {
            return response()->json([
                'success' => false,
                'message' => 'Օնլայն պատվերների ֆունկցիան ակտիվ չէ այս մենյուի համար (Basic փաթեթ)։',
            ], 403);
        }

        $validated = $request->validated();

        $result = $createOrderAction->execute($vendor, $validated);

        return response()->json([
            'success' => true,
            'order_number' => $result['order']->order_number,
            'total_amount' => $result['order']->total_amount,
            'whatsapp_url' => $result['whatsapp_url'],
        ]);
    }
}
