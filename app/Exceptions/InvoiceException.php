<?php

declare(strict_types=1);

namespace App\Exceptions;

class InvoiceException extends DomainException
{
    protected string $errorCode = 'INVOICE_ERROR';

    public static function notFound(): self
    {
        return new self('Invoice not found', 404, 'INVOICE_NOT_FOUND');
    }

    public static function cannotPay(): self
    {
        return new self('This invoice cannot be paid', 400, 'INVOICE_CANNOT_PAY');
    }

    public static function alreadyPaid(): self
    {
        return new self('Invoice has already been paid', 400, 'INVOICE_ALREADY_PAID');
    }

    public static function overdue(): self
    {
        return new self('Invoice is overdue', 400, 'INVOICE_OVERDUE');
    }
}
