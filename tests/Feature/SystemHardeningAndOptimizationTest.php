<?php

namespace Tests\Feature;

use App\Actions\CreateOrderAction;
use App\DTOs\CreateOrderDTO;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MenuManagementService;
use App\Services\TenantCache;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SystemHardeningAndOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected Location $location;

    protected Category $category;

    protected Product $product;

    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        Cache::flush();

        $plan = SubscriptionPlan::where('slug', 'pro')->first();

        $this->vendor = Vendor::create([
            'name' => 'Hardened Bistro',
            'slug' => 'hardened-bistro',
            'email' => 'hardened@bistro.am',
            'password' => bcrypt('secret123'),
            'subscription_plan' => 'pro',
            'subscription_plan_id' => $plan->id,
            'subscription_expires_at' => now()->addDays(30),
            'is_active' => true,
            'currency' => 'AMD',
            'primary_color' => '#10b981',
            'accent_color' => '#f59e0b',
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Center Branch',
            'slug' => 'center',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Entrees',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Grilled Ribeye',
            'price' => 7500,
            'is_available' => true,
        ]);

        $this->staffUser = User::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Manager John',
            'email' => 'manager@bistro.am',
            'password' => bcrypt('password123'),
            'role' => 'manager',
        ]);
    }

    /**
     * Test 1: Storefront menu is cached and automatically invalidated when menu changes.
     */
    public function test_storefront_menu_is_cached_and_invalidated_on_menu_updates(): void
    {
        // First hit to storefront primes the tenant cache
        $res1 = $this->get('/m/'.$this->vendor->slug);
        $res1->assertStatus(200);
        $res1->assertSee('Grilled Ribeye');

        $initialVersion = (int) TenantCache::get($this->vendor, 'menu_version', 1);
        $cachedCategories = TenantCache::get($this->vendor, "storefront_menu:v{$initialVersion}:{$this->location->id}");
        $this->assertNotNull($cachedCategories, 'Storefront menu must be stored in TenantCache');

        // Create new category via MenuManagementService
        $menuService = app(MenuManagementService::class);
        $newCategory = $menuService->createCategory($this->vendor, ['name' => 'New Desserts']);

        // Verify version bumped and legacy menu key invalidated
        $bumpedVersion = (int) TenantCache::get($this->vendor, 'menu_version');
        $this->assertGreaterThan($initialVersion, $bumpedVersion);

        // Next hit fetches fresh data and caches under new version
        $res2 = $this->get('/m/'.$this->vendor->slug);
        $res2->assertStatus(200);
        $res2->assertSee('New Desserts');

        $newCached = TenantCache::get($this->vendor, "storefront_menu:v{$bumpedVersion}:{$this->location->id}");
        $this->assertNotNull($newCached);
    }

    /**
     * Test 2: Unauthenticated live preview parameters are guarded against tampering.
     */
    public function test_unauthenticated_live_preview_parameters_are_ignored(): void
    {
        // Public guest attempt to override theme colors & custom CSS
        $response = $this->get('/m/'.$this->vendor->slug.'?primary_color=%23ff0000&custom_css=body{display:none;}');
        $response->assertStatus(200);

        // Vendor model rendered in view retains original colors and does not have the attacker's CSS
        $response->assertDontSee('#ff0000');
        $response->assertDontSee('body{display:none;}');

        // Authenticated vendor staff DOES see the live preview override
        $staffResponse = $this->actingAs($this->staffUser, 'web')
            ->get('/m/'.$this->vendor->slug.'?primary_color=%23ff0000');
        $staffResponse->assertStatus(200);
        $staffResponse->assertSee('#ff0000');

        // Signed URL also permits the preview
        $signedUrl = URL::signedRoute('client.menu', [
            'vendor_slug' => $this->vendor->slug,
            'primary_color' => '#ff0000',
        ]);
        $signedResponse = $this->get($signedUrl);
        $signedResponse->assertStatus(200);
        $signedResponse->assertSee('#ff0000');
    }

    /**
     * Test 3: Order creation generates collision-resistant unique order numbers.
     */
    public function test_order_creation_generates_collision_resistant_order_number(): void
    {
        $action = app(CreateOrderAction::class);

        $dto = CreateOrderDTO::fromArray([
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'table_number' => 'Table 5',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
            'customer_name' => 'Alice',
            'customer_phone' => '+37499112233',
        ]);

        $result = $action->execute($this->vendor, $dto);
        $order = $result['order'];

        $this->assertNotNull($order->order_number);
        $this->assertMatchesRegularExpression('/^ORD-[A-Z0-9]{8}$/', $order->order_number);
        $this->assertEquals(1, Order::where('order_number', $order->order_number)->count());
    }

    /**
     * Test 4: Verify KDS composite indexes exist on orders and waiter_calls tables.
     */
    public function test_kds_composite_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasTable('orders'));
        $this->assertTrue(Schema::hasTable('waiter_calls'));

        // Verify index existence through DB query
        $orderIndexes = Schema::getIndexes('orders');
        $orderIndexNames = array_column($orderIndexes, 'name');
        $this->assertContains('idx_orders_kds_feed', $orderIndexNames);

        $waiterIndexes = Schema::getIndexes('waiter_calls');
        $waiterIndexNames = array_column($waiterIndexes, 'name');
        $this->assertContains('idx_waiter_calls_kds_feed', $waiterIndexNames);
    }
}
