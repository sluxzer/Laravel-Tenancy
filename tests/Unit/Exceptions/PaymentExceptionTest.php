<?php

use App\Exceptions\PaymentException;

describe('PaymentException', function () {
    it('creates failed payment exception', function () {
        $exception = PaymentException::failed('Insufficient balance');
        expect($exception->getMessage())->toBe('Payment failed: Insufficient balance');
        expect($exception->getErrorCode())->toBe('PAYMENT_FAILED');
    });

    it('creates gateway error exception', function () {
        $exception = PaymentException::gatewayError('PayPal');
        expect($exception->getMessage())->toBe('Payment gateway error: PayPal');
        expect($exception->getErrorCode())->toBe('PAYMENT_GATEWAY_ERROR');
        expect($exception->getHttpStatus())->toBe(502);
    });

    it('creates insufficient funds exception', function () {
        $exception = PaymentException::insufficientFunds();
        expect($exception->getMessage())->toBe('Insufficient funds for this transaction');
        expect($exception->getErrorCode())->toBe('PAYMENT_INSUFFICIENT_FUNDS');
        expect($exception->getHttpStatus())->toBe(402);
    });

    it('creates already processed exception', function () {
        $exception = PaymentException::alreadyProcessed();
        expect($exception->getErrorCode())->toBe('PAYMENT_ALREADY_PROCESSED');
    });

    it('creates not found exception', function () {
        $exception = PaymentException::notFound();
        expect($exception->getErrorCode())->toBe('PAYMENT_NOT_FOUND');
        expect($exception->getHttpStatus())->toBe(404);
    });
});