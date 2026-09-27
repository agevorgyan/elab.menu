<?php

namespace App\Listeners;

use App\Events\WaiterCalled;
use App\Services\TelegramNotificationService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Log;

class SendTelegramWaiterCallNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public TelegramNotificationService $telegramService
    ) {}

    /**
     * Handle the event within the waiter call's specific tenant context.
     */
    public function handle(WaiterCalled $event): void
    {
        try {
            app(TenantContext::class)->runInTenantContext($event->waiterCall->vendor_id, function () use ($event) {
                $this->telegramService->sendWaiterCallNotification($event->waiterCall);
            });
        } catch (\Throwable $e) {
            Log::warning('SendTelegramWaiterCallNotification failed: '.$e->getMessage(), [
                'waiter_call_id' => $event->waiterCall->id,
                'vendor_id' => $event->waiterCall->vendor_id,
            ]);
        }
    }
}
