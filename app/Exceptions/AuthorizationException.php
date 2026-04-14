<?php

declare(strict_types=1);

namespace App\Exceptions;

class AuthorizationException extends DomainException
{
    protected string $errorCode = 'AUTHORIZATION_ERROR';

    public static function forbidden(): self
    {
        return new self('You do not have permission to perform this action', 403, 'FORBIDDEN');
    }

    public static function notOwner(): self
    {
        return new self('You do not own this resource', 403, 'NOT_OWNER');
    }

    public static function tenantMismatch(): self
    {
        return new self('Resource does not belong to your tenant', 403, 'TENANT_MISMATCH');
    }
}