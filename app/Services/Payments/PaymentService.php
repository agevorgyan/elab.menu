<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Vendor;
use App\Services\Payments\DTOs\PaymentInitiationResult;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(protected PaymentGatewayManager $gatewayManager) {}

    /**
     * Initiate an online or offline payment for an Order.
     * The server calculates authoritative values and never trusts client-supplied amounts.
     */
    public function initiateOrderPayment(Order $order, string $gateway): PaymentInitiationResult
    {
        $vendor = $order->vendor;
        $authoritativeAmount = (float) $order->total_amount;
        $currency = 'AMD';

        $merchantReference = 'ORD-'.$order->id.'-'.Str::upper(Str::random(10));
        $idempotencyKey = (string) Str::uuid();

        // Create PaymentAttempt record
        $attempt = PaymentAttempt::create([
            'uuid' => (string) Str::uuid(),
            'vendor_id' => $vendor->id,
            'order_id' => $order->id,
            'gateway' => $gateway,
            'merchant_reference' => $merchantReference,
            'amount' => $authoritativeAmount,
            'currency' => $currency,
            'status' => PaymentStatus::Created,
            'idempotency_key' => $idempotencyKey,
            'expires_at' => now()->addMinutes(30),
        ]);

        $order->update([
            'payment_method' => $gateway,
            'payment_status' => ($gateway === 'cash' || $gateway === 'pos_terminal') ? 'unpaid' : 'pending',
            'payment_transaction_id' => $merchantReference,
        ]);

        $gatewayAdapter = $this->gatewayManager->gateway($gateway);
        $result = $gatewayAdapter->initiatePayment($attempt);

        $attempt->update([
            'status' => PaymentStatus::Pending,
            'provider_transaction_id' => $result->providerTransactionId,
            'request_payload' => $result->gatewayData,
        ]);

        return $result;
    }

    /**
     * Initiate a subscription renewal payment for a Vendor.
     * Calculates authoritative subscription pricing with volume discounts.
     * Does NOT mark payment or subscription as active until provider verification succeeds.
     *
     * @return array{payment: SubscriptionPayment, attempt: PaymentAttempt, result: PaymentInitiationResult}
     */
    public function initiateSubscriptionRenewal(
        Vendor $vendor,
        SubscriptionPlan $plan,
        int $monthsCount,
        string $paymentMethod
    ): array {
        // Base monthly price * months
        $basePrice = (float) $plan->price;
        $totalAmount = $basePrice * $monthsCount;

        // Authoritative server-side discount
        if ($monthsCount >= 12) {
            $totalAmount *= 0.80; // 20% discount for 1 year
        } elseif ($monthsCount >= 6) {
            $totalAmount *= 0.90; // 10% discount for 6 months
        }

        $now = now();
        $currentExpiry = ($vendor->subscription_expires_at && $vendor->subscription_expires_at->isFuture())
            ? $vendor->subscription_expires_at
            : $now;

        $newExpiry = (clone $currentExpiry)->addMonths($monthsCount);
        $invoiceNumber = 'INV-'.Str::upper(Str::random(4)).'-'.date('Ymd');

        // Create pending subscription payment record
        $subscriptionPayment = SubscriptionPayment::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $plan->id,
            'amount' => $totalAmount,
            'currency' => $plan->currency ?? 'AMD',
            'payment_method' => $paymentMethod,
            'invoice_number' => $invoiceNumber,
            'period_start' => $currentExpiry->format('Y-m-d'),
            'period_end' => $newExpiry->format('Y-m-d'),
            'status' => 'pending', // Pending until verified!
            'notes' => "Երկարաձգում {$monthsCount} ամսով ({$plan->name} փաթեթ)",
        ]);

        $merchantReference = 'SUB-'.$subscriptionPayment->id.'-'.Str::upper(Str::random(10));
        $idempotencyKey = (string) Str::uuid();

        // Create PaymentAttempt record
        $attempt = PaymentAttempt::create([
            'uuid' => (string) Str::uuid(),
            'vendor_id' => $vendor->id,
            'subscription_id' => $subscriptionPayment->id,
            'gateway' => $paymentMethod,
            'merchant_reference' => $merchantReference,
            'amount' => $totalAmount,
            'currency' => $plan->currency ?? 'AMD',
            'status' => PaymentStatus::Pending,
            'idempotency_key' => $idempotencyKey,
            'expires_at' => now()->addMinutes(60),
        ]);

        $gatewayAdapter = $this->gatewayManager->gateway($paymentMethod);
        $initiationResult = $gatewayAdapter->initiatePayment($attempt);

        $attempt->update([
            'provider_transaction_id' => $initiationResult->providerTransactionId,
            'request_payload' => $initiationResult->gatewayData,
        ]);

        return [
            'payment' => $subscriptionPayment,
            'attempt' => $attempt,
            'result' => $initiationResult,
        ];
    }
}
