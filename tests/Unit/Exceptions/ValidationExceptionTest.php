<?php

use App\Exceptions\ValidationException;

describe('ValidationException', function () {
    it('has custom error code', function () {
        $exception = new ValidationException('Validation failed', ['field' => 'error']);
        expect($exception->getErrorCode())->toBe('VALIDATION_ERROR');
        expect($exception->getHttpStatus())->toBe(422);
        expect($exception->getErrors())->toBe(['field' => 'error']);
    });

    it('renders with errors', function () {
        $errors = ['name' => ['required'], 'email' => ['invalid']];
        $exception = new ValidationException('Validation failed', $errors);
        $rendered = $exception->render();

        expect($rendered)->toHaveKeys(['success', 'message', 'error_code', 'errors']);
        expect($rendered['errors'])->toBe($errors);
    });
});