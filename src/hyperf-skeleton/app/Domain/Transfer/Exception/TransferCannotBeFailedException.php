<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Exception;

use DomainException;

final class TransferCannotBeFailedException extends DomainException
{
    public static function alreadyCompleted(): self
    {
        return new self('A completed transfer cannot be failed.');
    }

    public static function alreadyFailed(): self
    {
        return new self('A failed transfer cannot be failed again.');
    }
}
