<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_scope_isolates_queries_automatically(): void
    {
        $tenantContext = app(TenantContext::class);

        $vendorA = Vendor::create([
            'name' => 'Vendor Alpha',
            'slug' => 'vendor-alpha',
            'email' => 'alpha@vendor.com',
            'password' => bcrypt('password'),
        ]);

        $vendorB = Vendor::create([
            'name' => 'Vendor Beta',
            'slug' => 'vendor-beta',
            'email' => 'beta@vendor.com',
            'password' => bcrypt('password'),
        ]);

        // Create records for Vendor A
        $locA = Location::create(['vendor_id' => $vendorA->id, 'name' => 'Loc A', 'slug' => 'loc-a']);
        $catA = Category::create(['vendor_id' => $vendorA->id, 'name' => 'Alpha Cat', 'sort_order' => 1]);
        $prodA = Product::create(['vendor_id' => $vendorA->id, 'category_id' => $catA->id, 'name' => 'Alpha Dish', 'price' => 1000]);
        $custA = Customer::create(['vendor_id' => $vendorA->id, 'name' => 'Customer A', 'phone' => '+37491000001']);
        $ordA = Order::create(['vendor_id' => $vendorA->id, 'location_id' => $locA->id, 'order_number' => 'ORD-A-01', 'total_amount' => 1000, 'status' => 'pending']);

        // Create records for Vendor B
        $locB = Location::create(['vendor_id' => $vendorB->id, 'name' => 'Loc B', 'slug' => 'loc-b']);
        $catB = Category::create(['vendor_id' => $vendorB->id, 'name' => 'Beta Cat', 'sort_order' => 1]);
        $prodB = Product::create(['vendor_id' => $vendorB->id, 'category_id' => $catB->id, 'name' => 'Beta Dish', 'price' => 2000]);
        $custB = Customer::create(['vendor_id' => $vendorB->id, 'name' => 'Customer B', 'phone' => '+37491000002']);
        $ordB = Order::create(['vendor_id' => $vendorB->id, 'location_id' => $locB->id, 'order_number' => 'ORD-B-01', 'total_amount' => 2000, 'status' => 'pending']);

        // Activate Tenant A Context
        $tenantContext->setTenantId($vendorA->id);

        // Queries without any where('vendor_id') MUST only return Tenant A records
        $categories = Category::all();
        $this->assertCount(1, $categories);
        $this->assertEquals('Alpha Cat', $categories->first()->name);

        $products = Product::all();
        $this->assertCount(1, $products);
        $this->assertEquals('Alpha Dish', $products->first()->name);

        $locations = Location::all();
        $this->assertCount(1, $locations);
        $this->assertEquals('Loc A', $locations->first()->name);

        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals('Customer A', $customers->first()->name);

        $orders = Order::all();
        $this->assertCount(1, $orders);
        $this->assertEquals('ORD-A-01', $orders->first()->order_number);

        // Finding Tenant B's ID MUST return null under Tenant A's context
        $this->assertNull(Category::find($catB->id));
        $this->assertNull(Product::find($prodB->id));
        $this->assertNull(Location::find($locB->id));
        $this->assertNull(Customer::find($custB->id));
        $this->assertNull(Order::find($ordB->id));

        // Switch to Tenant B Context
        $tenantContext->setTenantId($vendorB->id);
        $this->assertCount(1, Category::all());
        $this->assertEquals('Beta Cat', Category::first()->name);
        $this->assertNull(Product::find($prodA->id));
        $this->assertNull(Location::find($locA->id));
        $this->assertNull(Customer::find($custA->id));
        $this->assertNull(Order::find($ordA->id));

        $tenantContext->clear();
    }

    public function test_model_creation_auto_populates_vendor_id(): void
    {
        $tenantContext = app(TenantContext::class);

        $vendor = Vendor::create([
            'name' => 'Auto Tenant',
            'slug' => 'auto-tenant',
            'email' => 'auto@tenant.com',
            'password' => bcrypt('password'),
        ]);

        $tenantContext->setTenantId($vendor->id);

        // Create models without explicitly passing 'vendor_id'
        $category = Category::create([
            'name' => 'Auto Injected Category',
            'sort_order' => 5,
        ]);

        $location = Location::create([
            'name' => 'Auto Branch',
            'slug' => 'auto-branch',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Auto Product',
            'price' => 1200,
        ]);

        $customer = Customer::create([
            'name' => 'Auto Customer',
            'phone' => '+37499112233',
        ]);

        $order = Order::create([
            'location_id' => $location->id,
            'order_number' => 'ORD-AUTO-01',
            'total_amount' => 1200,
            'status' => 'pending',
        ]);

        $this->assertEquals($vendor->id, $category->vendor_id);
        $this->assertEquals($vendor->id, $location->vendor_id);
        $this->assertEquals($vendor->id, $product->vendor_id);
        $this->assertEquals($vendor->id, $customer->vendor_id);
        $this->assertEquals($vendor->id, $order->vendor_id);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('locations', ['id' => $location->id, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'vendor_id' => $vendor->id]);

        $tenantContext->clear();
    }

    public function test_superadmin_bypasses_tenant_scope(): void
    {
        $vendorA = Vendor::create(['name' => 'Vendor 1', 'slug' => 'v1', 'email' => 'v1@test.com', 'password' => bcrypt('p')]);
        $vendorB = Vendor::create(['name' => 'Vendor 2', 'slug' => 'v2', 'email' => 'v2@test.com', 'password' => bcrypt('p')]);

        Category::create(['vendor_id' => $vendorA->id, 'name' => 'Cat 1', 'sort_order' => 1]);
        Category::create(['vendor_id' => $vendorB->id, 'name' => 'Cat 2', 'sort_order' => 2]);

        $superAdmin = User::create([
            'name' => 'Platform SuperAdmin',
            'email' => 'super@qrmenu.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        $this->actingAs($superAdmin);

        // SuperAdmin should see all categories across all vendors
        $this->assertEquals(2, Category::count());
    }

    public function test_without_tenant_scope_query_helpers(): void
    {
        $tenantContext = app(TenantContext::class);

        $vendorA = Vendor::create(['name' => 'Vendor 1', 'slug' => 'v1', 'email' => 'v1@test.com', 'password' => bcrypt('p')]);
        $vendorB = Vendor::create(['name' => 'Vendor 2', 'slug' => 'v2', 'email' => 'v2@test.com', 'password' => bcrypt('p')]);

        Category::create(['vendor_id' => $vendorA->id, 'name' => 'Cat 1', 'sort_order' => 1]);
        Category::create(['vendor_id' => $vendorB->id, 'name' => 'Cat 2', 'sort_order' => 2]);

        $tenantContext->setTenantId($vendorA->id);
        $this->assertEquals(1, Category::count());

        // Using scope query macro
        $this->assertEquals(2, Category::withoutTenant()->count());

        // Using bypass closure
        $allCount = $tenantContext->bypass(function () {
            return Category::count();
        });
        $this->assertEquals(2, $allCount);

        // After bypass, scope is restored
        $this->assertEquals(1, Category::count());

        $tenantContext->clear();
    }
}
