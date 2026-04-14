<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends DomainException
{
    protected string $errorCode = 'VALIDATION_ERROR';
    protected array $errors = [];

    public function __construct(string $message, array $errors = [], ?int $status = 422)
    {
        parent::__construct($message, null, $status);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'errors' => $this->errors,
        ];
    }
}