<?php

declare(strict_types=1);

namespace App\Exceptions;

class AuthenticationException extends DomainException
{
    protected string $errorCode = 'AUTHENTICATION_ERROR';

    public static function unauthenticated(): self
    {
        return new self('Unauthenticated', 401, 'UNAUTHENTICATED');
    }

    public static function invalidCredentials(): self
    {
        return new self('Invalid credentials', 401, 'INVALID_CREDENTIALS');
    }

    public static function tokenExpired(): self
    {
        return new self('Authentication token has expired', 401, 'TOKEN_EXPIRED');
    }

    public static function accountLocked(): self
    {
        return new self('Account has been locked', 403, 'ACCOUNT_LOCKED');
    }
}
