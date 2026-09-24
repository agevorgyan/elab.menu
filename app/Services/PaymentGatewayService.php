<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    /**
     * Available supported gateways and descriptions.
     */
    public static function getSupportedGateways(): array
    {
        return [
            'idram' => [
                'name' => 'Idram Wallet / QR',
                'category' => 'local_am',
                'flag' => '🇦🇲',
                'icon' => 'fa-solid fa-wallet',
                'color' => '#f97316',
                'description' => 'Հայաստանի առաջատար էլ․ դրամապանակ և Idram QR վճարումներ։',
            ],
            'telcell' => [
                'name' => 'Telcell Wallet',
                'category' => 'local_am',
                'flag' => '🇦🇲',
                'icon' => 'fa-solid fa-mobile-screen-button',
                'color' => '#eab308',
                'description' => 'Telcell Wallet և Telcell QR ակնթարթային վճարումներ։',
            ],
            'fastshift' => [
                'name' => 'FastShift',
                'category' => 'local_am',
                'flag' => '🇦🇲',
                'icon' => 'fa-solid fa-bolt',
                'color' => '#06b6d4',
                'description' => 'Արագ օնլայն վճարումներ FastShift համակարգով։',
            ],
            'arca' => [
                'name' => 'ArCa / Ameriabank vPOS',
                'category' => 'cards_am',
                'flag' => '🇦🇲',
                'icon' => 'fa-solid fa-credit-card',
                'color' => '#2563eb',
                'description' => 'Վճարային քարտեր (ArCa, Visa, MasterCard) Ameriabank / ArCa vPOS միջոցով։',
            ],
            'stripe' => [
                'name' => 'Stripe (Cards / Apple Pay / Google Pay)',
                'category' => 'international',
                'flag' => '🌐',
                'icon' => 'fa-brands fa-stripe',
                'color' => '#6366f1',
                'description' => 'Միջազգային բանկային քարտեր, Apple Pay և Google Pay։',
            ],
            'cash' => [
                'name' => 'Կանխիկ կամ POS Տերմինալ տեղում',
                'category' => 'offline',
                'flag' => '💵',
                'icon' => 'fa-solid fa-money-bill-wave',
                'color' => '#10b981',
                'description' => 'Վճարում սեղանի մոտ կամ դրամարկղում՝ կանխիկով կամ բանկային քարտով։',
            ],
        ];
    }

    /**
     * Initiate customer order online payment.
     * Returns redirection URL or payment transaction payload.
     */
    public function initiateOrderPayment(Order $order, string $gateway): array
    {
        $vendor = $order->vendor;
        $settings = $vendor->getPaymentSettings();
        $gwConfig = $settings['gateways'][$gateway] ?? [];
        $isSandbox = ! empty($gwConfig['sandbox']);
        $transactionId = 'TXN-ORD-'.strtoupper(Str::random(10));

        $order->update([
            'payment_method' => $gateway,
            'payment_status' => ($gateway === 'cash' || $gateway === 'pos_terminal') ? 'unpaid' : 'pending',
            'payment_transaction_id' => $transactionId,
        ]);

        if ($gateway === 'cash' || $gateway === 'pos_terminal') {
            return [
                'success' => true,
                'type' => 'offline',
                'message' => 'Վճարումը կկատարվի տեղում (սեղանի մոտ կամ ստանալիս)։',
                'redirect_url' => null,
            ];
        }

        // Mock/Sandbox or Live Dispatcher
        switch ($gateway) {
            case 'idram':
                return [
                    'success' => true,
                    'type' => 'redirect',
                    'gateway' => 'idram',
                    'transaction_id' => $transactionId,
                    'redirect_url' => route('client.payment.callback', [
                        'vendor_slug' => $vendor->slug,
                        'order_id' => $order->id,
                        'gateway' => 'idram',
                        'token' => $transactionId,
                    ]),
                    'sandbox' => $isSandbox,
                ];

            case 'telcell':
                return [
                    'success' => true,
                    'type' => 'redirect',
                    'gateway' => 'telcell',
                    'transaction_id' => $transactionId,
                    'redirect_url' => route('client.payment.callback', [
                        'vendor_slug' => $vendor->slug,
                        'order_id' => $order->id,
                        'gateway' => 'telcell',
                        'token' => $transactionId,
                    ]),
                    'sandbox' => $isSandbox,
                ];

            case 'fastshift':
            case 'arca':
            case 'stripe':
            default:
                return [
                    'success' => true,
                    'type' => 'redirect',
                    'gateway' => $gateway,
                    'transaction_id' => $transactionId,
                    'redirect_url' => route('client.payment.callback', [
                        'vendor_slug' => $vendor->slug,
                        'order_id' => $order->id,
                        'gateway' => $gateway,
                        'token' => $transactionId,
                    ]),
                    'sandbox' => $isSandbox,
                ];
        }
    }

    /**
     * Process order payment callback / confirmation.
     */
    public function finalizeOrderPayment(Order $order, string $gateway, string $token): bool
    {
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $gateway,
            'payment_transaction_id' => $token,
        ]);

        Log::info("Order #{$order->order_number} marked as paid via gateway {$gateway}");

        return true;
    }

    /**
     * Initiate and process vendor SaaS subscription renewal.
     */
    public function processSubscriptionRenewal(
        Vendor $vendor,
        SubscriptionPlan $plan,
        int $monthsCount,
        string $paymentMethod
    ): SubscriptionPayment {
        // Base monthly price * months
        $basePrice = (float) $plan->price;
        $totalAmount = $basePrice * $monthsCount;

        // Discount for longer periods
        if ($monthsCount >= 12) {
            $totalAmount = $totalAmount * 0.80; // 20% discount for 1 year
        } elseif ($monthsCount >= 6) {
            $totalAmount = $totalAmount * 0.90; // 10% discount for 6 months
        }

        $now = now();
        $currentExpiry = ($vendor->subscription_expires_at && $vendor->subscription_expires_at->isFuture())
            ? $vendor->subscription_expires_at
            : $now;

        $newExpiry = (clone $currentExpiry)->addMonths($monthsCount);
        $invoiceNumber = 'INV-'.strtoupper(Str::random(4)).'-'.date('Ymd');

        // Create payment record
        $payment = SubscriptionPayment::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $plan->id,
            'amount' => $totalAmount,
            'currency' => $plan->currency ?? 'AMD',
            'payment_method' => $paymentMethod,
            'invoice_number' => $invoiceNumber,
            'period_start' => $currentExpiry->format('Y-m-d'),
            'period_end' => $newExpiry->format('Y-m-d'),
            'status' => 'completed',
            'notes' => "Երկարաձգում {$monthsCount} ամսով ({$plan->name} փաթեթ)",
        ]);

        // Update vendor subscription status and dates
        $vendor->update([
            'subscription_plan' => $plan->slug,
            'subscription_plan_id' => $plan->id,
            'subscription_status' => 'active',
            'is_active' => true,
            'subscription_expires_at' => $newExpiry,
        ]);

        Log::info("Vendor {$vendor->name} subscription renewed for {$monthsCount} months until {$newExpiry->toDateString()}");

        return $payment;
    }
}
