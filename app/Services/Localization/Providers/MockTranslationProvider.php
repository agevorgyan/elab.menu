<?php

namespace App\Services\Localization\Providers;

use App\Models\Vendor;
use App\Services\Localization\Contracts\TranslationProviderInterface;
use App\Services\Localization\DTOs\TranslationResult;

class MockTranslationProvider implements TranslationProviderInterface
{
    /**
     * @var array<string, array<string, string>>
     */
    protected array $mockDictionary = [];

    public function __construct(array $dictionary = [])
    {
        $this->mockDictionary = $dictionary;
    }

    /**
     * Seed custom dictionary pairs for tests.
     */
    public function setTranslation(string $sourceText, string $targetLocale, string $translatedText): self
    {
        $this->mockDictionary[$targetLocale][$sourceText] = $translatedText;

        return $this;
    }

    public function translate(string $text, string $sourceLocale, string $targetLocale, array $context = []): TranslationResult
    {
        if (isset($this->mockDictionary[$targetLocale][$text])) {
            return TranslationResult::success(
                translatedText: $this->mockDictionary[$targetLocale][$text],
                sourceText: $text,
                sourceLocale: $sourceLocale,
                targetLocale: $targetLocale,
                provider: $this->getName(),
                model: 'mock-deterministic',
                tokensUsed: 10
            );
        }

        // Generic deterministic mock transformation that preserves placeholders
        $translated = match ($targetLocale) {
            'hy' => '[HY] '.$text,
            'ru' => '[RU] '.$text,
            'en' => '[EN] '.$text,
            default => "[{$targetLocale}] ".$text,
        };

        return TranslationResult::success(
            translatedText: $translated,
            sourceText: $text,
            sourceLocale: $sourceLocale,
            targetLocale: $targetLocale,
            provider: $this->getName(),
            model: 'mock-deterministic',
            tokensUsed: 10
        );
    }

    public function translateBatch(array $texts, string $sourceLocale, string $targetLocale, array $context = []): array
    {
        $results = [];
        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $sourceLocale, $targetLocale, $context);
        }

        return $results;
    }

    public function getName(): string
    {
        return 'mock';
    }

    public function isAvailable(?Vendor $vendor = null): bool
    {
        return true;
    }
}
