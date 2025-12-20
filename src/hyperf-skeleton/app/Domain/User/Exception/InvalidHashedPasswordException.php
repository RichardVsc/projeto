<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use DomainException;

final class InvalidHashedPasswordException extends DomainException
{
    public static function empty(): self
    {
        return new self('HashedPassword cannot be empty.');
    }

    public static function invalidFormat(): self
    {
        return new self('Invalid HashedPassword format.');
    }

    public static function invalidLength(): self
    {
        return new self('Invalid HashedPassword length.');
    }
}