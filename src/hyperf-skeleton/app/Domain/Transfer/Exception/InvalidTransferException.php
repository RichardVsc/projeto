<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Exception;

use DomainException;

final class InvalidTransferException extends DomainException
{
    public static function emptyFailureReason(): self
    {
        return new self('TransferId cannot be empty.');
    }

    public static function invalidAmount(): self
    {
        return new self('Amount need to be more than zero.');
    }

    public static function cannotTransferToSelf(): self
    {
        return new self('Cannot transfer to self.');
    }
}