<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exception;

use DomainException;

final class InvalidWalletIdException extends DomainException
{
    public static function empty(): self
    {
        return new self('WalletId cannot be empty.');
    }
}