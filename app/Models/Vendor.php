<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'legal_name',
        'legal_address',
        'tax_id',
        'director_name',
        'contact_person_name',
        'operating_address',
        'expected_locations_count',
        'logo',
        'cover_image',
        'phone',
        'email',
        'currency',
        'custom_domain',
        'menu_template_id',
        'primary_color',
        'secondary_color',
        'accent_color',
        'text_color',
        'bg_color',
        'theme_mode',
        'custom_css',
        'subscription_plan',
        'subscription_plan_id',
        'subscription_status',
        'trial_ends_at',
        'subscription_expires_at',
        'custom_plan_notes',
        'is_active',
        'email_verified_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function menuTemplate()
    {
        return $this->belongsTo(MenuTemplate::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('sort_order', 'asc');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function analyticsLogs()
    {
        return $this->hasMany(AnalyticsLog::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class)->latest();
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class)->latest();
    }

    public function isTrialing(): bool
    {
        if ($this->subscription_status === 'trialing') {
            return $this->trial_ends_at ? $this->trial_ends_at->isFuture() : true;
        }

        return false;
    }

    public function isExpired(): bool
    {
        if (! $this->is_active || $this->subscription_status === 'expired') {
            return true;
        }

        if ($this->subscription_status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return true;
        }

        if ($this->subscription_expires_at && $this->subscription_expires_at->isPast()) {
            return true;
        }

        return false;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function daysLeft(): int
    {
        $targetDate = $this->isTrialing() ? $this->trial_ends_at : $this->subscription_expires_at;
        if (! $targetDate) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInHours($targetDate, false) / 24));
    }

    public function getSubscriptionStatusLabelAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Ավարտված / Անջատված';
        }
        if ($this->isTrialing()) {
            return 'Փորձնական (14 օր)';
        }
        if ($this->subscription_status === 'active') {
            return 'Ակտիվ';
        }

        return ucfirst($this->subscription_status ?? 'Active');
    }

    public function hasFeature(string $feature): bool
    {
        $planSlug = strtolower($this->plan?->slug ?? $this->subscription_plan ?? 'pro');

        if ($planSlug === 'custom' || $planSlug === 'business') {
            return true;
        }

        switch ($feature) {
            case 'orders':
            case 'online_orders':
            case 'customers':
                return in_array($planSlug, ['pro', 'business', 'custom']);

            case 'locations':
            case 'multi_location':
            case 'team':
            case 'staff_roles':
            case 'advanced_analytics':
                return in_array($planSlug, ['business', 'custom']);

            case 'menu':
            case 'qr':
            case 'branding':
            case 'analytics':
            default:
                return true;
        }
    }
}
