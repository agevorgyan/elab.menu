<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationGlossary extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'term',
        'source_locale',
        'target_locale',
        'translation',
        'translated_term',
        'forbidden_terms',
        'case_sensitive',
        'description',
        'notes',
        'is_verbatim',
        'is_forbidden',
    ];

    protected $casts = [
        'forbidden_terms' => 'array',
        'case_sensitive' => 'boolean',
    ];

    public function getTranslatedTermAttribute(): ?string
    {
        return $this->translation;
    }

    public function setTranslatedTermAttribute(?string $value): void
    {
        $this->translation = $value;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->description;
    }

    public function setNotesAttribute(?string $value): void
    {
        $this->description = $value;
    }

    public function getIsVerbatimAttribute(): bool
    {
        return empty($this->translation) && empty($this->forbidden_terms);
    }

    public function setIsVerbatimAttribute(mixed $value): void
    {
        if ((bool) $value) {
            $this->translation = null;
        }
    }

    public function getIsForbiddenAttribute(): bool
    {
        return ! empty($this->forbidden_terms);
    }

    public function setIsForbiddenAttribute(mixed $value): void
    {
        if ((bool) $value && empty($this->forbidden_terms)) {
            $this->forbidden_terms = [$this->term];
        }
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Scope to terms relevant for a specific target locale.
     */
    public function scopeForLocale(Builder $query, string $targetLocale): Builder
    {
        return $query->where(function ($q) use ($targetLocale) {
            $q->where('target_locale', $targetLocale)
                ->orWhere('target_locale', '*');
        });
    }

    /**
     * Scope to terms available for a specific vendor (including global ones).
     */
    public function scopeForVendorOrGlobal(Builder $query, ?int $vendorId): Builder
    {
        return $query->where(function ($q) use ($vendorId) {
            if ($vendorId) {
                $q->where('vendor_id', $vendorId)
                    ->orWhereNull('vendor_id');
            } else {
                $q->whereNull('vendor_id');
            }
        });
    }
}
