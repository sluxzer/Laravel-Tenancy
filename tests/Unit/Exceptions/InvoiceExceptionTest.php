<?php

use App\Exceptions\InvoiceException;

describe('InvoiceException', function () {
    it('creates not found exception', function () {
        $exception = InvoiceException::notFound();
        expect($exception->getErrorCode())->toBe('INVOICE_NOT_FOUND');
        expect($exception->getHttpStatus())->toBe(404);
    });

    it('creates cannot pay exception', function () {
        $exception = InvoiceException::cannotPay();
        expect($exception->getErrorCode())->toBe('INVOICE_CANNOT_PAY');
    });

    it('creates already paid exception', function () {
        $exception = InvoiceException::alreadyPaid();
        expect($exception->getErrorCode())->toBe('INVOICE_ALREADY_PAID');
    });

    it('creates overdue exception', function () {
        $exception = InvoiceException::overdue();
        expect($exception->getErrorCode())->toBe('INVOICE_OVERDUE');
    });
});