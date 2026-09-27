<?php

namespace Tests\Feature;

use App\Models\AiWaiterSession;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\LocationProductOverride;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStorageFile;
use App\Models\WaiterCall;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendorA;

    protected Vendor $vendorB;

    protected Location $locationA;

    protected Location $locationB;

    protected Category $categoryA;

    protected Category $categoryB;

    protected Product $productA;

    protected Product $productB;

    protected Order $orderA;

    protected Order $orderB;

    protected Customer $customerA;

    protected Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        // Vendor A
        $this->vendorA = Vendor::create([
            'name' => 'Bistro Alpha',
            'slug' => 'bistro-alpha',
            'email' => 'alpha@test.com',
            'is_active' => true,
        ]);

        $this->locationA = Location::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Downtown Branch',
            'slug' => 'downtown',
            'is_active' => true,
        ]);

        $this->categoryA = Category::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Starters A',
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $this->categoryA->id,
            'name' => 'Soup Alpha',
            'price' => 1500,
            'is_available' => true,
        ]);

        $this->customerA = Customer::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Alice Customer',
            'phone' => '+37491000001',
            'email' => 'alice@test.com',
        ]);

        $this->orderA = Order::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationA->id,
            'customer_id' => $this->customerA->id,
            'order_number' => 'ORD-A-001',
            'total_amount' => 3000,
            'status' => 'pending',
        ]);

        // Vendor B
        $this->vendorB = Vendor::create([
            'name' => 'Trattoria Beta',
            'slug' => 'trattoria-beta',
            'email' => 'beta@test.com',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'vendor_id' => $this->vendorB->id,
            'name' => 'Uptown Branch',
            'slug' => 'uptown',
            'is_active' => true,
        ]);

        $this->categoryB = Category::create([
            'vendor_id' => $this->vendorB->id,
            'name' => 'Pasta Beta',
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'vendor_id' => $this->vendorB->id,
            'category_id' => $this->categoryB->id,
            'name' => 'Pasta Beta Dish',
            'price' => 2500,
            'is_available' => true,
        ]);

        $this->customerB = Customer::create([
            'vendor_id' => $this->vendorB->id,
            'name' => 'Bob Customer',
            'phone' => '+37491000002',
            'email' => 'bob@test.com',
        ]);

        $this->orderB = Order::create([
            'vendor_id' => $this->vendorB->id,
            'location_id' => $this->locationB->id,
            'customer_id' => $this->customerB->id,
            'order_number' => 'ORD-B-001',
            'total_amount' => 5000,
            'status' => 'pending',
        ]);
    }

    public function test_product_rejects_category_from_another_vendor_at_model_boundary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cross-vendor category assignment forbidden.');

        Product::create([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $this->categoryB->id, // Mismatched vendor B category
            'name' => 'Intruder Product',
            'price' => 2000,
        ]);
    }

    public function test_product_rejects_category_from_another_vendor_at_database_boundary(): void
    {
        $this->expectException(QueryException::class);

        // Direct raw insert bypassing model hooks to test DB composite FK
        DB::table('products')->insert([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $this->categoryB->id, // Belongs to Vendor B
            'name' => 'Raw DB Intruder Product',
            'price' => 1200,
            'is_available' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_order_rejects_location_from_another_vendor_at_model_boundary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cross-vendor location assigned to order.');

        Order::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationB->id, // Mismatched vendor B location
            'order_number' => 'ORD-MISMATCH-LOC',
            'total_amount' => 1000,
            'status' => 'pending',
        ]);
    }

    public function test_order_rejects_location_from_another_vendor_at_database_boundary(): void
    {
        $this->expectException(QueryException::class);

        DB::table('orders')->insert([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationB->id, // Belongs to vendor B
            'order_number' => 'ORD-RAW-LOC-MISMATCH',
            'total_amount' => 1500,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_order_rejects_customer_from_another_vendor_at_model_boundary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cross-vendor customer attached to order.');

        Order::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationA->id,
            'customer_id' => $this->customerB->id, // Mismatched vendor B customer
            'order_number' => 'ORD-MISMATCH-CUST',
            'total_amount' => 2000,
            'status' => 'pending',
        ]);
    }

    public function test_order_rejects_customer_from_another_vendor_at_database_boundary(): void
    {
        $this->expectException(QueryException::class);

        DB::table('orders')->insert([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationA->id,
            'customer_id' => $this->customerB->id, // Belongs to vendor B
            'order_number' => 'ORD-RAW-CUST-MISMATCH',
            'total_amount' => 2500,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_category_rejects_location_from_another_vendor_at_model_and_database_boundary(): void
    {
        // Model boundary check
        try {
            Category::create([
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationB->id, // Mismatched vendor B location
                'name' => 'Bad Category',
            ]);
            $this->fail('Category creation should have thrown InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cross-vendor location assigned to category.', $e->getMessage());
        }

        // Database composite FK boundary check
        $this->expectException(QueryException::class);
        DB::table('categories')->insert([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationB->id,
            'name' => 'Raw Bad Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_location_product_override_rejects_cross_tenant_combinations(): void
    {
        // Model boundary check: location and product from different vendors
        try {
            LocationProductOverride::create([
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationA->id,
                'product_id' => $this->productB->id, // Product from vendor B!
                'override_price' => 3000,
            ]);
            $this->fail('LocationProductOverride should have thrown InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Tenant isolation violation', $e->getMessage());
        }

        // Database boundary: direct DB insert violating product_id/vendor_id FK
        $this->expectException(QueryException::class);
        DB::table('location_product_overrides')->insert([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationA->id,
            'product_id' => $this->productB->id,
            'override_price' => 3000,
            'is_available' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_order_item_rejects_product_from_another_vendor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cross-vendor product assigned to order item.');

        OrderItem::create([
            'order_id' => $this->orderA->id, // Vendor A order
            'product_id' => $this->productB->id, // Vendor B product
            'product_name' => 'Pasta Beta',
            'unit_price' => 2500,
            'quantity' => 2,
            'subtotal' => 5000,
        ]);
    }

    public function test_waiter_call_rejects_location_from_another_vendor(): void
    {
        // Model boundary
        try {
            WaiterCall::create([
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationB->id, // Vendor B location
                'table_number' => 'T12',
                'type' => 'call_waiter',
            ]);
            $this->fail('WaiterCall should have thrown InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cross-vendor location assigned to waiter call.', $e->getMessage());
        }

        // Database composite FK boundary
        $this->expectException(QueryException::class);
        DB::table('waiter_calls')->insert([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationB->id,
            'call_token' => 'wcl_test_'.bin2hex(random_bytes(10)),
            'table_number' => 'T12',
            'type' => 'call_waiter',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_ai_waiter_session_rejects_cross_tenant_location_or_order(): void
    {
        // Model boundary with foreign location
        try {
            AiWaiterSession::create([
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationB->id, // Vendor B location
                'session_token' => 'ai_tok_'.bin2hex(random_bytes(10)),
                'status' => 'active',
            ]);
            $this->fail('AiWaiterSession with wrong location should fail.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cross-vendor location assigned to AI session.', $e->getMessage());
        }

        // Model boundary with foreign order
        try {
            AiWaiterSession::create([
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationA->id,
                'order_id' => $this->orderB->id, // Vendor B order
                'session_token' => 'ai_tok_'.bin2hex(random_bytes(10)),
                'status' => 'active',
            ]);
            $this->fail('AiWaiterSession with wrong order should fail.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cross-vendor order assigned to AI session.', $e->getMessage());
        }

        // Database composite FK boundary
        $this->expectException(QueryException::class);
        DB::table('ai_waiter_sessions')->insert([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationB->id,
            'session_token' => 'ai_tok_db_'.bin2hex(random_bytes(10)),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_payment_attempt_rejects_cross_tenant_order_or_subscription(): void
    {
        // Model boundary
        try {
            PaymentAttempt::create([
                'vendor_id' => $this->vendorA->id,
                'order_id' => $this->orderB->id, // Vendor B order!
                'gateway' => 'arca',
                'merchant_reference' => 'MERCH-MISMATCH-001',
                'amount' => 5000,
                'currency' => 'AMD',
            ]);
            $this->fail('PaymentAttempt should have thrown InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cross-vendor order attached to payment attempt.', $e->getMessage());
        }

        // Database composite FK boundary
        $this->expectException(QueryException::class);
        DB::table('payment_attempts')->insert([
            'uuid' => (string) Str::uuid(),
            'vendor_id' => $this->vendorA->id,
            'order_id' => $this->orderB->id,
            'gateway' => 'arca',
            'merchant_reference' => 'MERCH-DB-MISMATCH-001',
            'amount' => 5000,
            'currency' => 'AMD',
            'status' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_vendor_storage_file_rejects_cross_tenant_entity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cross-vendor entity attached to vendor storage file.');

        VendorStorageFile::create([
            'vendor_id' => $this->vendorA->id,
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => 'vendors/'.$this->vendorA->id.'/test.jpg',
            'original_name' => 'dish.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'checksum' => hash('sha256', 'dummy'),
            'entity_type' => Product::class,
            'entity_id' => $this->productB->id, // Vendor B product!
            'status' => 'active',
        ]);
    }

    public function test_user_rejects_cross_tenant_location(): void
    {
        // Model boundary
        try {
            User::create([
                'name' => 'Staff Member',
                'email' => 'staff.alpha@test.com',
                'password' => bcrypt('password123'),
                'role' => 'staff',
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationB->id, // Vendor B location!
            ]);
            $this->fail('User with wrong location should throw InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cross-vendor location assigned to user.', $e->getMessage());
        }

        // Database composite FK boundary
        $this->expectException(QueryException::class);
        DB::table('users')->insert([
            'name' => 'Raw Staff Member',
            'email' => 'rawstaff.alpha@test.com',
            'password' => bcrypt('password123'),
            'role' => 'staff',
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_customer_rejects_cross_tenant_location(): void
    {
        // Model boundary
        try {
            Customer::create([
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationB->id, // Vendor B location
                'name' => 'Mismatched Customer',
                'phone' => '+37491999999',
            ]);
            $this->fail('Customer should have thrown InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cross-vendor location assigned to customer.', $e->getMessage());
        }

        // Database composite FK boundary
        $this->expectException(QueryException::class);
        DB::table('customers')->insert([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationB->id,
            'name' => 'Raw DB Mismatched Customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_check_constraints_prevent_negative_values(): void
    {
        // Product negative price
        try {
            Product::create([
                'vendor_id' => $this->vendorA->id,
                'category_id' => $this->categoryA->id,
                'name' => 'Negative Price Product',
                'price' => -100,
            ]);
            $this->fail('Negative product price should have thrown InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Product price cannot be negative', $e->getMessage());
        }

        // Order negative total
        try {
            Order::create([
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationA->id,
                'order_number' => 'ORD-NEG-001',
                'total_amount' => -500,
                'status' => 'pending',
            ]);
            $this->fail('Negative order total should have thrown InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Order total cannot be negative', $e->getMessage());
        }

        // Order item non-positive quantity
        try {
            OrderItem::create([
                'order_id' => $this->orderA->id,
                'product_id' => $this->productA->id,
                'product_name' => 'Soup',
                'unit_price' => 1500,
                'quantity' => 0,
                'subtotal' => 0,
            ]);
            $this->fail('Zero quantity should have thrown InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('quantity must be greater than zero', $e->getMessage());
        }

        // Payment attempt negative amount
        try {
            PaymentAttempt::create([
                'vendor_id' => $this->vendorA->id,
                'order_id' => $this->orderA->id,
                'gateway' => 'arca',
                'merchant_reference' => 'MERCH-NEG-001',
                'amount' => -200,
                'currency' => 'AMD',
            ]);
            $this->fail('Negative payment attempt amount should fail.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Payment attempt amount cannot be negative', $e->getMessage());
        }

        // Subscription payment negative amount
        try {
            SubscriptionPayment::create([
                'vendor_id' => $this->vendorA->id,
                'amount' => -1000,
                'currency' => 'AMD',
                'status' => 'pending',
            ]);
            $this->fail('Negative subscription payment amount should fail.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Subscription payment amount cannot be negative', $e->getMessage());
        }

        // LocationProductOverride negative override price
        try {
            LocationProductOverride::create([
                'vendor_id' => $this->vendorA->id,
                'location_id' => $this->locationA->id,
                'product_id' => $this->productA->id,
                'override_price' => -50,
            ]);
            $this->fail('Negative override price should fail.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Override price cannot be negative', $e->getMessage());
        }
    }

    public function test_cascade_deletes_maintain_tenant_integrity(): void
    {
        // 1. Deleting a category cascades to its products
        $catToDelete = Category::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Temporary Category',
        ]);
        $prodToDelete = Product::create([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $catToDelete->id,
            'name' => 'Temporary Product',
            'price' => 500,
        ]);

        $catId = $catToDelete->id;
        $prodId = $prodToDelete->id;

        $catToDelete->forceDelete();
        $this->assertNull(Product::find($prodId));

        // 2. Deleting an order cascades to its items and payment attempts
        $orderToDelete = Order::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationA->id,
            'order_number' => 'ORD-CASCADE-001',
            'total_amount' => 1500,
            'status' => 'pending',
        ]);

        $item = OrderItem::create([
            'order_id' => $orderToDelete->id,
            'product_id' => $this->productA->id,
            'product_name' => 'Soup Alpha',
            'unit_price' => 1500,
            'quantity' => 1,
            'subtotal' => 1500,
        ]);

        $attempt = PaymentAttempt::create([
            'vendor_id' => $this->vendorA->id,
            'order_id' => $orderToDelete->id,
            'gateway' => 'idram',
            'merchant_reference' => 'MERCH-CASCADE-001',
            'amount' => 1500,
            'currency' => 'AMD',
        ]);

        $orderId = $orderToDelete->id;
        $itemId = $item->id;
        $attemptId = $attempt->id;

        $orderToDelete->forceDelete();
        $this->assertNull(OrderItem::find($itemId));
        $this->assertNull(PaymentAttempt::find($attemptId));

        // 3. Deleting a location cascades to its overrides
        $override = LocationProductOverride::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $this->locationA->id,
            'product_id' => $this->productA->id,
            'override_price' => 1200,
        ]);
        $overrideId = $override->id;
        $this->locationA->delete();
        $this->assertNull(LocationProductOverride::find($overrideId));
    }

    public function test_platform_level_relationships_are_preserved(): void
    {
        // 1. Shared Allergens can be attached across any vendor
        $allergen = Allergen::create([
            'code' => 'nuts',
            'name' => 'Nuts',
        ]);

        DB::table('product_allergens')->insert([
            'product_id' => $this->productA->id,
            'allergen_id' => $allergen->id,
        ]);

        DB::table('product_allergens')->insert([
            'product_id' => $this->productB->id,
            'allergen_id' => $allergen->id,
        ]);

        $this->assertDatabaseHas('product_allergens', [
            'product_id' => $this->productA->id,
            'allergen_id' => $allergen->id,
        ]);
        $this->assertDatabaseHas('product_allergens', [
            'product_id' => $this->productB->id,
            'allergen_id' => $allergen->id,
        ]);

        // 2. Shared Subscription Plans can be referenced across vendors
        $plan = SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'price' => 15000,
            'currency' => 'AMD',
            'interval' => 'monthly',
            'is_active' => true,
        ]);

        $subPaymentA = SubscriptionPayment::create([
            'vendor_id' => $this->vendorA->id,
            'subscription_plan_id' => $plan->id,
            'amount' => 15000,
            'currency' => 'AMD',
            'status' => 'paid',
        ]);

        $subPaymentB = SubscriptionPayment::create([
            'vendor_id' => $this->vendorB->id,
            'subscription_plan_id' => $plan->id,
            'amount' => 15000,
            'currency' => 'AMD',
            'status' => 'paid',
        ]);

        $this->assertEquals($plan->id, $subPaymentA->subscription_plan_id);
        $this->assertEquals($plan->id, $subPaymentB->subscription_plan_id);

        // 3. SuperAdmin user without vendor_id operates without constraint issues
        $superAdmin = User::create([
            'name' => 'Root Admin',
            'email' => 'root@platform.internal',
            'password' => bcrypt('password123'),
            'role' => 'superadmin',
            'vendor_id' => null,
            'location_id' => null,
        ]);

        $this->assertNull($superAdmin->vendor_id);
        $this->assertNull($superAdmin->location_id);
        $this->assertTrue($superAdmin->exists);
    }

    public function test_tenant_first_composite_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasIndex('categories', 'idx_cat_vendor_active_sort'));
        $this->assertTrue(Schema::hasIndex('categories', 'idx_cat_vendor_deleted'));
        $this->assertTrue(Schema::hasIndex('products', 'idx_prod_vendor_cat_avail_sort'));
        $this->assertTrue(Schema::hasIndex('products', 'idx_prod_vendor_available'));
        $this->assertTrue(Schema::hasIndex('products', 'idx_prod_vendor_featured'));
        $this->assertTrue(Schema::hasIndex('products', 'idx_prod_vendor_deleted'));
        $this->assertTrue(Schema::hasIndex('orders', 'idx_orders_vendor_status_created'));
        $this->assertTrue(Schema::hasIndex('orders', 'idx_orders_vendor_loc_status'));
        $this->assertTrue(Schema::hasIndex('orders', 'idx_orders_vendor_type'));
        $this->assertTrue(Schema::hasIndex('orders', 'idx_orders_vendor_deleted'));
        $this->assertTrue(Schema::hasIndex('waiter_calls', 'idx_waiter_calls_vendor_status_created'));
        $this->assertTrue(Schema::hasIndex('waiter_calls', 'idx_waiter_calls_vendor_loc_status'));
        $this->assertTrue(Schema::hasIndex('waiter_calls', 'idx_waiter_calls_vendor_table_status'));
        $this->assertTrue(Schema::hasIndex('customers', 'idx_customers_vendor_phone'));
        $this->assertTrue(Schema::hasIndex('customers', 'idx_customers_vendor_email'));
        $this->assertTrue(Schema::hasIndex('ai_waiter_sessions', 'idx_ai_sessions_vendor_status'));
        $this->assertTrue(Schema::hasIndex('ai_waiter_sessions', 'idx_ai_sessions_vendor_loc_status'));
        $this->assertTrue(Schema::hasIndex('payment_attempts', 'idx_payment_attempts_vendor_status'));
        $this->assertTrue(Schema::hasIndex('payment_attempts', 'idx_payment_attempts_vendor_order'));
        $this->assertTrue(Schema::hasIndex('payment_attempts', 'idx_payment_attempts_vendor_ref'));
        $this->assertTrue(Schema::hasIndex('vendor_storage_files', 'idx_storage_files_vendor_status'));
        $this->assertTrue(Schema::hasIndex('vendor_storage_files', 'idx_storage_files_vendor_deleted'));
    }
}
