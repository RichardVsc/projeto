<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

enum WalletType
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
