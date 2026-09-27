<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class SecurityAuditService
{
    public function __construct(
        protected AuditSanitizer $sanitizer
    ) {}

    /**
     * Record a structured security audit event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $event,
        string $action,
        ?Vendor $vendor = null,
        ?User $actor = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): SecurityAuditLog {
        // Resolve actor from current auth session if not explicitly provided
        $currentActor = $actor ?? Auth::user();

        // Resolve vendor from user or tenant context if not provided
        $resolvedVendorId = $vendor?->id
            ?? $currentActor?->vendor_id
            ?? app(TenantContext::class)->getTenantId();

        $ip = $ipAddress ?? Request::ip();
        $ua = $userAgent ?? Request::userAgent();

        // Sanitize metadata to guarantee no sensitive secrets leak
        $cleanMetadata = $this->sanitizer->sanitize($metadata);

        // Determine actor type
        $actorType = 'system';
        if ($currentActor) {
            $actorType = $currentActor->isSuperAdmin() ? 'superadmin' : 'user';
        } elseif ($event === SecurityAuditLog::EVENT_FAILED_LOGIN) {
            $actorType = 'guest';
        }

        $log = SecurityAuditLog::withoutGlobalScopes()->create([
            'vendor_id' => $resolvedVendorId,
            'user_id' => $currentActor?->id,
            'actor_type' => $actorType,
            'actor_id' => $currentActor?->id,
            'actor_name' => $currentActor?->name,
            'actor_email' => $currentActor?->email,
            'event' => $event,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => $ip,
            'user_agent' => $ua ? substr($ua, 0, 500) : null,
            'metadata' => $cleanMetadata,
            'created_at' => now(),
        ]);

        // Output structured log entry
        Log::info("SECURITY AUDIT [{$event}]: {$action}", [
            'vendor_id' => $resolvedVendorId,
            'actor_id' => $currentActor?->id,
            'ip' => $ip,
            'metadata' => $cleanMetadata,
        ]);

        return $log;
    }

    /**
     * Record a successful authentication event.
     */
    public function logLogin(User $user, ?Vendor $vendor = null, array $metadata = []): SecurityAuditLog
    {
        return $this->record(
            event: SecurityAuditLog::EVENT_LOGIN,
            action: "User [{$user->email}] successfully logged in.",
            vendor: $vendor ?? $user->vendor,
            actor: $user,
            targetType: User::class,
            targetId: $user->id,
            metadata: $metadata
        );
    }

    /**
     * Record a user logout event.
     */
    public function logLogout(User $user, ?Vendor $vendor = null): SecurityAuditLog
    {
        return $this->record(
            event: SecurityAuditLog::EVENT_LOGOUT,
            action: "User [{$user->email}] logged out.",
            vendor: $vendor ?? $user->vendor,
            actor: $user,
            targetType: User::class,
            targetId: $user->id
        );
    }

    /**
     * Record a failed login attempt.
     * NEVER accepts or logs the submitted password.
     */
    public function logFailedLogin(
        string $attemptedIdentifier,
        ?Vendor $vendor = null,
        string $reason = 'Invalid credentials'
    ): SecurityAuditLog {
        return $this->record(
            event: SecurityAuditLog::EVENT_FAILED_LOGIN,
            action: "Failed login attempt for identifier [{$attemptedIdentifier}].",
            vendor: $vendor,
            actor: null,
            targetType: User::class,
            metadata: [
                'attempted_identifier' => $attemptedIdentifier,
                'failure_reason' => $reason,
            ]
        );
    }

    /**
     * Record changes to Two-Factor Authentication.
     */
    public function logTwoFactorChange(
        User $user,
        string $actionType, // 'enabled', 'disabled'
        string $type = 'authenticator',
        ?Vendor $vendor = null
    ): SecurityAuditLog {
        return $this->record(
            event: SecurityAuditLog::EVENT_2FA_CHANGES,
            action: "Two-factor authentication {$actionType} ({$type}) for [{$user->email}].",
            vendor: $vendor ?? $user->vendor,
            actor: $user,
            targetType: User::class,
            targetId: $user->id,
            metadata: [
                'action_type' => $actionType,
                'two_factor_type' => $type,
            ]
        );
    }

    /**
     * Record a password change event.
     */
    public function logPasswordChange(
        User $user,
        ?User $actor = null,
        ?Vendor $vendor = null
    ): SecurityAuditLog {
        $actorUser = $actor ?? $user;

        return $this->record(
            event: SecurityAuditLog::EVENT_PASSWORD_CHANGES,
            action: "Password changed for user [{$user->email}] by [{$actorUser->email}].",
            vendor: $vendor ?? $user->vendor,
            actor: $actorUser,
            targetType: User::class,
            targetId: $user->id
        );
    }

    /**
     * Record a vendor credential modification (API key, webhook secret, token).
     * NEVER logs the credential secret value.
     */
    public function logCredentialChange(
        Vendor $vendor,
        string $provider,
        string $credentialType,
        string $actionType = 'updated',
        ?User $actor = null
    ): SecurityAuditLog {
        return $this->record(
            event: SecurityAuditLog::EVENT_CREDENTIAL_CHANGES,
            action: "Credential [{$provider}/{$credentialType}] {$actionType} for vendor [{$vendor->name}].",
            vendor: $vendor,
            actor: $actor,
            targetType: Vendor::class,
            targetId: $vendor->id,
            metadata: [
                'provider' => $provider,
                'credential_type' => $credentialType,
                'action_type' => $actionType,
            ]
        );
    }

    /**
     * Record a user role change event.
     */
    public function logRoleChange(
        User $user,
        string $oldRole,
        string $newRole,
        ?User $actor = null,
        ?Vendor $vendor = null
    ): SecurityAuditLog {
        $actorUser = $actor ?? Auth::user();

        return $this->record(
            event: SecurityAuditLog::EVENT_ROLE_CHANGES,
            action: "Role for user [{$user->email}] changed from [{$oldRole}] to [{$newRole}].",
            vendor: $vendor ?? $user->vendor,
            actor: $actorUser,
            targetType: User::class,
            targetId: $user->id,
            metadata: [
                'user_id' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $newRole,
            ]
        );
    }

    /**
     * Record vendor suspension.
     */
    public function logVendorSuspension(
        Vendor $vendor,
        ?string $reason = null,
        ?User $actor = null
    ): SecurityAuditLog {
        return $this->record(
            event: SecurityAuditLog::EVENT_VENDOR_SUSPENDED,
            action: "Vendor [{$vendor->name}] suspended.",
            vendor: $vendor,
            actor: $actor,
            targetType: Vendor::class,
            targetId: $vendor->id,
            metadata: [
                'reason' => $reason,
            ]
        );
    }

    /**
     * Record vendor deletion or deletion request.
     */
    public function logVendorDeletion(
        Vendor $vendor,
        string $actionType = 'requested',
        ?string $reason = null,
        ?User $actor = null
    ): SecurityAuditLog {
        return $this->record(
            event: SecurityAuditLog::EVENT_VENDOR_DELETED,
            action: "Vendor [{$vendor->name}] deletion {$actionType}.",
            vendor: $vendor,
            actor: $actor,
            targetType: Vendor::class,
            targetId: $vendor->id,
            metadata: [
                'action_type' => $actionType,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Record a payment status transition (e.g. pending -> paid, unpaid -> failed).
     */
    public function logPaymentStatusChange(
        int|string $orderOrPaymentId,
        string $oldStatus,
        string $newStatus,
        ?Vendor $vendor = null,
        array $metadata = []
    ): SecurityAuditLog {
        return $this->record(
            event: SecurityAuditLog::EVENT_PAYMENT_STATUS_CHANGED,
            action: "Payment [{$orderOrPaymentId}] status transitioned from [{$oldStatus}] to [{$newStatus}].",
            vendor: $vendor,
            targetType: 'Payment',
            targetId: is_numeric($orderOrPaymentId) ? (int) $orderOrPaymentId : null,
            metadata: array_merge($metadata, [
                'payment_id' => $orderOrPaymentId,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
            ])
        );
    }

    /**
     * Record subscription plan or status change.
     */
    public function logSubscriptionChange(
        Vendor $vendor,
        string $oldStatus,
        string $newStatus,
        ?string $planSlug = null,
        ?User $actor = null
    ): SecurityAuditLog {
        return $this->record(
            event: SecurityAuditLog::EVENT_SUBSCRIPTION_CHANGED,
            action: "Subscription for vendor [{$vendor->name}] changed from [{$oldStatus}] to [{$newStatus}] (Plan: {$planSlug}).",
            vendor: $vendor,
            actor: $actor,
            targetType: 'Subscription',
            metadata: [
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'plan_slug' => $planSlug,
            ]
        );
    }

    /**
     * Record security configuration updates (custom domain, Wi-Fi password, 2FA policy).
     */
    public function logSecurityConfigChange(
        Vendor $vendor,
        string $configName,
        string $summary,
        ?User $actor = null,
        array $metadata = []
    ): SecurityAuditLog {
        return $this->record(
            event: SecurityAuditLog::EVENT_SECURITY_CONFIG_CHANGED,
            action: "Security configuration [{$configName}] updated for vendor [{$vendor->name}]: {$summary}",
            vendor: $vendor,
            actor: $actor,
            targetType: Vendor::class,
            targetId: $vendor->id,
            metadata: array_merge($metadata, [
                'config_name' => $configName,
            ])
        );
    }
}
