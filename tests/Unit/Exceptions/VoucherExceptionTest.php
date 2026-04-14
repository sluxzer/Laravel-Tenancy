<?php

use App\Exceptions\VoucherException;

describe('VoucherException', function () {
    it('creates not found exception', function () {
        $exception = VoucherException::notFound();
        expect($exception->getErrorCode())->toBe('VOUCHER_NOT_FOUND');
        expect($exception->getHttpStatus())->toBe(404);
    });

    it('creates expired exception', function () {
        $exception = VoucherException::expired();
        expect($exception->getErrorCode())->toBe('VOUCHER_EXPIRED');
    });

    it('creates not active exception', function () {
        $exception = VoucherException::notActive();
        expect($exception->getErrorCode())->toBe('VOUCHER_NOT_ACTIVE');
    });

    it('creates max uses reached exception', function () {
        $exception = VoucherException::maxUsesReached();
        expect($exception->getErrorCode())->toBe('VOUCHER_MAX_USES_REACHED');
    });

    it('creates already used exception', function () {
        $exception = VoucherException::alreadyUsed();
        expect($exception->getErrorCode())->toBe('VOUCHER_ALREADY_USED');
    });

    it('creates invalid for plan exception', function () {
        $exception = VoucherException::invalidForPlan();
        expect($exception->getErrorCode())->toBe('VOUCHER_INVALID_FOR_PLAN');
    });
});