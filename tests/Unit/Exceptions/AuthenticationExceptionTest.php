<?php

use App\Exceptions\AuthenticationException;

describe('AuthenticationException', function () {
    it('creates unauthenticated exception', function () {
        $exception = AuthenticationException::unauthenticated();
        expect($exception->getMessage())->toBe('Unauthenticated');
        expect($exception->getErrorCode())->toBe('UNAUTHENTICATED');
        expect($exception->getHttpStatus())->toBe(401);
    });

    it('creates invalid credentials exception', function () {
        $exception = AuthenticationException::invalidCredentials();
        expect($exception->getMessage())->toBe('Invalid credentials');
        expect($exception->getErrorCode())->toBe('INVALID_CREDENTIALS');
    });

    it('creates token expired exception', function () {
        $exception = AuthenticationException::tokenExpired();
        expect($exception->getMessage())->toBe('Authentication token has expired');
        expect($exception->getErrorCode())->toBe('TOKEN_EXPIRED');
    });

    it('creates account locked exception', function () {
        $exception = AuthenticationException::accountLocked();
        expect($exception->getMessage())->toBe('Account has been locked');
        expect($exception->getErrorCode())->toBe('ACCOUNT_LOCKED');
        expect($exception->getHttpStatus())->toBe(403);
    });
});