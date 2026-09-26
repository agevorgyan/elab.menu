<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Vendor;
use App\Services\Payments\DTOs\PaymentInitiationResult;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaymentVerificationService;

class PaymentGatewayService
{
    public function __construct(
        protected PaymentService $paymentService,
        protected PaymentVerificationService $verificationService
    ) {}

    /**
     * Available supported gateways and descriptions.
     *
     * @return array<string, array<string, string>>
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
     * Initiate customer order payment via PaymentService.
     *
     * @return array{success: bool, type: string, gateway: string, transaction_id: ?string, redirect_url: ?string, message?: string}
     */
    public function initiateOrderPayment(Order $order, string $gateway): array
    {
        $result = $this->paymentService->initiateOrderPayment($order, $gateway);

        if ($result->type === 'offline') {
            return [
                'success' => true,
                'type' => 'offline',
                'gateway' => $gateway,
                'transaction_id' => $result->merchantReference,
                'message' => 'Վճարումը կկատարվի տեղում (սեղանի մոտ կամ ստանալիս)։',
                'redirect_url' => null,
            ];
        }

        return [
            'success' => $result->success,
            'type' => $result->type,
            'gateway' => $gateway,
            'transaction_id' => $result->merchantReference,
            'redirect_url' => $result->redirectUrl,
        ];
    }

    /**
     * Finalize order payment via verified attempt.
     */
    public function finalizeOrderPayment(Order $order, string $gateway, string $token): bool
    {
        $attempt = PaymentAttempt::where('order_id', $order->id)
            ->where('gateway', $gateway)
            ->latest()
            ->first();

        if (! $attempt) {
            return false;
        }

        $result = $this->verificationService->verifyAndFinalizeAttempt($attempt, [
            'token' => $token,
            'gateway' => $gateway,
        ]);

        return $result->success;
    }

    /**
     * Initiate vendor SaaS subscription renewal via PaymentService.
     * Does NOT mark payment as completed upfront.
     *
     * @return array{payment: SubscriptionPayment, attempt: PaymentAttempt, result: PaymentInitiationResult}
     */
    public function initiateSubscriptionRenewal(
        Vendor $vendor,
        SubscriptionPlan $plan,
        int $monthsCount,
        string $paymentMethod
    ): array {
        return $this->paymentService->initiateSubscriptionRenewal(
            $vendor,
            $plan,
            $monthsCount,
            $paymentMethod
        );
    }
}
