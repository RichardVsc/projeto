<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exception;

use DomainException;

final class InvalidDebitAmountException extends DomainException
{
    public static function amountMustBePositive(): self
    {
        return new self('Debit amount must be greater than zero.');
    }
}