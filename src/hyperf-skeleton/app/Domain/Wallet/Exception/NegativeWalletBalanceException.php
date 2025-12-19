<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exception;

use DomainException;

final class NegativeWalletBalanceException extends DomainException
{
    public static function negativeBalance(): self
    {
        return new self('Wallet balance cannot be negative.');
    }
}
