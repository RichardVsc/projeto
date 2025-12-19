<?php

declare(strict_types=1);

namespace App\Domain\User;

enum UserType
{
    case USER;
    case MERCHANT;

    public function canSend(): bool
    {
        return match ($this) {
            self::USER => true,
            self::MERCHANT => false,
        };
    }
}
