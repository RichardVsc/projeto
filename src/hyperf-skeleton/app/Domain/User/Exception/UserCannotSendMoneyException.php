<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use DomainException;

final class UserCannotSendMoneyException extends DomainException
{
    public static function cannotSendMoney(): self
    {
        return new self('This user type cannot send money.');
    }
}
