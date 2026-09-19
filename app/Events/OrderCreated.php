<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
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
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'OrderCreated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'table_number' => $this->order->table_number,
            'type' => $this->order->type,
            'status' => $this->order->status,
            'total_amount' => (float) $this->order->total_amount,
            'customer_name' => $this->order->customer_name,
            'notes' => $this->order->notes,
            'items_count' => $this->order->items()->count(),
            'created_at_human' => $this->order->created_at?->diffForHumans() ?? 'հենց նոր',
            'created_at_time' => $this->order->created_at?->format('H:i') ?? date('H:i'),
        ];
    }
}
