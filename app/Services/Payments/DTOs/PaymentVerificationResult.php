<?php

namespace App\Services\Payments\DTOs;

use App\Enums\PaymentStatus;

class PaymentVerificationResult
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public bool $success,
        public PaymentStatus $status,
        public ?string $providerTransactionId = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $merchantReference = null,
        public array $rawPayload = [],
        public ?string $errorMessage = null,
        public bool $signatureValid = false,
    ) {}

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public static function paid(
        string $providerTransactionId,
        float $amount,
        string $currency,
        string $merchantReference,
        array $rawPayload = [],
        bool $signatureValid = true,
    ): self {
        return new self(
            success: true,
            status: PaymentStatus::Paid,
            providerTransactionId: $providerTransactionId,
            amount: $amount,
            currency: $currency,
            merchantReference: $merchantReference,
            rawPayload: $rawPayload,
            signatureValid: $signatureValid,
        );
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public static function failed(
        string $errorMessage,
        ?string $merchantReference = null,
        ?string $providerTransactionId = null,
        array $rawPayload = [],
        bool $signatureValid = false,
    ): self {
        return new self(
            success: false,
            status: PaymentStatus::Failed,
            providerTransactionId: $providerTransactionId,
            merchantReference: $merchantReference,
            rawPayload: $rawPayload,
            errorMessage: $errorMessage,
            signatureValid: $signatureValid,
        );
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public static function pending(
        string $merchantReference,
        ?string $providerTransactionId = null,
        array $rawPayload = [],
    ): self {
        return new self(
            success: false,
            status: PaymentStatus::Pending,
            providerTransactionId: $providerTransactionId,
            merchantReference: $merchantReference,
            rawPayload: $rawPayload,
            errorMessage: 'Payment is still pending',
            signatureValid: true,
        );
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public static function cancelled(
        string $merchantReference,
        ?string $providerTransactionId = null,
        array $rawPayload = [],
    ): self {
        return new self(
            success: false,
            status: PaymentStatus::Cancelled,
            providerTransactionId: $providerTransactionId,
            merchantReference: $merchantReference,
            rawPayload: $rawPayload,
            errorMessage: 'Payment was cancelled',
            signatureValid: true,
        );
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public static function refunded(
        string $providerTransactionId,
        float $amount,
        string $currency,
        string $merchantReference,
        array $rawPayload = [],
    ): self {
        return new self(
            success: true,
            status: PaymentStatus::Refunded,
            providerTransactionId: $providerTransactionId,
            amount: $amount,
            currency: $currency,
            merchantReference: $merchantReference,
            rawPayload: $rawPayload,
            signatureValid: true,
        );
    }
}
