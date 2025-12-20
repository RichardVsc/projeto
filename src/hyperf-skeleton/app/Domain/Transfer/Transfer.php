<?php

declare(strict_types=1);

namespace App\Domain\Transfer;

use App\Domain\Money\Money;
use App\Domain\Transfer\Exception\InvalidTransferException;
use App\Domain\Transfer\Exception\TransferCannotBeAuthorizedException;
use App\Domain\Transfer\Exception\TransferCannotBeCompletedException;
use App\Domain\Transfer\Exception\TransferCannotBeFailedException;
use App\Domain\User\UserId;
use DateTimeImmutable;

final class Transfer
{
    private TransferId $id;
    private UserId $payerId;
    private UserId $payeeId;
    private Money $amount;
    private TransferStatus $status;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $authorizedAt;
    private ?DateTimeImmutable $completedAt;
    private ?DateTimeImmutable $failedAt;
    private ?string $failureReason;

    private function __construct(
        TransferId $id,
        UserId $payerId,
        UserId $payeeId,
        Money $amount,
        TransferStatus $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $authorizedAt,
        ?DateTimeImmutable $completedAt,
        ?DateTimeImmutable $failedAt,
        ?string $failureReason
    ) {
        $this->id = $id;
        $this->payerId = $payerId;
        $this->payeeId = $payeeId;
        $this->amount = $amount;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->authorizedAt = $authorizedAt;
        $this->completedAt = $completedAt;
        $this->failedAt = $failedAt;
        $this->failureReason = $failureReason;
    }

    public static function create(
        TransferId $id,
        UserId $payerId,
        UserId $payeeId,
        Money $amount,
    ): self {
        if ($payerId->equals($payeeId)) {
            throw InvalidTransferException::cannotTransferToSelf();
        }

        if ($amount->isZero() || $amount->isNegative()) {
            throw InvalidTransferException::invalidAmount();
        }

        return new self(
            $id,
            $payerId,
            $payeeId,
            $amount,
            TransferStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
            null,
        );
    }

    public function authorize(): self
    {
        if (!$this->canBeAuthorized()) {
            throw TransferCannotBeAuthorizedException::notPending();
        }

        return new self(
            $this->id,
            $this->payerId,
            $this->payeeId,
            $this->amount,
            TransferStatus::AUTHORIZED,
            $this->createdAt,
            new DateTimeImmutable(),
            null,
            null,
            null
        );
    }

    public function complete(): self
    {
        if (!$this->canBeCompleted()) {
            throw TransferCannotBeCompletedException::notAuthorized();
        }

        return new self(
            $this->id,
            $this->payerId,
            $this->payeeId,
            $this->amount,
            TransferStatus::COMPLETED,
            $this->createdAt,
            $this->authorizedAt,
            new DateTimeImmutable(),
            null,
            null
        );
    }

    public function fail(string $reason): self
    {
        if ($this->isCompleted()) {
            throw TransferCannotBeFailedException::alreadyCompleted();
        }

        if ($this->isFailed()) {
            throw TransferCannotBeFailedException::alreadyFailed();
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw InvalidTransferException::emptyFailureReason();
        }

        return new self(
            $this->id,
            $this->payerId,
            $this->payeeId,
            $this->amount,
            TransferStatus::FAILED,
            $this->createdAt,
            $this->authorizedAt,
            null,
            new DateTimeImmutable(),
            $trimmedReason
        );
    }

    public function canBeAuthorized(): bool
    {
        return $this->isPending();
    }

    public function canBeCompleted(): bool
    {
        return $this->isAuthorized();
    }

    public function canBeFailed(): bool
    {
        return !$this->isCompleted() && !$this->isFailed();
    }

    public function isPending(): bool
    {
        return $this->status === TransferStatus::PENDING;
    }

    public function isAuthorized(): bool
    {
        return $this->status === TransferStatus::AUTHORIZED;
    }

    public function isCompleted(): bool
    {
        return $this->status === TransferStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === TransferStatus::FAILED;
    }

    public function getId(): TransferId
    {
        return $this->id;
    }

    public function getPayerId(): UserId
    {
        return $this->payerId;
    }

    public function getPayeeId(): UserId
    {
        return $this->payeeId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getStatus(): TransferStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAuthorizedAt(): ?DateTimeImmutable
    {
        return $this->authorizedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getFailedAt(): ?DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }
}
