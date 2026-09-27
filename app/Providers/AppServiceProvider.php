<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\WaiterCalled;
use App\Listeners\SecurityAuditAuthSubscriber;
use App\Listeners\SendTelegramOrderNotification;
use App\Listeners\SendTelegramWaiterCallNotification;
use App\Models\AiWaiterSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\LocationProductOverride;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStorageFile;
use App\Models\WaiterCall;
use App\Policies\AiWaiterSessionPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\LocationPolicy;
use App\Policies\LocationProductOverridePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorPolicy;
use App\Policies\VendorStorageFilePolicy;
use App\Policies\WaiterCallPolicy;
use App\Security\Permission;
use App\Services\TenantContext;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') || str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Guarantee tenant context isolation across queue worker lifecycle
        Queue::before(function (JobProcessing $event): void {
            app(TenantContext::class)->clear();
        });

        Queue::after(function (JobProcessed $event): void {
            app(TenantContext::class)->clear();
        });

        Queue::failing(function (JobFailed $event): void {
            app(TenantContext::class)->clear();
        });

        Queue::exceptionOccurred(function (JobExceptionOccurred $event): void {
            app(TenantContext::class)->clear();
        });

        // Register Telegram notification event listeners
        Event::listen(OrderCreated::class, SendTelegramOrderNotification::class);
        Event::listen(WaiterCalled::class, SendTelegramWaiterCallNotification::class);

        // Register Security Audit Authentication Subscriber
        Event::subscribe(SecurityAuditAuthSubscriber::class);

        // Register Multi-Tenant Security Policies
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(WaiterCall::class, WaiterCallPolicy::class);
        Gate::policy(LocationProductOverride::class, LocationProductOverridePolicy::class);
        Gate::policy(AiWaiterSession::class, AiWaiterSessionPolicy::class);
        Gate::policy(VendorStorageFile::class, VendorStorageFilePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Register Gates for all granular permissions
        foreach (Permission::all() as $permission) {
            Gate::define($permission, function (User $user) use ($permission): bool {
                return $user->hasPermission($permission);
            });
        }
    }
}
