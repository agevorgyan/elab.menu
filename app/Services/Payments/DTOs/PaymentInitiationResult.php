<?php

namespace App\Services\Payments\DTOs;

class PaymentInitiationResult
{
    /**
     * @param  array<string, mixed>  $gatewayData
     */
    public function __construct(
        public bool $success,
        public string $type, // 'redirect', 'offline', 'qr', 'embedded'
        public ?string $redirectUrl = null,
        public ?string $merchantReference = null,
        public ?string $providerTransactionId = null,
        public array $gatewayData = [],
        public ?string $errorMessage = null,
    ) {}

    /**
     * @param  array<string, mixed>  $gatewayData
     */
    public static function redirect(string $redirectUrl, string $merchantReference, ?string $providerTransactionId = null, array $gatewayData = []): self
    {
        return new self(
            success: true,
            type: 'redirect',
            redirectUrl: $redirectUrl,
            merchantReference: $merchantReference,
            providerTransactionId: $providerTransactionId,
            gatewayData: $gatewayData,
        );
    }

    public static function offline(string $merchantReference, ?string $message = null): self
    {
        return new self(
            success: true,
            type: 'offline',
            merchantReference: $merchantReference,
            errorMessage: $message,
        );
    }

    /**
     * @param  array<string, mixed>  $gatewayData
     */
    public static function failure(string $errorMessage, array $gatewayData = []): self
    {
        return new self(
            success: false,
            type: 'error',
            gatewayData: $gatewayData,
            errorMessage: $errorMessage,
        );
    }
}
