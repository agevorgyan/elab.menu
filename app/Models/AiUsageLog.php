<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'session_id',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'estimated_cost',
        'latency_ms',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
        'latency_ms' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * The AI Waiter session associated with this usage log, if any.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AiWaiterSession::class, 'session_id');
    }
}
