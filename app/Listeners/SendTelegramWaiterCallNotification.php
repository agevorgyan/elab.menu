<?php

namespace App\Listeners;

use App\Events\WaiterCalled;
use App\Services\TelegramNotificationService;
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
     * Handle the event.
     */
    public function handle(WaiterCalled $event): void
    {
        try {
            $this->telegramService->sendWaiterCallNotification($event->waiterCall);
        } catch (\Throwable $e) {
            Log::warning('SendTelegramWaiterCallNotification failed: '.$e->getMessage(), [
                'waiter_call_id' => $event->waiterCall->id,
            ]);
        }
    }
}
