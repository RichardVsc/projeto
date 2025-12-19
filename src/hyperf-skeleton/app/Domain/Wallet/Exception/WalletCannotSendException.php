<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exception;

use DomainException;

final class WalletCannotSendException extends DomainException
{
    public static function typeDoesNotAllowSending(): self
    {
        return new self('This wallet type is not allowed to send money.');
    }
}