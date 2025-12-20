<?php

declare(strict_types=1);

namespace App\Application\UseCase\TransferMoney\Exception;

use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public static function payerNotFound(string $payerId): self
    {
        return new self('Payer ' . $payerId . ' was not found.');
    }

    public static function payeeNotFound($payeeId): self
    {
        return new self('Payee ' . $payeeId . ' was not found.');
    }
}