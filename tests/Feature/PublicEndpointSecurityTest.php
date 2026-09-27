<?php

namespace Tests\Feature;

use App\Jobs\RecordAnalyticsVisitJob;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Vendor;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublicEndpointSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected Location $location;

    protected Category $category;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        Cache::flush();

        $plan = SubscriptionPlan::where('slug', 'pro')->first();

        $this->vendor = Vendor::create([
            'name' => 'Security Bistro',
            'slug' => 'security-bistro',
            'email' => 'security@bistro.am',
            'password' => bcrypt('secret123'),
            'subscription_plan' => 'pro',
            'subscription_plan_id' => $plan->id,
            'subscription_expires_at' => now()->addDays(30),
            'is_active' => true,
            'currency' => 'AMD',
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Downtown Hall',
            'slug' => 'downtown',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Signature Dishes',
            'slug' => 'signature-dishes',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Premium Steak',
            'price' => 7500,
            'is_available' => true,
        ]);
    }

    /**
     * Test that all public HTTP responses include hardening security headers.
     */
    public function test_public_endpoints_include_security_headers(): void
    {
        $response = $this->get('/m/'.$this->vendor->slug);

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    /**
     * Test order submission returns opaque cryptographic tracking token and omits database IDs.
     */
    public function test_order_submission_generates_cryptographic_tracking_token_and_omits_internal_ids(): void
    {
        $payload = [
            'type' => 'dine_in',
            'table_number' => '12',
            'location_id' => $this->location->id,
            'payment_method' => 'cash',
            'customer_name' => 'John Doe',
            'customer_phone' => '+37491123456',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 7500,
                ],
            ],
        ];

        $response = $this->postJson('/api/m/'.$this->vendor->slug.'/order', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['tracking_token']);
        $this->assertStringStartsWith('trk_', $data['tracking_token']);

        // Assert tracking_token in response is a secure opaque token
        $this->assertNotEmpty($data['tracking_token']);
        $this->assertNotEmpty($data['order_id']);

        // Assert items do not expose internal database IDs
        $this->assertNotEmpty($data['items']);
        $this->assertEquals('item_1', $data['items'][0]['id']);

        // Assert tracking token was saved to user session
        $this->assertEquals($data['tracking_token'], session('placed_order_'.$data['order_number']));
    }

    /**
     * Test that order status is accessible directly using the opaque tracking token.
     */
    public function test_order_status_accessible_directly_via_opaque_tracking_token(): void
    {
        $order = Order::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'order_number' => 'ORD-TEST101',
            'table_number' => '5',
            'type' => 'dine_in',
            'status' => 'preparing',
            'total_amount' => 15000,
            'customer_name' => 'Alice Secret',
            'customer_phone' => '+37499887766',
            'customer_email' => 'alice@secret.com',
            'notes' => 'Private allergic note: no peanuts',
        ]);

        $this->assertNotEmpty($order->tracking_token);

        // Fetch using the opaque tracking token
        $response = $this->getJson('/api/m/'.$this->vendor->slug.'/order/'.$order->tracking_token.'/status');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'order_number' => 'ORD-TEST101',
                'status' => 'preparing',
                'table_number' => '5',
            ],
        ]);

        $orderData = $response->json('order');

        // Check that order ID in response is the tracking token and NOT database integer id
        $this->assertEquals($order->tracking_token, $orderData['id']);
        $this->assertNotEquals($order->id, $orderData['id']);

        // Assert sensitive customer PII and vendor internals are NOT leaked in public status
        $this->assertArrayNotHasKey('customer_phone', $orderData);
        $this->assertArrayNotHasKey('customer_email', $orderData);
        $this->assertArrayNotHasKey('notes', $orderData);
        $this->assertArrayNotHasKey('vendor_id', $orderData);
    }

    /**
     * Test that querying by predictable order_number without a valid token is blocked (prevents ID enumeration).
     */
    public function test_order_status_prevents_sequential_id_enumeration_without_tracking_token(): void
    {
        $order = Order::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'order_number' => 'ORD-ENUM999',
            'table_number' => '7',
            'type' => 'dine_in',
            'status' => 'accepted',
            'total_amount' => 5000,
        ]);

        // Attempt direct access using predictable order_number without proof of possession
        $response = $this->getJson('/api/m/'.$this->vendor->slug.'/order/ORD-ENUM999/status');

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Մուտքն արգելված է։ Պահանջվում է վավեր հետևման թոքեն (Tracking token)։',
        ]);
    }

    /**
     * Test that order_number is permitted when accompanied by valid token (via query, header, or session).
     */
    public function test_order_status_allows_order_number_with_valid_tracking_token_proof(): void
    {
        $order = Order::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'order_number' => 'ORD-AUTH001',
            'table_number' => '3',
            'type' => 'dine_in',
            'status' => 'ready',
            'total_amount' => 8000,
        ]);

        // 1. Query parameter ?token=...
        $response1 = $this->getJson('/api/m/'.$this->vendor->slug.'/order/ORD-AUTH001/status?token='.$order->tracking_token);
        $response1->assertStatus(200);
        $response1->assertJsonPath('order.status', 'ready');

        // 2. HTTP Header X-Tracking-Token
        $response2 = $this->withHeaders(['X-Tracking-Token' => $order->tracking_token])
            ->getJson('/api/m/'.$this->vendor->slug.'/order/ORD-AUTH001/status');
        $response2->assertStatus(200);
        $response2->assertJsonPath('order.status', 'ready');

        // 3. Browser Session authorization
        $response3 = $this->withSession(['placed_order_ORD-AUTH001' => $order->tracking_token])
            ->getJson('/api/m/'.$this->vendor->slug.'/order/ORD-AUTH001/status');
        $response3->assertStatus(200);
        $response3->assertJsonPath('order.status', 'ready');
    }

    /**
     * Test that repeated failed order lookups trigger an automated IP lockout (anti-brute-force defense).
     */
    public function test_order_status_enforces_ip_lockout_after_multiple_failed_enumeration_attempts(): void
    {
        $clientIp = '198.51.100.42';

        // Perform 5 failed guesses (either non-existent order or forbidden token attempt)
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
                ->getJson('/api/m/'.$this->vendor->slug.'/order/ORD-GUESS-'.$i.'/status');

            $this->assertContains($response->status(), [403, 404]);
        }

        // 6th attempt from the same IP must be locked out with 429 Too Many Requests
        $lockedOutResponse = $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
            ->getJson('/api/m/'.$this->vendor->slug.'/order/ORD-GUESS-6/status');

        $lockedOutResponse->assertStatus(429);
        $lockedOutResponse->assertHeader('Retry-After');
        $lockedOutResponse->assertJson([
            'success' => false,
            'message' => 'Չափազանց շատ անհաջող փորձեր։ Մուտքն արգելափակված է։',
        ]);
    }

    /**
     * Test waiter call returns opaque cryptographic token and omits internal integer IDs.
     */
    public function test_waiter_call_generates_opaque_call_token_and_omits_database_ids(): void
    {
        $response = $this->postJson('/api/m/'.$this->vendor->slug.'/call-waiter', [
            'table_number' => 'Table 8',
            'type' => 'call_waiter',
            'notes' => 'Please bring napkins',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $callData = $response->json('call');
        $this->assertNotEmpty($callData['call_token']);
        $this->assertStringStartsWith('wcl_', $callData['call_token']);

        // Assert id in response is opaque call_token, not raw integer primary key
        $this->assertEquals($callData['call_token'], $callData['id']);
        $this->assertArrayNotHasKey('vendor_id', $callData);
    }

    /**
     * Test waiter call prevents automated rapid spam on the same table.
     */
    public function test_waiter_call_prevents_automated_spam_on_same_table(): void
    {
        // First call succeeds
        $firstCall = $this->postJson('/api/m/'.$this->vendor->slug.'/call-waiter', [
            'table_number' => 'Table 4',
            'type' => 'call_waiter',
        ]);
        $firstCall->assertStatus(200);

        // Immediate duplicate call for same table is rejected with 429
        $secondCall = $this->postJson('/api/m/'.$this->vendor->slug.'/call-waiter', [
            'table_number' => 'Table 4',
            'type' => 'call_waiter',
        ]);
        $secondCall->assertStatus(429);
        $secondCall->assertJson([
            'success' => false,
            'message' => 'Խնդրում ենք սպասել, Ձեր նախորդ կանչն արդեն փոխանցվել է մատուցողին։',
        ]);
        $secondCall->assertHeader('Retry-After');
    }

    /**
     * Test waiter call limits IP flooding across tables.
     */
    public function test_waiter_call_prevents_ip_flooding(): void
    {
        $clientIp = '203.0.113.88';

        // 15 calls across different tables from same IP
        for ($i = 1; $i <= 15; $i++) {
            $res = $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
                ->postJson('/api/m/'.$this->vendor->slug.'/call-waiter', [
                    'table_number' => 'Table '.$i,
                    'type' => 'call_waiter',
                ]);
            $res->assertStatus(200);
        }

        // 16th call from same IP is throttled with 429
        $sixteenthCall = $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
            ->postJson('/api/m/'.$this->vendor->slug.'/call-waiter', [
                'table_number' => 'Table 16',
                'type' => 'call_waiter',
            ]);

        $sixteenthCall->assertStatus(429);
    }

    /**
     * Test payment callback rejects raw numeric order ID tampering.
     */
    public function test_payment_callback_rejects_numeric_order_id_tampering(): void
    {
        $response = $this->get('/payment/callback/'.$this->vendor->slug.'/12345');

        $response->assertRedirect('/m/'.$this->vendor->slug);
        $response->assertSessionHas('error', 'Վճարման գրառումը չի գտնվել։');
    }

    /**
     * Test analytics visits are deduplicated per IP/hour to prevent metric flooding.
     */
    public function test_analytics_visits_are_deduplicated_per_ip(): void
    {
        Queue::fake();

        $clientIp = '198.51.100.99';

        // First visit
        $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
            ->get('/m/'.$this->vendor->slug);

        // Second visit immediately following
        $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
            ->get('/m/'.$this->vendor->slug);

        // Only 1 job should have been dispatched due to hourly IP deduplication
        Queue::assertPushed(RecordAnalyticsVisitJob::class, 1);
    }
}
