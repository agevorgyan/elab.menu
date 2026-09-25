<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\TelegramNotificationService;
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
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        try {
            $this->telegramService->sendOrderNotification($event->order);
        } catch (\Throwable $e) {
            Log::warning('SendTelegramOrderNotification failed: '.$e->getMessage(), [
                'order_id' => $event->order->id,
            ]);
        }
    }
}
