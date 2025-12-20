<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Exception;

use DomainException;

final class TransferCannotBeCompletedException extends DomainException
{
    public static function notAuthorized(): self
    {
        return new self('Only authorized transfers can be completed.');
    }
}
