<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\CustomerSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrderAction
{
    public function __construct(
        protected CustomerSyncService $customerSyncService
    ) {}

    /**
     * Process order submission within a database transaction.
     *
     * @param Vendor $vendor
     * @param array $data Validated request parameters
     * @return array{order: Order, whatsapp_url: ?string}
     */
    public function execute(Vendor $vendor, array $data): array
    {
        $phone = !empty($data['customer_phone']) ? trim($data['customer_phone']) : null;
        $email = !empty($data['customer_email']) ? strtolower(trim($data['customer_email'])) : null;
        $name = !empty($data['customer_name']) ? trim($data['customer_name']) : null;
        $marketingOptIn = filter_var($data['marketing_opt_in'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $order = null;

        DB::transaction(function () use ($vendor, $data, $name, $phone, $email, $marketingOptIn, &$order) {
            // Synchronize or create customer profile
            $customer = $this->customerSyncService->syncCustomerFromOrder(
                vendor: $vendor,
                name: $name,
                phone: $phone,
                email: $email,
                marketingOptIn: $marketingOptIn,
                locationId: $data['location_id']
            );

            // Create base order record
            $order = Order::create([
                'vendor_id' => $vendor->id,
                'location_id' => $data['location_id'],
                'customer_id' => $customer?->id,
                'order_number' => 'ORD-' . strtoupper(Str::random(6)),
                'table_number' => $data['table_number'] ?? 'Counter',
                'type' => $data['type'],
                'total_amount' => 0,
                'status' => 'pending',
                'customer_name' => $name ?? 'Guest',
                'customer_phone' => $phone,
                'customer_email' => $email,
                'marketing_opt_in' => $marketingOptIn,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                // Multi-tenant safe: ensure product belongs to this vendor
                $product = Product::where('vendor_id', $vendor->id)->find($item['product_id']);
                if (!$product) {
                    continue;
                }

                // Resolve variation and its price
                $variation = null;
                if (!empty($item['variation_id'])) {
                    $variation = $product->variations()->find($item['variation_id']);
                } elseif (!empty($item['variation_name'])) {
                    $variation = $product->variations()->where('name', $item['variation_name'])->first();
                }

                $unitPrice = $variation 
                    ? (float) $variation->price 
                    : (float) $product->getEffectivePrice($data['location_id']);

                $variationName = $variation 
                    ? $variation->name 
                    : ($item['variation_name'] ?? 'Standard Portion');

                $subtotal = $unitPrice * $item['quantity'];
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'variation_name' => $variationName,
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);

            if ($customer) {
                $this->customerSyncService->recalculateStats($customer);
            }
        });

        $whatsappUrl = $this->buildWhatsAppUrl($order, $vendor, (int)$data['location_id']);

        if (!empty($order->customer_email)) {
            try {
                \Illuminate\Support\Facades\Mail::to($order->customer_email)->queue(
                    new \App\Mail\OrderReceiptMail($order)
                );
            } catch (\Throwable $e) {
                // Silently ignore mail failure in queue dispatch
            }
        }

        return [
            'order' => $order,
            'whatsapp_url' => $whatsappUrl,
        ];
    }

    /**
     * Build WhatsApp dispatch URL if branch has configured phone number.
     */
    protected function buildWhatsAppUrl(Order $order, Vendor $vendor, int $locationId): ?string
    {
        $location = Location::find($locationId);
        if (!$location || empty($location->whatsapp_number)) {
            return null;
        }

        $msg = "🧾 *New Order #{$order->order_number}*\n";
        $msg .= "📍 *{$location->name}* " . ($order->table_number ? "({$order->table_number})" : "") . "\n";
        $msg .= "👤 Name: {$order->customer_name}\n";
        $msg .= "--------------------\n";

        foreach ($order->items as $i) {
            $msg .= "• {$i->quantity}x {$i->product_name} ({$i->variation_name}) - " . number_format($i->subtotal) . " {$vendor->currency}\n";
        }

        if (!empty($order->notes)) {
            $msg .= "\n📝 *Notes:* {$order->notes}\n";
        }

        $msg .= "--------------------\n";
        $msg .= "💰 *Total: " . number_format($order->total_amount) . " {$vendor->currency}*";

        $cleanPhone = preg_replace('/[^0-9]/', '', $location->whatsapp_number);

        return "https://wa.me/{$cleanPhone}?text=" . urlencode($msg);
    }
}
