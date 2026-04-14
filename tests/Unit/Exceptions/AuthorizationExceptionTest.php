<?php

use App\Exceptions\AuthorizationException;

describe('AuthorizationException', function () {
    it('creates forbidden exception', function () {
        $exception = AuthorizationException::forbidden();
        expect($exception->getMessage())->toBe('You do not have permission to perform this action');
        expect($exception->getErrorCode())->toBe('FORBIDDEN');
        expect($exception->getHttpStatus())->toBe(403);
    });

    it('creates not owner exception', function () {
        $exception = AuthorizationException::notOwner();
        expect($exception->getMessage())->toBe('You do not own this resource');
        expect($exception->getErrorCode())->toBe('NOT_OWNER');
    });

    it('creates tenant mismatch exception', function () {
        $exception = AuthorizationException::tenantMismatch();
        expect($exception->getMessage())->toBe('Resource does not belong to your tenant');
        expect($exception->getErrorCode())->toBe('TENANT_MISMATCH');
    });
});