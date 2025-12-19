<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use DomainException;

final class InvalidUserIdException extends DomainException
{
    public static function empty(): self
    {
        return new self('UserId cannot be empty.');
    }
}