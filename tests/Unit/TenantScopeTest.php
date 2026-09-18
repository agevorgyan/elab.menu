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
        $catA = Category::create(['vendor_id' => $vendorA->id, 'name' => 'Alpha Cat', 'sort_order' => 1]);
        $prodA = Product::create(['vendor_id' => $vendorA->id, 'category_id' => $catA->id, 'name' => 'Alpha Dish', 'price' => 1000]);

        // Create records for Vendor B
        $catB = Category::create(['vendor_id' => $vendorB->id, 'name' => 'Beta Cat', 'sort_order' => 1]);
        $prodB = Product::create(['vendor_id' => $vendorB->id, 'category_id' => $catB->id, 'name' => 'Beta Dish', 'price' => 2000]);

        // Activate Tenant A Context
        $tenantContext->setTenantId($vendorA->id);

        // Queries without any where('vendor_id') MUST only return Tenant A records
        $categories = Category::all();
        $this->assertCount(1, $categories);
        $this->assertEquals('Alpha Cat', $categories->first()->name);

        $products = Product::all();
        $this->assertCount(1, $products);
        $this->assertEquals('Alpha Dish', $products->first()->name);

        // Finding Tenant B's ID MUST return null under Tenant A's context
        $this->assertNull(Category::find($catB->id));
        $this->assertNull(Product::find($prodB->id));

        // Switch to Tenant B Context
        $tenantContext->setTenantId($vendorB->id);
        $this->assertCount(1, Category::all());
        $this->assertEquals('Beta Cat', Category::first()->name);
        $this->assertNull(Product::find($prodA->id));

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

        // Create category without explicitly passing 'vendor_id'
        $category = Category::create([
            'name' => 'Auto Injected Category',
            'sort_order' => 5,
        ]);

        $this->assertEquals($vendor->id, $category->vendor_id);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'vendor_id' => $vendor->id,
            'name' => 'Auto Injected Category',
        ]);

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
