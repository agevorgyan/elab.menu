<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentWebhookSecurityTest extends TestCase
{
    public function test_payment_webhook_sanitizes_sensitive_payload_in_logs(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                if (! str_contains($message, 'Payment webhook received for gateway: idram')) {
                    return false;
                }

                $payload = $context['payload'] ?? [];

                // Sensitive keys must be redacted
                return isset($payload['secret_key'])
                    && $payload['secret_key'] === '[REDACTED]'
                    && isset($payload['card_number'])
                    && $payload['card_number'] === '[REDACTED]'
                    && isset($payload['cvv'])
                    && $payload['cvv'] === '[REDACTED]'
                    && isset($payload['EDP_REC_ACCOUNT'])
                    && $payload['EDP_REC_ACCOUNT'] === '123456789';
            });

        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $this->postJson(route('api.webhooks.payment', ['gateway' => 'idram']), [
            'EDP_REC_ACCOUNT' => '123456789',
            'secret_key' => 'super-secret-gateway-key',
            'card_number' => '4111111111111111',
            'cvv' => '123',
        ]);
    }
}
