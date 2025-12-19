<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use DomainException;

final class InvalidEmailException extends DomainException
{
    public static function invalidFormat(): self
    {
        return new self('Invalid email format');
    }
}
