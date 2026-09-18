<?php

namespace Tests\Unit;

use App\Actions\CreateOrderAction;
use App\DTOs\CreateOrderDTO;
use App\DTOs\OrderItemDTO;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderDTOTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_dto_instantiates_from_array_correctly(): void
    {
        $raw = [
            'location_id' => '5',
            'type' => 'dine_in',
            'table_number' => '12',
            'customer_name' => 'Armen',
            'customer_phone' => '+37499112233',
            'customer_email' => 'armen@example.com',
            'notes' => 'Extra spicy',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => '10',
                    'quantity' => '3',
                    'variation_id' => '2',
                    'variation_name' => 'Large',
                ],
                [
                    'product_id' => 11,
                    'quantity' => 1,
                ]
            ]
        ];

        $dto = CreateOrderDTO::fromArray($raw);

        $this->assertSame(5, $dto->locationId);
        $this->assertSame('dine_in', $dto->type);
        $this->assertSame('12', $dto->tableNumber);
        $this->assertSame('Armen', $dto->customerName);
        $this->assertSame('+37499112233', $dto->customerPhone);
        $this->assertSame('armen@example.com', $dto->customerEmail);
        $this->assertSame('Extra spicy', $dto->notes);
        $this->assertSame('cash', $dto->paymentMethod);
        $this->assertCount(2, $dto->items);

        $this->assertInstanceOf(OrderItemDTO::class, $dto->items[0]);
        $this->assertSame(10, $dto->items[0]->productId);
        $this->assertSame(3, $dto->items[0]->quantity);
        $this->assertSame(2, $dto->items[0]->variationId);
        $this->assertSame('Large', $dto->items[0]->variationName);

        $this->assertInstanceOf(OrderItemDTO::class, $dto->items[1]);
        $this->assertSame(11, $dto->items[1]->productId);
        $this->assertSame(1, $dto->items[1]->quantity);
        $this->assertNull($dto->items[1]->variationId);
        $this->assertNull($dto->items[1]->variationName);
    }

    public function test_create_order_action_processes_dto_and_persists_order(): void
    {
        $vendor = Vendor::create([
            'name' => 'DTO Test Bistro',
            'slug' => 'dto-test-bistro',
            'email' => 'dto@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Center Branch',
            'slug' => 'center-branch',
            'address' => 'Northern Ave',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dishes',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Khachapuri',
            'price' => 2500,
        ]);

        $dto = new CreateOrderDTO(
            locationId: $location->id,
            type: 'dine_in',
            items: [
                new OrderItemDTO(
                    productId: $product->id,
                    quantity: 2,
                )
            ],
            tableNumber: 'Table 7',
            customerName: 'Ani',
            customerPhone: '098765432',
            notes: 'Well baked',
            paymentMethod: 'card'
        );

        $action = app(CreateOrderAction::class);
        $result = $action->execute($vendor, $dto);
        $order = $result['order'];

        $this->assertNotNull($order->id);
        $this->assertSame($vendor->id, $order->vendor_id);
        $this->assertSame($location->id, $order->location_id);
        $this->assertSame('Table 7', $order->table_number);
        $this->assertSame(5000.0, (float) $order->total_amount);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(2500.0, (float) $order->items->first()->unit_price);
        $this->assertSame(5000.0, (float) $order->items->first()->subtotal);
    }
}
