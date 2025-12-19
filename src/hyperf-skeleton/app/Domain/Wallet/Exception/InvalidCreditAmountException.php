<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exception;

use DomainException;

final class InvalidCreditAmountException extends DomainException
{
    public static function mustBePositive(): self
    {
        return new self('Credit amount must be greater than zero.');
    }
}