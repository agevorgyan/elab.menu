<?php

namespace App\Services\Localization\DTOs;

class ValidationResult
{
    /**
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public ?string $originalText = null,
        public ?string $translatedText = null
    ) {}

    public static function pass(?string $originalText = null, ?string $translatedText = null, array $warnings = []): self
    {
        return new self(
            isValid: true,
            errors: [],
            warnings: $warnings,
            originalText: $originalText,
            translatedText: $translatedText
        );
    }

    public static function fail(array|string $errors, ?string $originalText = null, ?string $translatedText = null, array $warnings = []): self
    {
        $errorList = is_array($errors) ? $errors : [$errors];

        return new self(
            isValid: false,
            errors: $errorList,
            warnings: $warnings,
            originalText: $originalText,
            translatedText: $translatedText
        );
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}
