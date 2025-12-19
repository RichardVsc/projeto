<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Exception\InvalidUserIdException;

final class UserId
{
    private string $value;

    public function __construct(string $value)
    {
        if ($value === '') {
            throw InvalidUserIdException::empty();
        }

        $this->value = $value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}