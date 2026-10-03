<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlinePaymentsAndFloorPlanTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $user;

    protected Location $location;

    protected Category $category;

    protected Product $product;

    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);

        $this->plan = SubscriptionPlan::where('slug', 'pro')->first();

        $this->vendor = Vendor::create([
            'name' => 'Armenian Gourmet Bistro',
            'slug' => 'armenian-gourmet',
            'email' => 'info@armeniangourmet.am',
            'password' => bcrypt('password123'),
            'subscription_plan' => 'pro',
            'subscription_plan_id' => $this->plan->id,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addDays(15),
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Bistro Owner',
            'email' => 'info@armeniangourmet.am',
            'password' => bcrypt('password123'),
            'vendor_id' => $this->vendor->id,
            'role' => 'vendor_owner',
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Branch Yerevan',
            'slug' => 'main-branch-yerevan',
            'address' => 'Northern Ave 10',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'name' => 'Appetizers',
            'slug' => 'appetizers',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Dolma Traditional',
            'slug' => 'dolma-traditional',
            'price' => 2500,
            'is_active' => true,
        ]);
    }

    public function test_vendor_can_renew_subscription_online_with_discount_and_invoice(): void
    {
        $oldExpiresAt = $this->vendor->subscription_expires_at;

        $response = $this->actingAs($this->user)
            ->from(route('admin.subscription'))
            ->post(route('admin.subscription.renew'), [
                'subscription_plan_id' => $this->plan->id,
                'period_months' => 6,
                'payment_method' => 'arca',
            ]);

        $response->assertRedirect();

        // Subscription payment and attempt are pending before verification
        $this->assertDatabaseHas('subscription_payments', [
            'vendor_id' => $this->vendor->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'pending',
            'payment_method' => 'arca',
        ]);

        $this->assertDatabaseHas('payment_attempts', [
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
            'gateway' => 'arca',
        ]);

        // Complete payment via ArCa webhook
        $attempt = PaymentAttempt::where('vendor_id', $this->vendor->id)->latest()->first();
        $webhookRes = $this->post(route('api.webhooks.payment', ['gateway' => 'arca']), [
            'orderNumber' => $attempt->merchant_reference,
            'orderId' => 'ARCA-TXN-123456',
            'status' => 2,
            'amount' => ((int) round($attempt->amount)) * 100,
        ]);
        $webhookRes->assertStatus(200);

        $this->vendor->refresh();
        $this->assertTrue($this->vendor->subscription_expires_at->gt($oldExpiresAt));
        $this->assertEquals('active', $this->vendor->subscription_status);

        $this->assertDatabaseHas('subscription_payments', [
            'vendor_id' => $this->vendor->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'completed',
            'payment_method' => 'arca',
        ]);
    }

    public function test_vendor_can_update_payment_crm_and_printer_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.update'), [
                'name' => $this->vendor->name,
                'email' => $this->vendor->email,
                'phone' => '099123456',
                'currency' => 'AMD',
                'currency_symbol' => '֏',
                'service_fee_enabled' => false,
                'service_fee_type' => 'percent',
                'service_fee_value' => 10,
                'delivery_enabled' => true,
                'delivery_fee' => 500,
                'delivery_min_amount' => 3000,
                'featured_dish_enabled' => false,
                'ai_waiter_enabled' => false,
                'allow_whatsapp_orders' => true,
                'payment_settings' => [
                    'online_enabled' => true,
                    'cash_enabled' => true,
                    'pos_terminal_enabled' => true,
                    'gateways' => [
                        'idram' => [
                            'enabled' => true,
                            'merchant_id' => '123456789',
                            'secret_key' => 'idram-secret',
                        ],
                        'stripe' => [
                            'enabled' => true,
                            'public_key' => 'pk_test_sample',
                            'secret_key' => 'sk_test_sample',
                        ],
                    ],
                ],
                'crm_settings' => [
                    'birthday_discount_enabled' => true,
                    'birthday_discount_percent' => 15,
                    'birthday_validity_days' => 3,
                    'sms_provider' => 'log',
                ],
                'thermal_printer_settings' => [
                    'auto_print_live_orders' => true,
                    'paper_width' => '80mm',
                    'header_title' => 'Bari Axorjak!',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $this->vendor->refresh();
        $this->assertTrue($this->vendor->isPaymentMethodEnabled('idram'));
        $this->assertTrue($this->vendor->isPaymentMethodEnabled('stripe'));
        $this->assertTrue($this->vendor->hasOnlinePaymentsEnabled());

        $crm = $this->vendor->getCrmSettings();
        $this->assertTrue($crm['birthday_discount_enabled']);
        $this->assertEquals(15, $crm['birthday_discount_percent']);

        $printer = $this->vendor->getThermalPrinterSettings();
        $this->assertTrue($printer['auto_print_live_orders']);
        $this->assertEquals('80mm', $printer['paper_width']);
    }

    public function test_storefront_order_with_online_payment_returns_redirect_and_processes_callback(): void
    {
        $this->vendor->update([
            'payment_settings' => [
                'online_enabled' => true,
                'gateways' => [
                    'idram' => ['enabled' => true, 'merchant_id' => '123456'],
                ],
            ],
        ]);

        $orderPayload = [
            'type' => 'dine_in',
            'location_id' => $this->location->id,
            'table_number' => '1',
            'customer_name' => 'Arman Babayan',
            'customer_phone' => '091001122',
            'payment_method' => 'idram',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->postJson("/api/m/{$this->vendor->slug}/order", $orderPayload);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'order_id',
            'payment_method',
            'payment_redirect_url',
        ]);

        $orderId = $response->json('order_id');
        $this->assertNotNull($orderId);

        $order = Order::find($orderId);
        $this->assertNotNull($order);
        $this->assertEquals('idram', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status);

        // Process payment callback: client browser callback is untrusted, so order remains pending
        $callbackResponse = $this->get("/payment/callback/{$this->vendor->slug}/{$order->id}?status=success&trx=TRX-998877");
        $callbackResponse->assertRedirect();

        $order->refresh();
        $this->assertEquals('pending', $order->payment_status);

        // Server-to-server webhook confirmation from Idram
        $attempt = $order->latestPaymentAttempt;
        $recAccount = $attempt->request_payload['EDP_REC_ACCOUNT'] ?? '100000000';
        $settings = $this->vendor->getPaymentSettings()['gateways']['idram'] ?? [];
        $secretKey = $settings['secret_key'] ?? '';
        $formattedAmount = number_format((float) $attempt->amount, 2, '.', '');
        $checksum = strtoupper(md5("{$recAccount}:{$formattedAmount}:{$secretKey}:{$attempt->merchant_reference}:TRX-998877"));

        $webhookRes = $this->post(route('api.webhooks.payment', ['gateway' => 'idram']), [
            'EDP_BILL_NO' => $attempt->merchant_reference,
            'EDP_REC_ACCOUNT' => $recAccount,
            'EDP_AMOUNT' => $formattedAmount,
            'EDP_TRANS_ID' => 'TRX-998877',
            'EDP_CHECKSUM' => $checksum,
        ]);
        $webhookRes->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('TRX-998877', $order->payment_transaction_id);
    }

    public function test_customer_birthday_discount_applied_on_order(): void
    {
        $this->vendor->update([
            'crm_settings' => [
                'birthday_discount_enabled' => true,
                'birthday_discount_percent' => 20,
                'birthday_validity_days' => 3,
            ],
        ]);

        // Create customer with birthday today
        Customer::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'name' => 'Birthday Customer',
            'phone' => '098765432',
            'birthdate' => now()->format('1992-m-d'),
        ]);

        $orderPayload = [
            'type' => 'dine_in',
            'location_id' => $this->location->id,
            'table_number' => '2',
            'customer_name' => 'Birthday Customer',
            'customer_phone' => '098765432',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2, // 2 * 2500 = 5000 AMD
                ],
            ],
        ];

        $response = $this->postJson("/api/m/{$this->vendor->slug}/order", $orderPayload);
        $response->assertStatus(200);

        $orderId = $response->json('order_id');
        $order = Order::find($orderId);

        $this->assertNotNull($order);
        $this->assertGreaterThan(0, $order->birthday_discount_amount);
        // 20% of 5000 is 1000
        $this->assertEquals(1000, $order->birthday_discount_amount);
        $this->assertEquals(4000, $order->total_amount);
    }

    public function test_thermal_printer_receipt_text_endpoint(): void
    {
        $order = Order::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'table_number' => '1',
            'order_number' => 'ORD-1001',
            'type' => 'dine_in',
            'status' => 'pending',
            'payment_method' => 'pos_terminal',
            'payment_status' => 'pending',
            'subtotal' => 2500,
            'total_amount' => 2500,
            'customer_name' => 'Karen Sargsyan',
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'product_name' => 'Dolma Traditional',
            'unit_price' => 2500,
            'quantity' => 1,
            'subtotal' => 2500,
            'total' => 2500,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.orders.receipt_text', $order));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'order_id',
            'order_number',
            'rawbt_url',
            'receipt_text',
        ]);

        $receiptText = $response->json('receipt_text');
        $this->assertStringContainsString('ORD-1001', $receiptText);
        $this->assertStringContainsString('Dolma Traditional', $receiptText);
    }

    public function test_vendor_can_view_and_save_interactive_floor_plan(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.floor_plan.index'));
        $response->assertStatus(200);
        $response->assertSee(__('Interactive Table Floor Plan'));

        $saveResponse = $this->actingAs($this->user)->postJson(route('admin.floor_plan.save'), [
            'tables' => [
                [
                    'id' => 1,
                    'name' => 'Սեղան 1',
                    'number' => '1',
                    'x' => 140,
                    'y' => 220,
                    'hall' => 'Գլխավոր Սրահ',
                    'shape' => 'round',
                    'capacity' => 4,
                ],
            ],
            'halls' => ['Գլխավոր Սրահ', 'Տեռասա'],
            'settings' => [
                'grid_size' => 25,
            ],
        ]);

        $saveResponse->assertStatus(200);
        $saveResponse->assertJson(['status' => 'success']);

        $this->vendor->refresh();
        $floorPlan = $this->vendor->getFloorPlanData();
        $this->assertArrayHasKey('tables', $floorPlan);
        $this->assertEquals(140, $floorPlan['tables'][0]['x']);
        $this->assertEquals(220, $floorPlan['tables'][0]['y']);
    }

    public function test_vendor_can_send_birthday_sms_to_customer(): void
    {
        $customer = Customer::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'name' => 'Anna Hakobyan',
            'phone' => '093556677',
            'birthdate' => now()->format('1995-m-d'),
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.customers.birthday_sms', $customer));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
