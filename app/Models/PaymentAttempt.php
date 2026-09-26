<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentAttempt extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'uuid',
        'vendor_id',
        'order_id',
        'subscription_id',
        'gateway',
        'merchant_reference',
        'provider_transaction_id',
        'amount',
        'currency',
        'status',
        'request_payload',
        'response_payload',
        'idempotency_key',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'request_payload' => 'array',
        'response_payload' => 'array',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PaymentAttempt $attempt): void {
            if (empty($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid();
            }
            if (empty($attempt->status)) {
                $attempt->status = PaymentStatus::Created;
            }
            if (empty($attempt->expires_at)) {
                $attempt->expires_at = now()->addMinutes(30);
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function subscriptionPayment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class, 'subscription_id');
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isExpired(): bool
    {
        if ($this->status === PaymentStatus::Expired) {
            return true;
        }

        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function canTransitionTo(PaymentStatus $target): bool
    {
        return $this->status->canTransitionTo($target);
    }
}
