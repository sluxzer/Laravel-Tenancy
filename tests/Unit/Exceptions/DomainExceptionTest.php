<?php

use App\Exceptions\DomainException;

describe('DomainException', function () {
    it('has default error code and status', function () {
        $exception = new class extends DomainException {
            public function __construct() {
                parent::__construct('Test message');
            }
        };

        expect($exception->getErrorCode())->toBe('DOMAIN_ERROR');
        expect($exception->getHttpStatus())->toBe(400);
    });

    it('renders error response format', function () {
        $exception = new class extends DomainException {
            public function __construct() {
                parent::__construct('Test message');
            }
        };

        $rendered = $exception->render();

        expect($rendered)->toHaveKeys(['success', 'message', 'error_code']);
        expect($rendered['success'])->toBeFalse();
        expect($rendered['message'])->toBe('Test message');
        expect($rendered['error_code'])->toBe('DOMAIN_ERROR');
    });
});