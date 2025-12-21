<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\Transfer;

use App\Domain\Money\Money;
use App\Domain\Transfer\Exception\InvalidTransferException;
use App\Domain\Transfer\Exception\TransferCannotBeAuthorizedException;
use App\Domain\Transfer\Exception\TransferCannotBeCompletedException;
use App\Domain\Transfer\Exception\TransferCannotBeFailedException;
use App\Domain\Transfer\Transfer;
use App\Domain\Transfer\TransferId;
use App\Domain\Transfer\TransferStatus;
use App\Domain\User\UserId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class TransferTest extends TestCase
{
    public function testCanCreateValidTransfer(): void
    {
        $transfer = Transfer::create(
            TransferId::generate(),
            UserId::generate(),
            UserId::generate(),
            Money::fromCents(100)
        );

        $this->assertTrue($transfer->isPending());
        $this->assertSame(TransferStatus::PENDING, $transfer->getStatus());
        $this->assertNotNull($transfer->getCreatedAt());
        $this->assertNull($transfer->getAuthorizedAt());
        $this->assertNull($transfer->getCompletedAt());
        $this->assertNull($transfer->getFailedAt());
        $this->assertNull($transfer->getFailureReason());
    }

    public function testCreationFailsWhenPayerAndPayeeAreTheSame(): void
    {
        $userId = UserId::generate();

        $this->expectException(InvalidTransferException::class);

        Transfer::create(
            TransferId::generate(),
            $userId,
            $userId,
            Money::fromCents(100)
        );
    }

    public function testCreationFailsWhenAmountIsZero(): void
    {
        $this->expectException(InvalidTransferException::class);

        Transfer::create(
            TransferId::generate(),
            UserId::generate(),
            UserId::generate(),
            Money::fromCents(0)
        );
    }

    public function testCreationFailsWhenAmountIsNegative(): void
    {
        $this->expectException(InvalidTransferException::class);

        Transfer::create(
            TransferId::generate(),
            UserId::generate(),
            UserId::generate(),
            Money::fromCents(-10)
        );
    }

    public function testCanAuthorizePendingTransfer(): void
    {
        $transfer = $this->createPendingTransfer();

        $authorized = $transfer->authorize();

        $this->assertTrue($authorized->isAuthorized());
        $this->assertNotNull($authorized->getAuthorizedAt());

        $this->assertTrue($transfer->isPending());
        $this->assertNull($transfer->getAuthorizedAt());
    }

    public function testAuthorizeFailsWhenNotPending(): void
    {
        $transfer = $this->createPendingTransfer()->authorize();

        $this->expectException(TransferCannotBeAuthorizedException::class);

        $transfer->authorize();
    }

    public function testCanCompleteAuthorizedTransfer(): void
    {
        $transfer = $this->createPendingTransfer()
            ->authorize();

        $completed = $transfer->complete();

        $this->assertTrue($completed->isCompleted());
        $this->assertNotNull($completed->getCompletedAt());
    }

    public function testCompleteFailsWhenNotAuthorized(): void
    {
        $transfer = $this->createPendingTransfer();

        $this->expectException(TransferCannotBeCompletedException::class);

        $transfer->complete();
    }

    public function testCanFailPendingTransfer(): void
    {
        $transfer = $this->createPendingTransfer();

        $failed = $transfer->fail('Insufficient funds');

        $this->assertTrue($failed->isFailed());
        $this->assertSame('Insufficient funds', $failed->getFailureReason());
        $this->assertNotNull($failed->getFailedAt());
    }

    public function testCanFailAuthorizedTransfer(): void
    {
        $transfer = $this->createPendingTransfer()
            ->authorize();

        $failed = $transfer->fail('Authorization revoked');

        $this->assertTrue($failed->isFailed());
    }

    public function testFailFailsWhenAlreadyCompleted(): void
    {
        $transfer = $this->createPendingTransfer()
            ->authorize()
            ->complete();

        $this->expectException(TransferCannotBeFailedException::class);

        $transfer->fail('Too late');
    }

    public function testFailFailsWhenAlreadyFailed(): void
    {
        $transfer = $this->createPendingTransfer()
            ->fail('Initial failure');

        $this->expectException(TransferCannotBeFailedException::class);

        $transfer->fail('Another reason');
    }

    public function testFailFailsWhenReasonIsEmpty(): void
    {
        $transfer = $this->createPendingTransfer();

        $this->expectException(InvalidTransferException::class);

        $transfer->fail('   ');
    }

    public function testTransferIsImmutable(): void
    {
        $transfer = $this->createPendingTransfer();
        $authorized = $transfer->authorize();

        $this->assertNotSame($transfer, $authorized);
        $this->assertTrue($transfer->isPending());
        $this->assertTrue($authorized->isAuthorized());
    }

    private function createPendingTransfer(): Transfer
    {
        return Transfer::create(
            TransferId::generate(),
            UserId::generate(),
            UserId::generate(),
            Money::fromCents(100)
        );
    }
}
