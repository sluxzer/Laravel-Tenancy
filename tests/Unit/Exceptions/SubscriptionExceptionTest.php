<?php

use App\Exceptions\SubscriptionException;

describe('SubscriptionException', function () {
    it('has correct default error code', function () {
        $exception = SubscriptionException::alreadyActive();
        expect($exception->getErrorCode())->toBe('SUBSCRIPTION_ALREADY_ACTIVE');
        expect($exception->getHttpStatus())->toBe(409);
    });

    it('creates not found exception', function () {
        $exception = SubscriptionException::notFound();
        expect($exception->getErrorCode())->toBe('SUBSCRIPTION_NOT_FOUND');
        expect($exception->getHttpStatus())->toBe(404);
    });

    it('creates cannot cancel exception', function () {
        $exception = SubscriptionException::cannotCancel('Reason');
        expect($exception->getMessage())->toBe('Cannot cancel subscription: Reason');
        expect($exception->getErrorCode())->toBe('SUBSCRIPTION_CANNOT_CANCEL');
    });

    it('creates invalid status exception', function () {
        $exception = SubscriptionException::invalidStatus('cancelled', ['active', 'paused']);
        expect($exception->getMessage())
            ->toBe('Invalid subscription status cancelled. Required: active, paused');
        expect($exception->getErrorCode())->toBe('SUBSCRIPTION_INVALID_STATUS');
    });

    it('creates cannot pause exception', function () {
        $exception = SubscriptionException::cannotPause();
        expect($exception->getErrorCode())->toBe('SUBSCRIPTION_CANNOT_PAUSE');
    });

    it('creates cannot resume exception', function () {
        $exception = SubscriptionException::cannotResume();
        expect($exception->getErrorCode())->toBe('SUBSCRIPTION_CANNOT_RESUME');
    });

    it('creates cannot renew exception', function () {
        $exception = SubscriptionException::cannotRenew();
        expect($exception->getErrorCode())->toBe('SUBSCRIPTION_CANNOT_RENEW');
    });
});