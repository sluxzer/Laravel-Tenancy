<?php

declare(strict_types=1);

namespace App\Exceptions;

class SubscriptionException extends DomainException
{
    protected string $errorCode = 'SUBSCRIPTION_ERROR';

    public static function alreadyActive(): self
    {
        return new self('User already has an active subscription', 409, 'SUBSCRIPTION_ALREADY_ACTIVE');
    }

    public static function notFound(): self
    {
        return new self('Subscription not found', 404, 'SUBSCRIPTION_NOT_FOUND');
    }

    public static function cannotCancel(string $reason): self
    {
        return new self("Cannot cancel subscription: {$reason}", 400, 'SUBSCRIPTION_CANNOT_CANCEL');
    }

    public static function invalidStatus(string $currentStatus, array $validStatuses): self
    {
        return new self(
            sprintf('Invalid subscription status %s. Required: %s', $currentStatus, implode(', ', $validStatuses)),
            400,
            'SUBSCRIPTION_INVALID_STATUS'
        );
    }

    public static function cannotPause(): self
    {
        return new self('Only active subscriptions can be paused', 400, 'SUBSCRIPTION_CANNOT_PAUSE');
    }

    public static function cannotResume(): self
    {
        return new self('Only paused subscriptions can be resumed', 400, 'SUBSCRIPTION_CANNOT_RESUME');
    }

    public static function cannotRenew(): self
    {
        return new self('Cannot renew this subscription', 400, 'SUBSCRIPTION_CANNOT_RENEW');
    }
}
