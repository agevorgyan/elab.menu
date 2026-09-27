<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Grace = 'grace';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Suspended = 'suspended';

    /**
     * Determine if this subscription state allows access to system features.
     */
    public function allowsAccess(): bool
    {
        return match ($this) {
            self::Trialing, self::Active, self::Grace => true,
            self::Cancelled => true, // Allowed until current period ends (checked in model)
            self::PastDue, self::Expired, self::Suspended => false,
        };
    }

    /**
     * Determine if transition to another status is allowed by the state machine.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::Trialing => in_array($target, [
                self::Active,
                self::PastDue,
                self::Grace,
                self::Cancelled,
                self::Expired,
                self::Suspended,
            ], true),

            self::Active => in_array($target, [
                self::PastDue,
                self::Grace,
                self::Cancelled,
                self::Expired,
                self::Suspended,
            ], true),

            self::PastDue => in_array($target, [
                self::Active,
                self::Grace,
                self::Expired,
                self::Cancelled,
                self::Suspended,
            ], true),

            self::Grace => in_array($target, [
                self::Active,
                self::PastDue,
                self::Expired,
                self::Cancelled,
                self::Suspended,
            ], true),

            self::Cancelled => in_array($target, [
                self::Active,
                self::Expired,
                self::Suspended,
            ], true),

            self::Expired => in_array($target, [
                self::Active,
                self::Suspended,
            ], true),

            self::Suspended => in_array($target, [
                self::Active,
                self::Expired,
                self::Cancelled,
            ], true),
        };
    }

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Փորձնական (Trial)',
            self::Active => 'Ակտիվ (Active)',
            self::PastDue => 'Ժամկետանց (Past Due)',
            self::Grace => 'Արտոնյալ ժամկետ (Grace Period)',
            self::Cancelled => 'Չեղարկված (Cancelled)',
            self::Expired => 'Ավարտված (Expired)',
            self::Suspended => 'Կասեցված (Suspended)',
        };
    }
}
