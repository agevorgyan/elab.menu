<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'subscription_plan_id',
        'status',
        'billing_interval',
        'trial_starts_at',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'grace_ends_at',
        'cancelled_at',
        'renewal_state',
        'auto_renew',
        'last_payment_reference',
        'metadata',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'grace_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Relationship to the owning vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Authoritative Subscription Plan (single source of truth).
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Associated billing and invoice payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
    }

    /**
     * Check if currently in a valid trial period.
     */
    public function isTrialing(): bool
    {
        if ($this->status === SubscriptionStatus::Trialing) {
            return $this->trial_ends_at ? $this->trial_ends_at->isFuture() : true;
        }

        return false;
    }

    /**
     * Check if subscription is actively paid and valid.
     */
    public function isActive(): bool
    {
        if ($this->status === SubscriptionStatus::Active) {
            return $this->current_period_end ? $this->current_period_end->isFuture() : true;
        }

        return false;
    }

    /**
     * Check if subscription is currently in grace period.
     */
    public function isInGracePeriod(): bool
    {
        return in_array($this->status, [SubscriptionStatus::Grace, SubscriptionStatus::PastDue], true)
            && $this->grace_ends_at !== null
            && $this->grace_ends_at->isFuture();
    }

    /**
     * Check if subscription was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled || $this->cancelled_at !== null;
    }

    /**
     * Check if subscription has been administratively suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === SubscriptionStatus::Suspended;
    }

    /**
     * Check if subscription has expired and has no access.
     */
    public function isExpired(): bool
    {
        if (in_array($this->status, [SubscriptionStatus::Expired, SubscriptionStatus::Suspended, SubscriptionStatus::PastDue], true)) {
            return true;
        }

        if ($this->isInGracePeriod()) {
            return false;
        }

        if ($this->status === SubscriptionStatus::Grace) {
            return true;
        }

        if ($this->isTrialing()) {
            return false;
        }

        if ($this->status === SubscriptionStatus::Trialing && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return true;
        }

        if ($this->current_period_end && $this->current_period_end->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if this subscription currently allows access to vendor features.
     */
    public function allowsAccess(): bool
    {
        if (in_array($this->status, [SubscriptionStatus::Suspended, SubscriptionStatus::Expired, SubscriptionStatus::PastDue], true)) {
            return false;
        }

        if ($this->isInGracePeriod()) {
            return true;
        }

        if ($this->status === SubscriptionStatus::Grace) {
            return false;
        }

        if ($this->isTrialing()) {
            return true;
        }

        if ($this->status === SubscriptionStatus::Active) {
            return $this->current_period_end ? $this->current_period_end->isFuture() : true;
        }

        if ($this->status === SubscriptionStatus::Cancelled) {
            return $this->current_period_end ? $this->current_period_end->isFuture() : false;
        }

        return false;
    }

    /**
     * Days left in current period, trial, or grace.
     */
    public function daysLeft(): int
    {
        $targetDate = null;

        if ($this->isInGracePeriod()) {
            $targetDate = $this->grace_ends_at;
        } elseif ($this->isTrialing()) {
            $targetDate = $this->trial_ends_at;
        } elseif ($this->current_period_end) {
            $targetDate = $this->current_period_end;
        }

        if (! $targetDate) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInHours($targetDate, false) / 24));
    }

    /**
     * Check if subscription includes a particular feature.
     */
    public function hasFeature(string $feature): bool
    {
        $planSlug = strtolower($this->plan?->slug ?? 'pro');

        if ($planSlug === 'custom' || $planSlug === 'business') {
            return true;
        }

        return match ($feature) {
            'orders', 'online_orders', 'customers' => in_array($planSlug, ['pro', 'business', 'custom'], true),
            'locations', 'multi_location', 'team', 'staff_roles', 'advanced_analytics' => in_array($planSlug, ['business', 'custom'], true),
            default => true,
        };
    }
}
