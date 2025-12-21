<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use DomainException;

final class InvalidUserException extends DomainException
{
    public static function emptyName(): self
    {
        return new self('Name cannot be empty.');
    }

    public static function nameTooShort(): self
    {
        return new self('Name needs to have at least 3 characters.');
    }

    public static function nameTooLong(): self
    {
        return new self('Name needs to have less than 255 characters.');
    }
}
