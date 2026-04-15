<?php

declare(strict_types=1);

namespace App\Exceptions;

class VoucherException extends DomainException
{
    protected string $errorCode = 'VOUCHER_ERROR';

    public static function notFound(): self
    {
        return new self('Voucher not found', 404, 'VOUCHER_NOT_FOUND');
    }

    public static function expired(): self
    {
        return new self('Voucher has expired', 400, 'VOUCHER_EXPIRED');
    }

    public static function notActive(): self
    {
        return new self('Voucher is not active', 400, 'VOUCHER_NOT_ACTIVE');
    }

    public static function maxUsesReached(): self
    {
        return new self('Voucher has reached maximum uses', 400, 'VOUCHER_MAX_USES_REACHED');
    }

    public static function alreadyUsed(): self
    {
        return new self('You have already used this voucher', 400, 'VOUCHER_ALREADY_USED');
    }

    public static function invalidForPlan(): self
    {
        return new self('This voucher is not valid for the selected plan', 400, 'VOUCHER_INVALID_FOR_PLAN');
    }
}
