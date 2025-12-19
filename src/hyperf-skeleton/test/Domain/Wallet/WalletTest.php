<?php

declare(strict_types=1);

namespace HyperfTest\Domain\Wallet;

use App\Domain\Money\Money;
use App\Domain\User\Wallet;
use App\Domain\User\WalletId;
use App\Domain\User\UserType;
use App\Domain\User\Exception\NegativeWalletBalanceException;
use PHPUnit\Framework\TestCase;

final class WalletTest extends TestCase
{
    public function test_can_be_created_with_non_negative_balance(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_1'),
            Money::fromCents(100),
            UserType::USER
        );

        $this->assertSame(100, $wallet->getBalance()->toInt());
        $this->assertEquals(new WalletId('wallet_1'), $wallet->getId());
        $this->assertSame(UserType::USER, $wallet->getType());
    }

    public function test_can_be_created_with_zero_balance(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_2'),
            Money::fromCents(0),
            UserType::USER
        );

        $this->assertSame(0, $wallet->getBalance()->toInt());
    }

    public function test_cannot_be_created_with_negative_balance(): void
    {
        $this->expectException(NegativeWalletBalanceException::class);

        new Wallet(
            new WalletId('wallet_3'),
            Money::fromCents(-10),
            UserType::USER
        );
    }

    public function test_user_wallet_can_send_money(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_4'),
            Money::fromCents(100),
            UserType::USER
        );

        $this->assertTrue($wallet->canSendMoney());
    }

    public function test_merchant_wallet_cannot_send_money(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_5'),
            Money::fromCents(100),
            UserType::MERCHANT
        );

        $this->assertFalse($wallet->canSendMoney());
    }

    public function test_can_check_if_has_sufficient_balance(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_6'),
            Money::fromCents(100),
            UserType::USER
        );

        $this->assertTrue($wallet->hasBalance(Money::fromCents(50)));
        $this->assertTrue($wallet->hasBalance(Money::fromCents(100)));
        $this->assertFalse($wallet->hasBalance(Money::fromCents(101)));
    }

    public function test_returns_new_instance_with_added_balance(): void
    {
        $original = new Wallet(
            new WalletId('wallet_7'),
            Money::fromCents(100),
            UserType::USER
        );

        $updated = $original->withAddedBalance(Money::fromCents(50));

        // ✅ Original não mudou (imutabilidade)
        $this->assertSame(100, $original->getBalance()->toInt());
        
        // ✅ Nova instância tem novo saldo
        $this->assertSame(150, $updated->getBalance()->toInt());
        
        // ✅ São instâncias diferentes
        $this->assertNotSame($original, $updated);
        
        // ✅ Mas têm o mesmo ID
        $this->assertTrue($original->getId()->equals($updated->getId()));
    }

    public function test_can_add_zero_balance(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_8'),
            Money::fromCents(100),
            UserType::USER
        );

        $updated = $wallet->withAddedBalance(Money::fromCents(0));

        $this->assertSame(100, $updated->getBalance()->toInt());
    }

    public function test_returns_new_instance_with_deducted_balance(): void
    {
        $original = new Wallet(
            new WalletId('wallet_9'),
            Money::fromCents(100),
            UserType::USER
        );

        $updated = $original->withDeductedBalance(Money::fromCents(50));

        // ✅ Original não mudou
        $this->assertSame(100, $original->getBalance()->toInt());
        
        // ✅ Nova instância tem novo saldo
        $this->assertSame(50, $updated->getBalance()->toInt());
        
        // ✅ São instâncias diferentes
        $this->assertNotSame($original, $updated);
    }

    public function test_cannot_deduct_more_than_available_balance(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_10'),
            Money::fromCents(50),
            UserType::USER
        );

        $this->expectException(NegativeWalletBalanceException::class);

        $wallet->withDeductedBalance(Money::fromCents(100));
    }

    public function test_can_deduct_exact_balance(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_11'),
            Money::fromCents(100),
            UserType::USER
        );

        $updated = $wallet->withDeductedBalance(Money::fromCents(100));

        $this->assertSame(0, $updated->getBalance()->toInt());
    }

    public function test_multiple_operations_maintain_immutability(): void
    {
        $wallet1 = new Wallet(
            new WalletId('wallet_12'),
            Money::fromCents(100),
            UserType::USER
        );

        $wallet2 = $wallet1->withAddedBalance(Money::fromCents(50));
        $wallet3 = $wallet2->withDeductedBalance(Money::fromCents(30));

        // ✅ Cada operação retorna nova instância
        $this->assertSame(100, $wallet1->getBalance()->toInt());
        $this->assertSame(150, $wallet2->getBalance()->toInt());
        $this->assertSame(120, $wallet3->getBalance()->toInt());

        // ✅ Todas têm o mesmo ID
        $this->assertTrue($wallet1->getId()->equals($wallet2->getId()));
        $this->assertTrue($wallet2->getId()->equals($wallet3->getId()));
    }

    public function test_type_is_preserved_across_operations(): void
    {
        $wallet = new Wallet(
            new WalletId('wallet_13'),
            Money::fromCents(100),
            UserType::MERCHANT
        );

        $updated = $wallet->withAddedBalance(Money::fromCents(50));

        $this->assertSame(UserType::MERCHANT, $updated->getType());
        $this->assertFalse($updated->canSendMoney());
    }
}