<?php

namespace App\Services\Localization\DTOs;

class TranslationResult
{
    public function __construct(
        public string $translatedText,
        public string $sourceText,
        public string $sourceLocale,
        public string $targetLocale,
        public string $provider,
        public ?string $model = null,
        public ?int $tokensUsed = null,
        public bool $isSuccessful = true,
        public ?string $errorMessage = null,
        public array $metadata = []
    ) {}

    public static function success(
        string $translatedText,
        string $sourceText,
        string $sourceLocale,
        string $targetLocale,
        string $provider,
        ?string $model = null,
        ?int $tokensUsed = null,
        array $metadata = []
    ): self {
        return new self(
            translatedText: $translatedText,
            sourceText: $sourceText,
            sourceLocale: $sourceLocale,
            targetLocale: $targetLocale,
            provider: $provider,
            model: $model,
            tokensUsed: $tokensUsed,
            isSuccessful: true,
            errorMessage: null,
            metadata: $metadata
        );
    }

    public static function failure(
        string $errorMessage,
        string $sourceText,
        string $sourceLocale,
        string $targetLocale,
        string $provider
    ): self {
        return new self(
            translatedText: $sourceText,
            sourceText: $sourceText,
            sourceLocale: $sourceLocale,
            targetLocale: $targetLocale,
            provider: $provider,
            isSuccessful: false,
            errorMessage: $errorMessage
        );
    }
}
