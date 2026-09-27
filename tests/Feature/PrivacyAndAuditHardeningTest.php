<?php

namespace Tests\Feature;

use App\Models\AnalyticsLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\SecurityAuditLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditSanitizer;
use App\Services\CredentialService;
use App\Services\CustomDomainService;
use App\Services\SecurityAuditService;
use App\Services\SubscriptionService;
use App\Services\TenantContext;
use App\Services\VendorLifecycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivacyAndAuditHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected User $owner1;

    protected User $owner2;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Vendor 1
        $this->vendor1 = Vendor::create([
            'name' => 'Pizza Napoli',
            'slug' => 'pizza-napoli',
            'email' => 'info@napoli.am',
            'is_active' => true,
            'subscription_plan' => 'pro',
            'subscription_status' => 'active',
        ]);

        $this->owner1 = User::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Mario Rossi',
            'email' => 'mario@napoli.am',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'vendor_owner',
        ]);

        // Vendor 2
        $this->vendor2 = Vendor::create([
            'name' => 'Tokyo Sushi',
            'slug' => 'tokyo-sushi',
            'email' => 'info@sushi.am',
            'is_active' => true,
            'subscription_plan' => 'pro',
            'subscription_status' => 'active',
        ]);

        $this->owner2 = User::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Kenji Sato',
            'email' => 'kenji@sushi.am',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'vendor_owner',
        ]);

        // SuperAdmin
        $this->superAdmin = User::create([
            'vendor_id' => null,
            'name' => 'Super Administrator',
            'email' => 'superadmin@elab.menu',
            'password' => Hash::make('SuperAdminPass123!'),
            'role' => 'superadmin',
        ]);
    }

    /**
     * Test 1: Failed login attempts NEVER leak the submitted password into audit logs or DB.
     */
    public function test_failed_login_never_logs_submitted_passwords(): void
    {
        $sensitivePasswordAttempt = 'SuperSecretPlaintextPassword987!#$';

        $response = $this->post(route('login.post'), [
            'email' => 'mario@napoli.am',
            'password' => $sensitivePasswordAttempt,
        ]);

        $response->assertSessionHasErrors('email');

        // Verify audit log exists for failed login
        $failedLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_FAILED_LOGIN)
            ->first();

        $this->assertNotNull($failedLog);
        $this->assertEquals('mario@napoli.am', $failedLog->metadata['attempted_identifier']);

        // Assert the plaintext password NEVER appears in any database column or metadata
        $this->assertStringNotContainsString($sensitivePasswordAttempt, json_encode($failedLog->toArray()));
        $this->assertDatabaseMissing('security_audit_logs', [
            'action' => $sensitivePasswordAttempt,
        ]);
    }

    /**
     * Test 2: Saving credentials never leaks secrets, API keys, or Telegram bot tokens in logs.
     */
    public function test_saving_credentials_never_leaks_secrets_in_audit_logs_or_metadata(): void
    {
        $credentialService = app(CredentialService::class);
        $rawStripeKey = 'sk_'.'live_'.'51ABCDEF1234567890abcdefghijklmnopqrstuvwxyz';
        $rawTelegramToken = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz_123456';

        $this->actingAs($this->owner1);

        $credentialService->set($this->vendor1, 'stripe', 'secret_key', $rawStripeKey);
        $credentialService->set($this->vendor1, 'telegram', 'bot_token', $rawTelegramToken);

        $auditLogs = SecurityAuditLog::withoutGlobalScopes()
            ->where('vendor_id', $this->vendor1->id)
            ->where('event', SecurityAuditLog::EVENT_CREDENTIAL_CHANGES)
            ->get();

        $this->assertCount(2, $auditLogs);

        foreach ($auditLogs as $log) {
            $json = json_encode($log->toArray());
            $this->assertStringNotContainsString($rawStripeKey, $json);
            $this->assertStringNotContainsString($rawTelegramToken, $json);
            $this->assertArrayHasKey('provider', $log->metadata);
            $this->assertArrayHasKey('credential_type', $log->metadata);
        }
    }

    /**
     * Test 3: AuditSanitizer deeply scrubs sensitive keys and regex patterns.
     */
    public function test_audit_sanitizer_redacts_sensitive_keys_and_embedded_patterns(): void
    {
        $sanitizer = app(AuditSanitizer::class);

        $mockStripeKey = 'sk_'.'live_'.'9876543210abcdef';
        $dirtyData = [
            'username' => 'john_doe',
            'password' => 'secret_pass_123',
            'api_key' => 'live_api_key_456',
            'session_id' => 'sess_abcdef',
            'nested' => [
                'bot_token' => 'bot999999:AAABBBCCCDDDEEEFFF',
                'card_number' => '4111222233334444',
                'normal_text' => "Here is Stripe key: {$mockStripeKey} and Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.xyz",
            ],
        ];

        $cleanData = $sanitizer->sanitize($dirtyData);

        $this->assertEquals('[REDACTED]', $cleanData['password']);
        $this->assertEquals('[REDACTED]', $cleanData['api_key']);
        $this->assertEquals('[REDACTED]', $cleanData['session_id']);
        $this->assertEquals('[REDACTED]', $cleanData['nested']['bot_token']);
        $this->assertEquals('[REDACTED]', $cleanData['nested']['card_number']);

        $normalText = $cleanData['nested']['normal_text'];
        $this->assertStringNotContainsString($mockStripeKey, $normalText);
        $this->assertStringContainsString('[REDACTED_STRIPE_KEY]', $normalText);
        $this->assertStringNotContainsString('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9', $normalText);
        $this->assertStringContainsString('[REDACTED_BEARER_TOKEN]', $normalText);
    }

    /**
     * Test 4: Authentication events (login and logout) are recorded.
     */
    public function test_login_and_logout_events_are_recorded(): void
    {
        // Login
        $this->post(route('login.post'), [
            'email' => 'mario@napoli.am',
            'password' => 'CorrectPassword123!',
        ]);

        $this->assertAuthenticatedAs($this->owner1);

        $loginLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_LOGIN)
            ->where('user_id', $this->owner1->id)
            ->first();

        $this->assertNotNull($loginLog);
        $this->assertEquals($this->vendor1->id, $loginLog->vendor_id);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        $logoutLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_LOGOUT)
            ->where('user_id', $this->owner1->id)
            ->first();

        $this->assertNotNull($logoutLog);
    }

    /**
     * Test 5: 2FA changes are recorded.
     */
    public function test_two_factor_changes_are_recorded(): void
    {
        $this->actingAs($this->owner1);

        // Enable 2FA
        $this->post(route('security.2fa.enable'), [
            'type' => 'email',
            'code' => '123456',
        ]);

        $this->owner1->refresh();
        $this->assertTrue($this->owner1->hasTwoFactorEnabled());

        $enableLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_2FA_CHANGES)
            ->where('user_id', $this->owner1->id)
            ->whereJsonContains('metadata->action_type', 'enabled')
            ->first();

        $this->assertNotNull($enableLog);

        // Disable 2FA
        $this->post(route('security.2fa.disable'), [
            'current_password' => 'CorrectPassword123!',
        ]);

        $this->owner1->refresh();
        $this->assertFalse($this->owner1->hasTwoFactorEnabled());

        $disableLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_2FA_CHANGES)
            ->where('user_id', $this->owner1->id)
            ->whereJsonContains('metadata->action_type', 'disabled')
            ->first();

        $this->assertNotNull($disableLog);
    }

    /**
     * Test 6: Password changes are recorded.
     */
    public function test_password_changes_are_recorded(): void
    {
        $this->actingAs($this->owner1);

        $newPassword = 'BrandNewSecurePassword456!';

        $this->post(route('security.password.update'), [
            'current_password' => 'CorrectPassword123!',
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $passLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_PASSWORD_CHANGES)
            ->where('user_id', $this->owner1->id)
            ->first();

        $this->assertNotNull($passLog);
        $this->assertStringNotContainsString($newPassword, json_encode($passLog->toArray()));
    }

    /**
     * Test 7: Role changes are recorded.
     */
    public function test_role_changes_are_recorded(): void
    {
        $this->actingAs($this->superAdmin);

        // Update role of owner1
        $this->owner1->update(['role' => 'manager']);

        $roleLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_ROLE_CHANGES)
            ->where('target_id', $this->owner1->id)
            ->first();

        $this->assertNotNull($roleLog);
        $this->assertEquals('vendor_owner', $roleLog->metadata['old_role']);
        $this->assertEquals('manager', $roleLog->metadata['new_role']);
    }

    /**
     * Test 8: Vendor suspension is recorded.
     */
    public function test_vendor_suspension_is_recorded(): void
    {
        $lifecycle = app(VendorLifecycleService::class);

        $lifecycle->suspend($this->vendor1, 'Terms of service violation', $this->superAdmin);

        $suspensionLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_VENDOR_SUSPENDED)
            ->where('vendor_id', $this->vendor1->id)
            ->first();

        $this->assertNotNull($suspensionLog);
        $this->assertEquals('Terms of service violation', $suspensionLog->metadata['reason']);
        $this->assertEquals($this->superAdmin->id, $suspensionLog->actor_id);
    }

    /**
     * Test 9: Vendor deletion is recorded.
     */
    public function test_vendor_deletion_is_recorded(): void
    {
        $lifecycle = app(VendorLifecycleService::class);

        $lifecycle->requestTermination($this->vendor1, 'Owner requested account deletion', $this->owner1);

        $deletionLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_VENDOR_DELETED)
            ->where('vendor_id', $this->vendor1->id)
            ->first();

        $this->assertNotNull($deletionLog);
        $this->assertEquals('requested', $deletionLog->metadata['action_type']);
        $this->assertEquals('Owner requested account deletion', $deletionLog->metadata['reason']);
    }

    /**
     * Test 10: Payment status changes are recorded.
     */
    public function test_payment_status_changes_are_recorded(): void
    {
        $category = Category::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Pizza',
            'is_active' => true,
        ]);

        $product = Product::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $category->id,
            'name' => 'Margherita',
            'price' => 3000,
            'is_available' => true,
        ]);

        $location = Location::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Downtown',
            'slug' => 'downtown',
            'is_active' => true,
        ]);

        $order = Order::create([
            'vendor_id' => $this->vendor1->id,
            'location_id' => $location->id,
            'order_number' => 'ORD-1001',
            'order_type' => 'dine_in',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 3000,
            'total_amount' => 3000,
        ]);

        // Transition payment
        app(SecurityAuditService::class)->logPaymentStatusChange(
            orderOrPaymentId: $order->id,
            oldStatus: 'pending',
            newStatus: 'paid',
            vendor: $this->vendor1,
            metadata: ['gateway' => 'stripe']
        );

        $paymentLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_PAYMENT_STATUS_CHANGED)
            ->where('vendor_id', $this->vendor1->id)
            ->first();

        $this->assertNotNull($paymentLog);
        $this->assertEquals('pending', $paymentLog->metadata['from_status']);
        $this->assertEquals('paid', $paymentLog->metadata['to_status']);
    }

    /**
     * Test 11: Subscription changes are recorded.
     */
    public function test_subscription_changes_are_recorded(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise',
            'price' => 25000,
            'currency' => 'AMD',
            'trial_days' => 7,
            'is_active' => true,
        ]);

        $subService = app(SubscriptionService::class);
        $subService->startTrial($this->vendor1, $plan, 7);

        $subLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_SUBSCRIPTION_CHANGED)
            ->where('vendor_id', $this->vendor1->id)
            ->first();

        $this->assertNotNull($subLog);
        $this->assertEquals('trialing', $subLog->metadata['to_status']);
    }

    /**
     * Test 12: Security configuration changes are recorded.
     */
    public function test_security_configuration_changes_are_recorded(): void
    {
        $domainService = app(CustomDomainService::class);

        $domainService->registerDomain($this->vendor1, 'order.napoli.am');

        $configLog = SecurityAuditLog::withoutGlobalScopes()
            ->where('event', SecurityAuditLog::EVENT_SECURITY_CONFIG_CHANGED)
            ->where('vendor_id', $this->vendor1->id)
            ->first();

        $this->assertNotNull($configLog);
        $this->assertEquals('custom_domain', $configLog->metadata['config_name']);
    }

    /**
     * Test 13: Audit logs strictly respect tenant isolation.
     */
    public function test_audit_logs_strictly_respect_tenant_isolation(): void
    {
        // Record event for Vendor 1
        SecurityAuditLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor1->id,
            'user_id' => $this->owner1->id,
            'actor_type' => 'user',
            'event' => 'custom_v1_event',
            'action' => 'Vendor 1 private action',
        ]);

        // Record event for Vendor 2
        SecurityAuditLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor2->id,
            'user_id' => $this->owner2->id,
            'actor_type' => 'user',
            'event' => 'custom_v2_event',
            'action' => 'Vendor 2 private action',
        ]);

        // Acting as Vendor 1 owner: fetch audit logs endpoint
        $this->actingAs($this->owner1);

        $response = $this->getJson(route('security.audit_logs'));
        $response->assertOk();

        $responseContent = $response->getContent();
        $this->assertStringContainsString('Vendor 1 private action', $responseContent);
        $this->assertStringNotContainsString('Vendor 2 private action', $responseContent);

        // Verify via TenantContext query scoping
        $tenantContext = app(TenantContext::class);
        $tenantContext->runInTenantContext($this->vendor1->id, function () {
            $visibleLogs = SecurityAuditLog::all();
            $this->assertTrue($visibleLogs->contains('action', 'Vendor 1 private action'));
            $this->assertFalse($visibleLogs->contains('action', 'Vendor 2 private action'));
        });
    }

    /**
     * Test 14: SuperAdmin platform logs contain cross-tenant metadata but no exposed secrets.
     */
    public function test_superadmin_can_view_cross_tenant_audit_logs_without_exposing_secrets(): void
    {
        // Vendor 1 action
        SecurityAuditLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor1->id,
            'user_id' => $this->owner1->id,
            'actor_type' => 'user',
            'event' => 'v1_event',
            'action' => 'Action on Napoli',
            'metadata' => [
                'password' => 'secret_pass', // auto-sanitized by mutator to [REDACTED]
                'note' => 'Napoli note',
            ],
        ]);

        // Vendor 2 action
        SecurityAuditLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor2->id,
            'user_id' => $this->owner2->id,
            'actor_type' => 'user',
            'event' => 'v2_event',
            'action' => 'Action on Tokyo Sushi',
            'metadata' => [
                'token' => 'live_token_123', // auto-sanitized to [REDACTED]
                'note' => 'Sushi note',
            ],
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->getJson(route('superadmin.audit_logs'));
        $response->assertOk();

        $content = $response->getContent();
        // Superadmin sees events from BOTH vendors
        $this->assertStringContainsString('Action on Napoli', $content);
        $this->assertStringContainsString('Action on Tokyo Sushi', $content);

        // Neither secret appears in the response
        $this->assertStringNotContainsString('secret_pass', $content);
        $this->assertStringNotContainsString('live_token_123', $content);
        $this->assertStringContainsString('[REDACTED]', $content);
    }

    /**
     * Test 15: Documented retention periods pruning command removes aged logs and anonymizes IPs.
     */
    public function test_privacy_prune_command_enforces_documented_retention_periods(): void
    {
        $sanitizer = app(AuditSanitizer::class);

        // 1. Audit log older than 365 days (expired)
        $expiredAudit = SecurityAuditLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor1->id,
            'event' => 'old_event',
            'action' => 'Ancient event',
            'created_at' => Carbon::now()->subDays(370),
        ]);

        // 2. Audit log within 365 days (retained)
        $retainedAudit = SecurityAuditLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor1->id,
            'event' => 'recent_event',
            'action' => 'Recent event',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // 3. Audit log between 30 and 365 days with raw IP (should have IP anonymized)
        $ipToAnonymizeAudit = SecurityAuditLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor1->id,
            'event' => 'mid_event',
            'action' => 'Mid-aged event',
            'ip_address' => '203.0.113.195',
            'created_at' => Carbon::now()->subDays(45),
        ]);

        // 4. Analytics log older than 90 days (expired)
        $expiredAnalytics = AnalyticsLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor1->id,
            'visit_date' => Carbon::now()->subDays(100),
            'ip_address' => '198.51.100.12',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => Carbon::now()->subDays(100),
        ]);

        // 5. Analytics log within 90 days (retained)
        $retainedAnalytics = AnalyticsLog::withoutGlobalScopes()->create([
            'vendor_id' => $this->vendor1->id,
            'visit_date' => Carbon::now()->subDays(5),
            'ip_address' => '198.51.100.14',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Run retention pruning command
        Artisan::call('privacy:prune');

        // Expired records deleted
        $this->assertDatabaseMissing('security_audit_logs', ['id' => $expiredAudit->id]);
        $this->assertDatabaseMissing('analytics_logs', ['id' => $expiredAnalytics->id]);

        // Retained records preserved
        $this->assertDatabaseHas('security_audit_logs', ['id' => $retainedAudit->id]);
        $this->assertDatabaseHas('analytics_logs', ['id' => $retainedAnalytics->id]);

        // IP masked for records older than 30 days
        $ipToAnonymizeAudit->refresh();
        $this->assertEquals('203.0.113.0', $ipToAnonymizeAudit->ip_address);
    }
}
