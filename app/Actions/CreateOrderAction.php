<?php

namespace App\Actions;

use App\DTOs\CreateOrderDTO;
use App\DTOs\OrderItemDTO;
use App\Events\OrderCreated;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\CustomerSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    public function __construct(
        protected CustomerSyncService $customerSyncService
    ) {}

    /**
     * Process order submission within a database transaction.
     *
     * @param Vendor $vendor
     * @param CreateOrderDTO|array $data Validated DTO or parameters array
     * @return array{order: Order, whatsapp_url: ?string}
     */
    public function execute(Vendor $vendor, CreateOrderDTO|array $data): array
    {
        $dto = $data instanceof CreateOrderDTO ? $data : CreateOrderDTO::fromArray($data);

        // Multi-tenant validation: ensure location belongs to vendor
        $location = Location::where('vendor_id', $vendor->id)->find($dto->locationId);
        if (!$location) {
            throw ValidationException::withMessages([
                'location_id' => 'The selected location does not belong to this vendor.',
            ]);
        }

        $order = null;

        DB::transaction(function () use ($vendor, $dto, &$order) {
            // Synchronize or create customer profile
            $customer = $this->customerSyncService->syncCustomerFromOrder(
                vendor: $vendor,
                name: $dto->customerName,
                phone: $dto->customerPhone,
                email: $dto->customerEmail,
                marketingOptIn: $dto->marketingOptIn,
                locationId: $dto->locationId
            );

            // Create base order record
            $order = Order::create([
                'vendor_id' => $vendor->id,
                'location_id' => $dto->locationId,
                'customer_id' => $customer?->id,
                'order_number' => 'ORD-' . strtoupper(Str::random(6)),
                'table_number' => $dto->tableNumber ?? 'Counter',
                'type' => $dto->type,
                'total_amount' => 0,
                'status' => 'pending',
                'customer_name' => $dto->customerName ?? 'Guest',
                'customer_phone' => $dto->customerPhone,
                'customer_email' => $dto->customerEmail,
                'marketing_opt_in' => $dto->marketingOptIn,
                'notes' => $dto->notes,
            ]);

            $totalAmount = 0;

            foreach ($dto->items as $item) {
                // Multi-tenant safe: ensure product belongs to this vendor
                $product = Product::where('vendor_id', $vendor->id)->find($item->productId);
                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => "Product #{$item->productId} does not belong to this vendor.",
                    ]);
                }

                $variationsCount = $product->variations()->count();
                $variation = null;

                // 1. Resolve variation if variationId is supplied
                if (!empty($item->variationId)) {
                    $variation = $product->variations()->find($item->variationId);
                    if (!$variation) {
                        throw ValidationException::withMessages([
                            'items' => "Invalid variation selected for dish {$product->name}.",
                        ]);
                    }
                } 
                // 2. Resolve variation if variationName is supplied
                elseif (!empty($item->variationName)) {
                    $variation = $product->variations()->where('name', $item->variationName)->first();
                    if (!$variation && $variationsCount > 0) {
                        throw ValidationException::withMessages([
                            'items' => "Invalid variation '{$item->variationName}' selected for dish {$product->name}.",
                        ]);
                    }
                }

                // 3. Prevent pricing bypass: multi-variation dishes must have an explicit valid variation
                if ($variationsCount > 1 && !$variation) {
                    throw ValidationException::withMessages([
                        'items' => "A valid variation must be selected for dish {$product->name}.",
                    ]);
                }

                // 4. If product has exactly 1 variation and none was passed, auto-select it
                if ($variationsCount === 1 && !$variation) {
                    $variation = $product->variations()->first();
                }

                // 5. Determine unit price strictly from variation or location override
                if ($variation) {
                    $override = $product->overrides->firstWhere('location_id', $dto->locationId);
                    if ($variationsCount === 1 && $override && $override->override_price !== null) {
                        $unitPrice = (float) $override->override_price;
                    } else {
                        $unitPrice = (float) $variation->price;
                    }
                    $variationName = $variation->name;
                } else {
                    $unitPrice = (float) $product->getEffectivePrice($dto->locationId);
                    $variationName = !empty($item->variationName) ? $item->variationName : 'Standard';
                }

                $subtotal = $unitPrice * $item->quantity;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'variation_name' => $variationName,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);

            if ($customer) {
                $this->customerSyncService->recalculateStats($customer);
            }
        });

        // Broadcast real-time OrderCreated event for kitchen & staff display
        event(new OrderCreated($order));

        $whatsappUrl = $this->buildWhatsAppUrl($order, $vendor, $dto->locationId);

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
