<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\WaiterCalled;
use App\Listeners\SendTelegramOrderNotification;
use App\Listeners\SendTelegramWaiterCallNotification;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Event;
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

        // Register Telegram notification event listeners
        Event::listen(OrderCreated::class, SendTelegramOrderNotification::class);
        Event::listen(WaiterCalled::class, SendTelegramWaiterCallNotification::class);
    }
}
