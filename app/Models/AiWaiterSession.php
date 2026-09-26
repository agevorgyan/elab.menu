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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
