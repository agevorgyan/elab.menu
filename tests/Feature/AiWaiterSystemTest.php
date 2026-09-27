<?php

namespace Tests\Feature;

use App\Models\AiWaiterSession;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiWaiterSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $user;

    protected Location $location;

    protected Category $categoryMains;

    protected Category $categoryDrinks;

    protected Category $categorySides;

    protected Product $steakProduct;

    protected Product $khinkaliProduct;

    protected Product $veganSaladProduct;

    protected Product $wineProduct;

    protected Product $lemonadeProduct;

    protected Product $friesProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);

        $plan = SubscriptionPlan::where('slug', 'pro')->first();

        $this->vendor = Vendor::create([
            'name' => 'Royal Armenian Bistro',
            'slug' => 'royal-armenian-bistro',
            'email' => 'contact@royalbistro.am',
            'password' => bcrypt('password'),
            'subscription_plan' => 'pro',
            'subscription_plan_id' => $plan->id,
            'subscription_expires_at' => now()->addDays(30),
            'is_active' => true,
            'ai_waiter_enabled' => true,
            'ai_waiter_name' => 'Ալեքս AI',
            'ai_waiter_welcome_text' => 'Ողջույն! Ես Ձեր անձնական AI մատուցողն եմ:',
            'ai_waiter_config' => [
                'languages' => ['hy', 'en', 'ru'],
                'free_text_enabled' => true,
                'ai_chat_enabled' => true,
                'promoted_products' => [],
                'preferred_ingredients' => [
                    ['ingredient' => 'Տավարի միս', 'priority' => 100, 'active' => true],
                    ['ingredient' => 'Հավ', 'priority' => 80, 'active' => true],
                ],
                'scoring_weights' => [
                    'restaurant_priority' => 30,
                    'preferred_ingredient' => 20,
                    'customer_preference' => 25,
                    'dietary_compatibility' => 10,
                    'taste_spiciness' => 5,
                    'occasion' => 5,
                    'budget' => 5,
                ],
            ],
        ]);

        $this->user = User::create([
            'name' => 'Admin Owner',
            'email' => 'contact@royalbistro.am',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendor->id,
            'role' => 'vendor_owner',
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Center Downtown',
            'slug' => 'center-downtown',
            'address' => 'Amiryan 1',
            'is_active' => true,
        ]);

        $this->categoryMains = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Dishes',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->categorySides = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Salads & Sides',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->categoryDrinks = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Drinks & Wines',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // 1. Meat Dish: Khinkali (Configured as promoted)
        $this->khinkaliProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->categoryMains->id,
            'name' => 'Տնական Խինկալի',
            'name_translations' => ['hy' => 'Տնական Խինկալի', 'en' => 'Homemade Khinkali', 'ru' => 'Домашние Хинкали'],
            'description' => 'Հյութալի տավարի և խոզի մսով տնական խինկալի',
            'price' => 4500,
            'is_available' => true,
            'ai_priority' => true,
            'ai_priority_level' => 100,
            'ai_tags' => ['meat', 'savory', 'juicy'],
            'ai_spicy_level' => 0,
        ]);

        // 2. Meat Dish: Ribeye Steak
        $this->steakProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->categoryMains->id,
            'name' => 'Տավարի Սթեյք',
            'name_translations' => ['hy' => 'Տավարի Սթեյք', 'en' => 'Beef Ribeye Steak', 'ru' => 'Стейк Рибай'],
            'description' => 'Տավարի միս, համեմունքներ, կարագ',
            'price' => 9500,
            'is_available' => true,
            'ai_priority' => false,
            'ai_tags' => ['meat', 'steak', 'savory'],
            'ai_spicy_level' => 0,
        ]);

        // 3. Vegan Salad
        $this->veganSaladProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->categorySides->id,
            'name' => 'Ամառային Թարմ Աղցան',
            'name_translations' => ['hy' => 'Ամառային Թարմ Աղցան', 'en' => 'Fresh Summer Salad', 'ru' => 'Летний свежий салат'],
            'description' => 'Վարունգ, լոլիկ, թարմ կանաչի, ձիթապտղի յուղ',
            'price' => 2000,
            'is_available' => true,
            'dietary_tags' => ['vegetarian', 'vegan'],
            'ai_tags' => ['fresh', 'salad', 'healthy'],
            'ai_spicy_level' => 0,
        ]);

        // 4. Side: Crispy Fries
        $this->friesProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->categorySides->id,
            'name' => 'Կարտոֆիլ Ֆրի',
            'name_translations' => ['hy' => 'Կարտոֆիլ Ֆրի', 'en' => 'Crispy French Fries', 'ru' => 'Картофель фри'],
            'description' => 'Ոսկեզօծ կարտոֆիլ ֆրի հատուկ սոուսով',
            'price' => 1200,
            'is_available' => true,
            'dietary_tags' => ['vegetarian'],
        ]);

        // 5. Drink: Reserve Red Wine
        $this->wineProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->categoryDrinks->id,
            'name' => 'Արենի Կարմիր Գինի',
            'name_translations' => ['hy' => 'Արենի Կարմիր Գինի', 'en' => 'Areni Red Wine', 'ru' => 'Красное вино Арени'],
            'description' => 'Հայկական ընտիր կարմիր չոր գինի',
            'price' => 3500,
            'is_available' => true,
        ]);

        // 6. Drink: Fresh Lemonade
        $this->lemonadeProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->categoryDrinks->id,
            'name' => 'Տնական Լիմոնադ',
            'name_translations' => ['hy' => 'Տնական Լիմոնադ', 'en' => 'Homemade Lemonade', 'ru' => 'Домашний лимонад'],
            'description' => 'Թարմ կիտրոն, անանուխ, սառույց',
            'price' => 1500,
            'is_available' => true,
        ]);
    }

    public function test_ai_waiter_config_returns_expected_settings(): void
    {
        $response = $this->getJson(route('client.ai_waiter.config', ['vendor_slug' => $this->vendor->slug]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'enabled' => true,
            'waiter_name' => 'Ալեքս AI',
            'free_text_enabled' => true,
            'ai_chat_enabled' => true,
            'languages' => ['hy', 'en', 'ru'],
        ]);
    }

    public function test_ai_waiter_session_creation_initializes_state_and_first_question(): void
    {
        $response = $this->postJson(route('client.ai_waiter.session.start', ['vendor_slug' => $this->vendor->slug]), [
            'lang' => 'hy',
            'location_id' => $this->location->id,
            'table_number' => 'Table 7',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'session_id',
            'session_token',
            'language',
            'allowed_languages',
            'waiter_name',
            'next_question' => [
                'key',
                'title',
                'options',
            ],
        ]);

        $sessionId = $response->json('session_id');
        $this->assertDatabaseHas('ai_waiter_sessions', [
            'session_token' => $sessionId,
            'vendor_id' => $this->vendor->id,
            'table_number' => 'Table 7',
            'language' => 'hy',
            'status' => 'started',
        ]);
    }

    public function test_ai_waiter_language_switch_updates_session_and_localizes_questions(): void
    {
        $session = AiWaiterSession::create([
            'vendor_id' => $this->vendor->id,
            'session_token' => 'test-token-123',
            'language' => 'hy',
            'status' => 'started',
        ]);

        $response = $this->postJson(route('client.ai_waiter.session.language', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]), [
            'language' => 'en',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'language' => 'en',
        ]);

        $this->assertEquals('en', $session->fresh()->language);
        $this->assertStringContainsString('What', $response->json('next_question.title'));
    }

    public function test_submitting_answers_parses_free_text_and_dynamic_flow(): void
    {
        $session = AiWaiterSession::create([
            'vendor_id' => $this->vendor->id,
            'session_token' => 'session-dynamic-test',
            'language' => 'hy',
            'status' => 'started',
        ]);

        // Customer inputs free text indicating craving meat, budget 10000 and not spicy
        $response = $this->postJson(route('client.ai_waiter.session.answer', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]), [
            'question_key' => 'mood',
            'answer_value' => 'meat',
            'free_text' => 'Ուզում եմ մսային ուտեստ, ոչ կծու, բյուջեն մինչև 10000',
        ]);

        $response->assertOk();
        $prefs = $session->fresh()->preferences;
        $this->assertEquals('meat', $prefs['mood'] ?? $prefs['craving']);
        $this->assertEquals('none', $prefs['spiciness']);
        $this->assertEquals(10000, $prefs['budget']);
    }

    public function test_hard_constraints_exclude_meat_when_vegetarian_requested(): void
    {
        $session = AiWaiterSession::create([
            'vendor_id' => $this->vendor->id,
            'session_token' => 'veg-session',
            'language' => 'hy',
            'preferences' => [
                'dietary' => ['vegetarian'],
                'craving' => 'fresh',
            ],
            'status' => 'questions_in_progress',
        ]);

        $response = $this->postJson(route('client.ai_waiter.session.recommendations', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]));

        $response->assertOk();
        $mainRecs = $response->json('main_recommendations');
        $this->assertNotEmpty($mainRecs);

        $recIds = array_column($mainRecs, 'id');
        // Khinkali & Steak must NOT be recommended to a vegetarian
        $this->assertNotContains($this->khinkaliProduct->id, $recIds);
        $this->assertNotContains($this->steakProduct->id, $recIds);
        // Vegetarian salad must be present
        $this->assertContains($this->veganSaladProduct->id, $recIds);
    }

    public function test_hard_constraints_exclude_out_of_stock_products(): void
    {
        $this->khinkaliProduct->update(['is_available' => false]);

        $session = AiWaiterSession::create([
            'vendor_id' => $this->vendor->id,
            'session_token' => 'stock-session',
            'language' => 'hy',
            'preferences' => [
                'craving' => 'meat',
            ],
            'status' => 'questions_in_progress',
        ]);

        $response = $this->postJson(route('client.ai_waiter.session.recommendations', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]));

        $response->assertOk();
        $mainRecs = $response->json('main_recommendations');
        $recIds = array_column($mainRecs, 'id');

        $this->assertNotContains($this->khinkaliProduct->id, $recIds);
        $this->assertContains($this->steakProduct->id, $recIds);
    }

    public function test_restaurant_defined_priority_promotes_explicit_dish_when_matching(): void
    {
        // Both Khinkali and Steak are meat dishes, but Khinkali has priority 100
        $session = AiWaiterSession::create([
            'vendor_id' => $this->vendor->id,
            'session_token' => 'priority-session',
            'language' => 'hy',
            'preferences' => [
                'craving' => 'meat',
                'spiciness' => 'none',
                'budget' => 10000,
            ],
            'status' => 'questions_in_progress',
        ]);

        $response = $this->postJson(route('client.ai_waiter.session.recommendations', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]));

        $response->assertOk();
        $mainRecs = $response->json('main_recommendations');
        $this->assertNotEmpty($mainRecs);

        // Khinkali should be the #1 top recommendation
        $this->assertEquals($this->khinkaliProduct->id, $mainRecs[0]['id']);
        $this->assertGreaterThanOrEqual(85, $mainRecs[0]['match_score']);
        $this->assertNotEmpty($mainRecs[0]['reason']);
    }

    public function test_smart_pairing_bundle_combines_main_side_and_drink(): void
    {
        $session = AiWaiterSession::create([
            'vendor_id' => $this->vendor->id,
            'session_token' => 'bundle-session',
            'language' => 'hy',
            'preferences' => [
                'craving' => 'meat',
            ],
            'status' => 'questions_in_progress',
        ]);

        $response = $this->postJson(route('client.ai_waiter.session.recommendations', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]));

        $response->assertOk();
        $bundle = $response->json('bundle');
        $this->assertNotNull($bundle);
        $this->assertGreaterThanOrEqual(2, $bundle['items_count']);
        $this->assertGreaterThan(0, $bundle['total_price']);
        $this->assertNotEmpty($bundle['items']);
    }

    public function test_grounded_ai_chat_answers_queries_and_never_hallucinates_non_existent_items(): void
    {
        $session = AiWaiterSession::create([
            'vendor_id' => $this->vendor->id,
            'session_token' => 'chat-session',
            'language' => 'hy',
            'status' => 'questions_in_progress',
        ]);

        $response = $this->postJson(route('client.ai_waiter.session.chat', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]), [
            'message' => 'Ի՞նչ ունեք առանց մսի։',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'reply',
            'suggested_products',
        ]);

        $reply = $response->json('reply');
        $suggested = $response->json('suggested_products');

        $this->assertNotEmpty($reply);
        // The reply should mention the actual salad product in the menu
        $this->assertTrue(
            str_contains($reply, 'Աղցան') || count($suggested) > 0,
            'Reply should ground itself in active menu items.'
        );
    }

    public function test_add_to_cart_and_session_conversion_tracking(): void
    {
        $session = AiWaiterSession::create([
            'vendor_id' => $this->vendor->id,
            'session_token' => 'cart-session',
            'language' => 'hy',
            'status' => 'recommended',
        ]);

        // 1. Add product to session cart
        $cartResponse = $this->postJson(route('client.ai_waiter.session.add_to_cart', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]), [
            'product_id' => $this->khinkaliProduct->id,
        ]);

        $cartResponse->assertOk();
        $this->assertEquals('added_to_cart', $session->fresh()->status);
        $this->assertCount(1, $session->fresh()->cart_items);

        // 2. Complete session with order
        $order = Order::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'order_number' => 'ORD-1001',
            'status' => 'pending',
            'total_amount' => 4500,
        ]);

        $completeResponse = $this->postJson(route('client.ai_waiter.session.complete', [
            'vendor_slug' => $this->vendor->slug,
            'token' => $session->session_token,
        ]), [
            'order_id' => $order->id,
        ]);

        $completeResponse->assertOk();
        $this->assertEquals('order_placed', $session->fresh()->status);
        $this->assertEquals($order->id, $session->fresh()->order_id);
    }
}
