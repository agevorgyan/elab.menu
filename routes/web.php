<?php

use App\Http\Controllers\AiMenuController;
use App\Http\Controllers\AiWaiterController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\ClientStorefrontController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MenuBuilderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QrStudioController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\VendorAdminController;
use App\Http\Controllers\VendorSettingsController;
use App\Http\Middleware\EnsurePlanHasFeature;
use App\Http\Middleware\EnsureSubscriptionIsActive;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. Landing & Client Storefront PWA Routes
Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/manifest.json', function (TenantContext $tenantContext) {
    $vendor = $tenantContext->getTenant();
    if ($vendor) {
        return app(ClientStorefrontController::class)->manifest($vendor->slug);
    }
    abort(404);
});

Route::get('/sw.js', function (TenantContext $tenantContext) {
    $vendor = $tenantContext->getTenant();
    if ($vendor) {
        return app(ClientStorefrontController::class)->serviceWorker($vendor->slug);
    }
    abort(404);
});

Route::get('/m/{vendor_slug}/manifest.json', [ClientStorefrontController::class, 'manifest'])->name('client.manifest');
Route::get('/m/{vendor_slug}/sw.js', [ClientStorefrontController::class, 'serviceWorker'])->name('client.sw');
Route::get('/m/{vendor_slug}/{location_slug?}', [ClientStorefrontController::class, 'showMenu'])->name('client.menu');

// Rate-limited public order & waiter endpoints (anti-spam & DDoS protection)
Route::middleware('throttle:15,1')->group(function () {
    Route::post('/api/m/{vendor_slug}/order', [ClientStorefrontController::class, 'submitOrder'])->name('client.order.submit');
    Route::post('/api/m/{vendor_slug}/call-waiter', [ClientStorefrontController::class, 'callWaiter'])->name('client.waiter.call');
});
Route::get('/api/m/{vendor_slug}/order/{order_number}/status', [ClientStorefrontController::class, 'orderStatus'])
    ->middleware('throttle:60,1')
    ->name('client.order.status');

// AI Waiter Advisor Endpoints
Route::prefix('/api/m/{vendor_slug}/ai-waiter')->group(function () {
    Route::post('/recommend', [AiWaiterController::class, 'recommend'])->name('client.ai_waiter.recommend');
    Route::get('/pairings/{product_id}', [AiWaiterController::class, 'pairings'])->name('client.ai_waiter.pairings');
});

// Admin Language Switcher
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['hy', 'en', 'ru'])) {
        session(['app_locale' => $locale]);
    }

    return redirect()->back();
})->name('lang.switch');

// Legal Documents
Route::get('/privacy-policy', function () {
    return view('legal.privacy');
})->name('legal.privacy');

Route::get('/terms-of-service', function () {
    return view('legal.terms');
})->name('legal.terms');

// 2. Auth & Registration Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/demo/login', [AuthController::class, 'showDemoLogin'])->name('demo.login');
Route::post('/demo/login', [AuthController::class, 'demoLogin'])->name('demo.login.post');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register.show');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

Route::get('/email/verify', [RegisterController::class, 'showVerificationNotice'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [RegisterController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/email/verify/demo', [RegisterController::class, 'directDemoVerify'])->name('verification.demo');

// 3. Super Admin Panel (/superadmin)
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/vendors', [SuperAdminController::class, 'vendorsIndex'])->name('vendors.index');
    Route::post('/vendors', [SuperAdminController::class, 'storeVendor'])->name('vendors.store');
    Route::post('/vendors/{vendor}/toggle', [SuperAdminController::class, 'toggleStatus'])->name('vendors.toggle');

    // Subscription Plans Management
    Route::get('/plans', [SuperAdminController::class, 'plansIndex'])->name('plans.index');
    Route::post('/plans', [SuperAdminController::class, 'storePlan'])->name('plans.store');
    Route::post('/plans/{plan}', [SuperAdminController::class, 'updatePlan'])->name('plans.update');
    Route::post('/plans/{plan}/toggle', [SuperAdminController::class, 'togglePlan'])->name('plans.toggle');
    Route::delete('/plans/{plan}', [SuperAdminController::class, 'destroyPlan'])->name('plans.destroy');

    // Vendor Subscriptions & Payment Logs
    Route::get('/subscriptions', [SuperAdminController::class, 'subscriptionsIndex'])->name('subscriptions.index');
    Route::post('/subscriptions/{vendor}', [SuperAdminController::class, 'updateVendorSubscription'])->name('subscriptions.update');
    Route::post('/subscriptions/{vendor}/payments', [SuperAdminController::class, 'storeVendorPayment'])->name('subscriptions.payments.store');

    // Landing & System Settings Management
    Route::get('/settings', [SuperAdminController::class, 'settingsIndex'])->name('settings.index');
    Route::post('/settings', [SuperAdminController::class, 'updateSettings'])->name('settings.update');
});

// 4. Vendor Admin Panel (/admin)
Route::middleware(['auth', 'role:vendor_owner,manager,staff', EnsureSubscriptionIsActive::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [VendorAdminController::class, 'dashboard'])->name('dashboard');

    // Vendor Subscription Status & Payment History
    Route::get('/subscription', [VendorAdminController::class, 'subscriptionIndex'])->name('subscription');

    // Locations & Team (Business Plan Feature)
    Route::middleware([EnsurePlanHasFeature::class.':locations'])->group(function () {
        Route::get('/locations', [VendorAdminController::class, 'locationsIndex'])->name('locations.index');
        Route::post('/locations', [VendorAdminController::class, 'storeLocation'])->name('locations.store');
    });

    Route::middleware([EnsurePlanHasFeature::class.':team'])->group(function () {
        Route::get('/team', [VendorAdminController::class, 'teamIndex'])->name('team.index');
        Route::post('/team', [VendorAdminController::class, 'storeTeamMember'])->name('team.store');
    });

    // Menu Builder
    Route::get('/menu', [MenuBuilderController::class, 'index'])->name('menu.index');
    Route::post('/menu/categories', [MenuBuilderController::class, 'storeCategory'])->name('menu.categories.store');
    Route::post('/menu/categories/{category}', [MenuBuilderController::class, 'updateCategory'])->name('menu.categories.update');
    Route::delete('/menu/categories/{category}', [MenuBuilderController::class, 'destroyCategory'])->name('menu.categories.destroy');
    Route::post('/menu/products', [MenuBuilderController::class, 'storeProduct'])->name('menu.products.store');
    Route::post('/menu/products/{product}', [MenuBuilderController::class, 'updateProduct'])->name('menu.products.update');
    Route::post('/menu/products/{product}/toggle', [MenuBuilderController::class, 'toggleAvailability'])->name('menu.products.toggle');
    Route::post('/menu/products/{product}/override', [MenuBuilderController::class, 'saveOverride'])->name('menu.products.override');
    Route::delete('/menu/products/{product}', [MenuBuilderController::class, 'destroyProduct'])->name('menu.products.destroy');

    // AI Tools
    Route::get('/ai/import', [AiMenuController::class, 'showImportForm'])->name('ai.import');
    Route::post('/ai/import/process', [AiMenuController::class, 'processImport'])->name('ai.import.process');
    Route::post('/ai/import/confirm', [AiMenuController::class, 'confirmImport'])->name('ai.import.confirm');
    Route::post('/ai/translate', [AiMenuController::class, 'translateMenu'])->name('ai.translate');

    // Live Orders & Kitchen Panel (Pro / Business Plan Feature)
    Route::middleware([EnsurePlanHasFeature::class.':orders'])->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/feed', [OrderController::class, 'feed'])->name('orders.feed');
        Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/waiter-calls/{waiterCall}/status', [OrderController::class, 'updateWaiterCallStatus'])->name('waiter_calls.status');
    });

    // Branding & Theme Customizer
    Route::get('/branding', [BrandingController::class, 'index'])->name('branding.index');
    Route::post('/branding', [BrandingController::class, 'update'])->name('branding.update');

    // Restaurant Settings (Service Fee & Delivery)
    Route::get('/settings', [VendorSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [VendorSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/domain/check', [VendorSettingsController::class, 'checkDomainDns'])->name('settings.domain.check');
    Route::get('/settings/ai', [VendorSettingsController::class, 'aiIndex'])->name('settings.ai');
    Route::post('/settings/ai', [VendorSettingsController::class, 'aiUpdate'])->name('settings.ai.update');
    Route::post('/settings/ai/test', [VendorSettingsController::class, 'testAiConnection'])->name('settings.ai.test');

    // QR Code Studio
    Route::get('/qr', [QrStudioController::class, 'index'])->name('qr.index');

    // Customers CRM (Pro / Business Plan Feature)
    Route::middleware([EnsurePlanHasFeature::class.':customers'])->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});

// 5. Custom Domain Branch/Location Route (e.g. https://example.com/cascades)
Route::get('/{location_slug}', function (Request $request, string $location_slug, TenantContext $tenantContext) {
    $customDomainVendor = $tenantContext->getTenant();
    if ($customDomainVendor) {
        $location = $customDomainVendor->locations()->where('slug', $location_slug)->first();
        if ($location) {
            return app(ClientStorefrontController::class)->showMenu($request, $customDomainVendor->slug, $location_slug);
        }
    }

    abort(404);
})->where('location_slug', '^[a-zA-Z0-9_-]+$');
