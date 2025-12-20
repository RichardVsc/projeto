<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Repository\TransferRepositoryInterface;
use App\Domain\Transfer\Transfer;
use App\Infrastructure\Persistence\Model\TransferModel;

final class EloquentTransferRepository implements TransferRepositoryInterface
{
    public function save(Transfer $transfer): void
    {
        TransferModel::updateOrCreate(
            ['id' => $transfer->getId()->getValue()],
            [
                'payer_id' => $transfer->getPayerId()->getValue(),
                'payee_id' => $transfer->getPayeeId()->getValue(),
                'amount' => $transfer->getAmount()->toInt(),
                'status' => $transfer->getStatus()->value,
                'failure_reason' => $transfer->getFailureReason(),
                'authorized_at' => $transfer->getAuthorizedAt(),
                'completed_at' => $transfer->getCompletedAt(),
                'failed_at' => $transfer->getFailedAt(),
            ]
        );
    }
}
