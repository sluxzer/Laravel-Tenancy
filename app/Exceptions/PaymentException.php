<?php

declare(strict_types=1);

namespace App\Exceptions;

class PaymentException extends DomainException
{
    protected string $errorCode = 'PAYMENT_ERROR';

    public static function failed(string $reason): self
    {
        return new self("Payment failed: {$reason}", 400, 'PAYMENT_FAILED');
    }

    public static function gatewayError(string $gateway): self
    {
        return new self("Payment gateway error: {$gateway}", 502, 'PAYMENT_GATEWAY_ERROR');
    }

    public static function insufficientFunds(): self
    {
        return new self('Insufficient funds for this transaction', 402, 'PAYMENT_INSUFFICIENT_FUNDS');
    }

    public static function alreadyProcessed(): self
    {
        return new self('Payment has already been processed', 400, 'PAYMENT_ALREADY_PROCESSED');
    }

    public static function notFound(): self
    {
        return new self('Payment not found', 404, 'PAYMENT_NOT_FOUND');
    }
}
