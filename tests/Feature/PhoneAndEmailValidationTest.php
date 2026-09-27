<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneAndEmailValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected Location $location;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Validation Bistro',
            'slug' => 'validation-bistro',
            'email' => 'contact@validation-bistro.com',
            'password' => bcrypt('secret123'),
            'delivery_enabled' => true,
            'takeaway_enabled' => true,
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Center Branch',
            'slug' => 'center-branch',
            'address' => 'Amiryan 1',
        ]);

        $category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Grill',
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Pork Ribs',
            'price' => 4500,
        ]);
    }

    protected function submitOrderPayload(array $overrides = []): array
    {
        return array_merge([
            'location_id' => $this->location->id,
            'type' => 'takeaway',
            'customer_name' => 'Hakob Hakobyan',
            'customer_phone' => '+37494112233',
            'customer_email' => 'hakob@example.com',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], $overrides);
    }

    public function test_accepts_valid_armenian_phone_with_plus374_and_8_digits(): void
    {
        $payload = $this->submitOrderPayload([
            'customer_phone' => '+37494112233',
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_accepts_valid_armenian_local_phone_with_9_digits(): void
    {
        $payload = $this->submitOrderPayload([
            'customer_phone' => '094112233',
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_rejects_armenian_phone_with_extra_or_missing_digits(): void
    {
        // 7 digits after +374 (missing 1 digit)
        $payloadMissing = $this->submitOrderPayload([
            'customer_phone' => '+3749411223',
        ]);
        $resMissing = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payloadMissing);
        $resMissing->assertStatus(422);
        $resMissing->assertJsonValidationErrors(['customer_phone']);

        // 9 digits after +374 (1 extra digit)
        $payloadExtra = $this->submitOrderPayload([
            'customer_phone' => '+374941122334',
        ]);
        $resExtra = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payloadExtra);
        $resExtra->assertStatus(422);
        $resExtra->assertJsonValidationErrors(['customer_phone']);

        // Local Armenian with 8 digits instead of 9
        $payloadLocalShort = $this->submitOrderPayload([
            'customer_phone' => '09411223',
        ]);
        $resLocalShort = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payloadLocalShort);
        $resLocalShort->assertStatus(422);
        $resLocalShort->assertJsonValidationErrors(['customer_phone']);
    }

    public function test_accepts_valid_russian_phone_with_plus7_and_10_digits(): void
    {
        $payload = $this->submitOrderPayload([
            'customer_phone' => '+79991234567',
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_rejects_russian_phone_with_invalid_digit_count(): void
    {
        // 9 digits after +7 (missing 1 digit)
        $payload = $this->submitOrderPayload([
            'customer_phone' => '+7999123456',
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_phone']);
    }

    public function test_accepts_valid_email(): void
    {
        $payload = $this->submitOrderPayload([
            'customer_email' => 'valid.customer@gmail.com',
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_rejects_invalid_email_format(): void
    {
        $payload = $this->submitOrderPayload([
            'customer_email' => 'invalid-email-address',
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_email']);
    }

    public function test_accepts_nullable_or_empty_email_when_optional(): void
    {
        $payload = $this->submitOrderPayload([
            'customer_email' => null,
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
