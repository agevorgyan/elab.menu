<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomDomain;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CustomDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class CustomDomainManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected User $owner1;

    protected User $owner2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor1 = Vendor::create([
            'name' => 'Artisan Bakery',
            'slug' => 'artisan-bakery',
            'email' => 'artisan@bakery.am',
            'custom_domain' => null,
            'is_active' => true,
            'subscription_plan' => 'pro',
            'subscription_status' => 'active',
        ]);

        $this->owner1 = User::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Artisan Owner',
            'email' => 'owner@artisan.am',
            'password' => Hash::make('secret123'),
            'role' => 'vendor_owner',
        ]);

        $this->vendor2 = Vendor::create([
            'name' => 'Gourmet Burger',
            'slug' => 'gourmet-burger',
            'email' => 'contact@burger.am',
            'custom_domain' => null,
            'is_active' => true,
            'subscription_plan' => 'pro',
            'subscription_status' => 'active',
        ]);

        $this->owner2 = User::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Burger Owner',
            'email' => 'owner@burger.am',
            'password' => Hash::make('secret123'),
            'role' => 'vendor_owner',
        ]);
    }

    /**
     * Test 1: Verify CustomDomain entity fields and model attributes.
     */
    public function test_custom_domain_entity_has_all_required_phase_13_fields(): void
    {
        $domain = CustomDomain::create([
            'vendor_id' => $this->vendor1->id,
            'domain' => 'menu.artisan.am',
            'normalized_domain' => 'menu.artisan.am',
            'is_primary' => true,
            'verification_token' => 'elab-verify-test-token-12345',
            'verification_method' => CustomDomain::METHOD_DNS_TXT,
            'verified_at' => now(),
            'dns_status' => CustomDomain::DNS_DETECTED,
            'dns_detected_at' => now(),
            'ssl_status' => CustomDomain::SSL_ACTIVE,
            'ssl_expires_at' => now()->addDays(90),
            'status' => CustomDomain::STATUS_ACTIVE,
            'failure_reason' => null,
        ]);

        $this->assertDatabaseHas('custom_domains', [
            'id' => $domain->id,
            'vendor_id' => $this->vendor1->id,
            'domain' => 'menu.artisan.am',
            'normalized_domain' => 'menu.artisan.am',
            'is_primary' => true,
            'verification_token' => 'elab-verify-test-token-12345',
            'verification_method' => 'dns_txt',
            'status' => 'active',
            'dns_status' => 'detected',
            'ssl_status' => 'active',
        ]);

        $this->assertTrue($domain->is_primary);
        $this->assertTrue($domain->isActive());
        $this->assertTrue($domain->isVerified());
        $this->assertTrue($domain->isDnsDetected());
        $this->assertEquals($this->vendor1->id, $domain->vendor->id);
    }

    /**
     * Test 2: Consistent domain normalization strips schemes, paths, queries, ports, dots, uppercase.
     */
    public function test_domain_normalization_handles_various_formats_consistently(): void
    {
        $testCases = [
            'https://MENU.Artisan.AM/' => 'menu.artisan.am',
            'http://bakery.am/storefront?param=1' => 'bakery.am',
            'MENU.MyRestaurant.com:8080/path/test#hash' => 'menu.myrestaurant.com',
            '   sub.domain.co.uk.   ' => 'sub.domain.co.uk',
            'ORDER.EATERY.AM' => 'order.eatery.am',
        ];

        foreach ($testCases as $input => $expected) {
            $this->assertEquals($expected, CustomDomain::normalize($input));
        }
    }

    /**
     * Test 3: Domain normalization rejects invalid syntax and platform-reserved domains.
     */
    public function test_domain_normalization_rejects_invalid_and_reserved_domains(): void
    {
        $invalidInputs = [
            '',
            '   ',
            'invalid_domain_without_dot',
            '-invalidstart.com',
            'invalidend-.com',
            'contains spaces.com',
            'localhost',
            '127.0.0.1',
            'menu.elab.am',
            'elab.menu',
        ];

        foreach ($invalidInputs as $input) {
            try {
                CustomDomain::normalize($input);
                $this->fail("Expected InvalidArgumentException for invalid domain: [{$input}]");
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * Test 4: Enforce unique normalized domains across all vendors.
     */
    public function test_prevents_one_domain_from_being_assigned_to_multiple_vendors(): void
    {
        $service = app(CustomDomainService::class);

        // Vendor 1 registers domain
        $service->registerDomain($this->vendor1, 'order.artisan.am', isPrimary: true);

        $this->assertDatabaseHas('custom_domains', [
            'vendor_id' => $this->vendor1->id,
            'normalized_domain' => 'order.artisan.am',
        ]);

        // Vendor 2 tries to register the same domain in different format
        $this->expectException(ValidationException::class);
        $service->registerDomain($this->vendor2, 'https://ORDER.ARTISAN.AM/somepath');
    }

    /**
     * Test 5: Cryptographic ownership verification via TXT challenge.
     */
    public function test_ownership_verification_succeeds_when_dns_txt_record_matches(): void
    {
        $service = app(CustomDomainService::class);
        $customDomain = $service->registerDomain($this->vendor1, 'menu.artisan.am');

        $this->assertNotNull($customDomain->verification_token);
        $this->assertStringStartsWith('elab-verify-', $customDomain->verification_token);
        $this->assertEquals('_elab-challenge.menu.artisan.am', $customDomain->getChallengeHost());
        $this->assertNull($customDomain->verified_at);

        // Resolver returns matching token
        $matchingResolver = function (string $host) use ($customDomain) {
            return [$customDomain->verification_token];
        };

        $result = $service->verifyOwnership($customDomain, $matchingResolver);

        $this->assertTrue($result['verified']);
        $customDomain->refresh();
        $this->assertTrue($customDomain->isVerified());
        $this->assertNotNull($customDomain->verified_at);
        $this->assertEquals(CustomDomain::METHOD_DNS_TXT, $customDomain->verification_method);
    }

    /**
     * Test 6: Ownership verification fails when DNS TXT record is missing or mismatched.
     */
    public function test_ownership_verification_fails_when_txt_record_mismatches_or_empty(): void
    {
        $service = app(CustomDomainService::class);
        $customDomain = $service->registerDomain($this->vendor1, 'menu.artisan.am');

        // Case A: No TXT records returned
        $emptyResolver = fn () => [];
        $resultA = $service->verifyOwnership($customDomain, $emptyResolver);

        $this->assertFalse($resultA['verified']);
        $this->assertNull($customDomain->fresh()->verified_at);

        // Case B: Mismatched TXT record
        $mismatchResolver = fn () => ['elab-verify-wrong-token-99999'];
        $resultB = $service->verifyOwnership($customDomain, $mismatchResolver);

        $this->assertFalse($resultB['verified']);
        $this->assertNull($customDomain->fresh()->verified_at);
    }

    /**
     * Test 7: Separation of DNS detection from Domain Ownership Verification.
     */
    public function test_separation_of_dns_detected_from_domain_ownership_verified(): void
    {
        $service = app(CustomDomainService::class);
        $customDomain = $service->registerDomain($this->vendor1, 'portal.artisan.am');

        $this->assertEquals(CustomDomain::STATUS_PENDING, $customDomain->status);
        $this->assertFalse($customDomain->isVerified());
        $this->assertFalse($customDomain->isDnsDetected());

        // Step 1: DNS is detected first (A record matches), but ownership NOT yet verified
        $customDomain->markDnsDetected();
        $customDomain->refresh();

        $this->assertTrue($customDomain->isDnsDetected());
        $this->assertFalse($customDomain->isVerified());
        // Lifecycle should be 'verifying', NOT 'active' yet!
        $this->assertEquals(CustomDomain::STATUS_VERIFYING, $customDomain->status);
        $this->assertFalse($customDomain->isActive());

        // Step 2: Now ownership is cryptographically verified
        $customDomain->markVerified(CustomDomain::METHOD_DNS_TXT);
        $customDomain->refresh();

        $this->assertTrue($customDomain->isVerified());
        $this->assertTrue($customDomain->isDnsDetected());
        // Now it transitions to 'active'
        $this->assertEquals(CustomDomain::STATUS_ACTIVE, $customDomain->status);
        $this->assertTrue($customDomain->isActive());
    }

    /**
     * Test 8: Separation in reverse order: Ownership verified first, DNS detected second.
     */
    public function test_ownership_verified_first_remains_verifying_until_dns_detected(): void
    {
        $service = app(CustomDomainService::class);
        $customDomain = $service->registerDomain($this->vendor1, 'secure.artisan.am');

        // Step 1: Ownership verified, but DNS not detected
        $customDomain->markVerified(CustomDomain::METHOD_DNS_TXT);
        $customDomain->refresh();

        $this->assertTrue($customDomain->isVerified());
        $this->assertFalse($customDomain->isDnsDetected());
        $this->assertEquals(CustomDomain::STATUS_VERIFYING, $customDomain->status);
        $this->assertFalse($customDomain->isActive());

        // Step 2: DNS routing is detected
        $customDomain->markDnsDetected();
        $customDomain->refresh();

        $this->assertTrue($customDomain->isActive());
        $this->assertEquals(CustomDomain::STATUS_ACTIVE, $customDomain->status);
    }

    /**
     * Test 9: Lifecycle transitions: pending -> verifying -> active -> suspended -> failed.
     */
    public function test_full_lifecycle_transitions(): void
    {
        $domain = CustomDomain::create([
            'vendor_id' => $this->vendor1->id,
            'domain' => 'lifecycle.artisan.am',
            'normalized_domain' => 'lifecycle.artisan.am',
            'is_primary' => true,
        ]);

        $this->assertEquals(CustomDomain::STATUS_PENDING, $domain->status);

        // Transition to verifying
        $domain->markVerifying();
        $this->assertEquals(CustomDomain::STATUS_VERIFYING, $domain->status);

        // Transition to active
        $domain->markActive();
        $this->assertEquals(CustomDomain::STATUS_ACTIVE, $domain->status);
        $this->assertTrue($domain->isActive());

        // Transition to suspended
        $domain->markSuspended('DNS configuration removed by client');
        $domain->refresh();
        $this->assertEquals(CustomDomain::STATUS_SUSPENDED, $domain->status);
        $this->assertEquals('DNS configuration removed by client', $domain->failure_reason);
        $this->assertFalse($domain->isActive());

        // Transition to failed
        $domain->markFailed('SSL renewal verification failed repeatedly');
        $domain->refresh();
        $this->assertEquals(CustomDomain::STATUS_FAILED, $domain->status);
        $this->assertEquals('SSL renewal verification failed repeatedly', $domain->failure_reason);
        $this->assertFalse($domain->isActive());
    }

    /**
     * Test 10: Primary domain assignment demotes existing primary domain for the vendor.
     */
    public function test_primary_domain_assignment_and_demotion(): void
    {
        $service = app(CustomDomainService::class);

        $domain1 = $service->registerDomain($this->vendor1, 'domain1.artisan.am', isPrimary: true);
        $this->assertTrue($domain1->fresh()->is_primary);

        $domain2 = $service->registerDomain($this->vendor1, 'domain2.artisan.am', isPrimary: false);
        $this->assertFalse($domain2->fresh()->is_primary);
        $this->assertTrue($domain1->fresh()->is_primary);

        // Make domain2 primary
        $domain2->makePrimary();

        $this->assertTrue($domain2->fresh()->is_primary);
        $this->assertFalse($domain1->fresh()->is_primary);
    }

    /**
     * Test 11: IdentifyTenant middleware gates traffic: only STATUS_ACTIVE serves traffic.
     */
    public function test_tenant_middleware_blocks_inactive_domains_and_resolves_active_domains(): void
    {
        // Set up location and product for Artisan Bakery
        $location = Location::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Main Street',
            'slug' => 'main-street',
            'is_active' => true,
        ]);

        $cat = Category::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Bakery',
            'is_active' => true,
        ]);

        Product::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $cat->id,
            'name' => 'Sourdough Bread',
            'price' => 1200,
            'is_available' => true,
        ]);

        $customDomain = CustomDomain::create([
            'vendor_id' => $this->vendor1->id,
            'domain' => 'menu.artisan.am',
            'normalized_domain' => 'menu.artisan.am',
            'is_primary' => true,
            'status' => CustomDomain::STATUS_PENDING, // PENDING initially
        ]);

        // Attempt visit on pending domain -> should NOT route to tenant storefront
        $pendingResponse = $this->get('http://menu.artisan.am/');
        $pendingResponse->assertDontSee('Sourdough Bread');

        // Update status to SUSPENDED -> should NOT route
        $customDomain->markSuspended('Non-payment');
        $suspendedResponse = $this->get('http://menu.artisan.am/');
        $suspendedResponse->assertDontSee('Sourdough Bread');

        // Update status to ACTIVE -> routes cleanly to storefront
        $customDomain->markActive();
        $activeResponse = $this->get('http://menu.artisan.am/');
        $activeResponse->assertOk();
        $activeResponse->assertSee('Artisan Bakery');
        $activeResponse->assertSee('Sourdough Bread');
    }

    /**
     * Test 12: Admin settings endpoints for ownership verification and DNS checking.
     */
    public function test_admin_settings_domain_verification_and_dns_endpoints(): void
    {
        $this->actingAs($this->owner1);

        // 1. Save new domain via settings form
        $response = $this->post(route('admin.settings.update'), [
            'name' => 'Artisan Bakery',
            'custom_domain' => 'https://FOOD.ARTISAN.AM',
            'service_fee_type' => 'percent',
            'service_fee_value' => 0,
            'delivery_fee' => 0,
            'delivery_min_amount' => 0,
        ]);

        $response->assertSessionHasNoErrors();

        $domainRecord = CustomDomain::where('normalized_domain', 'food.artisan.am')->first();
        $this->assertNotNull($domainRecord);
        $this->assertEquals($this->vendor1->id, $domainRecord->vendor_id);
        $this->assertEquals(CustomDomain::STATUS_PENDING, $domainRecord->status);

        // 2. Call DNS check endpoint
        $dnsResponse = $this->postJson(route('admin.settings.domain.check'), [
            'domain' => 'food.artisan.am',
        ]);
        $dnsResponse->assertOk();
        $dnsResponse->assertJsonStructure(['success', 'domain', 'server_ip', 'message']);

        // 3. Call Ownership Verify endpoint
        $verifyResponse = $this->postJson(route('admin.settings.domain.verify'), [
            'domain' => 'food.artisan.am',
        ]);
        $verifyResponse->assertOk();
        $verifyResponse->assertJsonStructure(['success', 'verified', 'message', 'token', 'challenge_host']);
    }

    /**
     * Test 13: Removing custom domain clears CustomDomain entities and vendor custom_domain.
     */
    public function test_clearing_custom_domain_removes_custom_domain_records(): void
    {
        $this->actingAs($this->owner1);

        // First set a domain
        $this->post(route('admin.settings.update'), [
            'name' => 'Artisan Bakery',
            'custom_domain' => 'clean.artisan.am',
            'service_fee_type' => 'percent',
            'service_fee_value' => 0,
            'delivery_fee' => 0,
            'delivery_min_amount' => 0,
        ]);

        $this->assertDatabaseHas('custom_domains', [
            'vendor_id' => $this->vendor1->id,
            'normalized_domain' => 'clean.artisan.am',
        ]);

        // Now clear it
        $this->post(route('admin.settings.update'), [
            'name' => 'Artisan Bakery',
            'custom_domain' => '',
            'service_fee_type' => 'percent',
            'service_fee_value' => 0,
            'delivery_fee' => 0,
            'delivery_min_amount' => 0,
        ]);

        $this->vendor1->refresh();
        $this->assertNull($this->vendor1->custom_domain);
        $this->assertDatabaseMissing('custom_domains', [
            'vendor_id' => $this->vendor1->id,
            'normalized_domain' => 'clean.artisan.am',
        ]);
    }
}
