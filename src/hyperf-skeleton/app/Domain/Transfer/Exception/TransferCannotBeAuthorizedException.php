<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Exception;

use DomainException;

final class TransferCannotBeAuthorizedException extends DomainException
{
    public static function notPending(): self
    {
        return new self('Only pending transfers can be authorized.');
    }
}
