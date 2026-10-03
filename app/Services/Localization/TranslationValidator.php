<?php

namespace App\Services\Localization;

use App\Models\TranslationGlossary;
use App\Models\Vendor;
use App\Services\Localization\DTOs\ValidationResult;

class TranslationValidator
{
    /**
     * Validate translated text against source text and optional glossary rules.
     *
     * @param  array<string, mixed>  $context
     */
    public function validate(
        string $sourceText,
        string $translatedText,
        string $sourceLocale,
        string $targetLocale,
        array $context = []
    ): ValidationResult {
        $errors = [];
        $warnings = [];

        $trimmedSource = trim($sourceText);
        $trimmedTranslated = trim($translatedText);

        // 1. Empty Check
        if ($trimmedSource !== '' && $trimmedTranslated === '') {
            $errors[] = 'The translation output is empty.';

            return ValidationResult::fail($errors, $sourceText, $translatedText, $warnings);
        }

        if ($trimmedSource === '' && $trimmedTranslated === '') {
            return ValidationResult::pass($sourceText, $translatedText);
        }

        // 2. Validate Laravel Placeholders (:name, :count, etc.)
        $sourcePlaceholders = $this->extractLaravelPlaceholders($sourceText);
        $translatedPlaceholders = $this->extractLaravelPlaceholders($translatedText);

        $missingPlaceholders = array_diff($sourcePlaceholders, $translatedPlaceholders);
        if (! empty($missingPlaceholders)) {
            $errors[] = 'Missing placeholder(s): '.implode(', ', $missingPlaceholders);
        }

        $extraPlaceholders = array_diff($translatedPlaceholders, $sourcePlaceholders);
        if (! empty($extraPlaceholders)) {
            $warnings[] = 'Unexpected extra placeholder(s) introduced: '.implode(', ', $extraPlaceholders);
        }

        // 3. Validate Curly Braced Tokens ({name}, {count})
        $sourceCurly = $this->extractCurlyTokens($sourceText);
        $translatedCurly = $this->extractCurlyTokens($translatedText);

        $missingCurly = array_diff($sourceCurly, $translatedCurly);
        if (! empty($missingCurly)) {
            $errors[] = 'Missing template variable(s): '.implode(', ', $missingCurly);
        }

        // 4. Validate Printf Placeholders (%s, %d, etc.)
        $sourcePrintf = $this->extractPrintfPlaceholders($sourceText);
        $translatedPrintf = $this->extractPrintfPlaceholders($translatedText);

        if (count($sourcePrintf) !== count($translatedPrintf)) {
            $errors[] = sprintf(
                'Mismatch in printf format specifiers count (expected %d, got %d).',
                count($sourcePrintf),
                count($translatedPrintf)
            );
        }

        // 5. Validate HTML Tags
        $sourceTags = $this->extractHtmlTags($sourceText);
        $translatedTags = $this->extractHtmlTags($translatedText);

        $missingTags = array_diff($sourceTags, $translatedTags);
        if (! empty($missingTags)) {
            $errors[] = 'Missing required HTML tag(s): <'.implode('>, <', $missingTags).'>';
        }

        // 6. Check Forbidden Glossary Words
        $vendor = $context['vendor'] ?? null;
        $forbiddenTerms = $this->getForbiddenTerms($targetLocale, $vendor);
        foreach ($forbiddenTerms as $forbidden) {
            if (mb_stripos($translatedText, $forbidden) !== false) {
                $errors[] = "Contains forbidden glossary term: '{$forbidden}'";
            }
        }

        // 7. Check Extreme Length Discrepancy (Heuristic warning)
        $sourceLen = mb_strlen($trimmedSource);
        $transLen = mb_strlen($trimmedTranslated);
        if ($sourceLen > 10 && ($transLen > $sourceLen * 4 || $transLen < max(3, (int) ($sourceLen * 0.15)))) {
            $warnings[] = "Unusual translation length difference (source: {$sourceLen} chars, translation: {$transLen} chars).";
        }

        if (! empty($errors)) {
            return ValidationResult::fail($errors, $sourceText, $translatedText, $warnings);
        }

        return ValidationResult::pass($sourceText, $translatedText, $warnings);
    }

    /**
     * Extract Laravel placeholders like :name, :count, :user_id.
     *
     * @return array<int, string>
     */
    public function extractLaravelPlaceholders(string $text): array
    {
        preg_match_all('/(?<!\w):([a-zA-Z0-9_]+)/', $text, $matches);

        return isset($matches[0]) ? array_values(array_unique($matches[0])) : [];
    }

    /**
     * Extract curly braced tokens like {name}, {count}.
     *
     * @return array<int, string>
     */
    public function extractCurlyTokens(string $text): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $text, $matches);

        return isset($matches[0]) ? array_values(array_unique($matches[0])) : [];
    }

    /**
     * Extract printf tokens like %s, %d, %1$s.
     *
     * @return array<int, string>
     */
    public function extractPrintfPlaceholders(string $text): array
    {
        preg_match_all('/%(?:\d+\$)?[+-]?(?:[ 0]|\'.)?-?\d*(?:\.\d+)?[bcdeEfFgGosuxX]/', $text, $matches);

        return isset($matches[0]) ? $matches[0] : [];
    }

    /**
     * Extract unique HTML tag names like 'b', 'span', 'br'.
     *
     * @return array<int, string>
     */
    public function extractHtmlTags(string $text): array
    {
        preg_match_all('/<\/?([a-zA-Z0-9]+)[^>]*>/', $text, $matches);

        if (! isset($matches[1])) {
            return [];
        }

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }

    /**
     * Retrieve forbidden terms for target locale from glossary.
     *
     * @return array<int, string>
     */
    protected function getForbiddenTerms(string $targetLocale, ?Vendor $vendor = null): array
    {
        try {
            return TranslationGlossary::query()
                ->active()
                ->where('is_forbidden', true)
                ->where(function ($q) use ($targetLocale) {
                    $q->where('target_locale', $targetLocale)
                        ->orWhereNull('target_locale');
                })
                ->where(function ($q) use ($vendor) {
                    $q->whereNull('vendor_id');
                    if ($vendor) {
                        $q->orWhere('vendor_id', $vendor->id);
                    }
                })
                ->pluck('forbidden_term')
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
