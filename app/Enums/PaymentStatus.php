<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Created = 'created';
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Expired = 'expired';

    /**
     * Determine if status is considered final.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Failed,
            self::Cancelled,
            self::Refunded,
            self::Expired,
        ], true);
    }

    /**
     * Determine if transition to another status is allowed.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::Created => in_array($target, [
                self::Pending,
                self::Processing,
                self::Paid,
                self::Failed,
                self::Cancelled,
                self::Expired,
            ], true),
            self::Pending => in_array($target, [
                self::Processing,
                self::Paid,
                self::Failed,
                self::Cancelled,
                self::Expired,
            ], true),
            self::Processing => in_array($target, [
                self::Paid,
                self::Failed,
                self::Cancelled,
                self::Expired,
            ], true),
            self::Paid => $target === self::Refunded,
            self::Failed, self::Cancelled, self::Refunded, self::Expired => false,
        };
    }
}
