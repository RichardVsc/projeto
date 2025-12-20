<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Exception;

use RuntimeException;

final class WalletNotFoundException extends RuntimeException
{
    public static function forUser(string $userId): self
    {
        return new self("Wallet not found for user: {$userId}");
    }
}