<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $attributes = [
        'takeaway_enabled' => true,
        'delivery_enabled' => true,
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'legal_name',
        'legal_address',
        'tax_id',
        'director_name',
        'director_phone',
        'contact_person_name',
        'contact_person_phone',
        'operating_address',
        'wifi_ssid',
        'wifi_password',
        'working_hours',
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
        'service_fee_enabled',
        'service_fee_type',
        'service_fee_value',
        'service_fee_min_order',
        'delivery_enabled',
        'delivery_fee',
        'delivery_min_amount',
        'delivery_free_from',
        'takeaway_enabled',
        'takeaway_min_amount',
        'featured_product_id',
        'featured_dish_enabled',
        'featured_dish_badge',
        'featured_dish_subtitle',
        'ai_waiter_enabled',
        'ai_waiter_name',
        'ai_waiter_avatar',
        'ai_waiter_priority_ingredients',
        'ai_waiter_welcome_text',
        'ai_waiter_featured_product_ids',
        'ai_settings',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'service_fee_enabled' => 'boolean',
        'service_fee_value' => 'decimal:2',
        'service_fee_min_order' => 'decimal:2',
        'delivery_enabled' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'delivery_min_amount' => 'decimal:2',
        'delivery_free_from' => 'decimal:2',
        'takeaway_enabled' => 'boolean',
        'takeaway_min_amount' => 'decimal:2',
        'featured_dish_enabled' => 'boolean',
        'ai_waiter_enabled' => 'boolean',
        'ai_waiter_featured_product_ids' => 'array',
        'ai_settings' => 'array',
    ];

    /**
     * Get configured AI Provider (gemini, openai, claude, deepseek, groq, openrouter, custom).
     */
    public function getAiProvider(): string
    {
        return $this->ai_settings['provider'] ?? 'gemini';
    }

    /**
     * Get configured AI Model for this vendor.
     */
    public function getAiModel(): string
    {
        if (! empty($this->ai_settings['model'])) {
            return $this->ai_settings['model'];
        }

        return match ($this->getAiProvider()) {
            'openai' => 'gpt-4o-mini',
            'claude' => 'claude-3-5-haiku-20241022',
            'deepseek' => 'deepseek-chat',
            'groq' => 'llama-3.3-70b-versatile',
            'openrouter' => 'openai/gpt-4o-mini',
            'custom' => 'custom-model',
            default => 'gemini-1.5-flash',
        };
    }

    /**
     * Get configured vendor AI API Key or fallback to system key.
     */
    public function getAiApiKey(): ?string
    {
        if (! empty($this->ai_settings['api_key'])) {
            return $this->ai_settings['api_key'];
        }

        // Fallback to system key for gemini
        if ($this->getAiProvider() === 'gemini') {
            return config('services.gemini.key') ?? env('GEMINI_API_KEY');
        }

        return null;
    }

    /**
     * Get custom base URL if configured.
     */
    public function getAiBaseUrl(): ?string
    {
        return $this->ai_settings['base_url'] ?? null;
    }

    /**
     * Check if vendor configured their own custom AI credentials.
     */
    public function hasCustomAiConfig(): bool
    {
        return ! empty($this->ai_settings['api_key']) || ! empty($this->ai_settings['provider']);
    }

    /**
     * Get list of priority ingredients configured by the vendor for AI waiter recommendations.
     *
     * @return array<int, string>
     */
    public function getAiWaiterPriorityIngredientsList(): array
    {
        if (empty($this->ai_waiter_priority_ingredients)) {
            return [];
        }

        $items = preg_split('/[,\n\r]+/', (string) $this->ai_waiter_priority_ingredients);

        return array_values(array_filter(array_map(function ($item) {
            return trim($item);
        }, $items ?: [])));
    }

    /**
     * Get AI Waiter display name.
     */
    public function getAiWaiterName(): string
    {
        return ! empty($this->ai_waiter_name) ? $this->ai_waiter_name : 'AI Մատուցող';
    }

    public function featuredProduct()
    {
        return $this->belongsTo(Product::class, 'featured_product_id');
    }

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
