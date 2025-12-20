<?php

declare(strict_types=1);

namespace App\Application\UseCase\TransferMoney;

final class TransferMoneyResponse
{
    public function __construct(
        public readonly string $transferId,
        public readonly string $status,
        public readonly ?string $failureReason = null
    ) {}
}