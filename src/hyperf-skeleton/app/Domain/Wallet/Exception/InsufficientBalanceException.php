<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exception;

use DomainException;

final class InsufficientBalanceException extends DomainException
{
    public static function notEnoughFunds(): self
    {
        return new self('Insufficient balance.');
    }
}