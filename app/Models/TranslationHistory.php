<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationHistory extends Model
{
    use BelongsToVendor, HasFactory;

    public const ACTION_AI_TRANSLATED = 'ai_translated';

    public const ACTION_EDITED = 'edited';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_PUBLISHED = 'published';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_RESTORED = 'restored';

    protected $fillable = [
        'content_translation_id',
        'vendor_id',
        'version_number',
        'source_text',
        'translation',
        'status',
        'source_type',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'metadata' => 'array',
    ];

    public function contentTranslation(): BelongsTo
    {
        return $this->belongsTo(ContentTranslation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getNewTextAttribute(): ?string
    {
        return $this->translation;
    }

    public function setNewTextAttribute(?string $value): void
    {
        $this->translation = $value;
    }

    public function getPreviousTextAttribute(): ?string
    {
        return $this->metadata['previous_text'] ?? null;
    }

    public function setPreviousTextAttribute(?string $value): void
    {
        $meta = $this->metadata ?? [];
        $meta['previous_text'] = $value;
        $this->metadata = $meta;
    }

    public function getActionAttribute(): ?string
    {
        return $this->metadata['action'] ?? $this->status;
    }

    public function setActionAttribute(?string $value): void
    {
        $meta = $this->metadata ?? [];
        $meta['action'] = $value;
        $this->metadata = $meta;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->metadata['notes'] ?? null;
    }

    public function setNotesAttribute(?string $value): void
    {
        $meta = $this->metadata ?? [];
        $meta['notes'] = $value;
        $this->metadata = $meta;
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->created_by;
    }

    public function setUserIdAttribute(?int $value): void
    {
        $this->created_by = $value;
    }
}
