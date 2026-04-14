<?php

use App\Exceptions\NotificationException;

describe('NotificationException', function () {
    it('creates not found exception', function () {
        $exception = NotificationException::notFound();
        expect($exception->getErrorCode())->toBe('NOTIFICATION_NOT_FOUND');
        expect($exception->getHttpStatus())->toBe(404);
    });

    it('creates send failed exception', function () {
        $exception = NotificationException::sendFailed('Email service unavailable');
        expect($exception->getMessage())->toBe('Failed to send notification: Email service unavailable');
        expect($exception->getErrorCode())->toBe('NOTIFICATION_SEND_FAILED');
        expect($exception->getHttpStatus())->toBe(500);
    });

    it('creates invalid channel exception', function () {
        $exception = NotificationException::invalidChannel('sms');
        expect($exception->getMessage())->toBe('Invalid notification channel: sms');
        expect($exception->getErrorCode())->toBe('NOTIFICATION_INVALID_CHANNEL');
    });
});