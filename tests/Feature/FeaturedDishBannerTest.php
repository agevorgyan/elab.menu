<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedDishBannerTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $user;

    protected Product $product;

    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);

        $plan = SubscriptionPlan::where('slug', 'pro')->first();

        $this->vendor = Vendor::create([
            'name' => 'Banner Test Bistro',
            'slug' => 'banner-test-bistro',
            'email' => 'banner@bistro.am',
            'password' => bcrypt('password'),
            'subscription_plan' => 'pro',
            'subscription_plan_id' => $plan->id,
            'subscription_expires_at' => now()->addDays(30),
            'is_active' => true,
            'service_fee_enabled' => false,
            'delivery_enabled' => false,
            'service_fee_type' => 'percent',
            'service_fee_value' => 0,
            'delivery_fee' => 0,
            'delivery_min_amount' => 0,
        ]);

        $this->user = User::create([
            'name' => 'Owner',
            'email' => 'banner@bistro.am',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendor->id,
            'role' => 'vendor_owner',
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Branch',
            'slug' => 'main-branch',
            'address' => 'Tamanyan 1',
            'is_active' => true,
        ]);

        $category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Specialties',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Grilled Ribeye Special',
            'description' => 'Tender ribeye with rosemary butter',
            'price' => 11500,
            'is_available' => true,
        ]);
    }

    public function test_vendor_settings_page_displays_featured_dish_options(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Օրվա Ուտեստի Բաներ');
        $response->assertSee('Grilled Ribeye Special');
        $response->assertSee('featured_product_id');
    }

    public function test_vendor_can_enable_and_update_featured_dish(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.settings.update'), [
            'location_id' => $this->location->id,
            'name' => 'Banner Test Bistro',
            'service_fee_type' => 'percent',
            'service_fee_value' => 0,
            'delivery_fee' => 0,
            'delivery_min_amount' => 0,
            'featured_dish_enabled' => '1',
            'featured_product_id' => $this->product->id,
            'featured_dish_badge' => '⭐ ՇԵՖԻ ԸՆՏՐԱՆԻ',
            'featured_dish_subtitle' => 'Այսօրվա հատուկ համեղ առաջարկը',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->vendor->refresh();
        $this->assertTrue($this->vendor->featured_dish_enabled);
        $this->assertSame($this->product->id, $this->vendor->featured_product_id);
        $this->assertSame('⭐ ՇԵՖԻ ԸՆՏՐԱՆԻ', $this->vendor->featured_dish_badge);
        $this->assertSame('Այսօրվա հատուկ համեղ առաջարկը', $this->vendor->featured_dish_subtitle);
    }

    public function test_vendor_cannot_set_product_of_another_vendor(): void
    {
        $otherVendor = Vendor::create([
            'name' => 'Other Vendor',
            'slug' => 'other-vendor',
            'email' => 'other@vendor.am',
            'password' => bcrypt('password'),
            'is_active' => true,
            'service_fee_type' => 'percent',
            'service_fee_value' => 0,
            'delivery_fee' => 0,
            'delivery_min_amount' => 0,
        ]);

        $otherCategory = Category::create([
            'vendor_id' => $otherVendor->id,
            'name' => 'Other Category',
        ]);

        $otherProduct = Product::create([
            'vendor_id' => $otherVendor->id,
            'category_id' => $otherCategory->id,
            'name' => 'Secret Dish',
            'price' => 5000,
            'is_available' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.settings.update'), [
            'location_id' => $this->location->id,
            'name' => 'Banner Test Bistro',
            'service_fee_type' => 'percent',
            'service_fee_value' => 0,
            'delivery_fee' => 0,
            'delivery_min_amount' => 0,
            'featured_dish_enabled' => '1',
            'featured_product_id' => $otherProduct->id,
        ]);

        $this->vendor->refresh();
        $this->assertNull($this->vendor->featured_product_id);
    }

    public function test_storefront_menu_displays_featured_dish_banner_when_enabled(): void
    {
        $this->vendor->update([
            'featured_dish_enabled' => true,
            'featured_product_id' => $this->product->id,
            'featured_dish_badge' => '⭐ ՕՐՎԱ ՈՒՏԵՍՏԸ',
            'featured_dish_subtitle' => 'Համեղ սթեյք հատուկ գնով',
        ]);

        $response = $this->get(route('client.menu', ['vendor_slug' => $this->vendor->slug]));

        $response->assertStatus(200);
        $response->assertSee('featured-dish-banner-container');
        $response->assertSee('Grilled Ribeye Special');
        $response->assertSee('⭐ ՕՐՎԱ ՈՒՏԵՍՏԸ');
        $response->assertSee('Համեղ սթեյք հատուկ գնով');
        $response->assertSee('11,500');
    }

    public function test_storefront_menu_does_not_display_featured_dish_banner_when_disabled(): void
    {
        $this->vendor->update([
            'featured_dish_enabled' => false,
            'featured_product_id' => $this->product->id,
        ]);

        $response = $this->get(route('client.menu', ['vendor_slug' => $this->vendor->slug]));

        $response->assertStatus(200);
        $response->assertDontSee('featured-dish-banner-container');
    }
}
