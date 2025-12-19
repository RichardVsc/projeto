<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use DomainException;

final class UserInsufficientFundsException extends DomainException
{
    public static function notEnoughBalance(): self
    {
        return new self('User does not have enough balance.');
    }
}
