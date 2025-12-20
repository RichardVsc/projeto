<?php

declare(strict_types=1);

namespace App\Domain\Transfer;

enum TransferStatus
{
    case PENDING;
    case AUTHORIZED;
    case COMPLETED;
    case FAILED;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::AUTHORIZED => 'authorized',
            self::COMPLETED => 'completed',
            self::FAILED => 'failed',
        };
    }
}
