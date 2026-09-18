<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\VendorAdminController;
use App\Http\Controllers\MenuBuilderController;
use App\Http\Controllers\AiMenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\QrStudioController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ClientStorefrontController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RegisterController;

// 1. Landing & Client Storefront PWA Routes
Route::get('/', function () {
    return redirect()->route('client.menu', ['vendor_slug' => 'bistro-yerevan']);
});

Route::get('/m/{vendor_slug}/{location_slug?}', [ClientStorefrontController::class, 'showMenu'])->name('client.menu');
Route::get('/m/{vendor_slug}/manifest.json', [ClientStorefrontController::class, 'manifest'])->name('client.manifest');
Route::get('/m/{vendor_slug}/sw.js', [ClientStorefrontController::class, 'serviceWorker'])->name('client.sw');
Route::post('/api/m/{vendor_slug}/order', [ClientStorefrontController::class, 'submitOrder'])->name('client.order.submit');

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
});

// 4. Vendor Admin Panel (/admin)
Route::middleware(['auth', 'role:vendor_owner,manager,staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [VendorAdminController::class, 'dashboard'])->name('dashboard');
    
    // Locations & Team
    Route::get('/locations', [VendorAdminController::class, 'locationsIndex'])->name('locations.index');
    Route::post('/locations', [VendorAdminController::class, 'storeLocation'])->name('locations.store');
    Route::get('/team', [VendorAdminController::class, 'teamIndex'])->name('team.index');
    Route::post('/team', [VendorAdminController::class, 'storeTeamMember'])->name('team.store');

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

    // Live Orders & Kitchen Panel
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

    // Branding & Theme Customizer
    Route::get('/branding', [BrandingController::class, 'index'])->name('branding.index');
    Route::post('/branding', [BrandingController::class, 'update'])->name('branding.update');

    // QR Code Studio
    Route::get('/qr', [QrStudioController::class, 'index'])->name('qr.index');

    // Customers CRM
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});
