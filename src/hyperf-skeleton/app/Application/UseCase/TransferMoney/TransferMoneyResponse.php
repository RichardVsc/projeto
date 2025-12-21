<?php

declare(strict_types=1);

namespace App\Application\UseCase\TransferMoney;

use App\Domain\Transfer\TransferStatus;

final class TransferMoneyResponse
{
    public function __construct(
        public readonly string $transferId,
        public readonly string $status,
        public readonly ?string $failureReason = null
    ) {
    }

    public function getTransferId(): string
    {
        return $this->transferId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function isSuccessful(): bool
    {
        return $this->status === TransferStatus::COMPLETED->value;
    }

    public function isFailed(): bool
    {
        return $this->status === TransferStatus::FAILED->value;
    }
}
