<?php

namespace App\Services\Localization\Providers;

use App\Models\Vendor;
use App\Services\AiGatewayService;
use App\Services\Localization\Contracts\TranslationProviderInterface;
use App\Services\Localization\DTOs\TranslationResult;
use App\Services\Localization\TranslationPromptBuilder;
use Illuminate\Support\Facades\Log;

class AiGatewayTranslationProvider implements TranslationProviderInterface
{
    public function __construct(
        protected AiGatewayService $gateway,
        protected TranslationPromptBuilder $promptBuilder
    ) {}

    public function translate(string $text, string $sourceLocale, string $targetLocale, array $context = []): TranslationResult
    {
        /** @var Vendor|null $vendor */
        $vendor = $context['vendor'] ?? null;
        if (! $vendor) {
            $vendor = Vendor::query()->first();
        }

        if (! $vendor || ! $this->isAvailable($vendor)) {
            return TranslationResult::failure(
                errorMessage: 'AI Provider not configured or credentials missing.',
                sourceText: $text,
                sourceLocale: $sourceLocale,
                targetLocale: $targetLocale,
                provider: $this->getName()
            );
        }

        $prompt = $this->promptBuilder->buildSinglePrompt($text, $sourceLocale, $targetLocale, $context);
        $providerName = $vendor->getAiProvider();
        $model = $vendor->getAiModel();

        try {
            $rawResponse = $this->gateway->generateText($vendor, $prompt, [
                'timeout' => $context['timeout'] ?? 25,
            ]);

            $cleaned = $this->cleanTranslatedText($rawResponse);

            if ($cleaned === '' && trim($text) !== '') {
                return TranslationResult::failure(
                    errorMessage: 'AI Gateway returned an empty translation response.',
                    sourceText: $text,
                    sourceLocale: $sourceLocale,
                    targetLocale: $targetLocale,
                    provider: $providerName
                );
            }

            return TranslationResult::success(
                translatedText: $cleaned,
                sourceText: $text,
                sourceLocale: $sourceLocale,
                targetLocale: $targetLocale,
                provider: $providerName,
                model: $model,
                tokensUsed: null,
                metadata: [
                    'vendor_id' => $vendor->id,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('AI Translation failed: '.$e->getMessage(), [
                'vendor_id' => $vendor->id,
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'error' => $e->getMessage(),
            ]);

            return TranslationResult::failure(
                errorMessage: $e->getMessage(),
                sourceText: $text,
                sourceLocale: $sourceLocale,
                targetLocale: $targetLocale,
                provider: $providerName
            );
        }
    }

    public function translateBatch(array $texts, string $sourceLocale, string $targetLocale, array $context = []): array
    {
        /** @var Vendor|null $vendor */
        $vendor = $context['vendor'] ?? null;
        if (! $vendor) {
            $vendor = Vendor::query()->first();
        }

        if (empty($texts)) {
            return [];
        }

        // If batch is small (<= 2 items) or credentials missing, run individual
        if (count($texts) <= 2 || ! $vendor || ! $this->isAvailable($vendor)) {
            $results = [];
            foreach ($texts as $key => $text) {
                $results[$key] = $this->translate($text, $sourceLocale, $targetLocale, $context);
            }

            return $results;
        }

        $prompt = $this->promptBuilder->buildBatchPrompt($texts, $sourceLocale, $targetLocale, $context);
        $providerName = $vendor->getAiProvider();
        $model = $vendor->getAiModel();

        try {
            $rawResponse = $this->gateway->generateText($vendor, $prompt, [
                'timeout' => $context['timeout'] ?? 45,
            ]);

            $jsonStr = $this->extractJsonString($rawResponse);
            $decoded = json_decode($jsonStr, true);

            if (is_array($decoded)) {
                $results = [];
                foreach ($texts as $key => $original) {
                    $translated = $decoded[$key] ?? null;
                    if ($translated !== null) {
                        $results[$key] = TranslationResult::success(
                            translatedText: trim((string) $translated),
                            sourceText: $original,
                            sourceLocale: $sourceLocale,
                            targetLocale: $targetLocale,
                            provider: $providerName,
                            model: $model
                        );
                    } else {
                        // Fallback individual translation for missing key
                        $results[$key] = $this->translate($original, $sourceLocale, $targetLocale, $context);
                    }
                }

                return $results;
            }
        } catch (\Throwable $e) {
            Log::warning('Batch translation JSON parsing failed, falling back to individual calls: '.$e->getMessage());
        }

        // Fallback to individual
        $results = [];
        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $sourceLocale, $targetLocale, $context);
        }

        return $results;
    }

    public function getName(): string
    {
        return 'ai_gateway';
    }

    public function isAvailable(?Vendor $vendor = null): bool
    {
        if (! $vendor) {
            $vendor = Vendor::query()->first();
        }

        if (! $vendor) {
            return false;
        }

        $apiKey = $vendor->getAiApiKey();
        $provider = $vendor->getAiProvider();

        return ! empty($apiKey) || $provider === 'custom';
    }

    /**
     * Clean markdown wrappers and unnecessary quotes.
     */
    protected function cleanTranslatedText(string $text): string
    {
        $cleaned = trim($text);

        // Strip markdown code fences if wrapped: ```...```
        if (preg_match('/^```(?:[a-zA-Z0-9]+)?\s*\n?(.*?)\n?```$/s', $cleaned, $matches)) {
            $cleaned = trim($matches[1]);
        }

        // Strip wrapping double or single quotes if whole string was quoted
        if ((str_starts_with($cleaned, '"') && str_ends_with($cleaned, '"')) ||
            (str_starts_with($cleaned, "'") && str_ends_with($cleaned, "'"))) {
            $cleaned = trim(substr($cleaned, 1, -1));
        }

        return $cleaned;
    }

    /**
     * Extract JSON block from potential markdown output.
     */
    protected function extractJsonString(string $text): string
    {
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            return trim($matches[1]);
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            return substr($text, $start, $end - $start + 1);
        }

        return trim($text);
    }
}
