<?php

declare(strict_types=1);

namespace App\Exceptions;

class NotificationException extends DomainException
{
    protected string $errorCode = 'NOTIFICATION_ERROR';

    public static function notFound(): self
    {
        return new self('Notification not found', 404, 'NOTIFICATION_NOT_FOUND');
    }

    public static function sendFailed(string $reason): self
    {
        return new self("Failed to send notification: {$reason}", 500, 'NOTIFICATION_SEND_FAILED');
    }

    public static function invalidChannel(string $channel): self
    {
        return new self("Invalid notification channel: {$channel}", 400, 'NOTIFICATION_INVALID_CHANNEL');
    }
}