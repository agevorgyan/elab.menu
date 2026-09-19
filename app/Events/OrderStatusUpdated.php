<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vendor.'.$this->order->vendor_id),
            new Channel('order.'.$this->order->order_number),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'OrderStatusUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $stepMap = [
            'pending' => ['step' => 1, 'percent' => 25],
            'accepted' => ['step' => 2, 'percent' => 50],
            'preparing' => ['step' => 3, 'percent' => 75],
            'ready' => ['step' => 4, 'percent' => 95],
            'completed' => ['step' => 5, 'percent' => 100],
            'cancelled' => ['step' => 0, 'percent' => 0],
        ];

        $meta = $stepMap[$this->order->status] ?? ['step' => 1, 'percent' => 20];

        return [
            'id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'status_label' => __('menu.status_'.$this->order->status),
            'status_desc' => __('menu.status_desc_'.$this->order->status),
            'status_step' => $meta['step'],
            'status_percent' => $meta['percent'],
        ];
    }
}
