<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Vendor;
use App\Services\AiGatewayService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VendorSettingsController extends Controller
{
    /**
     * Display the vendor service & delivery settings, branch info, and legal details.
     */
    public function index(Request $request): View
    {
        $vendor = Auth::user()->vendor;
        $vendor->load('featuredProduct');

        $products = $vendor->products()
            ->with(['category'])
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $locationId = $request->get('location_id', session('active_location_id', $vendor->locations->first()?->id));
        $location = $vendor->locations()->find($locationId) ?? $vendor->locations->first();

        return view('admin.settings.index', compact('vendor', 'location', 'products'));
    }

    /**
     * Update vendor service fee, delivery configuration, branch information, and legal details.
     */
    public function update(Request $request): RedirectResponse
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            // Branch / Storefront Details
            'location_id' => 'nullable|integer',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:255',
            'allow_whatsapp_orders' => 'nullable|boolean',

            // Legal & Vendor Details (SuperAdmin)
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'operating_address' => 'nullable|string|max:500',
            'director_name' => 'nullable|string|max:255',
            'director_phone' => 'nullable|string|max:50',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_phone' => 'nullable|string|max:50',

            // Service fee
            'service_fee_enabled' => 'nullable|boolean',
            'service_fee_type' => 'required|string|in:percent,fixed',
            'service_fee_value' => 'required|numeric|min:0',
            'service_fee_min_order' => 'nullable|numeric|min:0',

            // Delivery
            'delivery_enabled' => 'nullable|boolean',
            'delivery_fee' => 'required|numeric|min:0',
            'delivery_min_amount' => 'required|numeric|min:0',
            'delivery_free_from' => 'nullable|numeric|min:0',
            'desktop_max_width' => 'nullable|string|max:20',

            // Takeaway
            'takeaway_enabled' => 'nullable|boolean',
            'takeaway_min_amount' => 'nullable|numeric|min:0',

            // Featured Dish / Dish of the Day
            'featured_dish_enabled' => 'nullable|boolean',
            'featured_product_id' => 'nullable|integer|exists:products,id',
            'featured_dish_badge' => 'nullable|string|max:100',
            'featured_dish_subtitle' => 'nullable|string|max:255',

            // AI Waiter Advisor
            'ai_waiter_enabled' => 'nullable|boolean',
            'ai_waiter_name' => 'nullable|string|max:100',
            'ai_waiter_priority_ingredients' => 'nullable|string|max:2000',
            'ai_waiter_welcome_text' => 'nullable|string|max:1000',
        ]);

        $validated['service_fee_enabled'] = $request->boolean('service_fee_enabled');
        $validated['delivery_enabled'] = $request->boolean('delivery_enabled');
        $validated['takeaway_enabled'] = $request->boolean('takeaway_enabled');
        $validated['featured_dish_enabled'] = $request->boolean('featured_dish_enabled');
        $validated['ai_waiter_enabled'] = $request->boolean('ai_waiter_enabled');
        $validated['allow_whatsapp_orders'] = $request->boolean('allow_whatsapp_orders');

        $vendorUpdate = [
            'service_fee_enabled' => $validated['service_fee_enabled'],
            'service_fee_type' => $validated['service_fee_type'],
            'service_fee_value' => $validated['service_fee_value'],
            'service_fee_min_order' => $validated['service_fee_min_order'] ?? null,
            'delivery_enabled' => $validated['delivery_enabled'],
            'delivery_fee' => $validated['delivery_fee'],
            'delivery_min_amount' => $validated['delivery_min_amount'],
            'delivery_free_from' => $validated['delivery_free_from'] ?? null,
            'takeaway_enabled' => $validated['takeaway_enabled'],
            'takeaway_min_amount' => $validated['takeaway_min_amount'] ?? 0,
            'featured_dish_enabled' => $validated['featured_dish_enabled'],
            'featured_dish_badge' => $validated['featured_dish_badge'] ?? null,
            'featured_dish_subtitle' => $validated['featured_dish_subtitle'] ?? null,
            'ai_waiter_enabled' => $validated['ai_waiter_enabled'],
            'allow_whatsapp_orders' => $validated['allow_whatsapp_orders'],
        ];

        if (array_key_exists('ai_waiter_name', $validated)) {
            $vendorUpdate['ai_waiter_name'] = ! empty($validated['ai_waiter_name']) ? $validated['ai_waiter_name'] : 'AI Մատուցող';
        }
        if (array_key_exists('ai_waiter_priority_ingredients', $validated)) {
            $vendorUpdate['ai_waiter_priority_ingredients'] = $validated['ai_waiter_priority_ingredients'];
        }
        if (array_key_exists('ai_waiter_welcome_text', $validated)) {
            $vendorUpdate['ai_waiter_welcome_text'] = $validated['ai_waiter_welcome_text'];
        }

        // Ensure selected featured product belongs to this vendor
        if (! empty($validated['featured_product_id'])) {
            $productExists = $vendor->products()->where('id', $validated['featured_product_id'])->exists();
            $vendorUpdate['featured_product_id'] = $productExists ? $validated['featured_product_id'] : null;
        } else {
            $vendorUpdate['featured_product_id'] = null;
        }

        if (! empty($validated['name'])) {
            $vendorUpdate['name'] = $validated['name'];
        }
        if (! empty($validated['desktop_max_width'])) {
            $vendorUpdate['desktop_max_width'] = $validated['desktop_max_width'];
        }
        if (array_key_exists('phone', $validated)) {
            $vendorUpdate['phone'] = $validated['phone'];
        }
        if (array_key_exists('wifi_ssid', $validated)) {
            $vendorUpdate['wifi_ssid'] = $validated['wifi_ssid'];
        }
        if (array_key_exists('wifi_password', $validated)) {
            $vendorUpdate['wifi_password'] = $validated['wifi_password'];
        }
        if (array_key_exists('working_hours', $validated)) {
            $vendorUpdate['working_hours'] = $validated['working_hours'];
        }
        if (array_key_exists('legal_name', $validated)) {
            $vendorUpdate['legal_name'] = $validated['legal_name'];
        }
        if (array_key_exists('tax_id', $validated)) {
            $vendorUpdate['tax_id'] = $validated['tax_id'];
        }
        if (array_key_exists('operating_address', $validated)) {
            $vendorUpdate['operating_address'] = $validated['operating_address'];
        } elseif (array_key_exists('address', $validated) && ! empty($validated['address'])) {
            $vendorUpdate['operating_address'] = $validated['address'];
        }
        if (array_key_exists('director_name', $validated)) {
            $vendorUpdate['director_name'] = $validated['director_name'];
        }
        if (array_key_exists('director_phone', $validated)) {
            $vendorUpdate['director_phone'] = $validated['director_phone'];
        }
        if (array_key_exists('contact_person_name', $validated)) {
            $vendorUpdate['contact_person_name'] = $validated['contact_person_name'];
        }
        if (array_key_exists('contact_person_phone', $validated)) {
            $vendorUpdate['contact_person_phone'] = $validated['contact_person_phone'];
        }

        // Custom Domain Validation & Normalization
        if ($request->has('custom_domain')) {
            $rawDomain = $request->input('custom_domain');
            if (empty($rawDomain) || trim($rawDomain) === '') {
                $vendorUpdate['custom_domain'] = null;
            } else {
                $cleanDomain = preg_replace('#^https?://#i', '', trim($rawDomain));
                $cleanDomain = explode('/', $cleanDomain)[0];
                $cleanDomain = explode(':', $cleanDomain)[0];
                $cleanDomain = strtolower(trim($cleanDomain));

                $forbiddenHosts = [
                    'menu.elab.am',
                    'elab.am',
                    'localhost',
                    '127.0.0.1',
                    'qrmenu.local',
                ];
                $mainHost = strtolower(parse_url(config('app.url', 'https://menu.elab.am'), PHP_URL_HOST) ?? '');
                if ($mainHost) {
                    $forbiddenHosts[] = $mainHost;
                }

                if (in_array($cleanDomain, $forbiddenHosts)) {
                    return back()->withErrors([
                        'custom_domain' => 'Հիմնական հարթակի դոմենը չի կարող օգտագործվել որպես սեփական դոմեն։',
                    ])->withInput();
                }

                if (! preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]+\.)+[a-zA-Z]{2,}$/', $cleanDomain)) {
                    return back()->withErrors([
                        'custom_domain' => 'Դոմենի ձևաչափն անվավեր է (օրինակ՝ menu.restaurant.am կամ restaurant.com):',
                    ])->withInput();
                }

                // Check uniqueness across other vendors
                $existing = Vendor::where('custom_domain', $cleanDomain)
                    ->where('id', '!=', $vendor->id)
                    ->exists();

                if ($existing) {
                    return back()->withErrors([
                        'custom_domain' => 'Այս դոմենն արդեն օգտագործվում է այլ ռեստորանի կողմից։',
                    ])->withInput();
                }

                $vendorUpdate['custom_domain'] = $cleanDomain;
            }
        }

        // Payment Gateways Settings
        if ($request->has('payment_settings')) {
            $inputPayments = $request->input('payment_settings', []);
            $vendorUpdate['payment_settings'] = [
                'online_enabled' => ! empty($inputPayments['online_enabled']),
                'cash_enabled' => ! empty($inputPayments['cash_enabled']),
                'pos_terminal_enabled' => ! empty($inputPayments['pos_terminal_enabled']),
                'gateways' => [
                    'idram' => [
                        'enabled' => ! empty($inputPayments['gateways']['idram']['enabled']),
                        'title' => 'Idram',
                        'merchant_id' => (string) ($inputPayments['gateways']['idram']['merchant_id'] ?? ''),
                        'secret_key' => (string) ($inputPayments['gateways']['idram']['secret_key'] ?? ''),
                        'sandbox' => ! empty($inputPayments['gateways']['idram']['sandbox']),
                    ],
                    'telcell' => [
                        'enabled' => ! empty($inputPayments['gateways']['telcell']['enabled']),
                        'title' => 'Telcell Wallet',
                        'shop_id' => (string) ($inputPayments['gateways']['telcell']['shop_id'] ?? ''),
                        'key' => (string) ($inputPayments['gateways']['telcell']['key'] ?? ''),
                        'sandbox' => ! empty($inputPayments['gateways']['telcell']['sandbox']),
                    ],
                    'fastshift' => [
                        'enabled' => ! empty($inputPayments['gateways']['fastshift']['enabled']),
                        'title' => 'FastShift',
                        'merchant_id' => (string) ($inputPayments['gateways']['fastshift']['merchant_id'] ?? ''),
                        'api_key' => (string) ($inputPayments['gateways']['fastshift']['api_key'] ?? ''),
                        'sandbox' => ! empty($inputPayments['gateways']['fastshift']['sandbox']),
                    ],
                    'arca' => [
                        'enabled' => ! empty($inputPayments['gateways']['arca']['enabled']),
                        'title' => 'ArCa / Ameriabank vPOS',
                        'merchant_id' => (string) ($inputPayments['gateways']['arca']['merchant_id'] ?? ''),
                        'terminal_id' => (string) ($inputPayments['gateways']['arca']['terminal_id'] ?? ''),
                        'sandbox' => ! empty($inputPayments['gateways']['arca']['sandbox']),
                    ],
                    'stripe' => [
                        'enabled' => ! empty($inputPayments['gateways']['stripe']['enabled']),
                        'title' => 'Stripe (Cards / Apple Pay)',
                        'publishable_key' => (string) ($inputPayments['gateways']['stripe']['publishable_key'] ?? ''),
                        'secret_key' => (string) ($inputPayments['gateways']['stripe']['secret_key'] ?? ''),
                        'sandbox' => ! empty($inputPayments['gateways']['stripe']['sandbox']),
                    ],
                ],
            ];
        }

        // CRM Automation Settings
        if ($request->has('crm_settings')) {
            $crmInput = $request->input('crm_settings', []);
            $vendorUpdate['crm_settings'] = [
                'birthday_discount_enabled' => ! empty($crmInput['birthday_discount_enabled']),
                'birthday_discount_percent' => floatval($crmInput['birthday_discount_percent'] ?? 15),
                'birthday_validity_days' => intval($crmInput['birthday_validity_days'] ?? 3),
                'birthday_sms_enabled' => ! empty($crmInput['birthday_sms_enabled']),
                'birthday_sms_template' => (string) ($crmInput['birthday_sms_template'] ?? 'Շնորհավոր Ձեր ծննդյան օրը {NAME}։ Ձեզ սպասում է {DISCOUNT}% զեղչ {VENDOR}-ում։'),
                'order_ready_sms_enabled' => ! empty($crmInput['order_ready_sms_enabled']),
                'sms_provider' => (string) ($crmInput['sms_provider'] ?? 'mobipace'),
                'sms_api_key' => (string) ($crmInput['sms_api_key'] ?? ''),
                'sms_sender_id' => (string) ($crmInput['sms_sender_id'] ?? 'QRMENU'),
            ];
        }

        // Thermal Printer Settings
        if ($request->has('thermal_printer_settings')) {
            $printerInput = $request->input('thermal_printer_settings', []);
            $vendorUpdate['thermal_printer_settings'] = [
                'auto_print_live_orders' => ! empty($printerInput['auto_print_live_orders']),
                'paper_width' => in_array($printerInput['paper_width'] ?? '', ['58mm', '80mm']) ? $printerInput['paper_width'] : '80mm',
                'header_title' => (string) ($printerInput['header_title'] ?? $vendor->name),
                'footer_text' => (string) ($printerInput['footer_text'] ?? 'Շնորհակալություն այցելության համար!'),
                'print_customer_info' => ! empty($printerInput['print_customer_info']),
                'print_prices' => ! empty($printerInput['print_prices']),
                'copies' => max(1, min(5, intval($printerInput['copies'] ?? 1))),
            ];
        }

        // Telegram Notifications Settings
        if ($request->has('telegram_settings')) {
            $tgInput = $request->input('telegram_settings', []);
            $vendorUpdate['telegram_settings'] = [
                'enabled' => ! empty($tgInput['enabled']),
                'bot_token' => ! empty($tgInput['bot_token']) ? trim((string) $tgInput['bot_token']) : null,
                'chat_id' => ! empty($tgInput['chat_id']) ? trim((string) $tgInput['chat_id']) : null,
                'topic_id' => ! empty($tgInput['topic_id']) ? (int) $tgInput['topic_id'] : null,
                'notify_orders' => ! empty($tgInput['notify_orders']),
                'notify_waiter_calls' => ! empty($tgInput['notify_waiter_calls']),
                'notify_payments' => ! empty($tgInput['notify_payments']),
            ];
        }

        $vendor->update($vendorUpdate);

        // Update Location (Branch)
        $locationId = $validated['location_id'] ?? $request->get('location_id', session('active_location_id', $vendor->locations->first()?->id));
        $location = $vendor->locations()->find($locationId) ?? $vendor->locations->first();

        if ($location) {
            $locationUpdate = [];
            if (! empty($validated['name'])) {
                $locationUpdate['name'] = $validated['name'];
            }
            if (array_key_exists('address', $validated)) {
                $locationUpdate['address'] = $validated['address'];
            }
            if (array_key_exists('phone', $validated)) {
                $locationUpdate['phone'] = $validated['phone'];
            }
            if (array_key_exists('whatsapp_number', $validated)) {
                $locationUpdate['whatsapp_number'] = $validated['whatsapp_number'];
            }
            if (array_key_exists('wifi_ssid', $validated)) {
                $locationUpdate['wifi_ssid'] = $validated['wifi_ssid'];
            }
            if (array_key_exists('wifi_password', $validated)) {
                $locationUpdate['wifi_password'] = $validated['wifi_password'];
            }
            if (array_key_exists('working_hours', $validated)) {
                $locationUpdate['working_hours'] = $validated['working_hours'];
            }
            if ($request->has('telegram_chat_id')) {
                $locationUpdate['telegram_chat_id'] = $request->input('telegram_chat_id') ? trim((string) $request->input('telegram_chat_id')) : null;
            }
            $locationUpdate['allow_whatsapp_orders'] = $validated['allow_whatsapp_orders'];

            if (! empty($locationUpdate)) {
                $location->update($locationUpdate);
            }
        }

        return back()->with('success', 'Կարգավորումները հաջողությամբ պահպանվեցին:');
    }

    /**
     * Display the vendor AI configuration and AI Waiter settings.
     */
    public function aiIndex(Request $request): View
    {
        $vendor = Auth::user()->vendor;
        $providers = AiGatewayService::PROVIDERS;

        return view('admin.settings.ai', compact('vendor', 'providers'));
    }

    /**
     * Update AI service provider settings and AI Waiter configuration.
     */
    public function aiUpdate(Request $request): RedirectResponse
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            // AI Waiter Configuration
            'ai_waiter_enabled' => 'nullable|boolean',
            'ai_waiter_name' => 'nullable|string|max:100',
            'ai_waiter_priority_ingredients' => 'nullable|string|max:2000',
            'ai_waiter_welcome_text' => 'nullable|string|max:1000',

            // AI Provider Configuration
            'ai_provider' => 'required|string|in:gemini,openai,claude,deepseek,groq,openrouter,custom',
            'ai_model' => 'nullable|string|max:100',
            'custom_model' => 'nullable|string|max:100',
            'ai_api_key' => 'nullable|string|max:255',
            'ai_base_url' => 'nullable|string|max:500',
        ]);

        $aiModel = $validated['ai_model'] ?? null;
        if ($aiModel === 'custom' || empty($aiModel)) {
            $aiModel = ! empty($validated['custom_model']) ? trim((string) $validated['custom_model']) : ($aiModel ?: null);
        }

        $currentSettings = $vendor->ai_settings ?? [];
        $apiKey = $request->filled('ai_api_key')
            ? trim((string) $validated['ai_api_key'])
            : ($currentSettings['api_key'] ?? null);

        // If user explicitly checked to clear API key (or if passed empty while custom provider requires none)
        if ($request->has('clear_api_key') && $request->boolean('clear_api_key')) {
            $apiKey = null;
        }

        $newAiSettings = [
            'provider' => $validated['ai_provider'] ?? 'gemini',
            'model' => $aiModel,
            'api_key' => $apiKey,
            'base_url' => ! empty($validated['ai_base_url']) ? trim((string) $validated['ai_base_url']) : null,
        ];

        $vendor->update([
            'ai_waiter_enabled' => $request->boolean('ai_waiter_enabled'),
            'ai_waiter_name' => ! empty($validated['ai_waiter_name']) ? $validated['ai_waiter_name'] : 'AI Մատուցող',
            'ai_waiter_priority_ingredients' => $validated['ai_waiter_priority_ingredients'] ?? null,
            'ai_waiter_welcome_text' => $validated['ai_waiter_welcome_text'] ?? null,
            'ai_settings' => $newAiSettings,
        ]);

        return redirect()->route('admin.settings.ai')->with('success', '✨ AI կարգավորումները հաջողությամբ պահպանվեցին:');
    }

    /**
     * Test connection to the chosen AI provider.
     */
    public function testAiConnection(Request $request, AiGatewayService $gateway): JsonResponse
    {
        $vendor = Auth::user()->vendor;

        $provider = $request->input('ai_provider', 'gemini');
        $model = $request->input('ai_model');
        if ($model === 'custom' || empty($model)) {
            $model = $request->input('custom_model') ?: ($model ?: null);
        }

        $apiKey = $request->input('ai_api_key');
        if (empty($apiKey)) {
            $apiKey = $vendor->ai_settings['api_key'] ?? null;
        }

        $baseUrl = $request->input('ai_base_url') ?: ($vendor->ai_settings['base_url'] ?? null);

        $result = $gateway->testConnection(
            provider: $provider,
            apiKey: $apiKey,
            model: $model,
            baseUrl: $baseUrl
        );

        return response()->json($result);
    }

    /**
     * Check DNS resolution status for a custom domain via AJAX.
     */
    public function checkDomainDns(Request $request): JsonResponse
    {
        $domain = trim($request->input('domain', ''));
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];
        $domain = strtolower(trim($domain));

        if (empty($domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Խնդրում ենք մուտքագրել դոմենի հասցեն։',
            ]);
        }

        $serverIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname('menu.elab.am');
        $resolvedIp = @gethostbyname($domain);
        $isResolved = ($resolvedIp !== $domain && ! empty($resolvedIp));

        $records = @dns_get_record($domain, DNS_A + DNS_CNAME) ?: [];
        $aRecords = collect($records)->where('type', 'A')->pluck('ip')->toArray();
        $cnameRecords = collect($records)->where('type', 'CNAME')->pluck('target')->toArray();

        $isPointing = ($resolvedIp === $serverIp)
            || in_array($serverIp, $aRecords)
            || in_array('menu.elab.am', $cnameRecords);

        return response()->json([
            'success' => true,
            'domain' => $domain,
            'server_ip' => $serverIp,
            'resolved_ip' => $isResolved ? $resolvedIp : null,
            'is_pointing' => $isPointing,
            'message' => $isPointing
                ? "✅ Դոմենը հաջողությամբ ուղղված է ձեր սերվերի IP-ին ({$serverIp})։"
                : ($isResolved
                    ? "⚠️ Դոմենը մատնանշում է այլ IP ({$resolvedIp})։ Փոխեք A-record-ը սերվերի IP-ին՝ {$serverIp}"
                    : "⏳ Դոմենը դեռևս չունի ակտիվ DNS գրառումներ։ Ավելացրեք A-record դեպի {$serverIp} (կամ CNAME դեպի menu.elab.am)։"),
        ]);
    }

    /**
     * Test Telegram notification connection by sending a test message.
     */
    public function testTelegramConnection(Request $request, TelegramNotificationService $telegramService): JsonResponse
    {
        $vendor = Auth::user()->vendor;

        $chatId = $request->input('chat_id') ?: $vendor->getTelegramChatId();
        $botToken = $request->input('bot_token') ?: $vendor->getTelegramBotToken();
        $topicId = $request->input('topic_id') ? (int) $request->input('topic_id') : $vendor->getTelegramTopicId();

        if (empty($chatId)) {
            return response()->json([
                'success' => false,
                'message' => 'Խնդրում ենք լրացնել Telegram Chat ID դաշտը թեստային հաղորդագրություն ուղարկելու համար։',
            ]);
        }

        $result = $telegramService->sendTestMessage(
            chatId: (string) $chatId,
            botToken: $botToken,
            topicId: $topicId,
            sourceName: $vendor->name
        );

        return response()->json($result);
    }
}
