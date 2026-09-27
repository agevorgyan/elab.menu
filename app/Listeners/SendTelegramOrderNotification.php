<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\TelegramNotificationService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Log;

class SendTelegramOrderNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public TelegramNotificationService $telegramService
    ) {}

    /**
     * Handle the event within the order's specific tenant context.
     */
    public function handle(OrderCreated $event): void
    {
        try {
            app(TenantContext::class)->runInTenantContext($event->order->vendor_id, function () use ($event) {
                $this->telegramService->sendOrderNotification($event->order);
            });
        } catch (\Throwable $e) {
            Log::warning('SendTelegramOrderNotification failed: '.$e->getMessage(), [
                'order_id' => $event->order->id,
                'vendor_id' => $event->order->vendor_id,
            ]);
        }
    }
}
