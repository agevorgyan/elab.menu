<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompositeIndexesAndSoftDeletesTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;
    protected Location $location;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Index & SoftDelete Bistro',
            'slug' => 'index-softdelete-bistro',
            'email' => 'index@bistro.com',
            'password' => bcrypt('password123'),
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Downtown',
            'slug' => 'downtown',
            'address' => 'Amiryan St',
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Desserts',
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Baklava',
            'price' => 1200,
        ]);
    }

    public function test_composite_indexes_exist_on_orders_and_analytics_logs_tables(): void
    {
        $ordersIndexes = Schema::getIndexes('orders');
        $ordersIndexColumns = array_map(fn($idx) => $idx['columns'], $ordersIndexes);

        // Check ['vendor_id', 'created_at'] index on orders
        $hasVendorCreatedAt = false;
        foreach ($ordersIndexColumns as $cols) {
            if ($cols === ['vendor_id', 'created_at']) {
                $hasVendorCreatedAt = true;
                break;
            }
        }
        $this->assertTrue($hasVendorCreatedAt, "Composite index ['vendor_id', 'created_at'] should exist on orders table.");

        // Check ['vendor_id', 'status'] index on orders
        $hasVendorStatus = false;
        foreach ($ordersIndexColumns as $cols) {
            if ($cols === ['vendor_id', 'status']) {
                $hasVendorStatus = true;
                break;
            }
        }
        $this->assertTrue($hasVendorStatus, "Composite index ['vendor_id', 'status'] should exist on orders table.");

        // Check ['vendor_id', 'visit_date'] index on analytics_logs
        $analyticsIndexes = Schema::getIndexes('analytics_logs');
        $analyticsIndexColumns = array_map(fn($idx) => $idx['columns'], $analyticsIndexes);

        $hasVendorVisitDate = false;
        foreach ($analyticsIndexColumns as $cols) {
            if ($cols === ['vendor_id', 'visit_date']) {
                $hasVendorVisitDate = true;
                break;
            }
        }
        $this->assertTrue($hasVendorVisitDate, "Composite index ['vendor_id', 'visit_date'] should exist on analytics_logs table.");
    }

    public function test_product_soft_delete_preserves_historical_order_relationship(): void
    {
        // 1. Create an order with an item pointing to this product
        $order = Order::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'order_number' => 'ORD-SOFT-01',
            'type' => 'dine_in',
            'total_amount' => 2400,
            'status' => 'completed',
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => 1200,
            'quantity' => 2,
            'subtotal' => 2400,
        ]);

        // 2. Soft delete the product
        $this->product->delete();

        // 3. Verify product is soft-deleted
        $this->assertSoftDeleted('products', ['id' => $this->product->id]);
        $this->assertNull(Product::find($this->product->id));
        $this->assertNotNull(Product::withTrashed()->find($this->product->id));

        // 4. Verify historical order item still resolves the product via withTrashed()
        $freshItem = $orderItem->fresh();
        $this->assertNotNull($freshItem->product);
        $this->assertSame($this->product->id, $freshItem->product->id);
        $this->assertSame('Baklava', $freshItem->product->name);
        $this->assertTrue($freshItem->product->trashed());
    }

    public function test_category_soft_delete_preserves_product_category_relationship(): void
    {
        // Soft delete category
        $this->category->delete();

        $this->assertSoftDeleted('categories', ['id' => $this->category->id]);
        $this->assertNull(Category::find($this->category->id));
        $this->assertNotNull(Category::withTrashed()->find($this->category->id));

        // Product's category relation with withTrashed() still resolves
        $freshProduct = $this->product->fresh();
        $this->assertNotNull($freshProduct->category);
        $this->assertSame($this->category->id, $freshProduct->category->id);
        $this->assertSame('Desserts', $freshProduct->category->name);
        $this->assertTrue($freshProduct->category->trashed());
    }

    public function test_order_soft_delete_works_as_expected(): void
    {
        $order = Order::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'order_number' => 'ORD-SOFT-02',
            'type' => 'dine_in',
            'total_amount' => 5000,
            'status' => 'cancelled',
        ]);

        $order->delete();

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
        $this->assertNull(Order::find($order->id));
        $this->assertNotNull(Order::withTrashed()->find($order->id));
        $this->assertTrue(Order::withTrashed()->find($order->id)->trashed());
    }
}
