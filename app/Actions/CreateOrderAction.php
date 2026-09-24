<?php

namespace App\Actions;

use App\DTOs\CreateOrderDTO;
use App\DTOs\OrderItemDTO;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Mail\OrderReceiptMail;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\CrmAutomationService;
use App\Services\CustomerSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    public function __construct(
        protected CustomerSyncService $customerSyncService,
        protected ?CrmAutomationService $crmService = null,
    ) {
        $this->crmService = $crmService ?? app(CrmAutomationService::class);
    }

    /**
     * Process order submission within a database transaction.
     *
     * @param  CreateOrderDTO|array  $data  Validated DTO or parameters array
     * @return array{order: Order, whatsapp_url: ?string, is_appended: bool}
     */
    public function execute(Vendor $vendor, CreateOrderDTO|array $data): array
    {
        $dto = $data instanceof CreateOrderDTO ? $data : CreateOrderDTO::fromArray($data);

        // Multi-tenant validation: ensure location belongs to vendor
        $location = Location::where('vendor_id', $vendor->id)->find($dto->locationId);
        if (! $location) {
            throw ValidationException::withMessages([
                'location_id' => 'The selected location does not belong to this vendor.',
            ]);
        }

        // Check if customer is appending to an open, non-closed order
        $existingOrder = null;
        if (! empty($dto->activeOrderNumber)) {
            $existingOrder = Order::where('vendor_id', $vendor->id)
                ->where('location_id', $dto->locationId)
                ->where('order_number', $dto->activeOrderNumber)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->first();
        }

        if ($existingOrder) {
            return $this->appendItemsToOrder($vendor, $existingOrder, $dto);
        }

        return $this->createNewOrder($vendor, $dto);
    }

    /**
     * Append items to an existing open order.
     *
     * @return array{order: Order, whatsapp_url: ?string, is_appended: bool}
     */
    protected function appendItemsToOrder(Vendor $vendor, Order $order, CreateOrderDTO $dto): array
    {
        DB::transaction(function () use ($vendor, $order, $dto) {
            // Update customer link if provided
            if (! empty($dto->customerPhone) || ! empty($dto->customerEmail)) {
                $customer = $this->customerSyncService->syncCustomerFromOrder(
                    vendor: $vendor,
                    name: $dto->customerName,
                    phone: $dto->customerPhone,
                    email: $dto->customerEmail,
                    marketingOptIn: $dto->marketingOptIn,
                    locationId: $dto->locationId,
                    birthdate: $dto->customerBirthdate,
                    address: $dto->deliveryAddress
                );
                if ($customer && ! $order->customer_id) {
                    $order->customer_id = $customer->id;
                }
            }

            foreach ($dto->items as $item) {
                $product = Product::where('vendor_id', $vendor->id)->find($item->productId);
                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => "Product #{$item->productId} does not belong to this vendor.",
                    ]);
                }

                $pricing = $this->resolveItemPricing($product, $item, $dto->locationId);
                $subtotal = $pricing['unit_price'] * $item->quantity;

                // Check if exact same item and variation already exists in the order
                $existingItem = OrderItem::where('order_id', $order->id)
                    ->where('product_id', $product->id)
                    ->where(function ($query) use ($pricing) {
                        if ($pricing['variation_name'] === null) {
                            $query->whereNull('variation_name');
                        } else {
                            $query->where('variation_name', $pricing['variation_name']);
                        }
                    })
                    ->first();

                if ($existingItem) {
                    $existingItem->quantity += $item->quantity;
                    $existingItem->subtotal = $existingItem->quantity * $existingItem->unit_price;
                    $existingItem->save();
                } else {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'variation_name' => $pricing['variation_name'],
                        'unit_price' => $pricing['unit_price'],
                        'quantity' => $item->quantity,
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            // Append additional notes if provided
            if (! empty($dto->notes)) {
                $appendNote = '[Հավելում] '.$dto->notes;
                $order->notes = ! empty($order->notes) ? $order->notes.' | '.$appendNote : $appendNote;
            }

            // Recalculate totals
            $itemsSubtotal = (float) OrderItem::where('order_id', $order->id)->sum('subtotal');
            $birthdate = $order->customer_birthdate ?? ($order->customer?->birthdate ? ($order->customer->birthdate instanceof \DateTimeInterface ? $order->customer->birthdate->format('Y-m-d') : (string) $order->customer->birthdate) : null);
            $birthdayDiscount = $this->crmService->calculateBirthdayDiscount($itemsSubtotal, $birthdate, $vendor);
            $fees = $this->calculateFees($vendor, $itemsSubtotal, $order->type);
            $finalTotal = max(0, $fees['final_total'] - $birthdayDiscount);

            $order->update([
                'subtotal' => $itemsSubtotal,
                'birthday_discount_amount' => $birthdayDiscount,
                'service_fee' => $fees['service_fee'],
                'delivery_fee' => $fees['delivery_fee'],
                'total_amount' => $finalTotal,
                'notes' => $order->notes,
            ]);

            if ($order->customer) {
                $this->customerSyncService->recalculateStats($order->customer);
            }
        });

        $order->refresh();
        $order->load(['items.product', 'location', 'customer']);

        // Broadcast real-time events for kitchen/admin and customer tracking
        try {
            event(new OrderCreated($order));
        } catch (\Throwable $e) {
            Log::warning('OrderCreated broadcast failed: '.$e->getMessage());
        }

        try {
            event(new OrderStatusUpdated($order));
        } catch (\Throwable $e) {
            Log::warning('OrderStatusUpdated broadcast failed: '.$e->getMessage());
        }

        $whatsappUrl = $this->buildWhatsAppUrl($order, $vendor, $dto->locationId, true);

        if (! empty($order->customer_email)) {
            try {
                Mail::to($order->customer_email)->queue(
                    new OrderReceiptMail($order)
                );
            } catch (\Throwable $e) {
                // Silently ignore mail failure in queue dispatch
            }
        }

        return [
            'order' => $order,
            'whatsapp_url' => $whatsappUrl,
            'is_appended' => true,
        ];
    }

    /**
     * Create a brand new order.
     *
     * @return array{order: Order, whatsapp_url: ?string, is_appended: bool}
     */
    protected function createNewOrder(Vendor $vendor, CreateOrderDTO $dto): array
    {
        $order = null;

        DB::transaction(function () use ($vendor, $dto, &$order) {
            // Dine-in orders require a table number (scanned from table QR)
            if ($dto->type === 'dine_in' && empty($dto->tableNumber)) {
                throw ValidationException::withMessages([
                    'table_number' => 'Ռեստորանում պատվիրելու համար անհրաժեշտ է սկանավորել սեղանի QR կոդը:',
                ]);
            }

            // Synchronize or create customer profile
            $customer = $this->customerSyncService->syncCustomerFromOrder(
                vendor: $vendor,
                name: $dto->customerName,
                phone: $dto->customerPhone,
                email: $dto->customerEmail,
                marketingOptIn: $dto->marketingOptIn,
                locationId: $dto->locationId,
                birthdate: $dto->customerBirthdate,
                address: $dto->deliveryAddress
            );

            $paymentMethod = $dto->paymentMethod ?: 'cash';
            $paymentStatus = in_array($paymentMethod, ['cash', 'pos_terminal']) ? 'unpaid' : 'pending';

            // Create base order record
            $order = Order::create([
                'vendor_id' => $vendor->id,
                'location_id' => $dto->locationId,
                'customer_id' => $customer?->id,
                'order_number' => 'ORD-'.strtoupper(Str::random(6)),
                'table_number' => in_array($dto->type, ['delivery', 'takeaway']) ? null : ($dto->tableNumber ?? 'Counter'),
                'delivery_address' => $dto->deliveryAddress,
                'type' => $dto->type,
                'total_amount' => 0,
                'status' => 'pending',
                'customer_name' => $dto->customerName ?? 'Guest',
                'customer_phone' => $dto->customerPhone,
                'customer_email' => $dto->customerEmail,
                'customer_birthdate' => $dto->customerBirthdate,
                'marketing_opt_in' => $dto->marketingOptIn,
                'notes' => $dto->notes,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'birthday_discount_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($dto->items as $item) {
                $product = Product::where('vendor_id', $vendor->id)->find($item->productId);
                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => "Product #{$item->productId} does not belong to this vendor.",
                    ]);
                }

                $pricing = $this->resolveItemPricing($product, $item, $dto->locationId);
                $subtotal = $pricing['unit_price'] * $item->quantity;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'variation_name' => $pricing['variation_name'],
                    'unit_price' => $pricing['unit_price'],
                    'quantity' => $item->quantity,
                    'subtotal' => $subtotal,
                ]);
            }

            $itemsSubtotal = $totalAmount;

            if ($dto->type === 'delivery') {
                if (! $vendor->delivery_enabled) {
                    throw ValidationException::withMessages([
                        'delivery' => 'Առաքման ծառայությունը ներկայումս հասանելի չէ:',
                    ]);
                }

                if ((float) $vendor->delivery_min_amount > 0 && $itemsSubtotal < (float) $vendor->delivery_min_amount) {
                    throw ValidationException::withMessages([
                        'delivery' => 'Նվազագույն պատվերի գումարը առաքման համար՝ '.number_format((float) $vendor->delivery_min_amount)." {$vendor->currency}:",
                    ]);
                }
            } elseif ($dto->type === 'takeaway') {
                if (! ($vendor->takeaway_enabled ?? true)) {
                    throw ValidationException::withMessages([
                        'takeaway' => 'Տեղում վերցնելու (Takeaway) ծառայությունը ներկայումս հասանելի չէ:',
                    ]);
                }

                if ((float) $vendor->takeaway_min_amount > 0 && $itemsSubtotal < (float) $vendor->takeaway_min_amount) {
                    throw ValidationException::withMessages([
                        'takeaway' => 'Նվազագույն պատվերի գումարը տեղում վերցնելու համար՝ '.number_format((float) $vendor->takeaway_min_amount)." {$vendor->currency}:",
                    ]);
                }
            }

            $effectiveBirthdate = $dto->customerBirthdate ?? ($customer?->birthdate ? ($customer->birthdate instanceof \DateTimeInterface ? $customer->birthdate->format('Y-m-d') : (string) $customer->birthdate) : null);
            $birthdayDiscount = $this->crmService->calculateBirthdayDiscount($itemsSubtotal, $effectiveBirthdate, $vendor);
            $fees = $this->calculateFees($vendor, $itemsSubtotal, $dto->type);
            $finalTotal = max(0, $fees['final_total'] - $birthdayDiscount);

            $order->update([
                'subtotal' => $itemsSubtotal,
                'birthday_discount_amount' => $birthdayDiscount,
                'service_fee' => $fees['service_fee'],
                'delivery_fee' => $fees['delivery_fee'],
                'total_amount' => $finalTotal,
            ]);

            if ($customer) {
                $this->customerSyncService->recalculateStats($customer);
            }
        });

        // Broadcast real-time OrderCreated event for kitchen & staff display
        try {
            event(new OrderCreated($order));
        } catch (\Throwable $e) {
            Log::warning('OrderCreated broadcast failed: '.$e->getMessage());
        }

        $whatsappUrl = $this->buildWhatsAppUrl($order, $vendor, $dto->locationId, false);

        if (! empty($order->customer_email)) {
            try {
                Mail::to($order->customer_email)->queue(
                    new OrderReceiptMail($order)
                );
            } catch (\Throwable $e) {
                // Silently ignore mail failure in queue dispatch
            }
        }

        return [
            'order' => $order,
            'whatsapp_url' => $whatsappUrl,
            'is_appended' => false,
        ];
    }

    /**
     * Resolve unit price and variation name for an item.
     *
     * @return array{unit_price: float, variation_name: string}
     */
    protected function resolveItemPricing(Product $product, OrderItemDTO $item, int $locationId): array
    {
        $variationsCount = $product->variations()->count();
        $variation = null;

        // 1. Resolve variation if variationId is supplied
        if (! empty($item->variationId)) {
            $variation = $product->variations()->find($item->variationId);
            if (! $variation) {
                throw ValidationException::withMessages([
                    'items' => "Invalid variation selected for dish {$product->name}.",
                ]);
            }
        }
        // 2. Resolve variation if variationName is supplied
        elseif (! empty($item->variationName)) {
            $variation = $product->variations()->where(function ($query) use ($item) {
                $query->where('name', $item->variationName)
                    ->orWhere('name_translations->hy', $item->variationName)
                    ->orWhere('name_translations->ru', $item->variationName)
                    ->orWhere('name_translations->en', $item->variationName);
            })->first();
            if (! $variation && $variationsCount > 0) {
                throw ValidationException::withMessages([
                    'items' => "Invalid variation '{$item->variationName}' selected for dish {$product->name}.",
                ]);
            }
        }

        // 3. Prevent pricing bypass: multi-variation dishes must have an explicit valid variation
        if ($variationsCount > 1 && ! $variation) {
            throw ValidationException::withMessages([
                'items' => "A valid variation must be selected for dish {$product->name}.",
            ]);
        }

        // 4. If product has exactly 1 variation and none was passed, auto-select it
        if ($variationsCount === 1 && ! $variation) {
            $variation = $product->variations()->first();
        }

        // 5. Determine unit price strictly from variation, location override, or scheduled discount
        if ($variation) {
            $override = $product->overrides->firstWhere('location_id', $locationId);
            if ($variationsCount === 1 && $override && $override->override_price !== null) {
                if ($product->isDiscountActive() && (float) $product->price > 0) {
                    $ratio = (float) $product->discount_price / (float) $product->price;
                    $unitPrice = round((float) $override->override_price * $ratio, 2);
                } else {
                    $unitPrice = (float) $override->override_price;
                }
            } else {
                $unitPrice = (float) $variation->getEffectivePrice();
            }
            $variationName = $variation->name;
        } else {
            $unitPrice = (float) $product->getEffectivePrice($locationId);
            $variationName = ! empty($item->variationName) ? $item->variationName : 'Standard';
        }

        return [
            'unit_price' => $unitPrice,
            'variation_name' => $variationName,
        ];
    }

    /**
     * Calculate order fees based on vendor settings and order type.
     *
     * @return array{service_fee: float, delivery_fee: float, final_total: float}
     */
    protected function calculateFees(Vendor $vendor, float $itemsSubtotal, string $orderType): array
    {
        $serviceFee = 0.00;
        $deliveryFee = 0.00;

        if ($orderType === 'delivery') {
            if ($vendor->delivery_free_from !== null && (float) $vendor->delivery_free_from > 0 && $itemsSubtotal >= (float) $vendor->delivery_free_from) {
                $deliveryFee = 0.00;
            } else {
                $deliveryFee = (float) ($vendor->delivery_fee ?? 0);
            }
        } elseif ($orderType === 'dine_in') {
            if ($vendor->service_fee_enabled) {
                $minOrder = $vendor->service_fee_min_order !== null ? (float) $vendor->service_fee_min_order : 0;
                if ($minOrder <= 0 || $itemsSubtotal >= $minOrder) {
                    if ($vendor->service_fee_type === 'percent') {
                        $serviceFee = round(($itemsSubtotal * (float) $vendor->service_fee_value) / 100, 2);
                    } else {
                        $serviceFee = (float) ($vendor->service_fee_value ?? 0);
                    }
                }
            }
        }

        $finalTotal = $itemsSubtotal + $serviceFee + $deliveryFee;

        return [
            'service_fee' => $serviceFee,
            'delivery_fee' => $deliveryFee,
            'final_total' => $finalTotal,
        ];
    }

    /**
     * Build WhatsApp dispatch URL if branch has configured phone number.
     */
    protected function buildWhatsAppUrl(Order $order, Vendor $vendor, int $locationId, bool $isAppended = false): ?string
    {
        $location = Location::find($locationId);
        if (! $location || empty($location->whatsapp_number)) {
            return null;
        }

        $headerTitle = $isAppended
            ? "🧾 *Հավելյալ Պատվեր / Order Update #{$order->order_number}*"
            : "🧾 *New Order #{$order->order_number}*";

        $msg = "{$headerTitle}\n";
        if ($order->type === 'delivery') {
            $msg .= "🛵 *Տեսակը՝ ԱՌԱՔՈՒՄ (Delivery)*\n";
            $msg .= "📍 *Առաքման հասցե՝* {$order->delivery_address}\n";
        } elseif ($order->type === 'takeaway') {
            $msg .= "🛍️ *Տեսակը՝ ՏԵՂՈՒՄ ՎԵՐՑՆԵԼ (Takeaway)*\n";
            $msg .= "📍 *Մասնաճյուղ՝ {$location->name}*\n";
        } else {
            $msg .= "🍽️ *Տեսակը՝ ՌԵՍՏՈՐԱՆՈՒՄ (Dine-in)*\n";
            $msg .= "📍 *{$location->name}* ".($order->table_number ? "({$order->table_number})" : '')."\n";
        }
        $msg .= "👤 Name: {$order->customer_name}\n";
        if (! empty($order->customer_phone)) {
            $msg .= "📞 Phone: {$order->customer_phone}\n";
        }
        $msg .= "--------------------\n";

        foreach ($order->items as $i) {
            $msg .= "• {$i->quantity}x {$i->product_name} ({$i->variation_name}) - ".number_format($i->subtotal)." {$vendor->currency}\n";
        }

        if (! empty($order->notes)) {
            $msg .= "\n📝 *Notes:* {$order->notes}\n";
        }

        $msg .= "--------------------\n";
        $msg .= '🛒 *Ենթագումար (Subtotal): '.number_format($order->subtotal)." {$vendor->currency}*\n";

        if ($order->service_fee > 0) {
            $feeLabel = $vendor->service_fee_type === 'percent' ? " ({$vendor->service_fee_value}%)" : '';
            $msg .= "🛎️ *Սպասարկման վճար{$feeLabel}: ".number_format($order->service_fee)." {$vendor->currency}*\n";
        }

        if ($order->type === 'delivery') {
            if ($order->delivery_fee > 0) {
                $msg .= '🛵 *Առաքման վճար: '.number_format($order->delivery_fee)." {$vendor->currency}*\n";
            } else {
                $msg .= "🛵 *Առաքում: ԱՆՎՃԱՐ (Free)*\n";
            }
        }

        $msg .= '💰 *Ընդամենը (Total): '.number_format($order->total_amount)." {$vendor->currency}*";

        $cleanPhone = preg_replace('/[^0-9]/', '', $location->whatsapp_number);

        return "https://wa.me/{$cleanPhone}?text=".urlencode($msg);
    }
}
