<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\SecurityAuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class SecurityAuditAuthSubscriber
{
    public function __construct(
        protected SecurityAuditService $auditService
    ) {}

    /**
     * Handle user login.
     */
    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->auditService->logLogin($event->user);
        }
    }

    /**
     * Handle user logout.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->auditService->logLogout($event->user);
        }
    }

    /**
     * Handle failed login attempt.
     * Guaranteed never to log or store passwords.
     */
    public function handleFailed(Failed $event): void
    {
        $identifier = $event->credentials['email']
            ?? $event->credentials['username']
            ?? 'unknown';

        $vendor = $event->user?->vendor;

        $this->auditService->logFailedLogin(
            attemptedIdentifier: (string) $identifier,
            vendor: $vendor,
            reason: 'Invalid credentials or unverified user'
        );
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
        ];
    }
}
