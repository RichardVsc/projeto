<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use DomainException;

final class InvalidDocumentNumberException extends DomainException
{
    public static function invalidCpf(): self
    {
        return new self('Invalid CPF number');
    }

    public static function invalidCnpj(): self
    {
        return new self('Invalid CNPJ number');
    }
}
