<?php

declare(strict_types=1);

namespace App\Application\UseCase\TransferMoney;

final class TransferMoneyCommand
{
    public function __construct(
        public readonly string $payerId,
        public readonly string $payeeId,
        public readonly int $amountInCents
    ) {}
}