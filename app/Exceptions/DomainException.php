<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

abstract class DomainException extends Exception
{
    protected string $errorCode = 'DOMAIN_ERROR';

    protected int $httpStatus = 400;

    public function __construct(string $message = '', $code = null, $status = null)
    {
        parent::__construct($message);

        if ($code !== null && is_string($code)) {
            $this->errorCode = $code;
        }

        if ($status !== null && is_int($status)) {
            $this->httpStatus = $status;
        }
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function setHttpStatus(int $status): void
    {
        $this->httpStatus = $status;
    }

    public function render(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ];
    }
}
