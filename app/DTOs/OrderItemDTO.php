<?php

namespace App\DTOs;

class OrderItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity,
        public readonly ?int $variationId = null,
        public readonly ?string $variationName = null,
    ) {}

    /**
     * Instantiate from an associative array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: max(1, (int) ($data['quantity'] ?? 1)),
            variationId: !empty($data['variation_id']) ? (int) $data['variation_id'] : null,
            variationName: !empty($data['variation_name']) ? trim((string) $data['variation_name']) : null,
        );
    }
}
