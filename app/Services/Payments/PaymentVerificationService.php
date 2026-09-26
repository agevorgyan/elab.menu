<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\SubscriptionPayment;
use App\Models\Vendor;
use App\Services\Payments\DTOs\PaymentVerificationResult;
use App\Services\TelegramNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentVerificationService
{
    public function __construct(protected PaymentGatewayManager $gatewayManager) {}

    /**
     * Verify and finalize a payment attempt from a client redirect callback.
     * Uses database transaction and row locking to ensure idempotency and prevent race conditions.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function verifyAndFinalizeAttempt(
        PaymentAttempt $attempt,
        array $payload,
        array $headers = []
    ): PaymentVerificationResult {
        return DB::transaction(function () use ($attempt, $payload): PaymentVerificationResult {
            // Lock attempt row for update
            $lockedAttempt = PaymentAttempt::where('id', $attempt->id)->lockForUpdate()->first();
            if (! $lockedAttempt) {
                return PaymentVerificationResult::failed('Payment attempt not found');
            }

            // 1. Idempotency Check: Already verified as paid
            if ($lockedAttempt->status === PaymentStatus::Paid) {
                $gateway = $this->gatewayManager->gateway($lockedAttempt->gateway);
                $verification = $gateway->verifyCallback($lockedAttempt, $payload);
                if ($verification->status === PaymentStatus::Refunded) {
                    return $this->applyVerificationResult($lockedAttempt, $verification, $payload);
                }

                Log::info("Payment attempt {$lockedAttempt->merchant_reference} already finalized as paid. Idempotent return.");

                return PaymentVerificationResult::paid(
                    providerTransactionId: (string) $lockedAttempt->provider_transaction_id,
                    amount: (float) $lockedAttempt->amount,
                    currency: $lockedAttempt->currency,
                    merchantReference: $lockedAttempt->merchant_reference,
                    rawPayload: $lockedAttempt->response_payload ?? [],
                    signatureValid: true
                );
            }

            // 2. Expiration Check
            if ($lockedAttempt->isExpired()) {
                $lockedAttempt->update([
                    'status' => PaymentStatus::Expired,
                    'response_payload' => $payload,
                ]);

                return PaymentVerificationResult::failed('Payment attempt has expired', $lockedAttempt->merchant_reference);
            }

            // 3. Delegate to gateway verification
            $gateway = $this->gatewayManager->gateway($lockedAttempt->gateway);
            $verification = $gateway->verifyCallback($lockedAttempt, $payload);

            return $this->applyVerificationResult($lockedAttempt, $verification, $payload);
        });
    }

    /**
     * Verify and finalize a payment attempt from an incoming server-to-server webhook.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function verifyAndFinalizeWebhook(
        string $gatewayName,
        array $payload,
        array $headers = []
    ): PaymentVerificationResult {
        $gateway = $this->gatewayManager->gateway($gatewayName);
        $verification = $gateway->verifyWebhook($payload, $headers);

        if (! $verification->merchantReference && ! $verification->providerTransactionId) {
            return $verification;
        }

        return DB::transaction(function () use ($gatewayName, $verification, $payload): PaymentVerificationResult {
            // Find attempt by merchant reference or provider transaction ID
            $query = PaymentAttempt::where('gateway', $gatewayName);

            if ($verification->merchantReference) {
                $query->where('merchant_reference', $verification->merchantReference);
            } elseif ($verification->providerTransactionId) {
                $query->where('provider_transaction_id', $verification->providerTransactionId);
            }

            $lockedAttempt = $query->lockForUpdate()->first();

            if (! $lockedAttempt) {
                return PaymentVerificationResult::failed(
                    errorMessage: "Payment attempt not found for gateway {$gatewayName}",
                    merchantReference: $verification->merchantReference,
                    providerTransactionId: $verification->providerTransactionId,
                    rawPayload: $payload
                );
            }

            // 1. Idempotency Check: Already paid
            if ($lockedAttempt->status === PaymentStatus::Paid) {
                if ($verification->status === PaymentStatus::Refunded) {
                    return $this->applyVerificationResult($lockedAttempt, $verification, $payload);
                }

                Log::info("Webhook received for already paid attempt {$lockedAttempt->merchant_reference}. Returning idempotent success.");

                return PaymentVerificationResult::paid(
                    providerTransactionId: (string) $lockedAttempt->provider_transaction_id,
                    amount: (float) $lockedAttempt->amount,
                    currency: $lockedAttempt->currency,
                    merchantReference: $lockedAttempt->merchant_reference,
                    rawPayload: $lockedAttempt->response_payload ?? [],
                    signatureValid: true
                );
            }

            // 2. Expiration Check
            if ($lockedAttempt->isExpired()) {
                $lockedAttempt->update([
                    'status' => PaymentStatus::Expired,
                    'response_payload' => $payload,
                ]);

                return PaymentVerificationResult::failed('Payment attempt has expired', $lockedAttempt->merchant_reference);
            }

            return $this->applyVerificationResult($lockedAttempt, $verification, $payload);
        });
    }

    /**
     * Apply verification result to PaymentAttempt, Order, and SubscriptionPayment within transaction.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function applyVerificationResult(
        PaymentAttempt $attempt,
        PaymentVerificationResult $verification,
        array $payload
    ): PaymentVerificationResult {
        if ($verification->status === PaymentStatus::Pending) {
            return $verification;
        }

        if ($verification->status === PaymentStatus::Paid) {
            // Unique provider transaction check to prevent replay attacks across attempts
            if ($verification->providerTransactionId) {
                $alreadyUsed = PaymentAttempt::where('gateway', $attempt->gateway)
                    ->where('provider_transaction_id', $verification->providerTransactionId)
                    ->where('id', '!=', $attempt->id)
                    ->exists();

                if ($alreadyUsed) {
                    $attempt->update([
                        'status' => PaymentStatus::Failed,
                        'response_payload' => array_merge($payload, ['error' => 'Duplicate provider transaction ID']),
                    ]);

                    if ($attempt->order_id) {
                        Order::where('id', $attempt->order_id)->update(['payment_status' => 'failed']);
                    }
                    if ($attempt->subscription_id) {
                        SubscriptionPayment::where('id', $attempt->subscription_id)->update(['status' => 'failed']);
                    }

                    return PaymentVerificationResult::failed(
                        errorMessage: "Provider transaction {$verification->providerTransactionId} has already been used",
                        merchantReference: $attempt->merchant_reference,
                        providerTransactionId: $verification->providerTransactionId,
                        rawPayload: $payload
                    );
                }
            }

            // Authoritative amount check
            if ($verification->amount !== null) {
                if (abs(((float) $verification->amount) - ((float) $attempt->amount)) > 0.01) {
                    $attempt->update([
                        'status' => PaymentStatus::Failed,
                        'response_payload' => array_merge($payload, ['error' => 'Amount mismatch']),
                    ]);

                    if ($attempt->order_id) {
                        Order::where('id', $attempt->order_id)->update(['payment_status' => 'failed']);
                    }
                    if ($attempt->subscription_id) {
                        SubscriptionPayment::where('id', $attempt->subscription_id)->update(['status' => 'failed']);
                    }

                    return PaymentVerificationResult::failed(
                        errorMessage: "Amount mismatch: expected {$attempt->amount}, got {$verification->amount}",
                        merchantReference: $attempt->merchant_reference,
                        providerTransactionId: $verification->providerTransactionId,
                        rawPayload: $payload
                    );
                }
            }

            // Authoritative currency check
            if ($verification->currency !== null) {
                if (strtoupper($verification->currency) !== strtoupper($attempt->currency)) {
                    $attempt->update([
                        'status' => PaymentStatus::Failed,
                        'response_payload' => array_merge($payload, ['error' => 'Currency mismatch']),
                    ]);

                    if ($attempt->order_id) {
                        Order::where('id', $attempt->order_id)->update(['payment_status' => 'failed']);
                    }
                    if ($attempt->subscription_id) {
                        SubscriptionPayment::where('id', $attempt->subscription_id)->update(['status' => 'failed']);
                    }

                    return PaymentVerificationResult::failed(
                        errorMessage: "Currency mismatch: expected {$attempt->currency}, got {$verification->currency}",
                        merchantReference: $attempt->merchant_reference,
                        providerTransactionId: $verification->providerTransactionId,
                        rawPayload: $payload
                    );
                }
            }

            // Mark attempt paid
            $attempt->update([
                'status' => PaymentStatus::Paid,
                'provider_transaction_id' => $verification->providerTransactionId ?? $attempt->provider_transaction_id,
                'verified_at' => now(),
                'response_payload' => $payload,
            ]);

            // Finalize Order
            if ($attempt->order_id) {
                $order = Order::where('id', $attempt->order_id)
                    ->where('vendor_id', $attempt->vendor_id)
                    ->lockForUpdate()
                    ->first();

                if ($order && $order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'payment_method' => $attempt->gateway,
                        'payment_transaction_id' => $attempt->provider_transaction_id,
                    ]);

                    try {
                        app(TelegramNotificationService::class)->sendPaymentNotification($order, $attempt->gateway);
                    } catch (\Throwable $e) {
                        Log::warning('Telegram notification failed on payment finalization: '.$e->getMessage());
                    }
                }
            }

            // Finalize Subscription
            if ($attempt->subscription_id) {
                $subPayment = SubscriptionPayment::where('id', $attempt->subscription_id)
                    ->where('vendor_id', $attempt->vendor_id)
                    ->lockForUpdate()
                    ->first();

                if ($subPayment && $subPayment->status !== 'completed') {
                    $subPayment->update([
                        'status' => 'completed',
                        'payment_method' => $attempt->gateway,
                    ]);

                    $vendor = Vendor::where('id', $attempt->vendor_id)->lockForUpdate()->first();
                    if ($vendor) {
                        $currentExpiry = ($vendor->subscription_expires_at && $vendor->subscription_expires_at->isFuture())
                            ? $vendor->subscription_expires_at
                            : now();

                        $newExpiry = $subPayment->period_end
                            ? Carbon::parse($subPayment->period_end)
                            : (clone $currentExpiry)->addMonth();

                        $vendor->update([
                            'subscription_plan' => $subPayment->plan?->slug ?? $vendor->subscription_plan,
                            'subscription_plan_id' => $subPayment->subscription_plan_id ?? $vendor->subscription_plan_id,
                            'subscription_status' => 'active',
                            'is_active' => true,
                            'subscription_expires_at' => $newExpiry,
                        ]);

                        Log::info("Vendor {$vendor->name} subscription activated via verified payment {$attempt->merchant_reference}");
                    }
                }
            }

            return $verification;
        }

        if ($verification->status === PaymentStatus::Failed) {
            $attempt->update([
                'status' => PaymentStatus::Failed,
                'response_payload' => $payload,
            ]);

            if ($attempt->order_id) {
                Order::where('id', $attempt->order_id)->update(['payment_status' => 'failed']);
            }
            if ($attempt->subscription_id) {
                SubscriptionPayment::where('id', $attempt->subscription_id)->update(['status' => 'failed']);
            }

            return $verification;
        }

        if ($verification->status === PaymentStatus::Refunded) {
            $attempt->update([
                'status' => PaymentStatus::Refunded,
                'response_payload' => $payload,
            ]);

            if ($attempt->order_id) {
                Order::where('id', $attempt->order_id)->update(['payment_status' => 'refunded']);
            }

            return $verification;
        }

        return $verification;
    }
}
