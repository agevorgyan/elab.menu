<?php

namespace App\Services\Localization;

use App\Models\ContentTranslation;
use App\Models\TranslationHistory;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Localization\Contracts\TranslationProviderInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TranslationService
{
    public function __construct(
        protected TranslationProviderInterface $provider,
        protected TranslationValidator $validator
    ) {}

    /**
     * Swap the active translation provider (e.g. for testing).
     */
    public function setProvider(TranslationProviderInterface $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): TranslationProviderInterface
    {
        return $this->provider;
    }

    public function getValidator(): TranslationValidator
    {
        return $this->validator;
    }

    /**
     * Translate a specific field of an Eloquent model via AI.
     *
     * @param  array<string, mixed>  $options
     */
    public function translateModelField(
        Model $model,
        string $field,
        string $targetLocale,
        ?string $sourceLocale = null,
        ?User $user = null,
        array $options = []
    ): ContentTranslation {
        $sourceLocale = $sourceLocale ?: ($model->vendor?->getDefaultLanguageCode() ?? config('app.fallback_locale', 'en'));
        $sourceText = (string) ($model->{$field} ?? '');

        if (empty(trim($sourceText))) {
            throw new \InvalidArgumentException("Source text for field '{$field}' is empty.");
        }

        $vendorId = $model->vendor_id ?? null;
        $vendor = $model->vendor ?? ($vendorId ? Vendor::find($vendorId) : null);

        // Find or initialize ContentTranslation
        $translation = ContentTranslation::query()->firstOrNew([
            'translatable_type' => $model->getMorphClass(),
            'translatable_id' => $model->getKey(),
            'field' => $field,
            'locale' => $targetLocale,
        ], [
            'vendor_id' => $vendorId,
            'source_locale' => $sourceLocale,
            'source_text' => $sourceText,
            'status' => ContentTranslation::STATUS_TRANSLATING,
        ]);

        $isProtected = $translation->exists && ($translation->isApproved() || $translation->isPublished());

        // Call Provider
        $context = array_merge([
            'field' => $field,
            'entity_type' => class_basename($model),
            'vendor' => $vendor,
        ], $options);

        $result = $this->provider->translate($sourceText, $sourceLocale, $targetLocale, $context);

        return DB::transaction(function () use ($translation, $result, $sourceText, $sourceLocale, $targetLocale, $isProtected, $user) {
            if (! $result->isSuccessful) {
                $translation->status = ContentTranslation::STATUS_FAILED;
                $translation->error_message = $result->errorMessage;
                $translation->save();

                return $translation;
            }

            // Validate translation
            $validation = $this->validator->validate($sourceText, $result->translatedText, $sourceLocale, $targetLocale);

            if ($isProtected) {
                // NEVER silently overwrite approved/published translation!
                // Store AI proposal in metadata and mark as needs_review without changing published text
                $translation->needs_review = true;
                $meta = $translation->metadata ?? [];
                $meta['ai_proposal'] = [
                    'proposed_text' => $result->translatedText,
                    'proposed_at' => now()->toIso8601String(),
                    'provider' => $result->provider,
                    'validation_errors' => $validation->errors,
                    'warnings' => $validation->warnings,
                ];
                $translation->metadata = $meta;
                $translation->save();

                // Record history of the proposed re-translation
                $this->recordHistory(
                    translation: $translation,
                    action: TranslationHistory::ACTION_AI_TRANSLATED,
                    previousText: $translation->translated_text,
                    newText: $result->translatedText,
                    sourceType: 'ai_proposal',
                    user: $user,
                    notes: 'AI proposed re-translation for approved translation. Kept existing text intact.'
                );

                return $translation;
            }

            // Safe to update draft
            $previousText = $translation->translated_text;
            $translation->source_text = $sourceText;
            $translation->source_locale = $sourceLocale;
            $translation->translated_text = $result->translatedText;
            $translation->source_type = ContentTranslation::SOURCE_AI;
            $translation->error_message = $validation->hasErrors() ? $validation->firstError() : null;
            $translation->status = $validation->hasErrors()
                ? ContentTranslation::STATUS_NEEDS_REVIEW
                : ContentTranslation::STATUS_DRAFT;
            $translation->needs_review = $validation->hasErrors();
            $translation->metadata = array_merge($translation->metadata ?? [], [
                'provider' => $result->provider,
                'model' => $result->model,
                'validation_warnings' => $validation->warnings,
            ]);
            $translation->save();

            // Record history
            $this->recordHistory(
                translation: $translation,
                action: TranslationHistory::ACTION_AI_TRANSLATED,
                previousText: $previousText,
                newText: $result->translatedText,
                sourceType: 'ai',
                user: $user,
                notes: $validation->hasErrors() ? 'AI translated with validation warnings/errors.' : 'AI draft created.'
            );

            return $translation;
        });
    }

    /**
     * Translate all registered translatable fields for a model.
     *
     * @return array<string, ContentTranslation>
     */
    public function translateModel(
        Model $model,
        string $targetLocale,
        ?string $sourceLocale = null,
        ?User $user = null,
        array $options = []
    ): array {
        $fields = method_exists($model, 'getTranslatableFields')
            ? $model->getTranslatableFields()
            : ['name', 'description'];

        $translations = [];
        foreach ($fields as $field) {
            if (! empty($model->{$field})) {
                $translations[$field] = $this->translateModelField(
                    $model,
                    $field,
                    $targetLocale,
                    $sourceLocale,
                    $user,
                    $options
                );
            }
        }

        return $translations;
    }

    /**
     * Save human edits to a translation draft.
     */
    public function saveDraft(
        ContentTranslation $translation,
        string $newText,
        ?User $user = null,
        ?string $notes = null
    ): ContentTranslation {
        $previousText = $translation->translated_text;

        // Validate formatting & placeholders
        $validation = $this->validator->validate(
            $translation->source_text,
            $newText,
            $translation->source_locale,
            $translation->locale
        );

        $translation->translated_text = $newText;
        $translation->source_type = ContentTranslation::SOURCE_HUMAN;
        $translation->status = ContentTranslation::STATUS_DRAFT;
        $translation->needs_review = false;
        $translation->error_message = $validation->hasErrors() ? $validation->firstError() : null;
        $translation->reviewed_by = $user?->id;
        $translation->reviewed_at = now();
        $translation->save();

        $this->recordHistory(
            translation: $translation,
            action: TranslationHistory::ACTION_EDITED,
            previousText: $previousText,
            newText: $newText,
            sourceType: 'human',
            user: $user,
            notes: $notes ?: 'Draft updated manually by editor.'
        );

        return $translation;
    }

    /**
     * Approve a translation.
     */
    public function approve(ContentTranslation $translation, ?User $user = null): ContentTranslation
    {
        // Enforce validation before approval
        $validation = $this->validator->validate(
            $translation->source_text,
            $translation->translated_text ?? '',
            $translation->source_locale,
            $translation->locale
        );

        if ($validation->hasErrors()) {
            throw new \DomainException('Cannot approve translation with validation errors: '.$validation->firstError());
        }

        $translation->approve($user?->id);

        return $translation;
    }

    /**
     * Publish an approved translation and synchronize to storefront cache/columns.
     */
    public function publish(ContentTranslation $translation, ?User $user = null): ContentTranslation
    {
        if (! $translation->isApproved() && ! $translation->isPublished()) {
            // Auto-approve if valid before publishing
            $this->approve($translation, $user);
        }

        $translation->publish();

        return $translation;
    }

    /**
     * Reject a translation.
     */
    public function reject(ContentTranslation $translation, string $reason, ?User $user = null): ContentTranslation
    {
        $translation->status = ContentTranslation::STATUS_REJECTED;
        $translation->needs_review = true;
        $translation->error_message = $reason;
        $translation->reviewed_by = $user?->id;
        $translation->reviewed_at = now();
        $translation->save();

        $this->recordHistory(
            translation: $translation,
            action: TranslationHistory::ACTION_REJECTED,
            previousText: $translation->translated_text,
            newText: $translation->translated_text,
            sourceType: 'human',
            user: $user,
            notes: 'Rejected: '.$reason
        );

        return $translation;
    }

    /**
     * Restore a previous version from translation history.
     */
    public function restoreVersion(
        ContentTranslation $translation,
        int $historyId,
        ?User $user = null
    ): ContentTranslation {
        /** @var TranslationHistory $history */
        $history = $translation->history()->findOrFail($historyId);

        $restoredText = $history->new_text ?? $history->previous_text;
        if ($restoredText === null) {
            throw new \InvalidArgumentException('Selected history version has no valid text to restore.');
        }

        $previousText = $translation->translated_text;
        $translation->translated_text = $restoredText;
        $translation->status = ContentTranslation::STATUS_DRAFT;
        $translation->needs_review = false;
        $translation->save();

        $this->recordHistory(
            translation: $translation,
            action: TranslationHistory::ACTION_RESTORED,
            previousText: $previousText,
            newText: $restoredText,
            sourceType: 'human',
            user: $user,
            notes: "Restored version from history #{$historyId}."
        );

        return $translation;
    }

    /**
     * Record a snapshot of a translation action into version history.
     */
    protected function recordHistory(
        ContentTranslation $translation,
        string $action,
        ?string $previousText,
        ?string $newText,
        string $sourceType,
        ?User $user = null,
        ?string $notes = null,
        array $metadata = []
    ): TranslationHistory {
        $nextVersion = ((int) $translation->histories()->max('version_number')) + 1;

        return TranslationHistory::create([
            'content_translation_id' => $translation->id,
            'vendor_id' => $translation->vendor_id,
            'version_number' => $nextVersion,
            'source_text' => $translation->source_text,
            'translation' => $newText,
            'status' => $translation->status,
            'source_type' => $sourceType,
            'created_by' => $user?->id,
            'metadata' => array_merge([
                'action' => $action,
                'previous_text' => $previousText,
                'notes' => $notes,
            ], $metadata),
        ]);
    }
}
