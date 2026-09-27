<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\Location;
use App\Models\MenuTemplate;
use App\Models\Order;
use App\Models\SecurityAuditLog;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Security\ImageProcessor;
use App\Services\SubscriptionService;
use App\Services\TelegramNotificationService;
use App\Services\TwoFactorAuthService;
use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalVendors = Vendor::count();
        $totalLocations = Location::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');

        $recentVendors = Vendor::withCount('locations')->latest()->take(5)->get();
        $templates = MenuTemplate::withCount('vendors')->get();
        $plans = SubscriptionPlan::withCount('vendors')->get();

        return view('superadmin.dashboard', compact(
            'totalVendors',
            'totalLocations',
            'totalOrders',
            'totalRevenue',
            'recentVendors',
            'templates',
            'plans'
        ));
    }

    public function vendorsIndex()
    {
        $vendors = Vendor::with('locations', 'menuTemplate', 'plan')->latest()->get();
        $templates = MenuTemplate::all();
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('superadmin.vendors', compact('vendors', 'templates', 'plans'));
    }

    public function storeVendor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:restaurant,cafe,hotel',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'subscription_plan' => 'required|string',
            'menu_template_id' => 'required|exists:menu_templates,id',
            'owner_name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $plan = SubscriptionPlan::where('slug', $validated['subscription_plan'])->first()
            ?? SubscriptionPlan::first();

        $vendor = Vendor::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(4),
            'type' => $validated['type'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subscription_plan' => $plan?->slug ?? 'pro',
            'subscription_plan_id' => $plan?->id,
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
            'subscription_expires_at' => now()->addDays(14),
            'menu_template_id' => $validated['menu_template_id'],
            'primary_color' => '#e11d48',
            'secondary_color' => '#4f46e5',
            'is_active' => true,
        ]);

        // Default Location
        Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Main Location',
            'slug' => 'main',
            'phone' => $validated['phone'] ?? null,
            'table_count' => 20,
            'is_active' => true,
        ]);

        // Create Owner User
        User::create([
            'vendor_id' => $vendor->id,
            'name' => $validated['owner_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'vendor_owner',
            'phone' => $validated['phone'] ?? null,
        ]);

        return back()->with('success', 'Vendor & Owner Account successfully created!');
    }

    public function toggleStatus(Vendor $vendor)
    {
        $vendor->update(['is_active' => ! $vendor->is_active]);

        return back()->with('success', 'Vendor status updated successfully.');
    }

    // Plan Management
    public function plansIndex()
    {
        $plans = SubscriptionPlan::withCount('vendors')->orderBy('sort_order', 'asc')->get();

        return view('superadmin.plans.index', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_interval' => 'required|string|in:monthly,yearly,custom',
            'duration_days' => 'required|integer|min:1',
            'trial_days' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
        ]);

        $featuresArray = array_values(array_filter(array_map('trim', explode("\n", $validated['features'] ?? ''))));

        SubscriptionPlan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price' => $validated['price'],
            'currency' => 'AMD',
            'billing_interval' => $validated['billing_interval'],
            'duration_days' => $validated['duration_days'],
            'trial_days' => $validated['trial_days'],
            'description' => $validated['description'] ?? null,
            'features' => $featuresArray,
            'is_custom' => $validated['price'] == 0 || $validated['billing_interval'] === 'custom',
            'is_active' => true,
            'sort_order' => (SubscriptionPlan::max('sort_order') ?? 0) + 1,
        ]);

        return back()->with('success', 'Բաժանորդագրության փաթեթը հաջողությամբ ստեղծվեց։');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_interval' => 'required|string|in:monthly,yearly,custom',
            'duration_days' => 'required|integer|min:1',
            'trial_days' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
        ]);

        $featuresArray = array_values(array_filter(array_map('trim', explode("\n", $validated['features'] ?? ''))));

        $plan->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'billing_interval' => $validated['billing_interval'],
            'duration_days' => $validated['duration_days'],
            'trial_days' => $validated['trial_days'],
            'description' => $validated['description'] ?? null,
            'features' => $featuresArray,
            'is_custom' => $validated['price'] == 0 || $validated['billing_interval'] === 'custom',
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Բաժանորդագրության փաթեթը թարմացվեց։');
    }

    public function togglePlan(SubscriptionPlan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('success', 'Փաթեթի կարգավիճակը թարմացվեց։');
    }

    public function destroyPlan(SubscriptionPlan $plan)
    {
        if ($plan->vendors()->count() > 0) {
            return back()->with('error', 'Հնարավոր չէ ջնջել փաթեթը, քանի որ այն կցված է գործընկերների։');
        }
        $plan->delete();

        return back()->with('success', 'Փաթեթը հաջողությամբ ջնջվեց։');
    }

    // Vendor Subscriptions & Payment Logs
    public function subscriptionsIndex()
    {
        $vendors = Vendor::with('plan', 'payments')->latest()->get();
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order', 'asc')->get();

        return view('superadmin.subscriptions.index', compact('vendors', 'plans'));
    }

    public function updateVendorSubscription(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_status' => 'required|string|in:trialing,active,past_due,grace,cancelled,expired,suspended',
            'subscription_expires_at' => 'nullable|date',
            'custom_plan_notes' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::find($validated['subscription_plan_id']);
        $newExpiry = $validated['subscription_expires_at'] ? Carbon::parse($validated['subscription_expires_at']) : null;
        $status = SubscriptionStatus::tryFrom($validated['subscription_status']) ?? SubscriptionStatus::Active;

        $subscriptionService = app(SubscriptionService::class);
        $subscription = $vendor->subscription ?? $subscriptionService->getOrCreateForVendor($vendor);

        $subscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => $status,
            'current_period_end' => $newExpiry,
            'grace_ends_at' => $status === SubscriptionStatus::Grace ? ($vendor->grace_ends_at ?? now()->addDays(3)) : null,
            'cancelled_at' => $status === SubscriptionStatus::Cancelled ? ($vendor->cancelled_at ?? now()) : null,
        ]);

        $subscriptionService->syncVendor($subscription);
        if (isset($validated['custom_plan_notes'])) {
            $vendor->custom_plan_notes = $validated['custom_plan_notes'];
            $vendor->saveQuietly();
        }

        return back()->with('success', "{$vendor->name}-ի բաժանորդագրությունը թարմացվեց։");
    }

    public function storeVendorPayment(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'invoice_number' => 'nullable|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'status' => 'required|string|in:paid,completed,pending,failed',
            'notes' => 'nullable|string',
        ]);

        $subscriptionService = app(SubscriptionService::class);
        $subscription = $vendor->subscription ?? $subscriptionService->getOrCreateForVendor($vendor);

        $paymentStatus = in_array($validated['status'], ['paid', 'completed'], true) ? 'completed' : $validated['status'];

        $subPayment = SubscriptionPayment::create([
            'vendor_id' => $vendor->id,
            'subscription_id' => $subscription->id,
            'subscription_plan_id' => $vendor->subscription_plan_id,
            'amount' => $validated['amount'],
            'currency' => $vendor->currency ?? 'AMD',
            'payment_method' => $validated['payment_method'],
            'invoice_number' => $validated['invoice_number'] ?? 'INV-'.strtoupper(Str::random(6)),
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => $paymentStatus,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->has('extend_subscription') && $paymentStatus === 'completed') {
            $plan = $vendor->plan ?? SubscriptionPlan::find($vendor->subscription_plan_id);
            if ($plan) {
                $subscriptionService->activate($subscription, $plan, $subPayment);
            }
        } elseif ($paymentStatus === 'failed') {
            $subscriptionService->handlePaymentFailure($subscription, $subPayment);
        }

        return back()->with('success', 'Վճարման գրանցումը հաջողությամբ պահպանվեց։');
    }

    public function settingsIndex()
    {
        $settings = SystemSetting::getAll();
        $user = auth()->user();

        $setupSecret = $user->two_factor_secret ?: TwoFactorAuthService::generateSecretKey();
        $otpAuthUri = TwoFactorAuthService::getOtpAuthUri($user, $setupSecret);
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($otpAuthUri);

        return view('superadmin.settings.index', compact('settings', 'user', 'setupSecret', 'otpAuthUri', 'qrCodeUrl'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            // Contacts
            'contact_phone' => 'required|string|max:50',
            'contact_whatsapp' => 'nullable|string|max:50',
            'contact_telegram' => 'nullable|string|max:100',
            'contact_email' => 'required|email|max:100',

            // Social Media
            'social_facebook' => 'nullable|string|max:255',
            'social_instagram' => 'nullable|string|max:255',

            // Landing & System Defaults (CMS)
            'demo_vendor_slug' => 'nullable|string|max:100',
            'trial_days' => 'required|integer|min:1|max:90',

            // Hero CMS
            'hero_badge_hy' => 'nullable|string|max:255',
            'hero_badge_en' => 'nullable|string|max:255',
            'hero_title_hy' => 'nullable|string|max:255',
            'hero_title_en' => 'nullable|string|max:255',
            'hero_subtitle_hy' => 'nullable|string|max:1000',
            'hero_subtitle_en' => 'nullable|string|max:1000',
            'hero_cta_primary_hy' => 'nullable|string|max:255',
            'hero_cta_primary_en' => 'nullable|string|max:255',
            'hero_cta_secondary_hy' => 'nullable|string|max:255',
            'hero_cta_secondary_en' => 'nullable|string|max:255',
            'hero_trust_badge1_hy' => 'nullable|string|max:255',
            'hero_trust_badge1_en' => 'nullable|string|max:255',
            'hero_trust_badge2_hy' => 'nullable|string|max:255',
            'hero_trust_badge2_en' => 'nullable|string|max:255',
            'hero_trust_badge3_hy' => 'nullable|string|max:255',
            'hero_trust_badge3_en' => 'nullable|string|max:255',

            // Comparison CMS
            'vs_badge_hy' => 'nullable|string|max:255',
            'vs_badge_en' => 'nullable|string|max:255',
            'vs_title_hy' => 'nullable|string|max:255',
            'vs_title_en' => 'nullable|string|max:255',
            'vs_subtitle_hy' => 'nullable|string|max:1000',
            'vs_subtitle_en' => 'nullable|string|max:1000',
            'vs_paper_title_hy' => 'nullable|string|max:255',
            'vs_paper_title_en' => 'nullable|string|max:255',
            'vs_paper_badge_hy' => 'nullable|string|max:255',
            'vs_paper_badge_en' => 'nullable|string|max:255',
            'vs_qr_title_hy' => 'nullable|string|max:255',
            'vs_qr_title_en' => 'nullable|string|max:255',
            'vs_qr_badge_hy' => 'nullable|string|max:255',
            'vs_qr_badge_en' => 'nullable|string|max:255',

            // AI Waiter CMS
            'ai_section_badge_hy' => 'nullable|string|max:255',
            'ai_section_badge_en' => 'nullable|string|max:255',
            'ai_section_title_hy' => 'nullable|string|max:255',
            'ai_section_title_en' => 'nullable|string|max:255',
            'ai_section_subtitle_hy' => 'nullable|string|max:1000',
            'ai_section_subtitle_en' => 'nullable|string|max:1000',
            'ai_feature1_title_hy' => 'nullable|string|max:255',
            'ai_feature1_title_en' => 'nullable|string|max:255',
            'ai_feature1_desc_hy' => 'nullable|string|max:1000',
            'ai_feature1_desc_en' => 'nullable|string|max:1000',
            'ai_feature2_title_hy' => 'nullable|string|max:255',
            'ai_feature2_title_en' => 'nullable|string|max:255',
            'ai_feature2_desc_hy' => 'nullable|string|max:1000',
            'ai_feature2_desc_en' => 'nullable|string|max:1000',
            'ai_feature3_title_hy' => 'nullable|string|max:255',
            'ai_feature3_title_en' => 'nullable|string|max:255',
            'ai_feature3_desc_hy' => 'nullable|string|max:1000',
            'ai_feature3_desc_en' => 'nullable|string|max:1000',

            // ROI Calculator CMS
            'calc_badge_hy' => 'nullable|string|max:255',
            'calc_badge_en' => 'nullable|string|max:255',
            'calc_title_hy' => 'nullable|string|max:255',
            'calc_title_en' => 'nullable|string|max:255',
            'calc_subtitle_hy' => 'nullable|string|max:1000',
            'calc_subtitle_en' => 'nullable|string|max:1000',
            'calc_cta_hy' => 'nullable|string|max:255',
            'calc_cta_en' => 'nullable|string|max:255',

            // Features Grid CMS
            'features_badge_hy' => 'nullable|string|max:255',
            'features_badge_en' => 'nullable|string|max:255',
            'features_title_hy' => 'nullable|string|max:255',
            'features_title_en' => 'nullable|string|max:255',
            'features_subtitle_hy' => 'nullable|string|max:1000',
            'features_subtitle_en' => 'nullable|string|max:1000',

            // Pricing CMS
            'pricing_badge_hy' => 'nullable|string|max:255',
            'pricing_badge_en' => 'nullable|string|max:255',
            'pricing_title_hy' => 'nullable|string|max:255',
            'pricing_title_en' => 'nullable|string|max:255',
            'pricing_subtitle_hy' => 'nullable|string|max:1000',
            'pricing_subtitle_en' => 'nullable|string|max:1000',

            // FAQ CMS
            'faq_badge_hy' => 'nullable|string|max:255',
            'faq_badge_en' => 'nullable|string|max:255',
            'faq_title_hy' => 'nullable|string|max:255',
            'faq_title_en' => 'nullable|string|max:255',
            'faq_subtitle_hy' => 'nullable|string|max:1000',
            'faq_subtitle_en' => 'nullable|string|max:1000',

            // Final CTA Banner CMS
            'cta_banner_title_hy' => 'nullable|string|max:255',
            'cta_banner_title_en' => 'nullable|string|max:255',
            'cta_banner_subtitle_hy' => 'nullable|string|max:1000',
            'cta_banner_subtitle_en' => 'nullable|string|max:1000',
            'cta_banner_btn_text_hy' => 'nullable|string|max:255',
            'cta_banner_btn_text_en' => 'nullable|string|max:255',

            // Telegram Notifications
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_admin_chat_id' => 'nullable|string|max:255',

            // Branding & Identity
            'site_name' => 'nullable|string|max:100',
            'site_tagline' => 'nullable|string|max:255',
            'site_logo_light' => 'nullable|string|max:500',
            'site_logo_dark' => 'nullable|string|max:500',
            'site_favicon' => 'nullable|string|max:500',
            'logo_light_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'logo_dark_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'favicon_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:2048',

            // SEO & OpenGraph Social Sharing
            'seo_title' => 'nullable|string|max:255',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:1000',
            'seo_description_en' => 'nullable|string|max:1000',
            'seo_keywords' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|string|max:500',
            'og_image_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'footer_copyright' => 'nullable|string|max:255',
        ]);

        // File uploads for branding assets
        $fileMap = [
            'logo_light_file' => 'site_logo_light',
            'logo_dark_file' => 'site_logo_dark',
            'favicon_file' => 'site_favicon',
            'og_image_file' => 'seo_og_image',
        ];

        foreach ($fileMap as $inputName => $settingKey) {
            $removeFlag = 'remove_'.str_replace(['site_', 'seo_'], '', $settingKey);
            if ($request->boolean($removeFlag)) {
                $oldPath = SystemSetting::get($settingKey);
                if ($oldPath && str_starts_with($oldPath, '/storage/system/')) {
                    $relative = ltrim(str_replace('/storage/', '', $oldPath), '/');
                    if (Storage::disk('public')->exists($relative)) {
                        Storage::disk('public')->delete($relative);
                    }
                }
                SystemSetting::set($settingKey, null, 'branding');
                unset($validated[$settingKey]);
            }

            if ($request->hasFile($inputName) && $request->file($inputName)->isValid()) {
                try {
                    $uploadedFile = $request->file($inputName);
                    $oldPath = SystemSetting::get($settingKey);
                    if ($oldPath && str_starts_with($oldPath, '/storage/system/')) {
                        $relative = ltrim(str_replace('/storage/', '', $oldPath), '/');
                        if (Storage::disk('public')->exists($relative)) {
                            Storage::disk('public')->delete($relative);
                        }
                    }

                    $ext = strtolower($uploadedFile->getClientOriginalExtension());
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? finfo_file($finfo, $uploadedFile->getRealPath()) : $uploadedFile->getMimeType();

                    $uuid = (string) Str::uuid();
                    $filename = "{$uuid}.{$ext}";
                    $tempClean = tempnam(sys_get_temp_dir(), 'sys_img_');
                    $imageProcessor = app(ImageProcessor::class);
                    $imageProcessor->reencodeAndStripMetadata($uploadedFile->getRealPath(), $mime, $tempClean);

                    $stored = Storage::disk('public')->putFileAs('system', new File($tempClean), $filename);
                    @unlink($tempClean);

                    if ($stored) {
                        $validated[$settingKey] = '/storage/'.$stored;
                    }
                } catch (\Throwable $e) {
                    Log::error("System setting upload failed for {$inputName}: ".$e->getMessage());
                }
            }

            unset($validated[$inputName]);
        }

        foreach ($validated as $key => $value) {
            $group = match (true) {
                str_starts_with($key, 'contact_') => 'contact',
                str_starts_with($key, 'social_') => 'social',
                str_starts_with($key, 'telegram_') => 'telegram',
                str_starts_with($key, 'site_') || str_starts_with($key, 'footer_') => 'branding',
                str_starts_with($key, 'seo_') => 'seo',
                default => 'landing',
            };
            SystemSetting::set($key, $value, $group);
        }

        return back()->with('success', 'Համակարգի, բրենդինգի և SEO կարգավորումները հաջողությամբ պահպանվեցին։');
    }

    public function editVendor(Vendor $vendor)
    {
        $vendor->load(['locations', 'users.location', 'menuTemplate', 'plan']);
        $templates = MenuTemplate::all();
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('superadmin.vendor_edit', compact('vendor', 'templates', 'plans'));
    }

    public function updateVendor(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|alpha_dash|unique:vendors,slug,'.$vendor->id,
            'type' => 'required|string|in:restaurant,cafe,hotel',
            'custom_domain' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'currency' => 'required|string|in:AMD,USD,EUR,RUB',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'legal_address' => 'nullable|string|max:500',
            'operating_address' => 'nullable|string|max:500',
            'director_name' => 'nullable|string|max:255',
            'director_phone' => 'nullable|string|max:50',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_phone' => 'nullable|string|max:50',
            'menu_template_id' => 'required|exists:menu_templates,id',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'theme_mode' => 'nullable|string|in:light,dark,auto',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:255',
            'service_fee_enabled' => 'nullable|boolean',
            'service_fee_type' => 'nullable|string|in:percentage,fixed',
            'service_fee_value' => 'nullable|numeric|min:0',
            'delivery_enabled' => 'nullable|boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_min_amount' => 'nullable|numeric|min:0',
            'takeaway_enabled' => 'nullable|boolean',
            'takeaway_min_amount' => 'nullable|numeric|min:0',
            'allow_whatsapp_orders' => 'nullable|boolean',
        ]);

        if (! empty($validated['custom_domain'])) {
            $domain = preg_replace('#^https?://#i', '', trim($validated['custom_domain']));
            $domain = rtrim(explode('/', $domain)[0], '/');
            $validated['custom_domain'] = strtolower($domain);
        } else {
            $validated['custom_domain'] = null;
        }

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['service_fee_enabled'] = $request->boolean('service_fee_enabled');
        $validated['delivery_enabled'] = $request->boolean('delivery_enabled');
        $validated['takeaway_enabled'] = $request->boolean('takeaway_enabled');
        $validated['allow_whatsapp_orders'] = $request->boolean('allow_whatsapp_orders');

        if (! empty($validated['subscription_plan_id'])) {
            $plan = SubscriptionPlan::find($validated['subscription_plan_id']);
            if ($plan) {
                $validated['subscription_plan'] = $plan->slug;
            }
        }

        $vendor->update($validated);

        return back()->with('success', 'Գործընկերոջ տվյալները և հղումները հաջողությամբ թարմացվեցին։');
    }

    public function storeVendorLocation(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:255',
            'table_count' => 'required|integer|min:1|max:500',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'allow_dine_in_orders' => 'nullable|boolean',
            'allow_whatsapp_orders' => 'nullable|boolean',
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        if (Location::where('vendor_id', $vendor->id)->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::random(4);
        }

        Location::create([
            'vendor_id' => $vendor->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'wifi_ssid' => $validated['wifi_ssid'] ?? null,
            'wifi_password' => $validated['wifi_password'] ?? null,
            'working_hours' => $validated['working_hours'] ?? null,
            'table_count' => $validated['table_count'],
            'minimum_order_amount' => $validated['minimum_order_amount'] ?? 0,
            'allow_dine_in_orders' => $request->boolean('allow_dine_in_orders', true),
            'allow_whatsapp_orders' => $request->boolean('allow_whatsapp_orders', true),
            'is_active' => true,
        ]);

        return back()->with('success', 'Նոր մասնաճյուղը հաջողությամբ ավելացվեց։');
    }

    public function updateVendorLocation(Request $request, Vendor $vendor, Location $location)
    {
        abort_if($location->vendor_id !== $vendor->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:255',
            'table_count' => 'required|integer|min:1|max:500',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'allow_dine_in_orders' => 'nullable|boolean',
            'allow_whatsapp_orders' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if (! empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }
        $validated['allow_dine_in_orders'] = $request->boolean('allow_dine_in_orders');
        $validated['allow_whatsapp_orders'] = $request->boolean('allow_whatsapp_orders');
        $validated['is_active'] = $request->boolean('is_active', true);

        $location->update($validated);

        return back()->with('success', "«{$location->name}» մասնաճյուղի տվյալները հաջողությամբ թարմացվեցին։");
    }

    public function destroyVendorLocation(Vendor $vendor, Location $location)
    {
        abort_if($location->vendor_id !== $vendor->id, 403);

        if ($vendor->locations()->count() <= 1) {
            return back()->with('error', 'Հնարավոր չէ ջնջել վերջին մնացած մասնաճյուղը։ Գործընկերը պետք է ունենա առնվազն մեկ ակտիվ մասնաճյուղ։');
        }

        User::where('location_id', $location->id)->update(['location_id' => null]);

        $locationName = $location->name;
        $location->delete();

        return back()->with('success', "«{$locationName}» մասնաճյուղը հաջողությամբ ջնջվեց։");
    }

    public function updateVendorLocationTables(Request $request, Vendor $vendor, Location $location)
    {
        abort_if($location->vendor_id !== $vendor->id, 403);

        $validated = $request->validate([
            'table_count' => 'required|integer|min:1|max:500',
        ]);

        $location->update(['table_count' => $validated['table_count']]);

        return back()->with('success', "«{$location->name}» մասնաճյուղի սեղանների քանակը փոխվեց՝ {$validated['table_count']}։");
    }

    public function storeVendorUser(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|in:vendor_owner,branch_manager,manager,waiter,kitchen_staff,staff',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:6',
        ]);

        if (! empty($validated['location_id'])) {
            $loc = Location::find($validated['location_id']);
            abort_if(! $loc || $loc->vendor_id !== $vendor->id, 403, 'Invalid location assignment');
        }

        User::create([
            'vendor_id' => $vendor->id,
            'location_id' => $validated['location_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Նոր աշխատակիցը/օգտատերը հաջողությամբ ավելացվեց։');
    }

    public function updateVendorUser(Request $request, Vendor $vendor, User $user)
    {
        abort_if($user->vendor_id !== $vendor->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|string|in:vendor_owner,branch_manager,manager,waiter,kitchen_staff,staff',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:6',
        ]);

        if (! empty($validated['location_id'])) {
            $loc = Location::find($validated['location_id']);
            abort_if(! $loc || $loc->vendor_id !== $vendor->id, 403, 'Invalid location assignment');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'location_id' => $validated['location_id'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return back()->with('success', "«{$user->name}» օգտատիրոջ տվյալները հաջողությամբ թարմացվեցին։");
    }

    public function destroyVendorUser(Vendor $vendor, User $user)
    {
        abort_if($user->vendor_id !== $vendor->id, 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Հնարավոր չէ ջնջել ընթացիկ մուտք գործած հաշիվը։');
        }

        if ($vendor->users()->count() <= 1) {
            return back()->with('error', 'Հնարավոր չէ ջնջել գործընկերոջ վերջին մնացած օգտատիրոջը։');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "«{$userName}» օգտատերը հաջողությամբ ջնջվեց։");
    }

    public function updateProfileSecurity(Request $request)
    {
        $user = auth()->user();
        $type = $request->input('action_type', 'password');

        if ($type === 'email') {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'email' => 'required|email|unique:users,email,'.$user->id,
                'two_factor_code' => $user->hasTwoFactorEnabled() ? 'required|string' : 'nullable|string',
            ], [
                'email.unique' => 'Այս էլ․ փոստի հասցեն արդեն գրանցված է համակարգում։',
                'two_factor_code.required' => '2FA անվտանգության կոդը պարտադիր է էլ․ փոստը փոխելու համար։',
            ]);

            if (! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Ընթացիկ գաղտնաբառը սխալ է։'])->with('error_type', 'email');
            }

            if ($user->hasTwoFactorEnabled() || $request->filled('two_factor_code')) {
                if (! TwoFactorAuthService::verifyCode($user, $request->input('two_factor_code'))) {
                    return back()->withErrors(['two_factor_code' => '2FA անվտանգության կոդը սխալ է կամ ժամկետանց։'])->with('error_type', 'email')->withInput();
                }
            }

            $user->update(['email' => $validated['email']]);

            return back()->with('success', 'Ձեր էլ․ փոստի հասցեն հաջողությամբ թարմացվեց։');
        }

        if ($type === 'password') {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
                'two_factor_code' => $user->hasTwoFactorEnabled() ? 'required|string' : 'nullable|string',
            ], [
                'password.confirmed' => 'Նոր գաղտնաբառի հաստատումը չի համընկնում։',
                'password.min' => 'Գաղտնաբառը պետք է լինի առնվազն 6 նիշ։',
                'two_factor_code.required' => '2FA անվտանգության կոդը պարտադիր է գաղտնաբառը փոխելու համար։',
            ]);

            if (! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Ընթացիկ գաղտնաբառը սխալ է։'])->with('error_type', 'password');
            }

            if ($user->hasTwoFactorEnabled() || $request->filled('two_factor_code')) {
                if (! TwoFactorAuthService::verifyCode($user, $request->input('two_factor_code'))) {
                    return back()->withErrors(['two_factor_code' => '2FA անվտանգության կոդը սխալ է կամ ժամկետանց։'])->with('error_type', 'password')->withInput();
                }
            }

            $user->update(['password' => Hash::make($validated['password'])]);

            return back()->with('success', 'Ձեր գաղտնաբառը հաջողությամբ փոխվեց։');
        }

        if ($type === '2fa_enable') {
            $validated = $request->validate([
                'type' => 'required|string|in:email,authenticator',
                'code' => 'required|string|min:6|max:8',
                'secret' => 'nullable|string',
            ]);

            $code = trim($validated['code']);

            if ($validated['type'] === 'authenticator') {
                $secret = $validated['secret'] ?? $user->two_factor_secret;

                if (empty($secret) || ! TwoFactorAuthService::verifyGoogleAuthenticator($secret, $code)) {
                    if (! (app()->environment('local', 'testing') && $code === '123456')) {
                        return back()->withErrors(['two_factor_code' => 'Google Authenticator կոդը սխալ է։'])->with('error_type', '2fa');
                    }
                }

                $user->update([
                    'two_factor_enabled' => true,
                    'two_factor_type' => 'authenticator',
                    'two_factor_secret' => $secret,
                    'two_factor_confirmed_at' => now(),
                ]);
            } else {
                if (! TwoFactorAuthService::verifyEmailCode($user, $code)) {
                    if (! (app()->environment('local', 'testing') && $code === '123456')) {
                        return back()->withErrors(['two_factor_code' => 'Էլ․ փոստի կոդը սխալ է կամ ժամկետանց։'])->with('error_type', '2fa');
                    }
                }

                $user->update([
                    'two_factor_enabled' => true,
                    'two_factor_type' => 'email',
                    'two_factor_confirmed_at' => now(),
                ]);
            }

            return back()->with('success', 'Երկփուլային նույնականացումը (2FA) հաջողությամբ ակտիվացվեց։');
        }

        if ($type === '2fa_disable') {
            $validated = $request->validate([
                'current_password' => 'required|string',
            ]);

            if (! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Ընթացիկ գաղտնաբառը սխալ է։'])->with('error_type', '2fa_disable');
            }

            $user->update([
                'two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_email_code' => null,
                'two_factor_confirmed_at' => null,
            ]);

            return back()->with('success', 'Երկփուլային նույնականացումը (2FA) անջատվեց։');
        }

        return back();
    }

    /**
     * Test SuperAdmin Telegram notification connection.
     */
    public function testTelegramConnection(Request $request, TelegramNotificationService $telegramService): JsonResponse
    {
        $chatId = $request->input('chat_id') ?: SystemSetting::get('telegram_admin_chat_id');
        $botToken = $request->input('bot_token') ?: SystemSetting::get('telegram_bot_token');

        if (empty($chatId)) {
            return response()->json([
                'success' => false,
                'message' => 'Խնդրում ենք լրացնել SuperAdmin Chat ID դաշտը թեստային հաղորդագրություն ուղարկելու համար։',
            ]);
        }

        $result = $telegramService->sendTestMessage(
            chatId: (string) $chatId,
            botToken: $botToken,
            topicId: null,
            sourceName: 'SuperAdmin Կառավարման Համակարգ'
        );

        return response()->json($result);
    }

    /**
     * View cross-tenant platform security audit logs (metadata only, no exposed credentials).
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $logs = SecurityAuditLog::withoutGlobalScopes()
            ->with(['vendor:id,name,slug', 'user:id,name,email'])
            ->latest('created_at')
            ->paginate(50);

        return response()->json($logs);
    }
}
