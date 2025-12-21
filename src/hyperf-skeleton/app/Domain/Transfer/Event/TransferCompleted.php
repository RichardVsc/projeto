<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Event;

use App\Domain\Transfer\Transfer;
use DateTimeImmutable;

final class TransferCompleted
{
    public function __construct(
        private Transfer $transfer,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(Transfer $transfer): self
    {
        return new self($transfer, new DateTimeImmutable());
    }

    public function getTransfer(): Transfer
    {
        return $this->transfer;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}