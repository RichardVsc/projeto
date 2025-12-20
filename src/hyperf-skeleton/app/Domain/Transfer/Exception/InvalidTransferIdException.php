<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Exception;

use DomainException;

final class InvalidTransferIdException extends DomainException
{
    public static function empty(): self
    {
        return new self('TransferId cannot be empty.');
    }

    public static function invalidFormat(): self
    {
        return new self('Invalid TransferId format.');
    }
}