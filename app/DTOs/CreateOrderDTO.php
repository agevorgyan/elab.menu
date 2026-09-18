<?php

namespace App\DTOs;

use App\Http\Requests\SubmitOrderRequest;

class CreateOrderDTO
{
    /**
     * @param OrderItemDTO[] $items
     */
    public function __construct(
        public readonly int $locationId,
        public readonly string $type,
        public readonly array $items,
        public readonly ?string $tableNumber = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $customerEmail = null,
        public readonly bool $marketingOptIn = true,
        public readonly ?string $notes = null,
    ) {}

    /**
     * Instantiate from an associative array.
     */
    public static function fromArray(array $data): self
    {
        $rawItems = $data['items'] ?? [];
        $items = [];

        foreach ($rawItems as $item) {
            if ($item instanceof OrderItemDTO) {
                $items[] = $item;
            } elseif (is_array($item)) {
                $items[] = OrderItemDTO::fromArray($item);
            }
        }

        return new self(
            locationId: (int) $data['location_id'],
            type: !empty($data['type']) ? (string) $data['type'] : 'dine_in',
            items: $items,
            tableNumber: !empty($data['table_number']) ? trim((string) $data['table_number']) : null,
            customerName: !empty($data['customer_name']) ? trim((string) $data['customer_name']) : null,
            customerPhone: !empty($data['customer_phone']) ? trim((string) $data['customer_phone']) : null,
            customerEmail: !empty($data['customer_email']) ? strtolower(trim((string) $data['customer_email'])) : null,
            marketingOptIn: filter_var($data['marketing_opt_in'] ?? true, FILTER_VALIDATE_BOOLEAN),
            notes: !empty($data['notes']) ? trim((string) $data['notes']) : null,
        );
    }

    /**
     * Instantiate from a validated SubmitOrderRequest.
     */
    public static function fromRequest(SubmitOrderRequest $request): self
    {
        return self::fromArray($request->validated());
    }
}
