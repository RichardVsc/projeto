<?php

declare(strict_types=1);

namespace App\Domain\Transfer;

enum TransferRole
{
    case PAYER;
    case PAYEE;
    
    public function getLabel(): string
    {
        return match ($this) {
            self::PAYER => 'payer',
            self::PAYEE => 'payee',
        };
    }
}