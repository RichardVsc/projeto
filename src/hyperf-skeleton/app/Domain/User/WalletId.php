<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Exception\InvalidWalletIdException;

final class WalletId
{
    private string $value;

    public function __construct(string $value)
    {
        if ($value === '') {
            throw InvalidWalletIdException::empty();
        }

        $this->value = $value;
    }

    public function equals(WalletId $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}