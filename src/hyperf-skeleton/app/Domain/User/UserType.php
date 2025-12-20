<?php

declare(strict_types=1);

namespace App\Domain\User;

enum UserType: string 
{
    case COMMON = 'COMMON';
    case MERCHANT = 'MERCHANT';

    public function canSend(): bool
    {
        return match ($this) {
            self::COMMON => true,
            self::MERCHANT => false,
        };
    }
}
