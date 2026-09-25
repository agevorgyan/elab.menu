<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderAction;
use App\DTOs\CreateOrderDTO;
use App\Events\WaiterCalled;
use App\Http\Requests\CallWaiterRequest;
use App\Http\Requests\SubmitOrderRequest;
use App\Jobs\RecordAnalyticsVisitJob;
use App\Models\Category;
use App\Models\Location;
use App\Models\MenuTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\WaiterCall;
use App\Services\PaymentGatewayService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClientStorefrontController extends Controller
{
    public function showMenu(Request $request, string $vendor_slug, ?string $location_slug = null)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();

        $location = null;
        if ($location_slug) {
            $location = Location::where('vendor_id', $vendor->id)->where('slug', $location_slug)->first();
        }
        if (! $location) {
            $location = $vendor->locations->first();
        }

        $supportedLanguages = $vendor->getSupportedLanguages();
        $allowedCodes = array_map(fn ($l) => strtolower($l['code'] ?? ''), $supportedLanguages);
        $defaultCode = $allowedCodes[0] ?? 'hy';

        $lang = strtolower((string) ($request->get('lang') ?? session('app_locale', $defaultCode)));
        if (! in_array($lang, $allowedCodes) && ! in_array($lang, ['hy', 'en', 'ru', 'fr', 'de', 'es', 'it', 'ge', 'ar'])) {
            $lang = $defaultCode;
        }
        session(['app_locale' => $lang, 'locale' => $lang]);
        App::setLocale($lang);
        $table = $request->get('table', null);
        if ($request->has('table') && $request->filled('table')) {
            session(['storefront_table_'.$vendor->slug => $request->get('table')]);
        } elseif (session()->has('storefront_table_'.$vendor->slug)) {
            $table = session('storefront_table_'.$vendor->slug);
        }
        $channel = $request->get('mode', 'dine_in'); // dine_in vs ordering

        // Dispatch analytics visit asynchronously to background queue
        RecordAnalyticsVisitJob::dispatch([
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
            $tmpl = MenuTemplate::find($request->get('menu_template_id'));
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
        if ($request->has('logo') && $request->get('logo')) {
            $vendor->logo = $request->get('logo');
        }
        if ($request->has('cover_image') && $request->get('cover_image')) {
            $vendor->cover_image = $request->get('cover_image');
        }
        if ($request->has('custom_css') && $request->get('custom_css')) {
            $vendor->custom_css = $request->get('custom_css');
        }
        if ($request->has('desktop_max_width') && $request->get('desktop_max_width')) {
            $vendor->desktop_max_width = $request->get('desktop_max_width');
        }

        // Featured Dish / Dish of the Day
        $featuredDish = null;
        if ($vendor->featured_dish_enabled && $vendor->featured_product_id) {
            $featuredDish = Product::where('vendor_id', $vendor->id)
                ->where('id', $vendor->featured_product_id)
                ->where('is_available', true)
                ->with(['variations', 'allergens', 'overrides', 'category'])
                ->first();
        }

        return view("storefront.themes.{$themeSlug}", compact(
            'vendor',
            'location',
            'categories',
            'featuredDish',
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
                    'type' => 'image/png',
                ],
                [
                    'src' => $vendor->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=512&h=512&q=80',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
            'start_url' => route('client.menu', ['vendor_slug' => $vendor->slug]),
            'background_color' => '#0f172a',
            'theme_color' => $vendor->primary_color ?? '#e11d48',
            'display' => 'standalone',
            'orientation' => 'portrait',
        ]);
    }

    public function serviceWorker(string $vendor_slug)
    {
        $content = "
const CACHE_NAME = 'qrmenu-".$vendor_slug."-v1';

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

    public function submitOrder(SubmitOrderRequest $request, string $vendor_slug, CreateOrderAction $createOrderAction, PaymentGatewayService $paymentService)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->firstOrFail();

        if (! $vendor->hasFeature('orders')) {
            return response()->json([
                'success' => false,
                'message' => 'Օնլայն պատվերների ֆունկցիան ակտիվ չէ այս մենյուի համար (Basic փաթեթ)։',
            ], 403);
        }

        $validated = $request->validated();

        // Ensure all sent product_ids belong to this vendor
        $productIds = collect($validated['items'])->pluck('product_id')->unique();
        $vendorProductsCount = Product::where('vendor_id', $vendor->id)
            ->whereIn('id', $productIds)
            ->count();

        if ($vendorProductsCount !== $productIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Պատվերի մեջ առկա են անվավեր կամ այլ գործընկերոջ պատկանող ապրանքներ։',
            ], 422);
        }

        $dto = CreateOrderDTO::fromArray($validated);

        try {
            $result = DB::transaction(function () use ($createOrderAction, $vendor, $dto) {
                return $createOrderAction->execute($vendor, $dto);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        // Handle online payment gateway redirection
        $paymentResult = null;
        $chosenPayment = $validated['payment_method'] ?? 'cash';
        if (! in_array($chosenPayment, ['cash', 'pos_terminal'])) {
            $paymentResult = $paymentService->initiateOrderPayment($result['order'], $chosenPayment);
        }

        return response()->json([
            'success' => true,
            'is_appended' => $result['is_appended'] ?? false,
            'order_number' => $result['order']->order_number,
            'order_id' => $result['order']->id,
            'status' => $result['order']->status,
            'status_label' => __('menu.status_'.$result['order']->status),
            'payment_method' => $result['order']->payment_method,
            'payment_status' => $result['order']->payment_status,
            'payment_redirect_url' => $paymentResult['redirect_url'] ?? null,
            'birthday_discount' => (float) ($result['order']->birthday_discount_amount ?? 0),
            'subtotal' => (float) ($result['order']->subtotal ?? 0),
            'service_fee' => (float) ($result['order']->service_fee ?? 0),
            'delivery_fee' => (float) ($result['order']->delivery_fee ?? 0),
            'total_amount' => (float) $result['order']->total_amount,
            'whatsapp_url' => $result['whatsapp_url'],
            'items' => $result['order']->items->map(function ($item) {
                $unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);
                $subtotal = (float) ($item->subtotal ?? ($unitPrice * $item->quantity));

                return [
                    'id' => $item->id,
                    'name' => $item->product_name ?? $item->product?->name ?? 'Dish #'.$item->product_id,
                    'variation_name' => $item->variation_name,
                    'quantity' => $item->quantity,
                    'price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }),
        ]);
    }

    /**
     * Handle payment gateway success callback.
     */
    public function paymentCallback(string $vendor_slug, int $order_id, Request $request)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->firstOrFail();
        $order = Order::where('vendor_id', $vendor->id)->findOrFail($order_id);

        $gateway = $request->get('gateway', $order->payment_method ?? 'online');
        $token = $request->get('token') ?? $request->get('trx') ?? $request->get('transaction_id') ?? $order->payment_transaction_id;

        $order->update([
            'payment_status' => 'paid',
            'payment_transaction_id' => $token,
        ]);

        try {
            app(TelegramNotificationService::class)->sendPaymentNotification($order, (string) $gateway);
        } catch (\Throwable $e) {
            Log::warning('Telegram payment notification failed: '.$e->getMessage());
        }

        return redirect()->route('client.menu', [
            'vendor_slug' => $vendor->slug,
            'order_placed' => $order->order_number,
            'payment_success' => 1,
            'gateway' => $gateway,
        ])->with('success', 'Վճարումը հաջողությամբ կատարվել է։ Պատվերը փոխանցվել է խոհանոց։');
    }

    public function orderStatus(string $vendor_slug, string $order_number)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->firstOrFail();

        $order = Order::where('vendor_id', $vendor->id)
            ->where('order_number', $order_number)
            ->with(['items.product', 'location'])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Պատվերը չի գտնվել։',
            ], 404);
        }

        $stepMap = [
            'pending' => [
                'step' => 1,
                'percent' => 25,
                'label' => __('menu.status_pending'),
                'desc' => __('menu.status_desc_pending'),
                'icon' => 'fa-solid fa-clock',
            ],
            'accepted' => [
                'step' => 2,
                'percent' => 50,
                'label' => __('menu.status_accepted'),
                'desc' => __('menu.status_desc_accepted'),
                'icon' => 'fa-solid fa-clipboard-check',
            ],
            'preparing' => [
                'step' => 3,
                'percent' => 75,
                'label' => __('menu.status_preparing'),
                'desc' => __('menu.status_desc_preparing'),
                'icon' => 'fa-solid fa-utensils',
            ],
            'ready' => [
                'step' => 4,
                'percent' => 95,
                'label' => __('menu.status_ready'),
                'desc' => __('menu.status_desc_ready'),
                'icon' => 'fa-solid fa-bell-concierge',
            ],
            'completed' => [
                'step' => 5,
                'percent' => 100,
                'label' => __('menu.status_completed'),
                'desc' => __('menu.status_desc_completed'),
                'icon' => 'fa-solid fa-circle-check',
            ],
            'cancelled' => [
                'step' => 0,
                'percent' => 0,
                'label' => __('menu.status_cancelled'),
                'desc' => __('menu.status_desc_cancelled'),
                'icon' => 'fa-solid fa-circle-xmark',
            ],
        ];

        $meta = $stepMap[$order->status] ?? [
            'step' => 1,
            'percent' => 20,
            'label' => ucfirst($order->status),
            'desc' => '',
            'icon' => 'fa-solid fa-clock',
        ];

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'table_number' => $order->table_number,
                'type' => $order->type,
                'status' => $order->status,
                'status_step' => $meta['step'],
                'status_percent' => $meta['percent'],
                'status_label' => $meta['label'],
                'status_desc' => $meta['desc'],
                'status_icon' => $meta['icon'],
                'subtotal' => (float) ($order->subtotal ?? 0),
                'service_fee' => (float) ($order->service_fee ?? 0),
                'delivery_fee' => (float) ($order->delivery_fee ?? 0),
                'total_amount' => (float) $order->total_amount,
                'currency' => $vendor->currency,
                'created_at_human' => $order->created_at->diffForHumans(),
                'created_at_time' => $order->created_at->format('H:i'),
                'updated_at_timestamp' => $order->updated_at?->timestamp,
                'items' => $order->items->map(function ($item) {
                    $unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);
                    $subtotal = (float) ($item->subtotal ?? ($unitPrice * $item->quantity));

                    return [
                        'id' => $item->id,
                        'name' => $item->product_name ?? $item->product?->name ?? 'Dish #'.$item->product_id,
                        'variation_name' => $item->variation_name,
                        'quantity' => $item->quantity,
                        'price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ];
                }),
            ],
        ]);
    }

    public function callWaiter(CallWaiterRequest $request, string $vendor_slug)
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validated();

        $sessionTable = session('storefront_table_'.$vendor->slug);
        if ($sessionTable) {
            $normalizedSession = strtolower(trim((string) $sessionTable));
            $normalizedTable = strtolower(trim((string) $validated['table_number']));
            $matches = ($normalizedTable === $normalizedSession)
                || ($normalizedTable === 'table '.$normalizedSession)
                || ($normalizedTable === 'սեղան '.$normalizedSession)
                || ('table '.$normalizedTable === $normalizedSession);

            if (! $matches) {
                return response()->json([
                    'success' => false,
                    'message' => 'Դուք չեք կարող կանչել մատուցող այլ սեղանի համար։',
                ], 403);
            }
        }

        $locationId = $validated['location_id'] ?? $vendor->locations->first()?->id;

        $call = WaiterCall::create([
            'vendor_id' => $vendor->id,
            'location_id' => $locationId,
            'table_number' => $validated['table_number'],
            'type' => $validated['type'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Broadcast real-time WaiterCalled event
        try {
            event(new WaiterCalled($call));
        } catch (\Throwable $e) {
            Log::warning('WaiterCalled broadcast failed: '.$e->getMessage());
        }

        $message = match ($call->type) {
            'call_waiter' => __('menu.waiter_called_success'),
            'bill_cash', 'bill_card' => __('menu.bill_requested_success'),
            default => 'Հարցումն ուղարկված է։',
        };

        return response()->json([
            'success' => true,
            'message' => $message,
            'call' => [
                'id' => $call->id,
                'table_number' => $call->table_number,
                'type' => $call->type,
                'type_label' => $call->getTypeLabel(),
                'status' => $call->status,
                'created_at' => $call->created_at->diffForHumans(),
            ],
        ]);
    }
}
