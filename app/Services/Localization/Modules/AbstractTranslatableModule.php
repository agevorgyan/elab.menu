<?php

namespace App\Services\Localization\Modules;

use App\Models\Traits\HasContentTranslations;
use App\Services\Localization\Contracts\TranslatableModuleInterface;
use App\Services\Localization\TranslationValidator;

abstract class AbstractTranslatableModule implements TranslatableModuleInterface
{
    public function __construct(
        protected ?TranslationValidator $validator = null
    ) {
        $this->validator = $validator ?? new TranslationValidator;
    }

    public function getRequiredLocales(): array
    {
        return ['hy', 'en', 'ru'];
    }

    public function getTranslatableModels(): array
    {
        return [];
    }

    public function getExpectedKeys(): array
    {
        return [];
    }

    /**
     * Audit module localization resources for completeness and placeholder safety.
     */
    public function audit(): array
    {
        $namespace = $this->getNamespace();
        $requiredLocales = $this->getRequiredLocales();
        $expectedKeys = $this->getExpectedKeys();

        $missingFiles = [];
        $missingKeys = [];
        $placeholderErrors = [];
        $modelWarnings = [];

        // 1. Verify Translatable Models Contract
        foreach ($this->getTranslatableModels() as $modelClass) {
            if (! class_exists($modelClass)) {
                $modelWarnings[] = "Model class '{$modelClass}' does not exist.";

                continue;
            }

            $traits = class_uses_recursive($modelClass);
            if (! in_array(HasContentTranslations::class, $traits, true)) {
                $modelWarnings[] = "Model '{$modelClass}' does not use mandatory trait HasContentTranslations.";
            }
        }

        // 2. Load translations per locale
        $loadedTranslations = [];
        foreach ($requiredLocales as $locale) {
            $filePath = lang_path("{$locale}/{$namespace}.php");
            if (! file_exists($filePath)) {
                $missingFiles[$locale] = $filePath;
                $loadedTranslations[$locale] = [];

                continue;
            }

            $translations = include $filePath;
            if (! is_array($translations)) {
                $missingFiles[$locale] = "{$filePath} (File did not return an array)";
                $loadedTranslations[$locale] = [];

                continue;
            }

            $loadedTranslations[$locale] = $translations;

            // Check missing expected keys
            foreach ($expectedKeys as $key) {
                if (! array_key_exists($key, $translations) || trim((string) $translations[$key]) === '') {
                    $missingKeys[$locale][] = $key;
                }
            }
        }

        // 3. Placeholder Validation against base locale (default 'en')
        $baseLocale = in_array('en', $requiredLocales, true) ? 'en' : ($requiredLocales[0] ?? null);
        if ($baseLocale && isset($loadedTranslations[$baseLocale])) {
            $baseStrings = $loadedTranslations[$baseLocale];

            foreach ($requiredLocales as $locale) {
                if ($locale === $baseLocale || ! isset($loadedTranslations[$locale])) {
                    continue;
                }

                $targetStrings = $loadedTranslations[$locale];

                foreach ($baseStrings as $key => $baseText) {
                    if (is_string($baseText) && isset($targetStrings[$key]) && is_string($targetStrings[$key])) {
                        $validation = $this->validator->validate(
                            $baseText,
                            $targetStrings[$key],
                            $baseLocale,
                            $locale
                        );

                        if ($validation->hasErrors()) {
                            $placeholderErrors[$locale][$key] = $validation->errors;
                        }
                    }
                }
            }
        }

        $isValid = empty($missingFiles) && empty($missingKeys) && empty($placeholderErrors) && empty($modelWarnings);

        return [
            'is_valid' => $isValid,
            'missing_files' => $missingFiles,
            'missing_keys' => $missingKeys,
            'placeholder_errors' => $placeholderErrors,
            'model_warnings' => $modelWarnings,
        ];
    }
}
