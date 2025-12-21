<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Transfer\Transfer;

final class TransferNotificationData
{
    public function __construct(
        public readonly string $transferId,
        public readonly string $payerId,
        public readonly string $payeeId,
        public readonly int $amount,
        public readonly string $status,
    ) {}

    public static function fromTransfer(Transfer $transfer): self
    {
        return new self(
            $transfer->getId()->getValue(),
            $transfer->getPayerId()->getValue(),
            $transfer->getPayeeId()->getValue(),
            $transfer->getAmount()->toInt(),
            $transfer->getStatus()->value,
        );
    }

    public function toArray(): array
    {
        return [
            'transfer_id' => $this->transferId,
            'payer_id' => $this->payerId,
            'payee_id' => $this->payeeId,
            'amount' => $this->amount,
            'status' => $this->status,
        ];
    }
}