<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentTranslation extends Model
{
    use BelongsToVendor, HasFactory;

    // Translation Status Constants
    public const STATUS_MISSING = 'missing';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_TRANSLATING = 'translating';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    public const STATUS_STALE = 'stale';

    // Source Type Constants
    public const SOURCE_AI = 'ai';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_HUMAN = 'manual';

    public const SOURCE_IMPORT = 'import';

    protected $fillable = [
        'vendor_id',
        'translatable_type',
        'translatable_id',
        'field',
        'locale',
        'source_locale',
        'source_text',
        'source_hash',
        'translation',
        'translated_text',
        'status',
        'source_type',
        'ai_provider',
        'ai_model',
        'reviewed_by',
        'reviewed_at',
        'published_at',
        'is_outdated',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'is_outdated' => 'boolean',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Compatibility alias for translated text.
     */
    public function getTranslatedTextAttribute(): ?string
    {
        return $this->attributes['translation'] ?? null;
    }

    public function setTranslatedTextAttribute(?string $value): void
    {
        $this->attributes['translation'] = $value;
    }

    public function getNeedsReviewAttribute(): bool
    {
        return (bool) ($this->attributes['is_outdated'] ?? false);
    }

    public function setNeedsReviewAttribute(bool $value): void
    {
        $this->attributes['is_outdated'] = $value;
    }

    /**
     * Polymorphic relation to the translated entity.
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relationship to the vendor owning this translation.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Relationship to user who reviewed/approved the translation.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Relationship to historical versions of this translation.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(TranslationHistory::class)->orderBy('version_number', 'desc');
    }

    /**
     * Alias for histories relationship.
     */
    public function history(): HasMany
    {
        return $this->histories();
    }

    /**
     * Scope to published translations.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope to pending review translations.
     */
    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_NEEDS_REVIEW]);
    }

    /**
     * Compute SHA-256 hash of source text for change detection.
     */
    public static function computeHash(?string $text): string
    {
        return hash('sha256', trim((string) $text));
    }

    /**
     * Record a snapshot of the current state into version history.
     */
    public function recordHistory(?int $userId = null, ?array $metadata = null): TranslationHistory
    {
        $nextVersion = ((int) $this->histories()->max('version_number')) + 1;

        return $this->histories()->create([
            'vendor_id' => $this->vendor_id,
            'version_number' => $nextVersion,
            'source_text' => $this->source_text,
            'translation' => $this->translation,
            'status' => $this->status,
            'source_type' => $this->source_type,
            'created_by' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isApprovedOrPublished(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PUBLISHED], true);
    }

    /**
     * Approve translation.
     */
    public function approve(?int $userId = null): self
    {
        $this->status = self::STATUS_APPROVED;
        $this->reviewed_by = $userId;
        $this->reviewed_at = now();
        $this->is_outdated = false;
        $this->error_message = null;
        $this->save();

        $this->recordHistory($userId, ['action' => 'approved', 'notes' => 'Translation approved.']);

        return $this;
    }

    /**
     * Publish translation and sync to model's JSON translation column.
     */
    public function publish(?int $userId = null): self
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->published_at = now();
        $this->is_outdated = false;
        $this->error_message = null;
        if ($userId) {
            $this->reviewed_by = $userId;
            $this->reviewed_at = now();
        }
        $this->save();

        $this->syncToTranslatable();
        $this->recordHistory($userId, ['action' => 'published', 'notes' => 'Translation published.']);

        return $this;
    }

    /**
     * Sync this translation to the parent model's json column.
     */
    public function syncToTranslatable(): void
    {
        $entity = $this->translatable;
        if (! $entity) {
            return;
        }

        $jsonColumn = $this->field.'_translations';
        if (in_array($jsonColumn, $entity->getFillable(), true) || array_key_exists($jsonColumn, $entity->getCasts())) {
            $translations = $entity->{$jsonColumn} ?? [];
            $translations[$this->locale] = $this->translation;
            $entity->{$jsonColumn} = $translations;
            $entity->saveQuietly();
        }
    }
}
