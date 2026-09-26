<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Database\Factories\AiWaiterSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiWaiterSession extends Model
{
    /** @use HasFactory<AiWaiterSessionFactory> */
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'location_id',
        'session_token',
        'table_number',
        'language',
        'status',
        'preferences',
        'questions_history',
        'answers_history',
        'recommendations',
        'cart_items',
        'order_id',
        'total_order_amount',
        'completed_at',
    ];

    protected $casts = [
        'preferences' => 'array',
        'questions_history' => 'array',
        'answers_history' => 'array',
        'recommendations' => 'array',
        'cart_items' => 'array',
        'total_order_amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (AiWaiterSession $session) {
            if ($session->location_id && $session->vendor_id) {
                $location = Location::withoutGlobalScopes()->find($session->location_id);
                if ($location && (int) $location->vendor_id !== (int) $session->vendor_id) {
                    throw new \InvalidArgumentException('Cross-vendor location assigned to AI session.');
                }
            }

            if ($session->order_id && $session->vendor_id) {
                $order = Order::withoutGlobalScopes()->find($session->order_id);
                if ($order && (int) $order->vendor_id !== (int) $session->vendor_id) {
                    throw new \InvalidArgumentException('Cross-vendor order assigned to AI session.');
                }
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
