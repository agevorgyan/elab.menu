<?php

namespace App\Services;

use App\DTOs\OrderItemDTO;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Validation\ValidationException;

class OrderPricingService
{
    /**
     * Resolve unit price and variation name for an item.
     *
     * @return array{unit_price: float, variation_name: string}
     *
     * @throws ValidationException
     */
    public function resolveItemPricing(Product $product, OrderItemDTO $item, int $locationId): array
    {
        $variations = $product->relationLoaded('variations') ? $product->variations : $product->variations()->get();
        $variationsCount = $variations->count();
        $variation = null;

        // 1. Resolve variation if variationId is supplied
        if (! empty($item->variationId)) {
            $variation = $variations->firstWhere('id', (int) $item->variationId);
            if (! $variation) {
                throw ValidationException::withMessages([
                    'items' => "Invalid variation selected for dish {$product->name}.",
                ]);
            }
        }
        // 2. Resolve variation if variationName is supplied
        elseif (! empty($item->variationName)) {
            $target = mb_strtolower(trim((string) $item->variationName));
            $variation = $variations->first(function ($v) use ($target) {
                if (mb_strtolower(trim((string) $v->name)) === $target) {
                    return true;
                }
                $translations = is_array($v->name_translations)
                    ? $v->name_translations
                    : (json_decode($v->name_translations ?? '[]', true) ?: []);
                foreach ($translations as $val) {
                    if (! empty($val) && is_scalar($val) && mb_strtolower(trim((string) $val)) === $target) {
                        return true;
                    }
                }

                return false;
            });
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
            $variation = $variations->first();
        }

        // 5. Determine unit price strictly from variation, location override, or scheduled discount
        if ($variation) {
            if (! $variation->relationLoaded('product')) {
                $variation->setRelation('product', $product);
            }

            $override = $product->relationLoaded('overrides')
                ? $product->overrides->firstWhere('location_id', $locationId)
                : $product->overrides()->where('location_id', $locationId)->first();

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
    public function calculateFees(Vendor $vendor, float $itemsSubtotal, string $orderType): array
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
}
