<?php

namespace App\Events;

use App\Models\WaiterCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaiterCalled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WaiterCall $waiterCall
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vendor.' . $this->waiterCall->vendor_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'WaiterCalled';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->waiterCall->id,
            'table_number' => $this->waiterCall->table_number,
            'type' => $this->waiterCall->type,
            'type_label' => $this->waiterCall->getTypeLabel(),
            'status' => $this->waiterCall->status,
            'notes' => $this->waiterCall->notes,
            'created_at_human' => $this->waiterCall->created_at?->diffForHumans() ?? 'հենց նոր',
        ];
    }
}
