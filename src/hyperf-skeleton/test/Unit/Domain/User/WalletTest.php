<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\User;

use App\Domain\Money\Money;
use App\Domain\User\Wallet;
use App\Domain\User\WalletId;
use App\Domain\User\Exception\NegativeWalletBalanceException;
use PHPUnit\Framework\TestCase;

final class WalletTest extends TestCase
{
    public function test_can_be_created_with_non_negative_balance(): void
    {
        $walletId = WalletId::generate();

        $wallet = new Wallet(
            $walletId,
            Money::fromCents(100),
        );

        $this->assertSame(100, $wallet->getBalance()->toInt());
        $this->assertTrue($wallet->getId()->equals($walletId));
    }

    public function test_can_be_created_with_zero_balance(): void
    {
        $walletId = WalletId::generate();

        $wallet = new Wallet(
            $walletId,
            Money::fromCents(0),
        );

        $this->assertSame(0, $wallet->getBalance()->toInt());
    }

    public function test_cannot_be_created_with_negative_balance(): void
    {
        $this->expectException(NegativeWalletBalanceException::class);

        $walletId = WalletId::generate();
        new Wallet(
            $walletId,
            Money::fromCents(-10),
        );
    }

    public function test_can_check_if_has_sufficient_balance(): void
    {
        $walletId = WalletId::generate();
        $wallet = new Wallet(
            $walletId,
            Money::fromCents(100),
        );

        $this->assertTrue($wallet->hasBalance(Money::fromCents(50)));
        $this->assertTrue($wallet->hasBalance(Money::fromCents(100)));
        $this->assertFalse($wallet->hasBalance(Money::fromCents(101)));
    }

    public function test_returns_new_instance_with_added_balance(): void
    {
        $walletId = WalletId::generate();
        $original = new Wallet(
            $walletId,
            Money::fromCents(100),
        );

        $updated = $original->withAddedBalance(Money::fromCents(50));

        $this->assertSame(100, $original->getBalance()->toInt());

        $this->assertSame(150, $updated->getBalance()->toInt());

        $this->assertNotSame($original, $updated);

        $this->assertTrue($original->getId()->equals($updated->getId()));
    }

    public function test_can_add_zero_balance(): void
    {
        $walletId = WalletId::generate();
        $wallet = new Wallet(
            $walletId,
            Money::fromCents(100),
        );

        $updated = $wallet->withAddedBalance(Money::fromCents(0));

        $this->assertSame(100, $updated->getBalance()->toInt());
    }

    public function test_returns_new_instance_with_deducted_balance(): void
    {
        $walletId = WalletId::generate();
        $original = new Wallet(
            $walletId,
            Money::fromCents(100),
        );

        $updated = $original->withDeductedBalance(Money::fromCents(50));

        $this->assertSame(100, $original->getBalance()->toInt());

        $this->assertSame(50, $updated->getBalance()->toInt());

        $this->assertNotSame($original, $updated);
    }

    public function test_cannot_deduct_more_than_available_balance(): void
    {
        $walletId = WalletId::generate();
        $wallet = new Wallet(
            $walletId,
            Money::fromCents(50),
        );

        $this->expectException(NegativeWalletBalanceException::class);

        $wallet->withDeductedBalance(Money::fromCents(100));
    }

    public function test_can_deduct_exact_balance(): void
    {
        $walletId = WalletId::generate();
        $wallet = new Wallet(
            $walletId,
            Money::fromCents(100),
        );

        $updated = $wallet->withDeductedBalance(Money::fromCents(100));

        $this->assertSame(0, $updated->getBalance()->toInt());
    }

    public function test_multiple_operations_maintain_immutability(): void
    {
        $walletId = WalletId::generate();
        $wallet1 = new Wallet(
            $walletId,
            Money::fromCents(100),
        );

        $wallet2 = $wallet1->withAddedBalance(Money::fromCents(50));
        $wallet3 = $wallet2->withDeductedBalance(Money::fromCents(30));

        $this->assertSame(100, $wallet1->getBalance()->toInt());
        $this->assertSame(150, $wallet2->getBalance()->toInt());
        $this->assertSame(120, $wallet3->getBalance()->toInt());

        $this->assertTrue($wallet1->getId()->equals($wallet2->getId()));
        $this->assertTrue($wallet2->getId()->equals($wallet3->getId()));
    }
}
