<?php

declare(strict_types=1);

namespace App\Validators\Exception;

final class ValidationException extends \Exception
{
    private array $errors;
    
    public static function fromErrors(array $errors): self
    {
        $exception = new self('Validation failed');
        $exception->errors = $errors;
        return $exception;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}